<?php

/**
 * Crea un nuevo proyecto desde el framework.
 *
 * Uso:
 *   php servidor/consola/crear_proyecto.php --desde-json=project.json
 *   php servidor/consola/crear_proyecto.php --nombre="MiApp" ...
 */

declare(strict_types=1);

use LiteFramework\Config\GestorEntorno;
use LiteFramework\Servicios\GeneradorProyecto;

require_once __DIR__ . '/../autoload.php';
GestorEntorno::cargar();
if (!defined('DIRECTORIO_RAIZ')) {
    define('DIRECTORIO_RAIZ', realpath(__DIR__ . '/../..'));
}

function imprimirError(string $msg): void
{

    fwrite(STDERR, "[ERROR] $msg\n");
}

function imprimirExito(string $msg): void
{

    echo "[OK] $msg\n";
}

// Parsear argumentos
$rawArgs = getopt('', [
    'desde-json:',
    'nombre:', 'codigo:', 'descripcion:', 'version:',
    'directorio:', 'db-anfitrion:', 'db-nombre:', 'db-usuario:', 'db-clave:',
    'paleta:', 'estilo:', 'locale:',
    'modulos:', 'entidades:',
    'admin-nombre:', 'admin-correo:', 'admin-clave:',
    'empresa:',
]);
$args = is_array($rawArgs) ? $rawArgs : [];
if (isset($args['desde-json'])) {
    /** @phpstan-ignore-next-line */
    $rutaJson = (string)$args['desde-json'];
    if (!file_exists($rutaJson)) {
        imprimirError("Archivo no encontrado: $rutaJson");
        exit(1);
    }
    $contenido = file_get_contents($rutaJson) ?: '';
    $definicion = json_decode($contenido, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        imprimirError('JSON invalido: ' . json_last_error_msg());
        exit(1);
    }
} else {
    /** @phpstan-ignore-next-line */
    $modulosParam = isset($args['modulos']) ? (string)$args['modulos'] : '';
    $modulos = !empty($modulosParam) ? explode(',', $modulosParam) : ['inicio', 'panelControl', 'operadores', 'auditoria', 'configuracion', 'apariencia', 'migraciones'];
    $entidades = [];
    /** @phpstan-ignore-next-line */
    $entidadesParam = isset($args['entidades']) ? (string)$args['entidades'] : '';
    if (!empty($entidadesParam)) {
        foreach (explode(';', $entidadesParam) as $entStr) {
            $entStr = trim($entStr);
            if (empty($entStr)) {
                continue;
            }
            $partes = explode(':', $entStr, 2);
            $clase = $partes[0];
            $camposRaw = isset($partes[1]) ? explode(',', $partes[1]) : [];
            $campos = [];
            foreach ($camposRaw as $c) {
                $c = trim($c);
                if (empty($c)) {
                    continue;
                }
                $cp = explode(':', $c);
                $campos[] = [
                    'nombre' => $cp[0],
                    'tipo' => $cp[1] ?? 'string',
                    'reglas' => $cp[2] ?? '',
                ];
            }
            $entidades[] = ['clase' => $clase, 'tabla' => null, 'campos' => $campos];
        }
    }

    $definicion = [
        'proyecto' => [
            'nombre' => $args['nombre'] ?? 'Mi Aplicacion',
            'codigo' => $args['codigo'] ?? 'miapp',
            'descripcion' => $args['descripcion'] ?? 'Panel de control',
            'version' => $args['version'] ?? '1.0.0',
        ],
        'empresa' => [
            'nombre' => $args['empresa'] ?? ($args['nombre'] ?? 'Mi Empresa'),
        ],
        /** @phpstan-ignore-next-line */
        'directorio_salida' => rtrim($args['directorio'] ?? getcwd() . '/' . ($args['codigo'] ?? 'miapp'), '/\\'),
        'base_datos' => [
            'anfitrion' => $args['db-anfitrion'] ?? 'localhost',
            /** @phpstan-ignore-next-line */
            'nombre' => $args['db-nombre'] ?? ($args['codigo'] ?? 'miapp') . '_db',
            'usuario' => $args['db-usuario'] ?? 'root',
            'clave' => $args['db-clave'] ?? '',
        ],
        'apariencia' => [
            'paleta' => $args['paleta'] ?? 'indigo',
            'estilo' => $args['estilo'] ?? 'moderno',
            'locale' => $args['locale'] ?? 'es-CR',
        ],
        'modulos_activados' => $modulos,
        'entidades' => $entidades,
        'operador_inicial' => [
            'nombre' => $args['admin-nombre'] ?? 'Administrador',
            /** @phpstan-ignore-next-line */
            'correo' => $args['admin-correo'] ?? ('admin@' . ($args['codigo'] ?? 'app') . '.com'),
            'clave' => $args['admin-clave'] ?? 'Admin123!',
        ],
    ];
}

echo "=== Generando proyecto: {$definicion['proyecto']['nombre']} ===\n\n";
$resultado = GeneradorProyecto::generar($definicion);
if (!$resultado['exito']) {
    imprimirError($resultado['error'] ?? 'Error desconocido');
    if (!empty($resultado['errores'])) {
        foreach ($resultado['errores'] as $e) {
            imprimirError($e['tipo'] . ': ' . $e['mensaje']);
        }
    }
    exit(1);
}

echo "\nProyecto generado exitosamente:\n";
echo "  Directorio: {$resultado['directorio']}\n";
echo "  Archivos procesados: {$resultado['resumen']['archivos_procesados']}\n";
if (!empty($resultado['resumen']['entidades_generadas'])) {
    echo "  Entidades:\n";
    foreach ($resultado['resumen']['entidades_generadas'] as $e) {
        echo "    - $e\n";
    }
}

echo "  Modulos activos: " . implode(', ', $resultado['resumen']['modulos_activados']) . "\n";
echo "\nPasos siguientes:\n";
foreach ($resultado['resumen']['pasos_siguientes'] as $i => $paso) {
    echo "  " . ($i + 1) . ". $paso\n";
}

echo "\n¡Proyecto creado exitosamente!\n";
