<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

/**
 * OrquestadorIA — Catálogo inteligente de herramientas del framework.
 *
 * Permite a la IA descubrir qué helper/método usar según su intención,
 * sin necesidad de escanear ~350 archivos manualmente.
 *
 * Modos de uso:
 *   OrquestadorIA::catalogo()              → todo el catálogo
 *   OrquestadorIA::catalogo('texto')       → solo categoría
 *   OrquestadorIA::buscar('validar csrf')  → top 3 matches
 *   OrquestadorIA::ejecutar('Seguridad', 'validarCSRF', ['token' => '...'])
 */
class OrquestadorIA
{
    private static ?array $catalogo = null;

    private static array $chains = [
        'lite_read_file' => [
            ['herramienta' => 'lite_filtrar', 'params' => ['consulta' => '$intento', 'archivo' => '$path']],
        ],
        'lite_grep' => [
            ['herramienta' => 'lite_read_file', 'params' => ['path' => '$resultado.primer_archivo']],
        ],
        'lite_glob' => [
            ['herramienta' => 'lite_read_file', 'params' => ['path' => '$resultado.primer_archivo']],
        ],
        'lite_list_dir' => [
            ['herramienta' => 'lite_read_file', 'params' => ['path' => '$resultado.primer_archivo']],
        ],
        'lite_edit' => [
            ['herramienta' => 'lite_read_file', 'params' => ['path' => '$filePath']],
        ],
        'lite_diagnostico' => [
            ['herramienta' => 'lite_run', 'params' => ['command' => 'diagnostico:remediar', 'args' => '--tipo=']],
        ],
        'lite_pruebas' => [
            ['herramienta' => 'lite_read_file', 'params' => ['path' => '$resultado.archivo_fallo']],
        ],
    ];

    private static array $mapaCategoriaTool = [
        'buscar_codigo'     => 'lite_grep',
        'ejecutar_pruebas'  => 'lite_pruebas',
        'leer_archivo'      => 'lite_read_file',
        'editar_archivo'    => 'lite_edit',
        'escribir_archivo'  => 'lite_write_file',
        'listar_archivos'   => 'lite_list_dir',
        'generar_modulo'    => 'lite_modulo_generar',
        'generar_proyecto'  => 'lite_proyecto_crear',
        'ejecutar_migracion' => 'lite_migrar',
        'diagnosticar'      => 'lite_diagnostico',
        'crud_leer'         => 'lite_crud',
        'crud_escribir'     => 'lite_crud',
        'analizar_modulo'   => 'lite_grep',
    ];

    private static array $mapaCategoriaToolExterno = [
        'git'               => 'git',
        'firecrawl_scrape'  => 'firecrawl_scrape',
        'firecrawl_search'  => 'firecrawl_search',
        'firecrawl_crawl'   => 'firecrawl_crawl',
        'firecrawl_map'     => 'firecrawl_map',
        'firecrawl_extract' => 'firecrawl_extract',
        'browser_navegar'   => 'playwright_browser_navigate',
        'browser_click'     => 'playwright_browser_click',
        'browser_type'      => 'playwright_browser_type',
        'browser_screenshot' => 'playwright_browser_take_screenshot',
        'imagen'            => 'mcp_image_generate_image',
        'sentry'            => 'sentry_get_sentry_issue',
        'github_grep'       => 'gh_grep_searchGitHub',
        'investigar_paper'  => 'deepwiki_ask_question',
        'fetch_pagina'      => 'fetch_fetch_markdown',
        'tiempo'            => 'time_get_current_time',
        'context7'          => 'context7_resolve_library_id',
    ];

    private static array $mapaCategoriaParamsExterno = [
        'git'               => ['OPERACION' => 'command'],
        'firecrawl_scrape'  => ['URL' => 'url'],
        'firecrawl_search'  => ['CONSULTA' => 'query'],
        'firecrawl_crawl'   => ['URL' => 'url'],
        'firecrawl_map'     => ['URL' => 'url'],
        'firecrawl_extract' => ['URL' => 'urls'],
        'browser_navegar'   => ['URL' => 'url'],
        'browser_click'     => ['ELEMENTO' => 'target'],
        'browser_type'      => ['TEXTO' => 'text'],
        'browser_screenshot' => ['URL' => 'url'],
        'imagen'            => ['DESCRIPCION' => 'prompt'],
        'sentry'            => ['ISSUE' => 'issue_id_or_url'],
        'github_grep'       => ['PATRON' => 'query'],
        'investigar_paper'  => ['TEMA' => 'question'],
        'fetch_pagina'      => ['URL' => 'url'],
        'tiempo'            => [],
        'context7'          => ['LIBRERIA' => 'libraryName'],
    ];

    private static array $mapaCategoriaParamsMCP = [
        'buscar_codigo'     => ['PATRON' => 'pattern'],
        'leer_archivo'      => ['RUTA' => 'path'],
        'editar_archivo'    => ['RUTA' => 'filePath', 'ORIGINAL' => 'oldString', 'NUEVO' => 'newString'],
        'analizar_modulo'   => ['RUTA' => 'path'],
        'ejecutar_pruebas'  => ['FILTRO' => 'filtro'],
        'crud_leer'         => ['TABLA' => 'tabla'],
        'crud_escribir'     => ['TABLA' => 'tabla', 'DATOS' => 'data'],
        'generar_modulo'    => ['NOMBRE' => 'nombre_clase', 'CAMPOS' => 'campos'],
    ];

    /** @var array<string, array<string, string>> */
    private static array $documentacionIA = [
        'crud_leer|crud_escribir' => [
            'operador'    => 'documentacionIA/modelos/operador.md',
            'estadistica' => 'documentacionIA/modelos/operador.md',
        ],
        'leer_archivo|editar_archivo' => [
            'enrutador' => 'documentacionIA/nucleo/enrutador.md',
            'modelo'    => 'documentacionIA/nucleo/modelo.md',
            'freeze'    => 'documentacionIA/herramientas/freeze.md',
            'congelado' => 'documentacionIA/herramientas/freeze.md',
        ],
        'seguridad' => [
            'csrf'  => 'documentacionIA/seguridad/csrf.md',
            'token' => 'documentacionIA/seguridad/csrf.md',
        ],
        'autenticacion' => [
            'login'  => 'documentacionIA/api/autenticacion.md',
            'sesion' => 'documentacionIA/api/autenticacion.md',
        ],
        'api' => [
            'crud' => 'documentacionIA/api/crud.md',
        ],
    ];

    public static function catalogo(?string $categoria = null): array
    {
        self::init();
        if ($categoria !== null) {
            if (isset(self::$catalogo[$categoria])) {
                return self::$catalogo[$categoria];
            }
            $porAlias = [];
            foreach (self::$catalogo as $cat => $items) {
                foreach ($items as $h) {
                    $a = $h['alias'] ?? '';
                    if (strtolower($a) === strtolower($categoria) || strtolower($h['helper']) === strtolower($categoria)) {
                        $porAlias[$cat] = $h;
                    }
                }
            }
            return $porAlias ?: [];
        }
        return self::$catalogo;
    }

    public static function buscar(string $intento, int $limite = 3): array
    {
        self::init();

        $tokens = self::tokenizar($intento);
        if (empty($tokens)) {
            return [];
        }

        $puntuados = [];

        foreach (self::$catalogo as $categoria => $helpers) {
            foreach ($helpers as $h) {
                foreach ($h['metodos'] as $m) {
                    $palabras = array_merge(
                        [$m['nombre'], $h['alias'], $h['helper'], $categoria],
                        $m['k']
                    );
                    $textoBusqueda = implode(' ', $palabras) . ' ' . ($m['d'] ?? '');
                    $textoBusqueda = mb_strtolower($textoBusqueda);

                    $puntos = 0;
                    foreach ($tokens as $tok) {
                        if (str_contains($textoBusqueda, $tok)) {
                            $puntos += 3;
                        }
                        foreach ($m['k'] as $kw) {
                            if (str_contains(mb_strtolower($kw), $tok)) {
                                $puntos += 2;
                            }
                        }
                    }

                    if ($puntos > 0) {
                        $puntuados[] = [
                            'puntos' => $puntos,
                            'helper' => $h['helper'],
                            'alias' => $h['alias'],
                            'categoria' => $categoria,
                            'metodo' => $m['nombre'],
                            'descripcion' => $m['d'] ?? '',
                            'parametros' => $m['p'] ?? [],
                            'retorna' => $m['r'] ?? 'mixed',
                        ];
                    }
                }
            }
        }

        usort($puntuados, fn($a, $b) => $b['puntos'] <=> $a['puntos']);
        return array_slice($puntuados, 0, $limite);
    }

    public static function ejecutar(string $helper, string $metodo, array $params = []): mixed
    {
        self::init();

        // GuiasMCP: ejecuta la herramienta real
        if ($helper === 'GuiasMCP' || $helper === 'MCP') {
            return self::ejecutarMCP($metodo, $params);
        }

        $fqcn = 'LiteFramework\\Nucleo\\Helpers\\' . $helper;
        if (!class_exists($fqcn)) {
            $aliasMap = [
                'Seguridad' => 'AyudanteSeguridad',
                'Cadena' => 'AyudanteCadena',
                'Arreglo' => 'AyudanteArreglo',
                'General' => 'AyudanteGeneral',
                'Fecha' => 'AyudanteFecha',
                'ArchivoH' => 'AyudanteArchivo',
                'OperadorH' => 'AyudanteOperador',
                'HttpCliente' => 'AyudanteHttp',
                'Cache' => 'AyudanteCache',
                'Rendimiento' => 'AyudanteRendimiento',
                'Monitor' => 'AyudanteMonitor',
            ];
            $real = $aliasMap[$helper] ?? null;
            if ($real === null) {
                throw new \InvalidArgumentException("Helper no encontrado: $helper");
            }
            $fqcn = 'LiteFramework\\Nucleo\\Helpers\\' . $real;
        }

        if (!method_exists($fqcn, $metodo)) {
            throw new \InvalidArgumentException("Método $metodo no existe en $helper");
        }

        $ref = new \ReflectionMethod($fqcn, $metodo);
        if (!$ref->isPublic() || !$ref->isStatic()) {
            throw new \InvalidArgumentException("$helper::$metodo no es público y estático");
        }

        $args = [];
        foreach ($ref->getParameters() as $rp) {
            $nombre = $rp->getName();
            if (array_key_exists($nombre, $params)) {
                $args[] = $params[$nombre];
            } elseif ($rp->isDefaultValueAvailable()) {
                $args[] = $rp->getDefaultValue();
            } else {
                throw new \InvalidArgumentException("Falta parámetro requerido: \$$nombre");
            }
        }

        return $fqcn::$metodo(...$args);
    }

    public static function sugerir(string $intento, int $limite = 3): array
    {
        $resultados = self::buscar($intento, $limite);
        $agrupado = [];
        foreach ($resultados as $r) {
            $clave = $r['alias'] . '::' . $r['metodo'];
            $agrupado[$clave] = $r;
        }
        return $agrupado;
    }

