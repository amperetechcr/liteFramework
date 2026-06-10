<?php

declare(strict_types=1);

use LiteFramework\Config\GestorEntorno;
use LiteFramework\Servicios\GeneradorProyecto;
use LiteFramework\Cli\Consola;

function imprimirError(string $msg): void
{
    $consola = Consola::instance();
    if ($consola && $consola->estaEnModoJson()) {
        $consola->jsonError($msg, 'ERR_PROYECTO', 1);
    }
    fwrite(STDERR, "[ERROR] $msg\n");
}

function imprimirExito(string $msg): void
{
    $consola = Consola::instance();
    if ($consola && $consola->estaEnModoJson()) {
        return;
    }
    echo "[OK] $msg\n";
}

require_once __DIR__ . '/../autoload.php';
GestorEntorno::cargar();
if (!defined('DIRECTORIO_RAIZ')) {
    define('DIRECTORIO_RAIZ', realpath(__DIR__ . '/../..'));
}

$consola = Consola::instance();
$modoJson = $consola && $consola->estaEnModoJson();

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
if (isset($args['desde-json']) && is_string($args['desde-json'])) {
    $rutaJson = $args['desde-json'];
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
    $modulosParam = isset($args['modulos']) && is_string($args['modulos']) ? $args['modulos'] : '';
    $modulos = !empty($modulosParam) ? explode(',', $modulosParam) : ['inicio', 'panelControl', 'operadores', 'auditoria', 'configuracion', 'apariencia', 'migraciones'];
    $entidades = [];
    $entidadesParam = isset($args['entidades']) && is_string($args['entidades']) ? $args['entidades'] : '';
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

    $codigoProyecto = isset($args['codigo']) && is_string($args['codigo']) ? $args['codigo'] : 'miapp';
    $definicion = [
        'proyecto' => [
            'nombre' => $args['nombre'] ?? 'Mi Aplicacion',
            'codigo' => $codigoProyecto,
            'descripcion' => $args['descripcion'] ?? 'Panel de control',
            'version' => $args['version'] ?? '1.0.0',
        ],
        'empresa' => [
            'nombre' => $args['empresa'] ?? ($args['nombre'] ?? 'Mi Empresa'),
        ],
        'directorio_salida' => rtrim(
            isset($args['directorio']) && is_string($args['directorio']) ? $args['directorio'] : getcwd() . '/' . $codigoProyecto,
            '/\\'
        ),
        'base_datos' => [
            'anfitrion' => $args['db-anfitrion'] ?? 'localhost',
            'nombre' => $args['db-nombre'] ?? $codigoProyecto . '_db',
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
            'correo' => $args['admin-correo'] ?? ('admin@' . $codigoProyecto . '.com'),
            'clave' => $args['admin-clave'] ?? 'Admin123!',
        ],
    ];
}

if (!$modoJson) {
    echo "=== Generando proyecto: {$definicion['proyecto']['nombre']} ===\n\n";
}

$resultado = GeneradorProyecto::generar($definicion);

if ($modoJson) {
    if (!$resultado['exito']) {
        $consola->jsonError($resultado['error'] ?? 'Error desconocido', 'ERR_PROYECTO', 1);
    }
    $consola->jsonOut([
        'proyecto' => $definicion['proyecto']['nombre'],
        'directorio' => $resultado['directorio'] ?? $definicion['directorio_salida'],
        'archivos_procesados' => $resultado['resumen']['archivos_procesados'] ?? 0,
        'entidades_generadas' => $resultado['resumen']['entidades_generadas'] ?? [],
        'modulos_activados' => $resultado['resumen']['modulos_activados'] ?? [],
        'pasos_siguientes' => $resultado['resumen']['pasos_siguientes'] ?? [],
    ], 'proyecto:crear');
    exit(0);
}

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
