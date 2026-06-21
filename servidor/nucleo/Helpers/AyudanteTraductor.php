<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

use LiteFramework\Config\ConexionBaseDatos;
use Exception;

class AyudanteTraductor extends Helper
{
    private static ?array $plantillas = null;
    private static array $cache = [];
    private const CACHE_MAX = 100;

    public static function traducir(string $promptHumano): array
    {
        $inicio = hrtime(true);

        $detectada = self::detectarCategoria($promptHumano);
        if (empty($detectada)) {
            return [
                'exito' => false,
                'error' => 'No se pudo detectar la categoria del prompt',
                'prompt_original' => $promptHumano,
                'prompt_traducido' => $promptHumano,
                'categoria' => 'general',
                'confianza' => 0.0,
            ];
        }

        $parametros = self::extraerParametros(
            $promptHumano,
            $detectada['plantilla_humano']
        );

        $traducido = $parametros !== null
            ? self::llenarPlantillaIA(
                $detectada['plantilla_ia'],
                $parametros,
                $detectada['keywords_ia']
            )
            : $promptHumano;

        $confianza = (float)$detectada['confianza'];

        $ms = (int)((hrtime(true) - $inicio) / 1_000_000);

        $idHistorial = self::guardarHistorial(
            $promptHumano,
            $traducido,
            $detectada['categoria'],
            $confianza,
            false,
            $ms
        );

        return [
            'exito' => true,
            'id_historial' => $idHistorial,
            'prompt_original' => $promptHumano,
            'prompt_traducido' => $traducido,
            'categoria' => $detectada['categoria'],
            'nombre_plantilla' => $detectada['nombre'],
            'parametros' => $parametros ?? [],
            'confianza' => $confianza,
            'tiempo_ms' => $ms,
        ];
    }

    public static function humanoAIa(string $promptHumano): array
    {
        $hash = self::generarHash($promptHumano);
        if (isset(self::$cache[$hash])) {
            return self::$cache[$hash];
        }

        $resultado = self::traducir($promptHumano);
        if ($resultado['exito'] && count(self::$cache) < self::CACHE_MAX) {
            self::$cache[$hash] = $resultado;
        }

        return $resultado;
    }

    public static function iaAHumano(string $categoria, string $nombre, array $params = []): string
    {
        $plantillas = self::cargarPlantillas();
        foreach ($plantillas as $p) {
            if ($p['categoria'] === $categoria && $p['nombre'] === $nombre) {
                $paramIndex = 0;
                $partes = explode('%s', $p['plantilla_humano']);
                $resultado = '';
                foreach ($partes as $i => $parte) {
                    $resultado .= $parte;
                    if ($i < count($partes) - 1) {
                        $valor = $paramIndex < count($params)
                            ? $params[$paramIndex]
                            : ($params[$p['keywords_ia'][$paramIndex] ?? ''] ?? '?');
                        $resultado .= $valor;
                        $paramIndex++;
                    }
                }
                return $resultado;
            }
        }
        return self::generarPromptGenerico($categoria, $nombre, $params);
    }

    public static function detectarCategoria(string $prompt): ?array
    {
        $plantillas = self::cargarPlantillas();
        if (empty($plantillas)) {
            return null;
        }

        $mejor = null;
        $mejorPuntaje = 0;

        foreach ($plantillas as $p) {
            $regex = self::plantillaARegex($p['plantilla_humano']);
            if (preg_match($regex, $prompt)) {
                $puntaje = $p['confianza'] ?? 0.5;
                if ($puntaje > $mejorPuntaje) {
                    $mejorPuntaje = $puntaje;
                    $mejor = $p;
                }
            }
        }

        if ($mejor !== null) {
            $mejor['confianza'] = $mejorPuntaje;
            return $mejor;
        }

        $categoria = self::detectarPorPalabrasClave($prompt);
        if ($categoria !== null) {
            return $categoria;
        }

        return null;
    }

    public static function extraerParametros(string $prompt, string $plantillaHumano): ?array
    {
        $regex = self::plantillaARegex($plantillaHumano);
        if (!preg_match($regex, $prompt, $matches)) {
            return null;
        }

        $parametros = [];
        foreach ($matches as $clave => $valor) {
            if (is_string($clave) && str_starts_with($clave, 'p')) {
                $parametros[] = trim($valor);
            }
        }

        return $parametros;
    }

    public static function llenarPlantillaIA(string $plantillaIA, array $parametros, array $keywords): string
    {
        $resultado = $plantillaIA;
        foreach ($keywords as $i => $keyword) {
            $valor = $parametros[$i] ?? $keyword;
            $resultado = str_replace($keyword, $valor, $resultado);
        }
        return $resultado;
    }

    public static function registrarFeedback(int $idHistorial, bool $acierto): bool
    {
        try {
            $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();

            $sql = $bd->prepare(
                "UPDATE traduccion_historial SET feedback_recibido = :fb WHERE id_historial = :id"
            );
            $sql->execute([':fb' => $acierto ? 1 : 0, ':id' => $idHistorial]);

            $sql = $bd->prepare(
                "SELECT categoria_detectada FROM traduccion_historial WHERE id_historial = :id LIMIT 1"
            );
            $sql->execute([':id' => $idHistorial]);
            $categoria = $sql->fetchColumn();
            if ($categoria === false) {
                return false;
            }

            self::actualizarScore((string)$categoria, $acierto);
            return true;
        } catch (Exception $e) {
            error_log('[Traductor] Error en registrarFeedback: ' . $e->getMessage());
            return false;
        }
    }

