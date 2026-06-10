<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use LiteFramework\Modelos\Archivo;
use Exception;

class GeneradorProyecto
{
    private static array $MODULOS_DISPONIBLES = [
        'inicio' => ['ruta' => '/inicio', 'etiqueta' => 'Inicio', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
        'panelControl' => ['ruta' => '/panelControl', 'etiqueta' => 'Panel', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>'],
        'archivos' => ['ruta' => '/archivos', 'etiqueta' => 'Archivos', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>'],
        'operadores' => ['ruta' => '/operadores', 'etiqueta' => 'Operadores', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>'],
        'auditoria' => ['ruta' => '/auditoria', 'etiqueta' => 'Auditoria', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>'],
        'configuracion' => ['ruta' => '/configuracion', 'etiqueta' => 'Configuracion', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06-.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>'],
        'apariencia' => ['ruta' => '/apariencia', 'etiqueta' => 'Apariencia', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>'],
        'documentacion' => ['ruta' => '/documentacion', 'etiqueta' => 'Documentacion', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><polyline points="8 7 12 7"/><polyline points="8 11 16 11"/><polyline points="8 15 14 15"/></svg>'],
        'generadorModulo' => ['ruta' => '/generador-modulo', 'etiqueta' => 'Generador', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>'],
        'migraciones' => ['ruta' => '/migraciones', 'etiqueta' => 'Migraciones', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>'],
    ];

    private static array $EXCLUIR_COPIA = [
        '.agents', '.opencode', '.git', '.gitignore',
        'storage/backups', 'storage/logs',
        'plantillas/proyecto', 'node_modules',
        'opencode.json', 'skills-lock.json', 'test_edit.txt',
    ];

    private static array $EXTENSIONES_MD = ['md', 'MD', 'markdown'];

    public static function desdeJson(string $ruta): array
    {
        if (!file_exists($ruta)) {
            return ['exito' => false, 'error' => "Archivo no encontrado: $ruta"];
        }
        $contenido = file_get_contents($ruta) ?: '';
        $def = json_decode($contenido, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['exito' => false, 'error' => 'JSON invalido: ' . json_last_error_msg()];
        }
        return self::generar($def);
    }

    public static function generar(array $def): array
    {
        $errores = self::validarDefinicion($def);
        if (!empty($errores)) {
            return ['exito' => false, 'error' => implode('; ', $errores)];
        }

        $origen = defined('DIRECTORIO_RAIZ') ? DIRECTORIO_RAIZ : (__DIR__ . '/../..');
        $destino = rtrim($def['directorio_salida'], '/\\');

        if (is_dir($destino) && count(scandir($destino)) > 2) {
            return ['exito' => false, 'error' => "El directorio '$destino' ya existe y no esta vacio"];
        }

        $pasos = [];
        $pasos[] = self::copiarArbol($origen, $destino);

        $placeholders = self::buildPlaceholders($def);
        $templateDir = $origen . '/plantillas/proyecto';

        $pasos[] = self::renderizarTemplate("$templateDir/encabezado.php.template", "$destino/src/plantillas/encabezado.php", $placeholders);
        $pasos[] = self::renderizarTemplate("$templateDir/pie.php.template", "$destino/src/plantillas/pie.php", $placeholders);
        $pasos[] = self::renderizarTemplate("$templateDir/inicio_sesion.php.template", "$destino/src/vistas/inicio_sesion.php", $placeholders);
        $pasos[] = self::renderizarTemplate("$templateDir/error.php.template", "$destino/src/error.php", $placeholders);
        $pasos[] = self::renderizarTemplate("$templateDir/index.php.template", "$destino/index.php", $placeholders);
        $pasos[] = self::renderizarTemplate("$templateDir/.env.template", "$destino/.env", $placeholders);
        $pasos[] = self::renderizarTemplate("$templateDir/.htaccess.template", "$destino/.htaccess", $placeholders);
        $pasos[] = self::renderizarTemplate("$templateDir/lite.js.template", "$destino/src/js/ui/lite.js", $placeholders);
        $pasos[] = self::renderizarTemplate("$templateDir/navegacion.js.template", "$destino/src/js/ui/navegacion.js", $placeholders);
        $pasos[] = self::renderizarTemplate("$templateDir/inicio.php.template", "$destino/src/modulos/inicio/inicio.php", $placeholders);

        $pasos[] = self::configurarApariencia($destino, $def);
        $pasos[] = self::generarEntidades($destino, $def);
        $pasos[] = self::generarMigracionSemilla($destino, $def);

        $erroresGeneracion = array_filter($pasos, fn($p) => !$p['exito']);
        $exito = empty($erroresGeneracion);

        $entidadesInfo = [];
        if (!empty($def['entidades'])) {
            $entidadesInfo = array_map(fn($e) => $e['clase'] . ' (' . count($e['campos']) . ' campos)', $def['entidades']);
        }

        return [
            'exito' => $exito,
            'directorio' => $destino,
            'proyecto' => $def['proyecto']['nombre'] ?? '',
            'pasos' => $pasos,
            'errores' => $erroresGeneracion,
            'resumen' => [
                'archivos_procesados' => count($pasos),
                'entidades_generadas' => $entidadesInfo,
                'modulos_activados' => $def['modulos_activados'] ?? [],
                'pasos_siguientes' => [
                    'Accede al directorio: ' . $destino,
                    'Configura tu servidor web para apuntar a este directorio',
                    'Abre la URL del proyecto en el navegador',
                    'Ejecuta las migraciones desde el modulo Migraciones',
                    'Inicia sesion con el operador creado',
                ],
            ],
        ];
    }

    // ─── Validación ──────────────────────────────────────────────────────────

    private static function validarDefinicion(array $def): array
    {
        $errores = [];
        if (empty($def['proyecto']['nombre'])) {
            $errores[] = 'Falta: proyecto.nombre';
        }
        if (empty($def['proyecto']['codigo'])) {
            $errores[] = 'Falta: proyecto.codigo';
        }
        if (empty($def['directorio_salida'])) {
            $errores[] = 'Falta: directorio_salida';
        }
        if (empty($def['base_datos']['nombre'])) {
            $errores[] = 'Falta: base_datos.nombre';
        }
        if (empty($def['modulos_activados']) || !is_array($def['modulos_activados'])) {
            $errores[] = 'Falta: modulos_activados (selecciona al menos uno)';
        }
        if (!empty($def['entidades'])) {
            foreach ($def['entidades'] as $i => $e) {
                if (empty($e['clase'])) {
                    $errores[] = "Entidad #$i: falta clase";
                }
                if (empty($e['campos'])) {
                    $errores[] = "Entidad {$e['clase']}: falta campos";
                }
            }
        }
        return $errores;
    }

    // ─── Placeholders ────────────────────────────────────────────────────────

    private static function buildPlaceholders(array $def): array
    {
        $proy = $def['proyecto'];
        $emp = $def['empresa'] ?? [];
        $apariencia = $def['apariencia'] ?? [];
        $locale = $apariencia['locale'] ?? 'es-CR';
        $codigo = $proy['codigo'];
        $nombre = $proy['nombre'];
        $descripcion = $proy['descripcion'] ?? 'Panel de control';
        $version = $proy['version'] ?? '1.0.0';

        $enlacesNav = self::buildNavLinks($def['modulos_activados'] ?? [], $def['entidades'] ?? []);

        $descripcionLarga = "Sistema construido con $nombre. MVC, ORM, RBAC, CSRF, migraciones y personalizacion de UI. Sin dependencias externas.";
        $descripcionCorta = "$nombre - Sistema de gestion empresarial.";

        return [
            '{{APP_NOMBRE}}' => $nombre,
            '{{APP_CODIGO}}' => $codigo,
            '{{APP_DESCRIPCION}}' => $descripcion,
            '{{APP_VERSION}}' => $version,
            '{{APP_TAGLINE}}' => "$nombre &bull; Sistema de gestion &bull; MVC &bull; ORM &bull; RBAC &bull; CSRF &bull; Cero dependencias",
            '{{APP_DESCRIPCION_LARGA}}' => $descripcionLarga,
            '{{APP_DESCRIPCION_CORTA}}' => $descripcionCorta,
            '{{EMPRESA_NOMBRE}}' => $emp['nombre'] ?? $nombre,
            '{{EMPRESA_EMAIL_SEG}}' => $emp['correo_seguridad'] ?? '',
            '{{LOCALE}}' => $locale,
            '{{OG_LOCALE}}' => str_replace('-', '_', $locale),
            '{{STORAGE_PREFIX}}' => $codigo,
            '{{DB_ANFITRION}}' => $def['base_datos']['anfitrion'] ?? 'localhost',
            '{{DB_NOMBRE}}' => $def['base_datos']['nombre'],
            '{{DB_USUARIO}}' => $def['base_datos']['usuario'] ?? 'root',
            '{{DB_CLAVE}}' => $def['base_datos']['clave'] ?? '',
            '{{ENLACES_NAV}}' => $enlacesNav,
        ];
    }

    // ─── Navegacion ──────────────────────────────────────────────────────────

    private static function buildNavLinks(array $modulosActivados, array $entidades): string
    {
        $links = [];
        foreach ($modulosActivados as $mod) {
            if (isset(self::$MODULOS_DISPONIBLES[$mod])) {
                $m = self::$MODULOS_DISPONIBLES[$mod];
                $links[] = "    '$mod' => ['ruta' => '{$m['ruta']}', 'etiqueta' => '{$m['etiqueta']}', 'icono' => '{$m['icono']}']";
            }
        }
        foreach ($entidades as $e) {
            $clase = $e['clase'];
            $modulo = lcfirst($clase);
            $etiqueta = $clase;
            $links[] = "    '$modulo' => ['ruta' => '/$modulo', 'etiqueta' => '$etiqueta', 'icono' => '<svg width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\"><path d=\"M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2\"/><rect x=\"8\" y=\"2\" width=\"8\" height=\"4\" rx=\"1\" ry=\"1\"/></svg>']";
        }
        return implode(",\n", $links);
    }

    // ─── Copia de arbol ──────────────────────────────────────────────────────

    private static function copiarArbol(string $origen, string $destino): array
    {
        try {
            if (!is_dir($destino)) {
                mkdir($destino, 0755, true);
            }
            $items = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($origen, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            $contador = 0;
            foreach ($items as $item) {
                $rutaRel = substr($item->getPathname(), strlen($origen) + 1);
                $rutaRel = str_replace('\\', '/', $rutaRel);
                if (self::debeExcluir($rutaRel)) {
                    continue;
                }
                $dest = $destino . '/' . $rutaRel;
                if ($item->isDir()) {
                    if (!is_dir($dest)) {
                        mkdir($dest, 0755, true);
                    }
                } else {
                    copy($item->getPathname(), $dest);
                    $contador++;
                }
            }
            return ['exito' => true, 'tipo' => 'Copiar arbol', 'ruta' => $destino, 'mensaje' => "{$contador} archivos copiados"];
        } catch (Exception $e) {
            return ['exito' => false, 'tipo' => 'Copiar arbol', 'ruta' => $destino, 'mensaje' => $e->getMessage()];
        }
    }

    private static function debeExcluir(string $ruta): bool
    {
        foreach (self::$EXCLUIR_COPIA as $patron) {
            if (str_starts_with($ruta, $patron)) {
                return true;
            }
        }
        $ext = pathinfo($ruta, PATHINFO_EXTENSION);
        if (in_array($ext, self::$EXTENSIONES_MD)) {
            return true;
        }
        return false;
    }

    // ─── Templates ───────────────────────────────────────────────────────────

    private static function renderizarTemplate(string $templateFile, string $destinoFile, array $placeholders): array
    {
        if (!file_exists($templateFile)) {
            return ['exito' => false, 'tipo' => 'Template', 'ruta' => basename($destinoFile), 'mensaje' => 'Template no encontrado: ' . basename($templateFile)];
        }
        try {
            $contenido = file_get_contents($templateFile) ?: '';
            $contenido = str_replace(array_keys($placeholders), array_values($placeholders), $contenido);
            file_put_contents($destinoFile, $contenido);
            return ['exito' => true, 'tipo' => 'Template', 'ruta' => basename($destinoFile), 'mensaje' => 'Personalizado'];
        } catch (Exception $e) {
            return ['exito' => false, 'tipo' => 'Template', 'ruta' => basename($destinoFile), 'mensaje' => $e->getMessage()];
        }
    }

    // ─── Apariencia ──────────────────────────────────────────────────────────

    private static function configurarApariencia(string $destino, array $def): array
    {
        $uiFile = $destino . '/servidor/config/ui.php';
        if (!file_exists($uiFile)) {
            return ['exito' => false, 'tipo' => 'Apariencia', 'ruta' => 'servidor/config/ui.php', 'mensaje' => 'Archivo ui.php no encontrado'];
        }
        $apariencia = $def['apariencia'] ?? [];
        try {
            $config = [
                'paleta' => $apariencia['paleta'] ?? 'indigo',
                'estilo' => $apariencia['estilo'] ?? 'moderno',
                'fondo' => $apariencia['fondo'] ?? 'blanco',
                'textura' => 'ninguna',
                'radio' => 'normal',
                'animacion' => 'normal',
                'fuente' => 'sistema',
                'espaciado' => 'normal',
                'tamano' => 'normal',
                'grosor' => 'normal',
                'sombra' => 'normal',
            ];
            $contenido = "<?php\n\nreturn " . var_export($config, true) . ";\n";
            file_put_contents($uiFile, $contenido);
            return ['exito' => true, 'tipo' => 'Apariencia', 'ruta' => 'servidor/config/ui.php', 'mensaje' => "Paleta: {$config['paleta']}, Estilo: {$config['estilo']}"];
        } catch (Exception $e) {
            return ['exito' => false, 'tipo' => 'Apariencia', 'ruta' => 'servidor/config/ui.php', 'mensaje' => $e->getMessage()];
        }
    }

    // ─── Entidades ───────────────────────────────────────────────────────────

    private static function generarEntidades(string $destino, array $def): array
    {
        if (empty($def['entidades'])) {
            return ['exito' => true, 'tipo' => 'Entidades', 'ruta' => '', 'mensaje' => 'Ninguna entidad definida'];
        }
        $total = 0;
        $errores = [];
        foreach ($def['entidades'] as $e) {
            try {
                $res = GeneradorModulo::generarEn($destino, $e['clase'], $e['campos'], $e['tabla'] ?? null);
                $total += count($res['archivos']);
                if (!$res['exito']) {
                    $errores[] = $e['clase'] . ': ' . implode(', ', $res['errores']);
                }
            } catch (Exception $ex) {
                $errores[] = $e['clase'] . ': ' . $ex->getMessage();
            }
        }
        $msg = $total . ' archivos generados para ' . count($def['entidades']) . ' entidades';
        if (!empty($errores)) {
            $msg .= '; Errores: ' . implode(' | ', $errores);
        }
        return [
            'exito' => empty($errores),
            'tipo' => 'Entidades',
            'ruta' => count($def['entidades']) . ' entidades',
            'mensaje' => $msg,
        ];
    }

    // ─── Migracion semilla ──────────────────────────────────────────────────

    private static function generarMigracionSemilla(string $destino, array $def): array
    {
        $admin = $def['operador_inicial'] ?? [];
        $nombreAdmin = $admin['nombre'] ?? 'Administrador';
        $correoAdmin = $admin['correo'] ?? 'admin@' . ($def['proyecto']['codigo'] ?? 'app') . '.com';
        $claveAdmin = $admin['clave'] ?? 'Admin123!';
        $hashClave = password_hash($claveAdmin, PASSWORD_DEFAULT);
        $codigo = $def['proyecto']['codigo'] ?? 'app';

        $dirMigraciones = $destino . '/servidor/migraciones';
        if (!is_dir($dirMigraciones)) {
            mkdir($dirMigraciones, 0755, true);
        }

        $num = '000';
        $existing = glob($dirMigraciones . '/[0-9][0-9][0-9]_*.sql');
        if (!empty($existing)) {
            rsort($existing);
            preg_match('/^(\d+)/', basename($existing[0]), $m);
            $num = str_pad((string)((isset($m[1]) ? (int)$m[1] + 1 : 1)), 3, '0', STR_PAD_LEFT);
        }

        $file = "$dirMigraciones/{$num}_semilla_{$codigo}.sql";
        $contenido = <<<SQL
-- Migracion {$num}: Datos semilla para {$codigo}
-- Generado automaticamente por GeneradorProyecto

INSERT OR IGNORE INTO rbac_rol (nombre_rol, descripcion_rol, estado_rol) VALUES
    ('Administrador', 'Acceso completo al sistema', 'activo');

INSERT OR IGNORE INTO operador (nombre_completo, correo_electronico, clave_acceso, id_rol, estado_cuenta)
VALUES ('{$nombreAdmin}', '{$correoAdmin}', '{$hashClave}', 1, 'activo');

SQL;
        file_put_contents($file, $contenido);

        return [
            'exito' => true,
            'tipo' => 'Migracion semilla',
            'ruta' => 'servidor/migraciones/' . basename($file),
            'mensaje' => "Admin: {$correoAdmin} / {$claveAdmin}",
        ];
    }
}