    /**
     * Modo auto: recibe lenguaje natural, detecta el mejor helper/método,
     * y lo ejecuta automáticamente. Si hay ambigüedad, devuelve sugerencias.
     */
    public static function auto(string $intento, array $params = []): array
    {
        $categoriaTraductor = null;
        $parametrosTraductor = [];
        $idHistorial = null;

        // Feedback explícito post-ejecución de tool externa
        if (str_starts_with($intento, 'feedback:')) {
            $partes = explode(' ', $intento, 3);
            $idFeedback = (int)($partes[1] ?? 0);
            $resultadoFeedback = ($partes[2] ?? 'exito') === 'exito';
            if ($idFeedback > 0 && class_exists('\\Traductor')) {
                \Traductor::registrarFeedback($idFeedback, $resultadoFeedback);
            }
            return ['exito' => true, 'mensaje' => 'Feedback registrado'];
        }

        // Hook: Traductor de Prompts (humano -> IA)
        if (class_exists('\\Traductor')) {
            $t = \Traductor::humanoAIa($intento);
            if ($t['exito']) {
                $categoriaTraductor = $t['categoria'];
                $parametrosTraductor = $t['parametros'] ?? [];
                $idHistorial = $t['id_historial'] ?? null;

                // Categoría conocida → ejecutar tool MCP directamente
                if (isset(self::$mapaCategoriaTool[$t['categoria']])) {
                    return self::ejecutarPorCategoria(
                        $t['categoria'],
                        $t['prompt_traducido'],
                        $parametrosTraductor,
                        $params,
                        $intento,
                        $idHistorial
                    );
                }

                // Categoría de tool externa → devolver sugerencia
                if (isset(self::$mapaCategoriaToolExterno[$t['categoria']])) {
                    return self::ejecutarToolExterno(
                        $t['categoria'],
                        $parametrosTraductor,
                        $idHistorial
                    );
                }

                // Sin mapeo directo, usar intent traducido solo si confianza alta
                if (round($t['confianza'], 2) >= 0.75) {
                    $intento = $t['prompt_traducido'];
                    if (!empty($parametrosTraductor)) {
                        $params = array_merge($params, $parametrosTraductor);
                    }
                }
            }
        }

        // Feedback negativo implícito: confianza baja -> categoria general
        if (isset($t) && $t['exito'] && round($t['confianza'], 2) < 0.3) {
            \Traductor::calibrar(null, 'variaciones');
        }

        // Fallback: si el Traductor devolvió general/null con baja confianza,
        // escanear el intento original por verbos de acción conocidos
        if ($categoriaTraductor === null || $categoriaTraductor === 'general') {
            $intentLower = mb_strtolower($intento);
            if (preg_match('/\b(buscar|busca|grep|search|encuentra|donde)\b/', $intentLower)) {
                $extraParams = [];
                if (preg_match('/(?:buscar|busca|grep|search|encuentra)\s+(.+?)(?:\s+en\s+|\s+del\s+|\s+de\s+|\s*$)/i', $intento, $m)) {
                    $extraParams['PATRON'] = trim($m[1]);
                }
                return self::ejecutarPorCategoria('buscar_codigo', $intento, $extraParams, $params, $intento, null);
            }
        }

        $resultados = self::buscar($intento, 5);

        if (empty($resultados)) {
            return [
                'exito' => false,
                'error' => 'No se encontró ningún helper para: ' . $intento,
                'sugerencias' => [],
            ];
        }

        $mejor = $resultados[0];
        $segundo = $resultados[1] ?? null;

        $confianza = true;
        if ($segundo !== null && $segundo['puntos'] >= $mejor['puntos'] * 0.8) {
            $confianza = false;
        }
        if ($mejor['puntos'] < 4) {
            $confianza = false;
        }

        if (!$confianza) {
            $sugerencias = [];
            foreach ($resultados as $r) {
                $sugerencias[] = [
                    'helper' => $r['alias'],
                    'metodo' => $r['metodo'],
                    'descripcion' => $r['descripcion'],
                    'parametros' => $r['parametros'],
                    'confianza' => $r['puntos'],
                ];
            }
            $respuesta = [
                'exito' => false,
                'ambiguedad' => true,
                'error' => 'Múltiples opciones posibles. Especifica más.',
                'sugerencias' => $sugerencias,
            ];
            if ($categoriaTraductor !== null) {
                $respuesta['traductor_categoria'] = $categoriaTraductor;
                $respuesta['traductor_params'] = $parametrosTraductor;
            }
            return $respuesta;
        }

        try {
            $helperReal = $mejor['helper'];
            $aliasMap = [
                'AyudanteCadena' => 'Cadena',
                'AyudanteFecha' => 'Fecha',
                'AyudanteArreglo' => 'Arreglo',
                'AyudanteSeguridad' => 'Seguridad',
                'AyudanteArchivo' => 'ArchivoH',
                'AyudanteCache' => 'Cache',
                'AyudanteHttp' => 'HttpCliente',
                'AyudanteRendimiento' => 'Rendimiento',
                'AyudanteOperador' => 'OperadorH',
                'AyudanteMonitor' => 'Monitor',
                'AyudanteGeneral' => 'General',
            ];
            $alias = $aliasMap[$helperReal] ?? $mejor['alias'];

            // Completar path faltante desde el intento
            if ($mejor['metodo'] === 'lite_read_file' && (!isset($params['path']) || empty($params['path']))) {
                $pathFromIntent = self::extraerPath($intento);
                if ($pathFromIntent !== null) {
                    $params['path'] = $pathFromIntent;
                }
            }

            $resultado = self::ejecutar($alias, $mejor['metodo'], $params);

            // Ejecutar chains obligatorias
            $resultadosChain = [];
            $chainsMetodo = self::$chains[$mejor['metodo']] ?? [];
            foreach ($chainsMetodo as $chain) {
                $paramsChain = self::resolverParamsChain(
                    $chain['params'],
                    $intento,
                    $params,
                    $resultado
                );
                if ($mejor['metodo'] === 'lite_read_file' || $chain['herramienta'] === 'lite_read_file') {
                    $pathFromIntent = self::extraerPath($intento);
                    if (!isset($paramsChain['path']) || empty($paramsChain['path'])) {
                        $paramsChain['path'] = $pathFromIntent;
                    }
                }
                try {
                    $res = self::ejecutar('MCP', $chain['herramienta'], $paramsChain);
                    $resultadosChain[] = [
                        'herramienta' => $chain['herramienta'],
                        'resultado' => $res,
                    ];
                } catch (\Throwable $e) {
                    $resultadosChain[] = [
                        'herramienta' => $chain['herramienta'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $respuesta = [
                'exito' => true,
                'helper' => $alias,
                'metodo' => $mejor['metodo'],
                'descripcion' => $mejor['descripcion'],
                'resultado' => $resultado,
            ];
            if (!empty($resultadosChain)) {
                $respuesta['chains'] = $resultadosChain;
            }
            return $respuesta;
        } catch (\Throwable $e) {
            return [
                'exito' => false,
                'error' => $e->getMessage(),
                'helper_sugerido' => $mejor['alias'] . '::' . $mejor['metodo'],
                'parametros_requeridos' => $mejor['parametros'],
            ];
        }
    }

    /**
     * Ejecuta una herramienta MCP directamente desde una categoría del Traductor.
     * Puente directo: Traductor -> MCP, sin pasar por buscar() ni el scoring.
     */
    private static function ejecutarPorCategoria(string $categoria, string $intentoTraducido, array $paramsTraductor, array $paramsOriginales, string $intentoOriginal, ?int $idHistorial = null): array
    {
        $tool = self::$mapaCategoriaTool[$categoria];

        // Mapear parámetros del Traductor a parámetros MCP
        $paramsMCP = self::mapearParamsTraductorAMCP($categoria, $paramsTraductor);

        // Extraer nombre del modulo del intento para analizar_modulo
        if ($categoria === 'analizar_modulo' && empty($paramsMCP['pattern'])) {
            if (preg_match('/(?:modulo|modulo)\s+(?:de\s+)?(\w+)/i', $intentoOriginal, $m)) {
                $paramsMCP['pattern'] = $m[1];
            }
        }
        $paramsMCP = array_merge($paramsMCP, $paramsOriginales);

        // Completar path faltante desde el intento original
        // Default path para analizar_modulo: raíz del proyecto (chain filtrar hará el filtrado)
        if ($categoria === 'analizar_modulo' && empty($paramsMCP['path'])) {
            $paramsMCP['path'] = '';
        }

        if (in_array($tool, ['lite_read_file', 'lite_edit'], true) && (!isset($paramsMCP['path']) || empty($paramsMCP['path'])) && (!isset($paramsMCP['filePath']) || empty($paramsMCP['filePath']))) {
            $pathFromIntent = self::extraerPath($intentoOriginal);
            if ($pathFromIntent !== null) {
                if ($tool === 'lite_edit') {
                    $paramsMCP['filePath'] = $pathFromIntent;
                } else {
                    $paramsMCP['path'] = $pathFromIntent;
                }
            }
        }

        try {
            $resultado = self::ejecutarMCP($tool, $paramsMCP);
        } catch (\Throwable $e) {
            return [
                'exito' => false,
                'error' => $e->getMessage(),
                'helper_sugerido' => 'MCP::' . $tool,
                'fuente' => 'traductor_categoria',
            ];
        }

        // Ejecutar chains obligatorias
        $resultadosChain = [];
        $chainsMetodo = self::$chains[$tool] ?? [];
        foreach ($chainsMetodo as $chain) {
            $paramsChain = self::resolverParamsChain(
                $chain['params'],
                $intentoTraducido,
                $paramsMCP,
                $resultado
            );
            try {
                $res = self::ejecutar('MCP', $chain['herramienta'], $paramsChain);
                $resultadosChain[] = [
                    'herramienta' => $chain['herramienta'],
                    'resultado' => $res,
                ];
            } catch (\Throwable $e) {
                $resultadosChain[] = [
                    'herramienta' => $chain['herramienta'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Auto-feedback: traducción exitosa = acierto
        if ($idHistorial !== null && class_exists('\\Traductor')) {
            \Traductor::registrarFeedback($idHistorial, true);
        }

        $respuesta = [
            'exito' => true,
            'helper' => 'MCP',
            'metodo' => $tool,
            'descripcion' => $intentoTraducido,
            'resultado' => $resultado,
            'fuente' => 'traductor_categoria',
        ];
        if (!empty($resultadosChain)) {
            $respuesta['chains'] = $resultadosChain;
        }

        $docRuta = self::buscarDocumentacionIA($categoria, $paramsTraductor, $intentoOriginal);
        if ($docRuta !== null) {
            $dirRaiz = defined('DIRECTORIO_RAIZ') ? DIRECTORIO_RAIZ : (getcwd() ?: __DIR__ . '/../../..');
            $dirRaiz = rtrim(str_replace('\\', '/', $dirRaiz), '/');
            if (!file_exists($dirRaiz . '/consola') && defined('DIRECTORIO_RAIZ')) {
                $dirRaiz = rtrim(str_replace('\\', '/', DIRECTORIO_RAIZ), '/');
            }
            $docResultado = self::ejecutarReadFile(['path' => $docRuta], $dirRaiz);
            if (($docResultado['exito'] ?? false)) {
                $respuesta['documentacion'] = $docResultado['contenido'];
            }
        }

        return $respuesta;
    }

    private static function mapearParamsTraductorAMCP(string $categoria, array $params): array
    {
        $mcp = [];
        $mapping = self::$mapaCategoriaParamsMCP[$categoria] ?? [];
        foreach ($mapping as $tKey => $mKey) {
            if (isset($params[$tKey])) {
                $mcp[$mKey] = $params[$tKey];
            }
        }
        return $mcp;
    }

    /**
     * Busca documentación IA asociada a una categoría+intento.
     */
    private static function buscarDocumentacionIA(
        string $categoria,
        array $params,
        string $intentoOriginal
    ): ?string {
        $clave = '';
        foreach ($params as $v) {
            if (is_string($v) && strlen($v) > 1) {
                $clave = strtolower(rtrim($v, 's'));
                break;
            }
        }
        if ($clave === '') {
            $tokens = self::tokenizar($intentoOriginal);
            $clave = $tokens[0] ?? '';
        }
        foreach (self::$documentacionIA as $cats => $docs) {
            $catList = explode('|', $cats);
            if (in_array($categoria, $catList, true) && isset($docs[$clave])) {
                return $docs[$clave];
            }
        }
        return null;
    }

    /**
     * Devuelve sugerencia para herramienta MCP externa (no lite_*).
     * El orquestador ordena qué tool usar y la IA ejecuta.
     */
    private static function ejecutarToolExterno(string $categoria, array $paramsTraductor, ?int $idHistorial): array
    {
        $tool = self::$mapaCategoriaToolExterno[$categoria] ?? $categoria;
        $mapping = self::$mapaCategoriaParamsExterno[$categoria] ?? [];
        $paramsSugeridos = [];
        foreach ($mapping as $tKey => $mKey) {
            if (isset($paramsTraductor[$tKey])) {
                $paramsSugeridos[$mKey] = $paramsTraductor[$tKey];
            }
        }
        return [
            'exito' => true,
            'modo' => 'tool_suggestion',
            'tool' => $tool,
            'params' => $paramsSugeridos,
            'id_historial' => $idHistorial,
            'descripcion' => "El orquestador ordena usar {$tool} con los parametros indicados. "
                . "Ejecutar y luego reportar feedback con: ia(intent=\"feedback: {$idHistorial} exito\")",
        ];
    }

    public static function equipoRapido(string $tarea, array $especialistas = []): array
    {
        if (empty($especialistas)) {
            return ['exito' => false, 'error' => 'Se requieren especialistas'];
        }
        $agentesStr = implode(',', $especialistas);
        return [
            'exito' => true,
            'comando' => 'lite_equipo',
            'config' => [
                'tipo' => 'custom',
                'agentes' => $agentesStr,
                'tarea' => $tarea,
                'max_iter' => count($especialistas) * 2,
            ],
        ];
    }

    public static function checkpoint(int $iteracion, int $cada = 4): bool
    {
        if ($iteracion <= 0) {
            return false;
        }
        if ($iteracion % $cada === 0) {
            return true;
        }
        return false;
    }

    private static function tokenizar(string $texto): array
    {
        $stopwords = ['el', 'la', 'los', 'las', 'de', 'del', 'en', 'un', 'una', 'y', 'e', 'o', 'a',
                       'que', 'es', 'por', 'con', 'para', 'su', 'se', 'no', 'lo', 'como', 'mas',
                       'pero', 'sus', 'le', 'ya', 'este', 'entre', 'porque', 'todo', 'sin', 'the',
                       'and', 'for', 'are', 'not', 'was', 'has', 'but', 'all', 'any', 'can', 'get',
                       'al', 'del', 'me', 'te', 'nos', 'os'];
        $palabras = preg_split('/[\s\-_]+/', mb_strtolower($texto));
        $resultado = [];
        foreach ($palabras as $p) {
            $p = trim($p);
            if (strlen($p) > 1 && !in_array($p, $stopwords, true)) {
                $resultado[] = $p;
            }
        }
        return array_unique($resultado);
    }

    private static function ejecutarMCP(string $metodo, array $params): mixed
    {
        $dirRaiz = defined('DIRECTORIO_RAIZ') ? DIRECTORIO_RAIZ : (getcwd() ?: __DIR__ . '/../../..');
        $dirRaiz = rtrim(str_replace('\\', '/', $dirRaiz), '/');
        if (!file_exists($dirRaiz . '/consola') && defined('DIRECTORIO_RAIZ')) {
            $dirRaiz = rtrim(str_replace('\\', '/', DIRECTORIO_RAIZ), '/');
        }
        return match ($metodo) {
            'lite_read_file' => self::ejecutarReadFile($params, $dirRaiz),
            'lite_filtrar' => self::ejecutarFiltrar($params),
            'lite_grep' => self::ejecutarGrep($params, $dirRaiz),
            'lite_glob' => self::ejecutarGlob($params, $dirRaiz),
            'lite_list_dir' => self::ejecutarListDir($params, $dirRaiz),
            'lite_write_file' => self::ejecutarWriteFile($params, $dirRaiz),
            'lite_edit' => self::ejecutarEdit($params, $dirRaiz),
            'lite_run' => self::ejecutarRun($params, $dirRaiz),
            'lite_diagnostico' => self::ejecutarRun(['command' => 'diagnostico:ejecutar'], $dirRaiz),
            'lite_pruebas' => self::ejecutarPruebas($params, $dirRaiz),
            'lite_crud' => self::ejecutarRun(['command' => 'crud', 'args' => self::buildCrudArgs($params)], $dirRaiz),
            'lite_modulo_generar' => self::ejecutarRun(['command' => 'modulo:generar', 'args' => self::buildModuloArgs($params)], $dirRaiz),
            'lite_migrar' => self::ejecutarRun(['command' => 'migrar'], $dirRaiz),
            'lite_equipo' => ['exito' => true, 'comando' => 'lite_equipo', 'config' => $params],
            'lite_operador_crear' => self::ejecutarRun(['command' => 'operador:crear', 'args' => self::buildOperadorArgs($params)], $dirRaiz),
            default => "GUIA: Usa {$metodo} segun la documentacion del sistema",
        };
    }

    private static function resolverParamsChain(array $templates, string $intento, array $params, mixed $resultado): array
    {
        $resueltos = [];
        $resultadoArr = is_array($resultado) ? $resultado : [];

        foreach ($templates as $clave => $template) {
            $valor = $template;
            $valor = str_replace('$intento', $intento, $valor);
            if (isset($params['path'])) $valor = str_replace('$path', $params['path'], $valor);
            if (isset($params['filePath'])) $valor = str_replace('$filePath', $params['filePath'], $valor);
            foreach ($resultadoArr as $rk => $rv) {
                if (is_string($rv)) {
                    $valor = str_replace("\$resultado.{$rk}", $rv, $valor);
                }
            }
            $resueltos[$clave] = $valor;
        }
        return $resueltos;
    }

    private static function resolverRuta(string $path, string $dirRaiz): string
    {
        $path = str_replace('\\', '/', $path);
        $dirRaiz = rtrim(str_replace('\\', '/', $dirRaiz), '/');
        if (str_starts_with($path, '/')) {
            return $path;
        }
        return $dirRaiz . '/' . ltrim($path, '/');
    }

    private static function ejecutarReadFile(array $params, string $dirRaiz): array
    {
        $path = $params['path'] ?? '';
        $abs = self::resolverRuta($path, $dirRaiz);
        if (!file_exists($abs)) {
            return ['exito' => false, 'error' => "Archivo no encontrado: {$path} (abs: {$abs})"];
        }
        $contenido = file_get_contents($abs);
        if ($contenido === false) {
            return ['exito' => false, 'error' => "No se pudo leer: {$path}"];
        }
        return [
            'exito' => true,
            'path' => $path,
            'contenido' => $contenido,
            'lineas' => substr_count($contenido, "\n") + 1,
        ];
    }

    private static function ejecutarFiltrar(array $params): array
    {
        $consulta = mb_strtolower($params['consulta'] ?? '');
        $archivo = $params['archivo'] ?? '';
        $texto = $params['texto'] ?? '';
        if (empty($texto) && !empty($archivo)) {
            $dirRaiz = defined('DIRECTORIO_RAIZ') ? DIRECTORIO_RAIZ : (getcwd() ?: __DIR__ . '/../../..');
            $abs = self::resolverRuta($archivo, rtrim(str_replace('\\', '/', $dirRaiz), '/'));
            if (file_exists($abs)) {
                $texto = file_get_contents($abs);
            }
        }
        if (empty($texto)) {
            return ['exito' => false, 'error' => 'No hay texto ni archivo para filtrar'];
        }
        $tokens = preg_split('/\s+/', $consulta);
        $tokens = array_filter($tokens, fn($t) => strlen($t) > 2);
        if (empty($tokens)) {
            return ['exito' => true, 'contenido_filtrado' => $texto, 'lineas' => substr_count($texto, "\n") + 1];
        }
        $lineas = explode("\n", $texto);
        $relevantes = [];
        $totalLineas = count($lineas);
        foreach ($lineas as $n => $linea) {
            $lineaLower = mb_strtolower($linea);
            $match = false;
            foreach ($tokens as $tok) {
                if (str_contains($lineaLower, $tok)) { $match = true; break; }
            }
            if ($match) {
                $ctxStart = max(0, $n - 2);
                for ($i = $ctxStart; $i <= $n + 2 && $i < $totalLineas; $i++) {
                    $idx = $i + 1;
                    if (!isset($relevantes[$idx])) {
                        $relevantes[$idx] = $lineas[$i];
                    }
                }
            }
        }
        ksort($relevantes);
        $filtrado = implode("\n", $relevantes);
        return [
            'exito' => true,
            'contenido_filtrado' => $filtrado,
            'lineas' => count($relevantes),
            'total_original' => $totalLineas,
        ];
    }

    private static function ejecutarGrep(array $params, string $dirRaiz): array
    {
        $pattern = $params['pattern'] ?? '';
        $path = $params['path'] ?? '';
        $include = $params['include'] ?? '*.php';
        if (empty($pattern)) {
            return ['exito' => false, 'error' => 'Patron de busqueda requerido'];
        }
        $dir = self::resolverRuta($path, $dirRaiz);
        if (!empty($path) && !is_dir($dir)) {
            return ['exito' => false, 'error' => "Directorio no encontrado: {$path}"];
        }
        if (empty($path)) {
            $dir = $dirRaiz;
        }
        $resultados = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && fnmatch($include, $file->getFilename())) {
                $contenido = file_get_contents($file->getRealPath());
                if (preg_match_all('/' . preg_quote($pattern, '/') . '/i', $contenido, $matches, PREG_OFFSET_CAPTURE)) {
                    $lineas = [];
                    foreach ($matches[0] as $m) {
                        $offset = $m[1];
                        $numLinea = substr_count(substr($contenido, 0, $offset), "\n") + 1;
                        $lineas[] = $numLinea;
                    }
                    $relPath = str_replace($dirRaiz . '/', '', str_replace('\\', '/', $file->getRealPath()));
                    $resultados[] = [
                        'archivo' => $relPath,
                        'coincidencias' => count($lineas),
                        'lineas' => $lineas,
                    ];
                }
            }
        }
        return [
            'exito' => true,
            'resultados' => $resultados,
            'total' => count($resultados),
            'primer_archivo' => $resultados[0]['archivo'] ?? null,
        ];
    }

    private static function ejecutarGlob(array $params, string $dirRaiz): array
    {
        $pattern = $params['pattern'] ?? '**/*';
        $path = $params['path'] ?? '';
        $dir = !empty($path) ? self::resolverRuta($path, $dirRaiz) : $dirRaiz;
        $globPattern = rtrim($dir, '/') . '/' . ltrim($pattern, '/');
        $archivos = glob($globPattern) ?: [];
        $archivosRel = [];
        foreach ($archivos as $a) {
            $archivosRel[] = str_replace($dirRaiz . DIRECTORY_SEPARATOR, '', $a);
        }
        sort($archivosRel);
        return [
            'exito' => true,
            'archivos' => $archivosRel,
            'total' => count($archivosRel),
            'primer_archivo' => $archivosRel[0] ?? null,
        ];
    }

    private static function ejecutarListDir(array $params, string $dirRaiz): array
    {
        $path = $params['path'] ?? '';
        $dir = !empty($path) ? self::resolverRuta($path, $dirRaiz) : $dirRaiz;
        if (!is_dir($dir)) {
            return ['exito' => false, 'error' => "Directorio no encontrado: {$path}"];
        }
        $items = scandir($dir);
        $archivos = [];
        $carpetas = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $ruta = $dir . DIRECTORY_SEPARATOR . $item;
            $rel = str_replace($dirRaiz . DIRECTORY_SEPARATOR, '', $ruta);
            if (is_dir($ruta)) {
                $carpetas[] = $rel . DIRECTORY_SEPARATOR;
            } else {
                $archivos[] = $rel;
            }
        }
        return [
            'exito' => true,
            'carpetas' => $carpetas,
            'archivos' => $archivos,
            'total' => count($archivos) + count($carpetas),
            'primer_archivo' => !empty($archivos) ? $archivos[0] : (!empty($carpetas) ? $carpetas[0] : null),
        ];
    }

    private static function ejecutarWriteFile(array $params, string $dirRaiz): array
    {
        $path = $params['path'] ?? '';
        $content = $params['content'] ?? '';
        if (empty($path)) {
            return ['exito' => false, 'error' => 'Ruta requerida'];
        }
        $abs = self::resolverRuta($path, $dirRaiz);
        $dirPadre = dirname($abs);
        if (!is_dir($dirPadre)) {
            mkdir($dirPadre, 0755, true);
        }
        file_put_contents($abs, $content);
        return ['exito' => true, 'path' => $path, 'bytes' => strlen($content)];
    }

    private static function ejecutarEdit(array $params, string $dirRaiz): array
    {
        $filePath = $params['filePath'] ?? '';
        $oldString = $params['oldString'] ?? '';
        $newString = $params['newString'] ?? '';
        $replaceAll = !empty($params['replaceAll']);
        if (empty($filePath) || empty($oldString)) {
            return ['exito' => false, 'error' => 'filePath y oldString requeridos'];
        }
        $abs = self::resolverRuta($filePath, $dirRaiz);
        if (!file_exists($abs)) {
            return ['exito' => false, 'error' => "Archivo no encontrado: {$filePath}"];
        }
        $contenido = file_get_contents($abs);
        if ($replaceAll) {
            $nuevo = str_replace($oldString, $newString, $contenido);
        } else {
            $nuevo = substr_replace($contenido, $newString, strpos($contenido, $oldString), strlen($oldString));
        }
        file_put_contents($abs, $nuevo);
        $ocurrencias = $replaceAll ? substr_count($contenido, $oldString) : 1;
        return ['exito' => true, 'path' => $filePath, 'reemplazos' => $ocurrencias, 'modificado' => $contenido !== $nuevo];
    }

    private static function ejecutarRun(array $params, string $dirRaiz): mixed
    {
        $command = $params['command'] ?? '';
        $args = $params['args'] ?? '';
        if (empty($command)) {
            return ['exito' => false, 'error' => 'Comando requerido'];
        }
        $consola = rtrim($dirRaiz, '/') . '/consola';
        if (!file_exists($consola)) {
            return ['exito' => false, 'error' => "Consola no encontrada en {$consola}"];
        }
        $cmd = PHP_BINARY . ' ' . escapeshellarg($consola) . ' ' . escapeshellarg($command);
        if (!empty($args)) {
            $cmd .= ' ' . $args;
        }
        $output = shell_exec($cmd . ' 2>&1');
        return [
            'exito' => true,
            'comando' => $command,
            'salida' => $output,
        ];
    }

    private static function ejecutarPruebas(array $params, string $dirRaiz): array
    {
        $filtro = $params['filtro'] ?? '';
        $testDir = rtrim($dirRaiz, '/') . '/tests';
        $runner = $testDir . '/liteTest.php';
        if (!file_exists($runner)) {
            return ['exito' => false, 'error' => 'liteTest.php no encontrado'];
        }
        $cmd = PHP_BINARY . ' ' . escapeshellarg($runner);
        if (!empty($filtro)) {
            $cmd .= ' --filtro=' . escapeshellarg($filtro);
        }
        $output = shell_exec($cmd . ' 2>&1');
        preg_match('/Pasados:\s+(\d+)/', $output, $mPasados);
        preg_match('/Fallos:\s+(\d+)/', $output, $mFallos);
        preg_match('/Errores:\s+(\d+)/', $output, $mErrores);
        return [
            'exito' => true,
            'salida' => $output,
            'pasados' => (int)($mPasados[1] ?? 0),
            'fallos' => (int)($mFallos[1] ?? 0),
            'errores' => (int)($mErrores[1] ?? 0),
            'archivo_fallo' => self::extraerPrimerFallo($output),
        ];
    }

    private static function extraerPrimerFallo(string $output): ?string
    {
        if (preg_match('/^  ([A-Za-z0-9_]+Test\.php)::test/m', $output, $m)) {
            $testDir = defined('DIRECTORIO_RAIZ') ? DIRECTORIO_RAIZ . '/tests' : 'tests';
            $parts = explode('::', $m[1]);
            $archivo = $parts[0];
            return $testDir . '/Casos/' . $archivo;
        }
        return null;
    }

    private static function extraerPath(string $intento): ?string
    {
        if (preg_match('/(?:archivo|file|ruta)\s+([^\s,;]+(?:\/[^\s,;]+)*)/i', $intento, $m)) {
            return $m[1];
        }
        if (preg_match('/(?:servidor|src|tests|vendor|storage|plantillas|rutas)\/[^\s,;]+/i', $intento, $m)) {
            return $m[0];
        }
        return null;
    }

    private static function buildCrudArgs(array $params): string
    {
        $args = '';
        if (!empty($params['tabla'])) $args .= ' --tabla=' . escapeshellarg($params['tabla']);
        if (!empty($params['accion'])) $args .= ' --accion=' . escapeshellarg($params['accion']);
        if (!empty($params['data'])) $args .= ' --data=' . escapeshellarg(is_string($params['data']) ? $params['data'] : json_encode($params['data']));
        if (!empty($params['id'])) $args .= ' --id=' . (int)$params['id'];
        return $args;
    }

    private static function buildModuloArgs(array $params): string
    {
        $args = escapeshellarg($params['nombre_clase'] ?? '');
        if (!empty($params['tabla'])) $args .= ' --tabla=' . escapeshellarg($params['tabla']);
        if (!empty($params['campos'])) $args .= ' --campos=' . escapeshellarg($params['campos']);
        return $args;
    }

    private static function buildOperadorArgs(array $params): string
    {
        $args = '';
        if (!empty($params['nombre'])) $args .= ' --nombre=' . escapeshellarg($params['nombre']);
        if (!empty($params['email'])) $args .= ' --email=' . escapeshellarg($params['email']);
        if (!empty($params['clave'])) $args .= ' --clave=' . escapeshellarg($params['clave']);
        return $args;
    }

    private static function init(): void
    {
        if (self::$catalogo !== null) return;

        if (class_exists('LiteFramework\\Nucleo\\Helpers\\AyudanteCache')) {
            $cacheado = \LiteFramework\Nucleo\Helpers\AyudanteCache::recordar(
                'orquestador_catalogo',
                fn() => self::generarCatalogo(),
                3600
            );
            if (is_array($cacheado)) {
                self::$catalogo = $cacheado;
                return;
            }
        }

        self::$catalogo = self::generarCatalogo();
    }

    private static function generarCatalogo(): array
    {
        return [
            'texto' => [
                [
                    'helper' => 'AyudanteCadena',
                    'alias' => 'Cadena',
                    'descripcion' => 'Manipulación de cadenas de texto',
                    'metodos' => [
                        ['nombre' => 'limitar', 'p' => ['texto:string', 'limite:int', 'fin:string|...'], 'r' => 'string', 'd' => 'Limita texto a N caracteres con puntos suspensivos', 'k' => ['acortar', 'cortar', 'resumir', 'truncar']],
                        ['nombre' => 'truncar', 'p' => ['texto:string|null', 'limite:int', 'fin:string|...'], 'r' => 'string', 'd' => 'Corta texto al final de la última palabra completa', 'k' => ['cortar palabra', 'no romper palabra']],
                        ['nombre' => 'slug', 'p' => ['texto:string|null'], 'r' => 'string', 'd' => 'Convierte texto a slug URL-friendly', 'k' => ['url amigable', 'url slug', 'slug', 'normalizar url', 'seo']],
                        ['nombre' => 'capitalizar', 'p' => ['texto:string|null'], 'r' => 'string', 'd' => 'Primera letra en mayúscula', 'k' => ['primera mayuscula', 'upper first']],
                        ['nombre' => 'titulo', 'p' => ['texto:string|null'], 'r' => 'string', 'd' => 'Capitaliza cada palabra (title case)', 'k' => ['title case', 'cada palabra']],
                        ['nombre' => 'minusculas', 'p' => ['texto:string|null'], 'r' => 'string', 'd' => 'Convierte a minúsculas', 'k' => ['lowercase', 'lower', 'minuscula']],
                        ['nombre' => 'mayusculas', 'p' => ['texto:string|null'], 'r' => 'string', 'd' => 'Convierte a mayúsculas', 'k' => ['uppercase', 'upper', 'mayuscula']],
                        ['nombre' => 'contiene', 'p' => ['cadena:string|null', 'buscar:string', 'sensitivo:bool=false'], 'r' => 'bool', 'd' => 'Verifica si un texto contiene una subcadena', 'k' => ['buscar texto', 'subcadena', 'contains']],
                        ['nombre' => 'iniciar', 'p' => ['texto:string|null', 'longitud:int'], 'r' => 'string', 'd' => 'Obtiene los primeros N caracteres', 'k' => ['primeros', 'inicio', 'left']],
                        ['nombre' => 'terminar', 'p' => ['texto:string|null', 'longitud:int'], 'r' => 'string', 'd' => 'Obtiene los últimos N caracteres', 'k' => ['ultimos', 'final', 'right']],
                        ['nombre' => 'aleatorio', 'p' => ['longitud:int=16'], 'r' => 'string', 'd' => 'Genera cadena alfanumérica aleatoria', 'k' => ['random', 'azar', 'token texto']],
                        ['nombre' => 'espaciar', 'p' => ['texto:string|null'], 'r' => 'string', 'd' => 'Elimina espacios en blanco excesivos', 'k' => ['normalizar espacios', 'espacios multiples']],
                        ['nombre' => 'envolver', 'p' => ['texto:string|null', 'etiquetaApertura:string', 'etiquetaCierre:string|null'], 'r' => 'string', 'd' => 'Envuelve texto entre etiquetas HTML', 'k' => ['wrap', 'html wrap', 'etiquetas']],
                        ['nombre' => 'reemplazarEntre', 'p' => ['texto:string|null', 'etiqueta:string', 'reemplazo:string'], 'r' => 'string', 'd' => 'Reemplaza contenido entre etiquetas HTML', 'k' => ['reemplazar html', 'replace between']],
                        ['nombre' => 'extraer', 'p' => ['texto:string|null', 'inicio:string', 'fin:string'], 'r' => 'string', 'd' => 'Extrae texto entre dos marcadores', 'k' => ['extract between', 'entre marcadores']],
                        ['nombre' => 'palabras', 'p' => ['texto:string|null'], 'r' => 'int', 'd' => 'Cuenta palabras en un texto', 'k' => ['contar palabras', 'word count']],
                        ['nombre' => 'contarCaracteres', 'p' => ['texto:string|null'], 'r' => 'int', 'd' => 'Cuenta caracteres sin espacios', 'k' => ['contar caracteres', 'char count']],
                        ['nombre' => 'invertir', 'p' => ['texto:string|null'], 'r' => 'string', 'd' => 'Invierte una cadena', 'k' => ['reverse', 'reversa']],
                        ['nombre' => 'hash', 'p' => ['texto:string|null'], 'r' => 'string', 'd' => 'Genera hash corto único (SHA256 truncado)', 'k' => ['hash rapido', 'hash corto']],
                        ['nombre' => 'stripTags', 'p' => ['texto:string|null', 'etiquetasPermitidas:string|null'], 'r' => 'string', 'd' => 'Elimina etiquetas HTML (opcional: permite algunas)', 'k' => ['limpiar html', 'strip html', 'quitar etiquetas']],
                        ['nombre' => 'escapar', 'p' => ['texto:string|null'], 'r' => 'string', 'd' => 'Escapa caracteres especiales HTML', 'k' => ['html escape', 'escape html', 'entidades']],
                        ['nombre' => 'desescapar', 'p' => ['texto:string|null'], 'r' => 'string', 'd' => 'Decodifica entidades HTML', 'k' => ['html decode', 'unescape', 'decodificar']],
                        ['nombre' => 'esEmail', 'p' => ['texto:string|null'], 'r' => 'bool', 'd' => 'Verifica si es un email válido', 'k' => ['validar email', 'email valido', 'correo']],
                        ['nombre' => 'esUrl', 'p' => ['texto:string|null'], 'r' => 'bool', 'd' => 'Verifica si es una URL válida', 'k' => ['validar url', 'url valida']],
                        ['nombre' => 'enmascarar', 'p' => ['texto:string|null', 'patron:string=*', 'mostrarInicio:int=4', 'mostrarFin:int=4'], 'r' => 'string', 'd' => 'Enmascara texto (ej: 1234****5678)', 'k' => ['mascara', 'ocultar', 'mask', 'tarjeta']],
                        ['nombre' => 'normalizar', 'p' => ['texto:string|null'], 'r' => 'string', 'd' => 'Normaliza texto quitando acentos', 'k' => ['quitar acentos', 'sin tildes', 'normalizar']],
                    ],
                ],
            ],
            'fecha' => [
                [
                    'helper' => 'AyudanteFecha',
                    'alias' => 'Fecha',
                    'descripcion' => 'Manipulación de fechas y tiempos',
                    'metodos' => [
                        ['nombre' => 'hoy', 'p' => ['formato:string=Y-m-d'], 'r' => 'string', 'd' => 'Fecha actual en formato dado', 'k' => ['fecha actual', 'today', 'hoy']],
                        ['nombre' => 'ahora', 'p' => ['formato:string=Y-m-d H:i:s'], 'r' => 'string', 'd' => 'Fecha y hora actual', 'k' => ['ahora', 'now', 'fecha hora']],
                        ['nombre' => 'formatear', 'p' => ['fecha:string|DateTime|null', 'formato:string=d/m/Y H:i:s', 'timezoneOffset:int|null'], 'r' => 'string', 'd' => 'Formatea una fecha con formato personalizado', 'k' => ['formatear fecha', 'date format']],
                        ['nombre' => 'diferencia', 'p' => ['fecha1:string|DateTime', 'fecha2:string|DateTime', 'unidad:string=dias'], 'r' => 'int', 'd' => 'Diferencia entre dos fechas en seg/min/hrs/dias/sem/meses/años', 'k' => ['diferencia fechas', 'date diff', 'calcular dias']],
                        ['nombre' => 'relativo', 'p' => ['fecha:string|DateTime|null'], 'r' => 'string', 'd' => 'Texto relativo: "hace 3 días", "en 2 horas"', 'k' => ['fecha relativa', 'time ago', 'hace cuanto']],
                        ['nombre' => 'edad', 'p' => ['fechaNacimiento:string|DateTime'], 'r' => 'int', 'd' => 'Calcula edad desde fecha de nacimiento', 'k' => ['calcular edad', 'birthday']],
                        ['nombre' => 'esHoy', 'p' => ['fecha:string|DateTime'], 'r' => 'bool', 'd' => 'Verifica si una fecha es hoy', 'k' => ['es hoy', 'is today']],
                        ['nombre' => 'esPasado', 'p' => ['fecha:string|DateTime'], 'r' => 'bool', 'd' => 'Verifica si una fecha ya pasó', 'k' => ['es pasado', 'ya paso', 'is past']],
                        ['nombre' => 'esFuturo', 'p' => ['fecha:string|DateTime'], 'r' => 'bool', 'd' => 'Verifica si una fecha es futura', 'k' => ['es futuro', 'futura', 'is future']],
                        ['nombre' => 'sumarDias', 'p' => ['fecha:string|DateTime', 'dias:int'], 'r' => 'DateTime', 'd' => 'Suma días a una fecha', 'k' => ['sumar dias', 'add days']],
                        ['nombre' => 'restarDias', 'p' => ['fecha:string|DateTime', 'dias:int'], 'r' => 'DateTime', 'd' => 'Resta días a una fecha', 'k' => ['restar dias', 'sub days']],
                        ['nombre' => 'primerDiaMes', 'p' => ['fecha:string|DateTime|null=null'], 'r' => 'DateTime', 'd' => 'Primer día del mes de una fecha', 'k' => ['primer dia mes', 'first day month']],
                        ['nombre' => 'ultimoDiaMes', 'p' => ['fecha:string|DateTime|null=null'], 'r' => 'DateTime', 'd' => 'Último día del mes de una fecha', 'k' => ['ultimo dia mes', 'last day month']],
                        ['nombre' => 'comparar', 'p' => ['fecha1:string|DateTime', 'fecha2:string|DateTime'], 'r' => 'int', 'd' => 'Compara dos fechas (-1, 0, 1)', 'k' => ['comparar fechas', 'date compare']],
                        ['nombre' => 'estaEntre', 'p' => ['fecha:string|DateTime', 'inicio:string|DateTime', 'fin:string|DateTime'], 'r' => 'bool', 'd' => 'Verifica si fecha está en rango', 'k' => ['entre fechas', 'rango', 'date between']],
                        ['nombre' => 'aTimestamp', 'p' => ['fecha:string|DateTime'], 'r' => 'int', 'd' => 'Convierte fecha a Unix timestamp', 'k' => ['a timestamp', 'unix', 'epoch']],
                        ['nombre' => 'crear', 'p' => ['fecha:string|DateTime|null=null'], 'r' => 'DateTime', 'd' => 'Crea objeto DateTime desde string o null', 'k' => ['crear fecha', 'new date', 'datetime']],
                        ['nombre' => 'aMySQL', 'p' => ['fecha:string|DateTime'], 'r' => 'string', 'd' => 'Formatea fecha a formato MySQL (Y-m-d H:i:s)', 'k' => ['formato mysql', 'mysql date']],
                    ],
                ],
            ],
            'arreglo' => [
                [
                    'helper' => 'AyudanteArreglo',
                    'alias' => 'Arreglo',
                    'descripcion' => 'Utilidades para arreglos y colecciones',
                    'metodos' => [
                        ['nombre' => 'primero', 'p' => ['arreglo:array'], 'r' => 'mixed', 'd' => 'Primer elemento del arreglo', 'k' => ['primer elemento', 'first']],
                        ['nombre' => 'ultimo', 'p' => ['arreglo:array'], 'r' => 'mixed', 'd' => 'Último elemento del arreglo', 'k' => ['ultimo elemento', 'last']],
                        ['nombre' => 'obtener', 'p' => ['arreglo:array', 'clave:int|string', 'defecto:mixed=null'], 'r' => 'mixed', 'd' => 'Valor del arreglo por clave con fallback', 'k' => ['get', 'obtener clave', 'array get']],
                        ['nombre' => 'tomar', 'p' => ['arreglo:array', 'limite:int'], 'r' => 'array', 'd' => 'Toma los primeros N elementos', 'k' => ['tomar n', 'take', 'slice inicio']],
                        ['nombre' => 'ignorar', 'p' => ['arreglo:array', 'limite:int'], 'r' => 'array', 'd' => 'Ignora los primeros N elementos', 'k' => ['skip', 'saltar', 'desde']],
                        ['nombre' => 'pluck', 'p' => ['arreglo:array', 'clave:string', 'indice:string|null=null'], 'r' => 'array', 'd' => 'Extrae columna de array de objetos/arrays', 'k' => ['columna', 'extraer campo', 'pluck']],
                        ['nombre' => 'agrupar', 'p' => ['arreglo:array', 'clave:string'], 'r' => 'array', 'd' => 'Agrupa elementos por una clave', 'k' => ['group by', 'agrupar por']],
                        ['nombre' => 'filtrar', 'p' => ['arreglo:array', 'callback:callable'], 'r' => 'array', 'd' => 'Filtra con función callback', 'k' => ['filter', 'filtrar por']],
                        ['nombre' => 'ordenar', 'p' => ['arreglo:array', 'clave:string', 'direccion:string=ASC'], 'r' => 'array', 'd' => 'Ordena por clave ASC/DESC', 'k' => ['ordenar por', 'sort', 'order by']],
                        ['nombre' => 'aplanar', 'p' => ['arreglo:array', 'profundidad:int=-1'], 'r' => 'array', 'd' => 'Aplana array multidimensional', 'k' => ['flatten', 'aplanar', 'array flat']],
                        ['nombre' => 'unico', 'p' => ['arreglo:array'], 'r' => 'array', 'd' => 'Valores únicos del arreglo', 'k' => ['unique', 'valores unicos', 'duplicados']],
                        ['nombre' => 'contiene', 'p' => ['arreglo:array', 'valor:mixed', 'estricto:bool=true'], 'r' => 'bool', 'd' => 'Verifica si existe un valor', 'k' => ['in array', 'contiene valor']],
                        ['nombre' => 'indiceDe', 'p' => ['arreglo:array', 'valor:mixed', 'estricto:bool=true'], 'r' => 'int|string|null', 'd' => 'Busca índice de un valor', 'k' => ['array search', 'buscar indice']],
                        ['nombre' => 'chunks', 'p' => ['arreglo:array', 'tamano:int'], 'r' => 'array', 'd' => 'Divide en fragmentos de tamaño N', 'k' => ['chunk', 'fragmentos', 'partes']],
                        ['nombre' => 'combinar', 'p' => ['claves:array', 'valores:array'], 'r' => 'array', 'd' => 'Combina arrays (claves + valores)', 'k' => ['combine', 'array combine']],
                        ['nombre' => 'invertir', 'p' => ['arreglo:array'], 'r' => 'array', 'd' => 'Invierte orden preservando keys', 'k' => ['reverse', 'array reverse']],
                        ['nombre' => 'claves', 'p' => ['arreglo:array'], 'r' => 'array', 'd' => 'Obtiene las claves del arreglo', 'k' => ['keys', 'array keys']],
                        ['nombre' => 'valores', 'p' => ['arreglo:array'], 'r' => 'array', 'd' => 'Obtiene los valores del arreglo', 'k' => ['values', 'array values']],
                        ['nombre' => 'contarPor', 'p' => ['arreglo:array', 'callback:callable|string'], 'r' => 'array', 'd' => 'Cuenta ocurrencias agrupadas por campo/callback', 'k' => ['count by', 'contar agrupado']],
                        ['nombre' => 'reducir', 'p' => ['arreglo:array', 'callback:callable', 'inicial:mixed=null'], 'r' => 'mixed', 'd' => 'Reduce array a un solo valor', 'k' => ['reduce', 'fold']],
                        ['nombre' => 'cada', 'p' => ['arreglo:array', 'callback:callable'], 'r' => 'array', 'd' => 'Aplica función a cada elemento', 'k' => ['each', 'map', 'mapear', 'transformar']],
                        ['nombre' => 'estaVacio', 'p' => ['arreglo:array'], 'r' => 'bool', 'd' => 'Verifica si el arreglo está vacío', 'k' => ['empty', 'vacio', 'is empty']],
                        ['nombre' => 'conteo', 'p' => ['arreglo:array'], 'r' => 'int', 'd' => 'Cuenta elementos del arreglo', 'k' => ['count', 'contar', 'length']],
                        ['nombre' => 'buscar', 'p' => ['arreglo:array', 'callback:callable'], 'r' => 'array', 'd' => 'Filtra elementos que coinciden con callback', 'k' => ['buscar en', 'find', 'search array']],
                        ['nombre' => 'sumar', 'p' => ['arreglo:array', 'clave:string'], 'r' => 'int|float', 'd' => 'Suma valores de una columna', 'k' => ['sum', 'sumar campo']],
                        ['nombre' => 'promedio', 'p' => ['arreglo:array', 'clave:string'], 'r' => 'float', 'd' => 'Promedio de valores de una columna', 'k' => ['avg', 'average', 'promedio campo']],
                    ],
                ],
            ],
            'seguridad' => [
                [
                    'helper' => 'AyudanteSeguridad',
                    'alias' => 'Seguridad',
                    'descripcion' => 'Autenticación, autorización y CSRF',
                    'metodos' => [
                        ['nombre' => 'tokenCSRF', 'p' => [], 'r' => 'string', 'd' => 'Obtiene el token CSRF de la sesión actual', 'k' => ['csrf token', 'obtener token']],
                        ['nombre' => 'sesionActiva', 'p' => [], 'r' => 'bool', 'd' => 'Verifica si hay sesión de operador activa', 'k' => ['sesion activa', 'logged in', 'autenticado']],
                        ['nombre' => 'idOperador', 'p' => [], 'r' => 'int', 'd' => 'ID del operador en sesión (0 si no autenticado)', 'k' => ['id operador', 'user id', 'operador actual']],
                        ['nombre' => 'autenticacionRequerida', 'p' => [], 'r' => 'void', 'd' => 'Redirige al login si no hay sesión activa', 'k' => ['requiere autenticacion', 'login required']],
                        ['nombre' => 'permisoRequerido', 'p' => ['clave:string'], 'r' => 'void', 'd' => 'Lanza excepción si el operador no tiene permiso', 'k' => ['requiere permiso', 'check permission']],
                        ['nombre' => 'tienePermiso', 'p' => ['clave:string'], 'r' => 'bool', 'd' => 'Verifica si el operador tiene un permiso específico', 'k' => ['tiene permiso', 'has permission', 'verificar permiso']],
                        ['nombre' => 'validarCSRF', 'p' => ['token:string'], 'r' => 'bool', 'd' => 'Valida un token CSRF contra la sesión', 'k' => ['validar csrf', 'csrf valido', 'verify csrf']],
                        ['nombre' => 'csrfMeta', 'p' => [], 'r' => 'string', 'd' => 'Genera meta tag HTML con el token CSRF', 'k' => ['csrf meta tag', 'csrf html']],
                        ['nombre' => 'tokenNuevo', 'p' => [], 'r' => 'string', 'd' => 'Genera y guarda un nuevo token CSRF', 'k' => ['generar csrf', 'new token', 'regenerar']],
                        ['nombre' => 'ipCliente', 'p' => [], 'r' => 'string', 'd' => 'Dirección IP del cliente', 'k' => ['ip cliente', 'client ip', 'remote addr']],
                        ['nombre' => 'agenteUsuario', 'p' => [], 'r' => 'string', 'd' => 'User-Agent del cliente', 'k' => ['user agent', 'navegador']],
                    ],
                ],
            ],
            'archivo' => [
                [
                    'helper' => 'AyudanteArchivo',
                    'alias' => 'ArchivoH',
                    'descripcion' => 'Clasificación y sanitización de archivos',
                    'metodos' => [
                        ['nombre' => 'tamanoLegible', 'p' => ['bytes:int'], 'r' => 'string', 'd' => 'Convierte bytes a formato legible (KB, MB, GB)', 'k' => ['tamano archivo', 'file size', 'bytes legible']],
                        ['nombre' => 'esImagen', 'p' => ['mime:string'], 'r' => 'bool', 'd' => 'Verifica si un MIME es de imagen', 'k' => ['es imagen', 'is image', 'mime imagen']],
                        ['nombre' => 'esDocumento', 'p' => ['mime:string'], 'r' => 'bool', 'd' => 'Verifica si un MIME es de documento', 'k' => ['es documento', 'documento', 'mime doc']],
                        ['nombre' => 'esVideo', 'p' => ['mime:string'], 'r' => 'bool', 'd' => 'Verifica si un MIME es de video', 'k' => ['es video', 'video', 'mime video']],
                        ['nombre' => 'esAudio', 'p' => ['mime:string'], 'r' => 'bool', 'd' => 'Verifica si un MIME es de audio', 'k' => ['es audio', 'audio', 'mime audio']],
                        ['nombre' => 'esComprimido', 'p' => ['mime:string'], 'r' => 'bool', 'd' => 'Verifica si un MIME es comprimido', 'k' => ['es comprimido', 'zip', 'mime zip']],
                        ['nombre' => 'categoriaMime', 'p' => ['mime:string'], 'r' => 'string', 'd' => 'Categoría general del tipo MIME', 'k' => ['categoria mime', 'tipo mime']],
                        ['nombre' => 'iconoExtension', 'p' => ['extension:string'], 'r' => 'string', 'd' => 'Icono emoji para la extensión del archivo', 'k' => ['icono archivo', 'file icon', 'emoji']],
                        ['nombre' => 'extensionSegura', 'p' => ['nombre:string'], 'r' => 'string', 'd' => 'Extrae la extensión con validación de seguridad', 'k' => ['extension segura', 'safe extension']],
                        ['nombre' => 'sanitizarNombre', 'p' => ['nombre:string'], 'r' => 'string', 'd' => 'Limpia nombre de archivo eliminando caracteres peligrosos', 'k' => ['sanitizar archivo', 'clean filename', 'nombre seguro']],
                        ['nombre' => 'esNombreSeguro', 'p' => ['nombre:string'], 'r' => 'bool', 'd' => 'Verifica si el nombre de archivo es seguro', 'k' => ['nombre seguro', 'filename safe', 'validar nombre']],
                    ],
                ],
            ],
            'cache' => [
                [
                    'helper' => 'AyudanteCache',
                    'alias' => 'Cache',
                    'descripcion' => 'Sistema de caché multi-backend (APCu + archivo + memoria)',
                    'metodos' => [
                        ['nombre' => 'recordar', 'p' => ['clave:string', 'generar:callable', 'ttl:int=300'], 'r' => 'mixed', 'd' => 'Cachea resultado de un callback con TTL', 'k' => ['cachear', 'remember', 'memorizar']],
                        ['nombre' => 'recordarJson', 'p' => ['clave:string', 'generar:callable', 'ttl:int=300'], 'r' => 'array', 'd' => 'Cachea callback que retorna array, devuelve array siempre', 'k' => ['cachear json', 'remember json']],
                        ['nombre' => 'obtener', 'p' => ['clave:string'], 'r' => 'mixed', 'd' => 'Obtiene valor de la caché por clave', 'k' => ['obtener cache', 'get cache', 'leer cache']],
                        ['nombre' => 'guardar', 'p' => ['clave:string', 'valor:mixed', 'ttl:int=300'], 'r' => 'bool', 'd' => 'Guarda valor en caché con TTL', 'k' => ['guardar cache', 'set cache', 'almacenar']],
                        ['nombre' => 'olvidar', 'p' => ['clave:string'], 'r' => 'bool', 'd' => 'Elimina una clave de la caché', 'k' => ['olvidar cache', 'forget', 'eliminar cache']],
                        ['nombre' => 'limpiar', 'p' => [], 'r' => 'bool', 'd' => 'Limpia toda la caché', 'k' => ['limpiar cache', 'clear cache', 'flush']],
                        ['nombre' => 'tiene', 'p' => ['clave:string'], 'r' => 'bool', 'd' => 'Verifica si una clave existe en caché', 'k' => ['existe cache', 'has cache']],
                        ['nombre' => 'recordarResultadosPaginados', 'p' => ['prefijo:string', 'pagina:int', 'porPagina:int', 'generar:callable', 'ttl:int=300'], 'r' => 'array', 'd' => 'Cachea resultados paginados', 'k' => ['cache paginado', 'paginacion cache']],
                        ['nombre' => 'olvidarPorPrefijo', 'p' => ['prefijo:string'], 'r' => 'int', 'd' => 'Elimina todas las claves que coincidan con prefijo', 'k' => ['olvidar prefijo', 'forget prefix']],
                        ['nombre' => 'info', 'p' => [], 'r' => 'array', 'd' => 'Información del estado de la caché (APCu, memoria, archivos)', 'k' => ['info cache', 'estado cache', 'cache status']],
                    ],
                ],
            ],
            'http' => [
                [
                    'helper' => 'AyudanteHttp',
                    'alias' => 'HttpCliente',
                    'descripcion' => 'Cliente HTTP con cURL (GET, POST, paralelo)',
                    'metodos' => [
                        ['nombre' => 'obtener', 'p' => ['url:string', 'cabeceras:array=[]', 'timeout:int=15'], 'r' => 'array', 'd' => 'Petición GET con cabeceras personalizadas', 'k' => ['get', 'http get', 'obtener url']],
                        ['nombre' => 'post', 'p' => ['url:string', 'datos:array|string', 'cabeceras:array=[]', 'timeout:int=15'], 'r' => 'array', 'd' => 'Petición POST con datos de formulario', 'k' => ['post', 'http post', 'enviar formulario']],
                        ['nombre' => 'postJson', 'p' => ['url:string', 'datos:array', 'cabeceras:array=[]', 'timeout:int=15'], 'r' => 'array', 'd' => 'Petición POST con body JSON', 'k' => ['post json', 'json api', 'api call']],
                        ['nombre' => 'enviar', 'p' => ['metodo:string', 'url:string', 'opciones:array=[]'], 'r' => 'array', 'd' => 'Petición HTTP genérica (GET, POST, PUT, DELETE, HEAD, PATCH)', 'k' => ['enviar request', 'http request', 'curl']],
                        ['nombre' => 'paralelo', 'p' => ['peticiones:array', 'timeout:int=15'], 'r' => 'array', 'd' => 'Múltiples peticiones HTTP en paralelo (curl_multi)', 'k' => ['paralelo', 'multi request', 'concurrente', 'batch']],
                        ['nombre' => 'codigoComoTexto', 'p' => ['codigo:int'], 'r' => 'string', 'd' => 'Texto descriptivo del código HTTP', 'k' => ['codigo http', 'http status text']],
                        ['nombre' => 'verificarDisponible', 'p' => [], 'r' => 'bool', 'd' => 'Verifica si cURL está disponible', 'k' => ['curl disponible', 'curl check']],
                    ],
                ],
            ],
            'rendimiento' => [
                [
                    'helper' => 'AyudanteRendimiento',
                    'alias' => 'Rendimiento',
                    'descripcion' => 'Benchmarking y perfilado de rendimiento',
                    'metodos' => [
                        ['nombre' => 'iniciar', 'p' => ['nombre:string'], 'r' => 'void', 'd' => 'Inicia medición de tiempo y memoria', 'k' => ['iniciar medicion', 'start benchmark']],
                        ['nombre' => 'detener', 'p' => ['nombre:string'], 'r' => 'array', 'd' => 'Detiene medición y devuelve resultado', 'k' => ['detener medicion', 'stop benchmark', 'resultado']],
                        ['nombre' => 'medir', 'p' => ['callback:callable', 'nombre:string=', 'iteraciones:int=1'], 'r' => 'array', 'd' => 'Ejecuta callback midiendo tiempo y memoria', 'k' => ['medir funcion', 'benchmark', 'profile']],
                        ['nombre' => 'comparar', 'p' => ['escenarios:array', 'iteraciones:int=100'], 'r' => 'array', 'd' => 'Compara múltiples implementaciones', 'k' => ['comparar rendimiento', 'A/B test', 'performance compare']],
                        ['nombre' => 'reporte', 'p' => [], 'r' => 'array', 'd' => 'Reporte completo de todas las mediciones', 'k' => ['reporte rendimiento', 'performance report']],
                        ['nombre' => 'formatearTexto', 'p' => [], 'r' => 'string', 'd' => 'Reporte formateado como texto legible', 'k' => ['reporte texto', 'formatear benchmark']],
                        ['nombre' => 'loggear', 'p' => ['archivo:string='], 'r' => 'bool', 'd' => 'Guarda reporte en archivo de log', 'k' => ['log rendimiento', 'guardar benchmark']],
                        ['nombre' => 'limpiar', 'p' => [], 'r' => 'void', 'd' => 'Limpia todas las mediciones en memoria', 'k' => ['limpiar mediciones', 'reset benchmark']],
                        ['nombre' => 'cabeceras', 'p' => [], 'r' => 'array', 'd' => 'Cabeceras HTTP con datos de rendimiento (X-Lite-Tiempo, etc)', 'k' => ['cabeceras rendimiento', 'performance headers']],
                    ],
                ],
            ],
            'usuario' => [
                [
                    'helper' => 'AyudanteOperador',
                    'alias' => 'OperadorH',
                    'descripcion' => 'Información y validación del operador/usuario en sesión',
                    'metodos' => [
                        ['nombre' => 'estadoEtiqueta', 'p' => ['estado:int'], 'r' => 'string', 'd' => 'HTML con etiqueta visual del estado (Activo/Suspendido)', 'k' => ['etiqueta estado', 'status badge']],
                        ['nombre' => 'estadoTexto', 'p' => ['estado:int'], 'r' => 'string', 'd' => 'Texto del estado: Activo o Suspendido', 'k' => ['texto estado', 'status text']],
                        ['nombre' => 'nombreRol', 'p' => ['idRol:int'], 'r' => 'string', 'd' => 'Nombre del rol desde su ID', 'k' => ['nombre rol', 'role name']],
                        ['nombre' => 'estaActivo', 'p' => ['idOperador:int|null=null'], 'r' => 'bool', 'd' => 'Verifica si operador (actual o por ID) está activo', 'k' => ['operador activo', 'usuario activo']],
                        ['nombre' => 'tienePermiso', 'p' => ['clave:string'], 'r' => 'bool', 'd' => 'Verifica si operador actual tiene permiso', 'k' => ['tiene permiso', 'check rbac']],
                        ['nombre' => 'nombreActual', 'p' => [], 'r' => 'string', 'd' => 'Nombre del operador en sesión', 'k' => ['nombre operador', 'usuario actual']],
                        ['nombre' => 'idActual', 'p' => [], 'r' => 'int', 'd' => 'ID del operador en sesión (0 si no autenticado)', 'k' => ['id operador actual', 'user id']],
                        ['nombre' => 'rolActual', 'p' => [], 'r' => 'int', 'd' => 'ID del rol del operador en sesión', 'k' => ['rol actual', 'rol usuario']],
                        ['nombre' => 'permisosActuales', 'p' => [], 'r' => 'array', 'd' => 'Matriz de permisos del operador actual', 'k' => ['permisos usuario', 'matriz permisos']],
                        ['nombre' => 'permisoRequerido', 'p' => ['clave:string'], 'r' => 'void', 'd' => 'Lanza excepción si no tiene permiso específico', 'k' => ['requiere permiso', 'permiso necesario']],
                    ],
                ],
            ],
            'monitor' => [
                [
                    'helper' => 'AyudanteMonitor',
                    'alias' => 'Monitor',
                    'descripcion' => 'Estadísticas de rendimiento desde archivo de log',
                    'metodos' => [
                        ['nombre' => 'inicializar', 'p' => ['archivoLog:string='], 'r' => 'void', 'd' => 'Configura la ruta del archivo de log', 'k' => ['inicializar monitor', 'set log path']],
                        ['nombre' => 'obtenerEstadisticas', 'p' => [], 'r' => 'array', 'd' => 'Estadísticas completas: promedio, p99, p95, distribución, lentos', 'k' => ['estadisticas', 'metricas', 'dashboard rendimiento']],
                        ['nombre' => 'obtenerUltimos', 'p' => [], 'r' => 'array', 'd' => 'Últimos 10 registros de rendimiento', 'k' => ['ultimos registros', 'recent']],
                        ['nombre' => 'logPath', 'p' => [], 'r' => 'string', 'd' => 'Ruta actual del archivo de log', 'k' => ['ruta log', 'log file path']],
                    ],
                ],
            ],
            
            'herramientas_mcp' => [
                [
                    'helper' => 'GuiasMCP',
                    'alias' => 'MCP',
                    'descripcion' => 'Herramientas del sistema MCP que la IA puede invocar directamente',
                    'metodos' => [
                        ['nombre' => 'lite_read_file', 'p' => ['path: string - ruta relativa al proyecto (ej: servidor/modelos/Usuario.php)'], 'r' => 'string (guia)', 'd' => 'GUIA: Lee contenido completo de un archivo. Usa lite_read_file(path="ruta")', 'k' => ['leer', 'archivo', 'file', 'contenido', 'read', 'abrir', 'ver', 'analizar', 'analiza', 'revisar', 'inspeccionar']],
                        ['nombre' => 'lite_write_file', 'p' => ['path: string - ruta', 'content: string - contenido'], 'r' => 'string (guia)', 'd' => 'GUIA: Escribe o crea un archivo. Crea directorios automaticamente. Usa lite_write_file(path="ruta", content="...")', 'k' => ['escribir', 'crear', 'guardar', 'file', 'write', 'nuevo']],
                        ['nombre' => 'lite_edit', 'p' => ['filePath: string - ruta', 'oldString: string - texto exacto', 'newString: string - texto nuevo', 'replaceAll: bool - opcional'], 'r' => 'string (guia)', 'd' => 'GUIA: Reemplaza texto exacto en un archivo. Usa lite_edit(filePath="ruta", oldString="...", newString="...")', 'k' => ['editar', 'reemplazar', 'modificar', 'cambiar', 'edit', 'update']],
                        ['nombre' => 'lite_grep', 'p' => ['pattern: string - regex a buscar', 'path: string - directorio opcional', 'include: string - filtro de archivo (ej: *.php)'], 'r' => 'string (guia)', 'd' => 'GUIA: Busca texto con regex en archivos. Usa lite_grep(pattern="texto", path="dir", include="*.php")', 'k' => ['buscar', 'search', 'grep', 'patron', 'pattern', 'find', 'texto']],
                        ['nombre' => 'lite_glob', 'p' => ['pattern: string - patron glob (ej: **/*.php)', 'path: string - directorio opcional'], 'r' => 'string (guia)', 'd' => 'GUIA: Busca archivos por patron glob. Usa lite_glob(pattern="**/*.php")', 'k' => ['listar', 'archivos', 'glob', 'patron', 'files', 'encontrar', 'analizar', 'explorar', 'inspeccionar']],
                        ['nombre' => 'lite_list_dir', 'p' => ['path: string - ruta del directorio'], 'r' => 'string (guia)', 'd' => 'GUIA: Lista contenido de un directorio. Usa lite_list_dir(path="carpeta")', 'k' => ['listar', 'directorio', 'carpeta', 'folder', 'dir', 'contenido', 'explorar', 'estructura']],
                        ['nombre' => 'lite_run', 'p' => ['command: string - comando CLI (modulo:generar, crud, migrar, pruebas...)', 'args: string - argumentos opcionales'], 'r' => 'string (guia)', 'd' => 'GUIA: Ejecuta comandos CLI del framework. Usa lite_run(command="comando", args="--args")', 'k' => ['ejecutar', 'comando', 'cli', 'run', 'consola', 'cmd', 'command', 'migrar', 'pruebas']],
                        ['nombre' => 'lite_equipo', 'p' => ['tipo: string - analisis, desarrollo, seguridad, frontend, backend, completo...', 'tarea: string - instruccion detallada', 'agentes: string - lista custom separada por coma (opcional)'], 'r' => 'string (guia)', 'd' => 'GUIA: Ejecuta un equipo de agentes IA. Usa lite_equipo(tipo="desarrollo", tarea="...")', 'k' => ['equipo', 'team', 'crew', 'agentes', 'orquestar', 'desarrollo', 'analisis']],
                        ['nombre' => 'lite_diagnostico', 'p' => [], 'r' => 'string (guia)', 'd' => 'GUIA: Diagnostica el sistema completo (BD, archivos, seguridad, PHP). Usa lite_run(command="diagnostico")', 'k' => ['diagnostico', 'diagnostic', 'system', 'salud', 'health', 'check']],
                        ['nombre' => 'lite_crud', 'p' => ['tabla: string - nombre tabla', 'accion: string - listar/leer/crear/actualizar/eliminar', 'data: string - JSON opcional', 'id: int - ID opcional'], 'r' => 'string (guia)', 'd' => 'GUIA: CRUD generico sobre cualquier tabla. Usa lite_run(command="crud", args="--tabla=X --accion=listar")', 'k' => ['crud', 'tabla', 'base', 'datos', 'database', 'bd', 'registro']],
                        ['nombre' => 'lite_modulo_generar', 'p' => ['nombre_clase: string - CamelCase (ej: Producto)', 'tabla: string - nombre tabla opcional', 'campos: string - formato "campo:tipo,..."'], 'r' => 'string (guia)', 'd' => 'GUIA: Genera estructura completa de archivos para un modulo. Usa lite_run(command="modulo:generar", args="NombreClase --campos=...")', 'k' => ['crear crud', 'generar entidad', 'scaffold', 'nuevo crud', 'crud completo', 'generar proyecto']],
                        ['nombre' => 'lite_migrar', 'p' => [], 'r' => 'string (guia)', 'd' => 'GUIA: Ejecuta migraciones pendientes de BD. Usa lite_run(command="migrar")', 'k' => ['migrar', 'migrate', 'bd', 'database', 'schema']],
                        ['nombre' => 'lite_pruebas', 'p' => ['filtro: string - nombre de clase/metodo a filtrar (opcional)', 'cobertura: bool - reporte de cobertura (opcional)'], 'r' => 'string (guia)', 'd' => 'GUIA: Ejecuta tests PHPUnit. Usa lite_run(command="pruebas") o lite_run(command="pruebas", args="--filtro=NombreTest")', 'k' => ['test', 'pruebas', 'phpunit', 'unit', 'testing', 'tests', 'cobertura']],
                        ['nombre' => 'lite_operador_crear', 'p' => ['nombre: string', 'email: string', 'clave: string'], 'r' => 'string (guia)', 'd' => 'GUIA: Crea un operador/usuario en el sistema. Usa lite_run(command="operador:crear", args="--nombre=X --email=Y --clave=Z")', 'k' => ['usuario', 'operador', 'crear', 'user', 'register']],
                        ['nombre' => 'lite_filtrar', 'p' => ['consulta: string - texto relevante', 'archivo: string - ruta (opcional)', 'texto: string - texto directo (opcional)'], 'r' => 'string (guia)', 'd' => 'GUIA: Filtra contenido irrelevante para reducir tokens. Usa lite_filtrar(consulta="...", archivo="ruta")', 'k' => ['filtrar', 'filter', 'tokens', 'resumir', 'relevant', 'contenido']],
                    ],
                ],
            ],
'utilidades' => [
                [
                    'helper' => 'AyudanteGeneral',
                    'alias' => 'General',
                    'descripcion' => 'Utilidades misceláneas que no encajan en otras categorías',
                    'metodos' => [
                        ['nombre' => 'tieneValor', 'p' => ['valor:mixed'], 'r' => 'bool', 'd' => 'Verifica si variable tiene contenido no vacío', 'k' => ['tiene valor', 'not empty', 'is set']],
                        ['nombre' => 'noEstaVacio', 'p' => ['valor:mixed'], 'r' => 'bool', 'd' => 'Alias de tieneValor', 'k' => ['no vacio', 'is not empty']],
                        ['nombre' => 'estaVacio', 'p' => ['valor:mixed'], 'r' => 'bool', 'd' => 'Alias inverso de tieneValor', 'k' => ['esta vacio', 'is empty']],
                        ['nombre' => 'obtenerValor', 'p' => ['fuente:array', 'clave:string', 'defecto:mixed=null'], 'r' => 'mixed', 'd' => 'Valor de array por clave con fallback', 'k' => ['obtener array', 'array get']],
                        ['nombre' => 'desde', 'p' => ['fuente:array', 'clave:string', 'defecto:mixed=null'], 'r' => 'mixed', 'd' => 'Alias de obtenerValor', 'k' => ['desde array']],
                        ['nombre' => 'generarToken', 'p' => ['longitud:int=32'], 'r' => 'string', 'd' => 'Token hexadecimal aleatorio seguro', 'k' => ['generar token', 'random token', 'hex']],
                        ['nombre' => 'clonar', 'p' => ['objeto:object'], 'r' => 'object', 'd' => 'Clona un objeto', 'k' => ['clonar objeto', 'clone']],
                        ['nombre' => 'esMetodo', 'p' => ['objeto:object', 'metodo:string'], 'r' => 'bool', 'd' => 'Verifica si objeto tiene un método', 'k' => ['existe metodo', 'method exists']],
                        ['nombre' => 'tienePropiedad', 'p' => ['objeto:object', 'propiedad:string'], 'r' => 'bool', 'd' => 'Verifica si objeto tiene una propiedad', 'k' => ['tiene propiedad', 'property exists']],
                        ['nombre' => 'tipoDe', 'p' => ['objeto:object'], 'r' => 'string', 'd' => 'Nombre de la clase del objeto', 'k' => ['tipo objeto', 'get class', 'class name']],
                        ['nombre' => 'esObjeto', 'p' => ['valor:mixed'], 'r' => 'bool', 'd' => 'Verifica si es un objeto', 'k' => ['es objeto', 'is object']],
                        ['nombre' => 'esArreglo', 'p' => ['valor:mixed'], 'r' => 'bool', 'd' => 'Verifica si es un array', 'k' => ['es arreglo', 'is array']],
                        ['nombre' => 'esString', 'p' => ['valor:mixed'], 'r' => 'bool', 'd' => 'Verifica si es string', 'k' => ['es string', 'is string']],
                        ['nombre' => 'esNumerico', 'p' => ['valor:mixed'], 'r' => 'bool', 'd' => 'Verifica si es numérico', 'k' => ['es numerico', 'is numeric']],
                        ['nombre' => 'esBooleano', 'p' => ['valor:mixed'], 'r' => 'bool', 'd' => 'Verifica si es booleano', 'k' => ['es booleano', 'is bool']],
                        ['nombre' => 'aBooleano', 'p' => ['valor:mixed'], 'r' => 'bool', 'd' => 'Convierte a booleano ("si", "1", true → true)', 'k' => ['convertir bool', 'to boolean']],
                        ['nombre' => 'aEntero', 'p' => ['valor:mixed', 'defecto:int=0'], 'r' => 'int', 'd' => 'Convierte a entero con fallback', 'k' => ['convertir int', 'to integer']],
                        ['nombre' => 'aFlotante', 'p' => ['valor:mixed', 'defecto:float=0.0'], 'r' => 'float', 'd' => 'Convierte a float con fallback', 'k' => ['convertir float', 'to float']],
                        ['nombre' => 'aString', 'p' => ['valor:mixed'], 'r' => 'string', 'd' => 'Convierte a string (null→"", bool→"1"/"0", array→json)', 'k' => ['convertir string', 'to string']],
                        ['nombre' => 'aJson', 'p' => ['valor:mixed', 'pretty:bool=false'], 'r' => 'string', 'd' => 'Serializa a JSON (opcional: pretty print)', 'k' => ['a json', 'to json', 'serializar']],
                        ['nombre' => 'desdeJson', 'p' => ['json:string', 'defecto:mixed=null'], 'r' => 'mixed', 'd' => 'Deserializa JSON a array/mixed', 'k' => ['desde json', 'from json', 'parse json', 'decodificar']],
                        ['nombre' => 'esJson', 'p' => ['texto:string'], 'r' => 'bool', 'd' => 'Verifica si un string es JSON válido', 'k' => ['es json', 'valid json', 'json valido']],
                        ['nombre' => 'moneda', 'p' => ['monto:float|int', 'simbolo:string=$', 'separadorMiles:string=,', 'separadorDecimal:string=.'], 'r' => 'string', 'd' => 'Formatea número como moneda', 'k' => ['formato moneda', 'currency', 'dinero']],
                        ['nombre' => 'numero', 'p' => ['numero:int|float', 'separadorMiles:string=,', 'separadorDecimal:string=.'], 'r' => 'string', 'd' => 'Formatea número con separadores', 'k' => ['formato numero', 'number format', 'miles']],
                        ['nombre' => 'truncarNumero', 'p' => ['numero:float', 'decimales:int=0'], 'r' => 'float', 'd' => 'Trunca número sin redondear', 'k' => ['truncar', 'floor decimal']],
                        ['nombre' => 'redondear', 'p' => ['numero:float', 'decimales:int=0', 'modo:string=normal'], 'r' => 'float', 'd' => 'Redondea número (modos: normal, arriba, abajo)', 'k' => ['redondear', 'round', 'ceil', 'floor']],
                        ['nombre' => 'bytesLegibles', 'p' => ['bytes:int'], 'r' => 'string', 'd' => 'Convierte bytes a formato legible (B, KB, MB, GB, TB)', 'k' => ['bytes legibles', 'human size', 'file size']],
                        ['nombre' => 'dormir', 'p' => ['segundos:int'], 'r' => 'void', 'd' => 'Pausa la ejecución N segundos', 'k' => ['sleep', 'esperar', 'delay']],
                        ['nombre' => 'dormirMilisegundos', 'p' => ['milisegundos:int'], 'r' => 'void', 'd' => 'Pausa la ejecución N milisegundos', 'k' => ['usleep', 'esperar ms', 'micro delay']],
                        ['nombre' => 'unaVez', 'p' => ['clave:string', 'funcion:callable'], 'r' => 'mixed', 'd' => 'Ejecuta función solo una vez, cachea resultado', 'k' => ['una vez', 'singleton fn', 'once']],
                        ['nombre' => 'resetUnaVez', 'p' => ['clave:string='], 'r' => 'void', 'd' => 'Resetea cache de unaVez', 'k' => ['reset once', 'limpiar singletons']],
                        ['nombre' => 'dd', 'p' => ['...valores:mixed'], 'r' => 'void', 'd' => 'Dump and die — debug con var_dump + exit', 'k' => ['debug', 'dump', 'var_dump', 'die']],
                        ['nombre' => 'dump', 'p' => ['...valores:mixed'], 'r' => 'void', 'd' => 'Debug sin morir — var_dump sin exit', 'k' => ['debug sin morir', 'dump no die']],
                    ],
                ],
            ],
        ];
    }
}