    public static function calibrar(?string $categoria = null, string $modo = 'completo'): array
    {
        try {
            $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            self::limpiarCache();

            $plantillas = $categoria !== null
                ? array_filter(self::cargarPlantillas(), fn($p) => $p['categoria'] === $categoria)
                : self::cargarPlantillas();

            if (empty($plantillas)) {
                return ['exito' => false, 'error' => 'No hay plantillas para calibrar'];
            }

            $total = 0;
            $aciertos = 0;
            $porCategoria = [];

            // --- Fase 1: Coincidencia exacta contra plantillas ---
            $exactas = 0;
            $exactasOk = 0;

            foreach ($plantillas as $p) {
                $cat = $p['categoria'];
                if (!isset($porCategoria[$cat])) {
                    $porCategoria[$cat] = ['total' => 0, 'aciertos' => 0];
                }

                $parametros = self::generarParametrosPrueba($p);
                $promptHumano = self::generarPromptPrueba($p, $parametros);
                $esperadoIA = self::llenarPlantillaIA(
                    $p['plantilla_ia'],
                    $parametros,
                    $p['keywords_ia']
                );

                $resultado = self::traducir($promptHumano);

                $exactas++;
                $total++;
                $porCategoria[$cat]['total']++;

                if ($resultado['exito'] && $resultado['prompt_traducido'] === $esperadoIA) {
                    $aciertos++;
                    $exactasOk++;
                    $porCategoria[$cat]['aciertos']++;
                }
            }

            // --- Fase 2: Variaciones del mundo real ---
            $variacionesOk = 0;
            $variaciones = 0;
            $variacionesPorCat = [];

            if ($modo === 'variaciones' || $modo === 'completo') {
                $casos = self::obtenerVariaciones();
                foreach ($casos as $caso) {
                    $cat = $caso['categoria'];
                    if ($categoria !== null && $cat !== $categoria) continue;

                    if (!isset($porCategoria[$cat])) {
                        $porCategoria[$cat] = ['total' => 0, 'aciertos' => 0];
                    }
                    if (!isset($variacionesPorCat[$cat])) {
                        $variacionesPorCat[$cat] = ['total' => 0, 'aciertos' => 0];
                    }

                    $resultado = self::detectarCategoria($caso['prompt']);
                    $catDetectada = $resultado['categoria'] ?? null;

                    $variaciones++;
                    $total++;
                    $porCategoria[$cat]['total']++;

                    if ($catDetectada === $cat) {
                        $aciertos++;
                        $variacionesOk++;
                        $porCategoria[$cat]['aciertos']++;
                        $variacionesPorCat[$cat]['aciertos']++;
                    }
                    $variacionesPorCat[$cat]['total']++;
                }
            }

            $precisionGral = $total > 0 ? round($aciertos / $total, 4) : 0;

            $bd->prepare(
                "INSERT INTO traduccion_benchmark
                (version_algoritmo, total_pruebas, aciertos, precision_general, precision_por_categoria)
                VALUES (:ver, :total, :aci, :prec, :cat)"
            )->execute([
                ':ver' => '1.1.0',
                ':total' => $total,
                ':aci' => $aciertos,
                ':prec' => $precisionGral,
                ':cat' => json_encode($porCategoria),
            ]);

            $res = [
                'exito' => true,
                'modo' => $modo,
                'total_pruebas' => $total,
                'aciertos' => $aciertos,
                'precision_general' => $precisionGral,
                'precision_por_categoria' => $porCategoria,
            ];

            if ($modo === 'completo' || $modo === 'variaciones') {
                $res['exactas'] = ['total' => $exactas, 'aciertos' => $exactasOk];
                $res['variaciones'] = ['total' => $variaciones, 'aciertos' => $variacionesOk];
                $res['variaciones_por_categoria'] = $variacionesPorCat;
            }

            return $res;
        } catch (Exception $e) {
            return ['exito' => false, 'error' => $e->getMessage()];
        }
    }

    private static function obtenerVariaciones(): array
    {
        return [
            // generar_modulo
            ['prompt' => 'crea un modulo de usuarios', 'categoria' => 'generar_modulo'],
            ['prompt' => 'generame un CRUD de Productos', 'categoria' => 'generar_modulo'],
            ['prompt' => 'necesito un modulo para Factura', 'categoria' => 'generar_modulo'],
            ['prompt' => 'haz un modulo de Categorias', 'categoria' => 'generar_modulo'],
            // leer_archivo
            ['prompt' => 'leer el archivo AyudanteTraductor.php', 'categoria' => 'leer_archivo'],
            ['prompt' => 'muestrame el contenido de OrquestadorIA', 'categoria' => 'leer_archivo'],
            ['prompt' => 'abrir el archivo de migracion 003', 'categoria' => 'leer_archivo'],
            // editar_archivo
            ['prompt' => 'cambia el umbral de 0.5 a 0.75', 'categoria' => 'editar_archivo'],
            ['prompt' => 'actualiza composer.json', 'categoria' => 'editar_archivo'],
            ['prompt' => 'reemplaza X por Y en autoload', 'categoria' => 'editar_archivo'],
            // buscar_codigo
            ['prompt' => 'donde se referencia Servicios\Traductor', 'categoria' => 'buscar_codigo'],
            ['prompt' => 'busca en los archivos la palabra Traductor', 'categoria' => 'buscar_codigo'],
            ['prompt' => 'encuentra donde se usa ERR_IA', 'categoria' => 'buscar_codigo'],
            // ejecutar_pruebas
            ['prompt' => 'correr los tests del traductor', 'categoria' => 'ejecutar_pruebas'],
            ['prompt' => 'ejecuta las pruebas unitarias', 'categoria' => 'ejecutar_pruebas'],
            ['prompt' => 'phpunit a ver si pasa', 'categoria' => 'ejecutar_pruebas'],
            ['prompt' => 'lanza la suite de tests completa', 'categoria' => 'ejecutar_pruebas'],
            // diagnosticar
            ['prompt' => 'verifica que todo este operativo', 'categoria' => 'diagnosticar'],
            ['prompt' => 'haz un diagnostico del sistema', 'categoria' => 'diagnosticar'],
            ['prompt' => 'componentes funcionando', 'categoria' => 'diagnosticar'],
            // ejecutar_migracion
            ['prompt' => 'corre las migraciones pendientes', 'categoria' => 'ejecutar_migracion'],
            ['prompt' => 'aplica la migracion 003 a la BD', 'categoria' => 'ejecutar_migracion'],
            // seguridad
            ['prompt' => 'valida el token de seguridad', 'categoria' => 'seguridad'],
            ['prompt' => 'tengo permiso para acceder a esto', 'categoria' => 'usuario'],
            // crud_leer
            ['prompt' => 'busca usuarios en la BD', 'categoria' => 'crud_leer'],
            ['prompt' => 'listame todos los registros', 'categoria' => 'crud_leer'],
            // crud_escribir
            ['prompt' => 'crea un operador nuevo en el sistema', 'categoria' => 'crud_escribir'],
            ['prompt' => 'registra este producto en el catalogo', 'categoria' => 'crud_escribir'],
            ['prompt' => 'inserta un pedido en la base', 'categoria' => 'crud_escribir'],
            // cache
            ['prompt' => 'guardame esto en memoria', 'categoria' => 'cache'],
            ['prompt' => 'cachea el resultado por 5 minutos', 'categoria' => 'cache'],
            ['prompt' => 'limpia toda la cache', 'categoria' => 'cache'],
            ['prompt' => 'borra el cache', 'categoria' => 'cache'],
            // http
            ['prompt' => 'consume la API de usuarios', 'categoria' => 'http'],
            ['prompt' => 'haz una llamada a la API de pagos', 'categoria' => 'http'],
            ['prompt' => 'trae los datos del servicio', 'categoria' => 'http'],
            // rendimiento
            ['prompt' => 'que tan rapido corre esta funcion', 'categoria' => 'rendimiento'],
            ['prompt' => 'haz un benchmark del sistema', 'categoria' => 'rendimiento'],
            ['prompt' => 'compara la velocidad entre dos opciones', 'categoria' => 'rendimiento'],
            ['prompt' => 'necesito crear un proyecto nuevo, utilizando lite framework como base', 'categoria' => 'generar_proyecto'],
            ['prompt' => 'quiero hacer una landing page de una empresa de contruccion, usa liteframework como base', 'categoria' => 'generar_proyecto'],
            ['prompt' => 'necesito analizar el modulo vizualizacion de manera objetiva y profunda', 'categoria' => 'analizar_modulo'],
            ['prompt' => 'revisame el modulo de usuarios a ver que tal esta', 'categoria' => 'analizar_modulo'],
            // general
            ['prompt' => 'que hicimos hasta ahora', 'categoria' => 'general'],
            ['prompt' => 'necesitamos reiniciar opencode', 'categoria' => 'general'],
            ['prompt' => 'no entiendo a que te refieres', 'categoria' => 'general'],
            ['prompt' => 'asi no es jaja', 'categoria' => 'general'],
            // git
            ['prompt' => 'muestrame los cambios del repositorio', 'categoria' => 'git'],
            ['prompt' => 'haz un git diff', 'categoria' => 'git'],
            ['prompt' => 'cual es el historial de commits', 'categoria' => 'git'],
            // firecrawl_scrape
            ['prompt' => 'scrapea el contenido de example.com', 'categoria' => 'firecrawl_scrape'],
            ['prompt' => 'extrae la pagina web de google', 'categoria' => 'firecrawl_scrape'],
            // firecrawl_search
            ['prompt' => 'busca en internet sobre inteligencia artificial', 'categoria' => 'firecrawl_search'],
            ['prompt' => 'investiga sobre el framework laravel', 'categoria' => 'firecrawl_search'],
            ['prompt' => 'googlea las ultimas noticias de tecnologia', 'categoria' => 'firecrawl_search'],
            // browser_navegar
            ['prompt' => 'navega a google.com', 'categoria' => 'browser_navegar'],
            ['prompt' => 'abre la pagina de login en el navegador', 'categoria' => 'browser_navegar'],
            // browser_click
            ['prompt' => 'haz click en el boton de enviar', 'categoria' => 'browser_click'],
            ['prompt' => 'presiona el link de registro', 'categoria' => 'browser_click'],
            // browser_type
            ['prompt' => 'escribe mi correo en el campo email', 'categoria' => 'browser_type'],
            ['prompt' => 'teclea la contrasena en el formulario', 'categoria' => 'browser_type'],
            // browser_screenshot
            ['prompt' => 'saca una captura de pantalla de la pagina', 'categoria' => 'browser_screenshot'],
            ['prompt' => 'toma un screenshot del sitio actual', 'categoria' => 'browser_screenshot'],
            // imagen
            ['prompt' => 'genera una imagen de un gato', 'categoria' => 'imagen'],
            ['prompt' => 'crea una ilustracion de una montaña', 'categoria' => 'imagen'],
            // sentry
            ['prompt' => 'muestrame el error 12345 de sentry', 'categoria' => 'sentry'],
            ['prompt' => 'analiza el issue de produccion en sentry', 'categoria' => 'sentry'],
            // github_grep
            ['prompt' => 'busca ejemplos de useState en github', 'categoria' => 'github_grep'],
            ['prompt' => 'encuentra codigo de ejemplo de express en github', 'categoria' => 'github_grep'],
            // fetch_pagina
            ['prompt' => 'obten el contenido de https://ejemplo.com', 'categoria' => 'fetch_pagina'],
            ['prompt' => 'descarga la pagina de wikipedia', 'categoria' => 'fetch_pagina'],
            // tiempo
            ['prompt' => 'que hora es', 'categoria' => 'tiempo'],
            ['prompt' => 'dame la fecha y hora actual', 'categoria' => 'tiempo'],
            // context7
            ['prompt' => 'busca documentacion de react', 'categoria' => 'context7'],
            ['prompt' => 'como se usa el hook useEffect', 'categoria' => 'context7'],
            // firecrawl_crawl
            ['prompt' => 'crawlea todo el sitio de docs.ejemplo.com', 'categoria' => 'firecrawl_crawl'],
            // firecrawl_map
            ['prompt' => 'mapea las urls del sitio example.com', 'categoria' => 'firecrawl_map'],
            // firecrawl_extract
            ['prompt' => 'extrae los precios de la pagina de productos', 'categoria' => 'firecrawl_extract'],
            // investigar_paper
            ['prompt' => 'investiga papers sobre transformers en IA', 'categoria' => 'investigar_paper'],
            ['prompt' => 'busca articulos cientificos sobre machine learning', 'categoria' => 'investigar_paper'],
        ];
    }

    public static function limpiarCache(): void
    {
        self::$cache = [];
        self::$plantillas = null;
    }

    private static function cargarPlantillas(): array
    {
        if (self::$plantillas !== null) {
            return self::$plantillas;
        }

        try {
            $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $stmt = $bd->query(
                "SELECT pp.*, COALESCE(ts.confianza, 0.50) AS confianza
                FROM plantilla_prompt pp
                LEFT JOIN traduccion_score ts ON ts.categoria = pp.categoria
                ORDER BY ts.confianza DESC, pp.uso_total DESC"
            );

            $rows = $stmt->fetchAll();
            self::$plantillas = [];

            foreach ($rows as $row) {
                $row['keywords_ia'] = self::extraerKeywordsIA($row['plantilla_ia']);
                self::$plantillas[] = $row;
            }

            return self::$plantillas;
        } catch (Exception $e) {
            error_log('[Traductor] Error cargando plantillas: ' . $e->getMessage());
            return [];
        }
    }

    private static function extraerKeywordsIA(string $plantillaIA): array
    {
        preg_match_all('/\b[A-Z][A-Z0-9_]+\b/', $plantillaIA, $matches);
        $denylist = ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'PATCH', 'API', 'SQL', 'HTML',
                      'CSS', 'JSON', 'XML', 'HTTP', 'HTTPS', 'CRUD', 'ID', 'ASC', 'DESC',
                      'AND', 'OR', 'NOT', 'IN', 'ON', 'AS', 'TO', 'BY', 'AT', 'IF',
                      'NO', 'SI', 'ES', 'LA', 'EL', 'LO', 'CON', 'POR', 'QUE', 'DEL',
                      'NULL', 'TRUE', 'FALSE', 'MIN', 'MAX', 'SUM', 'AVG', 'COUNT',
                      'NULO', 'NUEVO', 'TODO', 'CADA', 'MES', 'DIA', 'ANO', 'HORA'];
        return array_values(array_diff($matches[0] ?? [], $denylist));
    }

    private static function plantillaARegex(string $plantillaHumano): string
    {
        $parts = explode('%s', $plantillaHumano);
        $pattern = '';
        foreach ($parts as $i => $part) {
            $pattern .= preg_quote($part, '/');
            if ($i < count($parts) - 1) {
                $pattern .= '(?P<p' . $i . '>.+?)';
            }
        }
        return '/^' . $pattern . '$/ui';
    }

    private static function detectarPorPalabrasClave(string $prompt): ?array
    {
        $promptLower = mb_strtolower($prompt);
        $categorias = self::obtenerPalabrasClave();

        $mejorCat = null;
        $mejorScore = 0;

        foreach ($categorias as $cat => $info) {
            $score = 0;
            foreach ($info['palabras'] as $palabra) {
                if (mb_strpos($promptLower, $palabra) !== false) {
                    $score++;
                }
            }
            if ($score > $mejorScore) {
                $mejorScore = $score;
                $mejorCat = $cat;
            }
        }

        if ($mejorCat === null || $mejorScore === 0) {
            return [
                'categoria' => 'general',
                'nombre' => 'Ayuda general',
                'plantilla_humano' => 'Necesito ayuda con %s',
                'plantilla_ia' => 'ayuda con CONSULTA',
                'descripcion' => 'Consulta generica al sistema',
                'version' => 1,
                'confianza' => 0.1,
                'keywords_ia' => ['CONSULTA'],
            ];
        }

        return [
            'categoria' => $mejorCat,
            'nombre' => $categorias[$mejorCat]['nombre'],
            'plantilla_humano' => $categorias[$mejorCat]['plantilla_humano'],
            'plantilla_ia' => $categorias[$mejorCat]['plantilla_ia'],
            'descripcion' => $categorias[$mejorCat]['descripcion'],
            'version' => 1,
            'confianza' => max(0.1, $mejorScore * 0.15),
            'keywords_ia' => $categorias[$mejorCat]['keywords_ia'] ?? [],
        ];
    }

    private static function obtenerPalabrasClave(): array
    {
        return [
            'generar_modulo' => [
                'nombre' => 'Generar modulo',
                'palabras' => ['crear modulo', 'generar modulo', 'nuevo modulo', 'crud', 'hazme un modulo', 'necesito un modulo', 'creame un modulo', 'generame un modulo', 'haz un modulo', 'crea un modulo'],
                'plantilla_humano' => 'Necesito crear un modulo para %s',
                'plantilla_ia' => 'generar modulo CRUD para ENTIDAD',
                'descripcion' => 'Creacion de modulos CRUD',
                'keywords_ia' => ['ENTIDAD'],
            ],
            'generar_proyecto' => [
                'nombre' => 'Nuevo proyecto',
                'palabras' => ['proyecto nuevo', 'crear proyecto', 'nuevo proyecto', 'framework', 'generar proyecto', 'proyecto base', 'pagina web', 'landing page', 'hacer una pagina', 'sitio web', 'crear una pagina', 'hacer una landing', 'liteframework', 'empresa de'],
                'plantilla_humano' => 'Crear un proyecto nuevo llamado %s con BD %s',
                'plantilla_ia' => 'generar proyecto NOMBRE con base de datos BD',
                'descripcion' => 'Creacion de proyectos desde JSON',
                'keywords_ia' => ['NOMBRE', 'BD'],
            ],
            'ejecutar_migracion' => [
                'nombre' => 'Migrar BD',
                'palabras' => ['migracion', 'migraciones pendientes', 'aplicar migracion', 'aplica la migracion', 'correr migracion', 'corre las migraciones'],
                'plantilla_humano' => 'Ejecutar migraciones pendientes',
                'plantilla_ia' => 'ejecutar migraciones pendientes de base de datos',
                'descripcion' => 'Aplicar migraciones SQL',
                'keywords_ia' => [],
            ],
            'ejecutar_pruebas' => [
                'nombre' => 'Correr tests',
                'palabras' => ['prueba', 'test', 'phpunit', 'testing', 'pruebas unitarias', 'suite de tests', 'lanza los tests'],
                'plantilla_humano' => 'Ejecutar pruebas con filtro %s',
                'plantilla_ia' => 'ejecutar pruebas unitarias con filtro FILTRO',
                'descripcion' => 'Ejecutar PHPUnit',
                'keywords_ia' => ['FILTRO'],
            ],
            'diagnosticar' => [
                'nombre' => 'Diagnosticar sistema',
                'palabras' => ['diagnostico', 'diagnosticar', 'status', 'estado sistema', 'verifica que', 'componentes', 'operativo', 'revisar'],
                'plantilla_humano' => 'Diagnosticar el sistema',
                'plantilla_ia' => 'diagnosticar sistema completo',
                'descripcion' => 'Diagnostico completo',
                'keywords_ia' => [],
            ],
            'seguridad' => [
                'nombre' => 'Seguridad',
                'palabras' => ['csrf', 'token', 'permiso', 'seguridad', 'validar', 'valida', 'acceso', 'autorizar'],
                'plantilla_humano' => 'Validar token CSRF: %s',
                'plantilla_ia' => 'validar token csrf TOKEN',
                'descripcion' => 'Validacion de seguridad',
                'keywords_ia' => ['TOKEN'],
            ],
            'crud_leer' => [
                'nombre' => 'Consultar datos',
                'palabras' => ['listar', 'lista', 'consultar', 'mostrar', 'muestra', 'ver', 'recuperar', 'obtener', 'buscar datos', 'busca usuario', 'busca usuarios', 'listame', 'buscar en la'],
                'plantilla_humano' => 'Listar registros de %s',
                'plantilla_ia' => 'listar registros de TABLA',
                'descripcion' => 'Lectura de registros',
                'keywords_ia' => ['TABLA'],
            ],
            'crud_escribir' => [
                'nombre' => 'Escribir datos',
                'palabras' => ['crear registro', 'actualizar', 'eliminar', 'guardar', 'nuevo registro', 'registrar', 'insertar', 'inserta', 'agregar', 'agrega', 'dar de alta', 'registra', 'crea en'],
                'plantilla_humano' => 'Crear registro en %s',
                'plantilla_ia' => 'crear registro en TABLA con datos DATOS',
                'descripcion' => 'Escritura de registros',
                'keywords_ia' => ['TABLA', 'DATOS'],
            ],
            'editar_archivo' => [
                'nombre' => 'Editar archivo',
                'palabras' => ['editar', 'edita', 'modificar archivo', 'modifica', 'cambiar', 'cambia', 'reemplazar', 'reemplaza', 'actualizar', 'actualiza'],
                'plantilla_humano' => 'Editar el archivo %s',
                'plantilla_ia' => 'editar archivo RUTA reemplazar ORIGINAL por NUEVO',
                'descripcion' => 'Edicion de archivos',
                'keywords_ia' => ['RUTA', 'ORIGINAL', 'NUEVO'],
            ],
            'leer_archivo' => [
                'nombre' => 'Leer archivo',
                'palabras' => ['leer', 'lee', 'mostrar', 'contenido de', 'abrir', 'abrir archivo', 'muestrame', 'contenido del', 'muestra el archivo', 'archivo de', 'el archivo'],
                'plantilla_humano' => 'Leer el archivo %s',
                'plantilla_ia' => 'leer archivo RUTA',
                'descripcion' => 'Lectura de archivos',
                'keywords_ia' => ['RUTA'],
            ],
            'buscar_codigo' => [
                'nombre' => 'Buscar en codigo',
                'palabras' => ['buscar en archivos', 'encontrar', 'grep', 'search', 'referencia', 'donde se usa', 'donde aparece', 'ocurre', 'busca en los archivos', 'encuentra donde'],
                'plantilla_humano' => 'Buscar %s en el codigo',
                'plantilla_ia' => 'buscar PATRON en archivos del proyecto',
                'descripcion' => 'Busqueda en codigo fuente',
                'keywords_ia' => ['PATRON'],
            ],
            'optimizar' => [
                'nombre' => 'Optimizar consulta',
                'palabras' => ['optimizar', 'rendimiento', 'lento', 'slow', 'query', 'index', 'mejorar consulta'],
                'plantilla_humano' => 'Optimizar la consulta SQL: %s',
                'plantilla_ia' => 'optimizar consulta SQL CONSULTA',
                'descripcion' => 'Optimizacion de consultas a BD',
                'keywords_ia' => ['CONSULTA'],
            ],
            'cache' => [
                'nombre' => 'Manejo de cache',
                'palabras' => ['cache', 'caché', 'recordar', 'olvidar cache', 'guardar cache', 'limpiar cache', 'memoria'],
                'plantilla_humano' => 'Guardar %s en cache por %s segundos',
                'plantilla_ia' => 'guardar en cache CLAVE con datos DATOS por TTL segundos',
                'descripcion' => 'Manejo del sistema de cache',
                'keywords_ia' => ['CLAVE', 'DATOS', 'TTL'],
            ],
            'http' => [
                'nombre' => 'Peticion HTTP',
                'palabras' => ['http', 'get', 'post', 'api', 'peticion', 'solicitud', 'curl', 'llamar servicio', 'consumir', 'llamar', 'trae los datos', 'servicio', 'endpoint'],
                'plantilla_humano' => 'Hacer peticion GET a %s',
                'plantilla_ia' => 'ejecutar peticion GET a URL',
                'descripcion' => 'Peticiones HTTP via cURL',
                'keywords_ia' => ['URL'],
            ],
            'rendimiento' => [
                'nombre' => 'Benchmark y perfilado',
                'palabras' => ['rendimiento', 'benchmark', 'medir', 'tiempo', 'velocidad', 'comparar', 'perfil', 'perfilado', 'rendir', 'rapido', 'lento', 'que tan'],
                'plantilla_humano' => 'Medir el rendimiento de %s',
                'plantilla_ia' => 'medir rendimiento de OPERACION',
                'descripcion' => 'Benchmarking y perfilado de rendimiento',
                'keywords_ia' => ['OPERACION'],
            ],
            'usuario' => [
                'nombre' => 'Informacion de operador',
                'palabras' => ['quien soy', 'quien soy', 'mi nombre', 'usuario actual', 'rol', 'permiso', 'operador', 'informacion usuario'],
                'plantilla_humano' => 'Mostrar mi informacion de operador',
                'plantilla_ia' => 'obtener informacion del operador actual',
                'descripcion' => 'Informacion del operador en sesion',
                'keywords_ia' => [],
            ],
            'analizar_modulo' => [
                'nombre' => 'Analizar modulo',
                'palabras' => ['analizar', 'analiza', 'revisar', 'revisa', 'evaluar', 'inspeccionar', 'explorar codigo', 'analisis', 'estudiar', 'modulo', 'analizar modulo', 'analiza el modulo', 'manera objetiva', 'objetiva'],
                'plantilla_humano' => 'Analizar el modulo %s de manera objetiva',
                'plantilla_ia' => 'analizar codigo fuente del modulo MODULO',
                'descripcion' => 'Analisis de modulos y codigo fuente',
                'keywords_ia' => ['MODULO'],
            ],
            // externas — herramientas MCP externas al framework
            'git' => [
                'nombre' => 'Git',
                'palabras' => ['git', 'commit', 'diff', 'log', 'status', 'push', 'pull', 'branch', 'merge', 'repositorio', 'cambios', 'historial'],
                'plantilla_humano' => 'Ejecutar git %s',
                'plantilla_ia' => 'ejecutar git OPERACION',
                'descripcion' => 'Operaciones git sobre el repositorio',
                'keywords_ia' => ['OPERACION'],
            ],
            'firecrawl_scrape' => [
                'nombre' => 'Scrapear web',
                'palabras' => ['scrape', 'scrapear', 'extraer contenido', 'leer pagina web', 'contenido de la url', 'obtener pagina', 'pagina web'],
                'plantilla_humano' => 'Scrapear la URL %s',
                'plantilla_ia' => 'firecrawl scrape URL URL',
                'descripcion' => 'Extraer contenido de una pagina web',
                'keywords_ia' => ['URL'],
            ],
            'firecrawl_search' => [
                'nombre' => 'Buscar en web',
                'palabras' => ['buscar en internet', 'buscar en la web', 'investigar en internet', 'googlear', 'encontrar informacion sobre', 'busca informacion', 'buscar web'],
                'plantilla_humano' => 'Buscar en internet sobre %s',
                'plantilla_ia' => 'firecrawl search busqueda CONSULTA',
                'descripcion' => 'Busqueda web con Firecrawl',
                'keywords_ia' => ['CONSULTA'],
            ],
            'firecrawl_crawl' => [
                'nombre' => 'Crawlear sitio',
                'palabras' => ['crawlear', 'recorrer sitio', 'explorar sitio', 'rastrear', 'scrapear multiples paginas'],
                'plantilla_humano' => 'Crawlear el sitio %s',
                'plantilla_ia' => 'firecrawl crawl URL URL',
                'descripcion' => 'Rastrear multiples paginas de un sitio',
                'keywords_ia' => ['URL'],
            ],
            'firecrawl_map' => [
                'nombre' => 'Mapear sitio',
                'palabras' => ['mapear sitio', 'descubrir urls', 'listar paginas', 'encontrar urls', 'mapa del sitio'],
                'plantilla_humano' => 'Mapear las URLs de %s',
                'plantilla_ia' => 'firecrawl map URL URL',
                'descripcion' => 'Descubrir URLs de un sitio web',
                'keywords_ia' => ['URL'],
            ],
            'firecrawl_extract' => [
                'nombre' => 'Extraer datos estructurados',
                'palabras' => ['extraer datos', 'extraer informacion', 'scrapear datos', 'estructurar datos'],
                'plantilla_humano' => 'Extraer datos estructurados de %s',
                'plantilla_ia' => 'firecrawl extract URL URL',
                'descripcion' => 'Extraccion estructurada de datos web',
                'keywords_ia' => ['URL'],
            ],
            'browser_navegar' => [
                'nombre' => 'Navegar en navegador',
                'palabras' => ['navegar a', 'abrir pagina', 'ir a la url', 'abrir navegador', 'cargar sitio', 'navegar'],
                'plantilla_humano' => 'Navegar a la URL %s',
                'plantilla_ia' => 'browser navigate URL URL',
                'descripcion' => 'Abrir una URL en el navegador',
                'keywords_ia' => ['URL'],
            ],
            'browser_click' => [
                'nombre' => 'Click en elemento',
                'palabras' => ['hacer click', 'clic en', 'presionar', 'pulsar', 'clickear', 'seleccionar'],
                'plantilla_humano' => 'Hacer click en %s',
                'plantilla_ia' => 'browser click ELEMENTO',
                'descripcion' => 'Click en un elemento de la pagina',
                'keywords_ia' => ['ELEMENTO'],
            ],
            'browser_type' => [
                'nombre' => 'Escribir texto',
                'palabras' => ['escribir', 'teclear', 'llenar campo', 'ingresar texto', 'type', 'rellenar'],
                'plantilla_humano' => 'Escribir %s en el campo',
                'plantilla_ia' => 'browser type TEXTO',
                'descripcion' => 'Escribir texto en un campo del formulario',
                'keywords_ia' => ['TEXTO'],
            ],
            'browser_screenshot' => [
                'nombre' => 'Captura de pantalla',
                'palabras' => ['captura', 'screenshot', 'pantallazo', 'foto de la pagina', 'capturar pantalla'],
                'plantilla_humano' => 'Tomar captura de pantalla de %s',
                'plantilla_ia' => 'browser screenshot URL',
                'descripcion' => 'Capturar pantalla del navegador',
                'keywords_ia' => ['URL'],
            ],
            'imagen' => [
                'nombre' => 'Generar imagen',
                'palabras' => ['generar imagen', 'dibujar', 'crear ilustracion', 'hacer un dibujo', 'renderizar imagen', 'crear imagen'],
                'plantilla_humano' => 'Generar una imagen de %s',
                'plantilla_ia' => 'generar imagen de DESCRIPCION',
                'descripcion' => 'Generacion de imagenes por IA',
                'keywords_ia' => ['DESCRIPCION'],
            ],
            'sentry' => [
                'nombre' => 'Sentry',
                'palabras' => ['sentry', 'error en produccion', 'crash', 'excepcion', 'issue', 'stacktrace'],
                'plantilla_humano' => 'Consultar error Sentry %s',
                'plantilla_ia' => 'sentry consultar ISSUE',
                'descripcion' => 'Consultar errores en Sentry',
                'keywords_ia' => ['ISSUE'],
            ],
            'github_grep' => [
                'nombre' => 'Buscar en GitHub',
                'palabras' => ['buscar en github', 'ejemplos de codigo', 'codigo en github', 'github search', 'repositorios'],
                'plantilla_humano' => 'Buscar codigo en GitHub: %s',
                'plantilla_ia' => 'github grep buscar PATRON',
                'descripcion' => 'Busqueda de codigo en GitHub',
                'keywords_ia' => ['PATRON'],
            ],
            'investigar_paper' => [
                'nombre' => 'Investigar papers',
                'palabras' => ['investigar paper', 'buscar paper', 'paper academico', 'arxiv', 'articulo cientifico', 'investigacion'],
                'plantilla_humano' => 'Investigar papers sobre %s',
                'plantilla_ia' => 'deepwiki investigar TEMA',
                'descripcion' => 'Busqueda de papers academicos',
                'keywords_ia' => ['TEMA'],
            ],
            'fetch_pagina' => [
                'nombre' => 'Fetch web',
                'palabras' => ['fetch', 'obtener pagina', 'descargar contenido web', 'leer url', 'http get'],
                'plantilla_humano' => 'Obtener el contenido de %s',
                'plantilla_ia' => 'fetch URL URL',
                'descripcion' => 'Obtener contenido de una URL',
                'keywords_ia' => ['URL'],
            ],
            'tiempo' => [
                'nombre' => 'Fecha y hora',
                'palabras' => ['que hora es', 'fecha actual', 'hora actual', 'que dia es', 'momento actual'],
                'plantilla_humano' => 'Que hora es',
                'plantilla_ia' => 'obtener fecha y hora actual',
                'descripcion' => 'Consultar fecha y hora',
                'keywords_ia' => [],
            ],
            'context7' => [
                'nombre' => 'Documentacion de librerias',
                'palabras' => ['documentacion', 'libreria', 'api de', 'como se usa', 'como usar', 'libreria de', 'buscar documentacion', 'consultar doc'],
                'plantilla_humano' => 'Buscar documentacion de %s',
                'plantilla_ia' => 'context7 documentacion LIBRERIA',
                'descripcion' => 'Consultar documentacion tecnica',
                'keywords_ia' => ['LIBRERIA'],
            ],
        ];
    }

    private static function generarHash(string $texto): string
    {
        return 't_' . substr(md5($texto), 0, 12);
    }

    private static function guardarHistorial(
        string $original,
        string $traducido,
        string $categoria,
        float $confianza,
        bool $cacheHit,
        int $tiempoMs
    ): ?int {
        try {
            $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $sql = $bd->prepare(
                "INSERT INTO traduccion_historial
                (prompt_original, prompt_traducido, categoria_detectada, confianza, cache_hit, tiempo_procesamiento_ms)
                VALUES (:orig, :trad, :cat, :conf, :cache, :ms)"
            );
            $sql->execute([
                ':orig' => $original,
                ':trad' => $traducido,
                ':cat' => $categoria,
                ':conf' => $confianza,
                ':cache' => $cacheHit ? 1 : 0,
                ':ms' => $tiempoMs,
            ]);
            return (int)$bd->lastInsertId();
        } catch (Exception $e) {
            error_log('[Traductor] Error guardando historial: ' . $e->getMessage());
            return null;
        }
    }

    private static function actualizarScore(string $categoria, bool $acierto): void
    {
        try {
            $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();

            $bd->prepare(
                "INSERT INTO traduccion_score (categoria, aciertos, fallos, total_uso, confianza, ultima_calibracion)
                VALUES (:cat, :aci, :fal, 1, :conf, NOW())
                ON DUPLICATE KEY UPDATE
                    aciertos = aciertos + :aci2,
                    fallos = fallos + :fal2,
                    total_uso = total_uso + 1,
                    confianza = (aciertos + :aci3) / (total_uso + 1),
                    ultima_calibracion = NOW()"
            )->execute([
                ':cat' => $categoria,
                ':aci' => $acierto ? 1 : 0,
                ':fal' => $acierto ? 0 : 1,
                ':conf' => $acierto ? 1.0 : 0.0,
                ':aci2' => $acierto ? 1 : 0,
                ':fal2' => $acierto ? 0 : 1,
                ':aci3' => $acierto ? 1 : 0,
            ]);

            $bd->prepare(
                "UPDATE plantilla_prompt SET uso_total = uso_total + 1,
                    uso_exitoso = uso_exitoso + :exito
                WHERE categoria = :cat"
            )->execute([
                ':exito' => $acierto ? 1 : 0,
                ':cat' => $categoria,
            ]);
        } catch (Exception $e) {
            error_log('[Traductor] Error actualizando score: ' . $e->getMessage());
        }
    }

    private static function generarParametrosPrueba(array $plantilla): array
    {
        $keywords = $plantilla['keywords_ia'];
        $parametros = [];

        $valoresPrueba = [
            'ENTIDAD' => 'Producto',
            'CAMPOS' => 'nombre:string, precio:decimal',
            'TABLA' => 'usuarios',
            'CAMPO' => 'email',
            'VALOR' => 'test@example.com',
            'DATOS' => 'nombre: Juan, email: juan@test.com',
            'FILTRO' => 'UsuarioTest',
            'TOKEN' => 'abc123token',
            'CLAVE' => 'acceso_total',
            'RUTA' => 'servidor/config/app.php',
            'ORIGINAL' => 'debug=true',
            'NUEVO' => 'debug=false',
            'PATRON' => 'function getUser',
            'CONSULTA' => 'SELECT * FROM usuarios',
            'MODULO' => 'Reporte',
            'NOMBRE' => 'MiProyecto',
            'BD' => 'mi_base',
            'DESTINO' => 'sistema',
            'URL' => 'https://api.ejemplo.com/usuarios',
            'OPERACION' => 'consulta de usuarios',
            'TTL' => '300',
        ];

        foreach ($keywords as $kw) {
            $parametros[] = $valoresPrueba[$kw] ?? $kw;
        }

        return $parametros;
    }

    private static function generarPromptPrueba(array $plantilla, array $parametros): string
    {
        $paramIndex = 0;
        $resultado = '';
        $partes = explode('%s', $plantilla['plantilla_humano']);

        foreach ($partes as $i => $parte) {
            $resultado .= $parte;
            if ($i < count($partes) - 1) {
                $resultado .= $parametros[$paramIndex] ?? '?';
                $paramIndex++;
            }
        }

        return $resultado;
    }

    private static function generarPromptGenerico(string $categoria, string $nombre, array $params): string
    {
        $texto = "Ejecutar {$categoria}: {$nombre}";
        if (!empty($params)) {
            $texto .= ' (' . implode(', ', $params) . ')';
        }
        return $texto;
    }
}

class Traductor extends AyudanteTraductor
{
}
