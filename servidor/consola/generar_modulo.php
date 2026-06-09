<?php

declare(strict_types=1);

use LiteFramework\Servicios\GeneradorModulo;
use LiteFramework\Cli\Consola;

require_once __DIR__ . '/../autoload.php';

$consola = Consola::instance();
$modoJson = $consola && $consola->estaEnModoJson();

if ($argc < 2) {
    if ($modoJson) {
        $consola?->jsonError('Se requiere <NombreClase>', 'ERR_ARG', 400);
    }
    echo "Uso: php generar_modulo.php <NombreClase> [--campos=\"...\"] [--tabla=...]\n";
    echo "Ejemplo:\n";
    echo "  php generar_modulo.php Producto --campos=\"nombre:string:required,precio:decimal:required\" --tabla=producto\n";
    exit(1);
}

$claseNombre = $argv[1];
$tabla = null;
$camposRaw = [];

for ($i = 2; $i < $argc; $i++) {
    if (str_starts_with($argv[$i], '--campos=')) {
        $camposRaw = explode(',', substr($argv[$i], 9));
    } elseif (str_starts_with($argv[$i], '--tabla=')) {
        $tabla = substr($argv[$i], 8);
    }
}

$campos = GeneradorModulo::parsearCamposDesdeArgs($camposRaw);
$resultado = GeneradorModulo::generar($claseNombre, $campos, $tabla);

if ($modoJson) {
    if (!$resultado['exito']) {
        $consola?->jsonResultado($resultado);
    }
    $consola?->jsonOut([
        'clase' => $claseNombre,
        'tabla' => $tabla,
        'archivos' => array_map(fn($a) => [
            'tipo' => $a['tipo'],
            'ruta' => $a['ruta'],
        ], $resultado['archivos']),
        'pasos_siguientes' => $resultado['pasos_siguientes'],
    ], 'modulo:generar');
    exit(0);
}

if (!$resultado['exito']) {
    echo "\n⚠️  Errores durante la generacion:\n";
    foreach ($resultado['errores'] as $err) {
        echo "  - {$err}\n";
    }
    exit(1);
}

echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "🎉 Modulo {$claseNombre} generado exitosamente!\n";
echo "═══════════════════════════════════════════════════\n";
echo "\n";
echo "Archivos creados:\n";
foreach ($resultado['archivos'] as $a) {
    $icono = match ($a['tipo']) {
        'Migracion' => '🗄',
        'Modelo' => '📄',
        'Controlador API' => '🌐',
        'Vista modulo' => '🖥',
        'JS modulo' => '⚡',
        'Rutas' => '🔗',
        'Autoload' => '🔧',
        default => '📄',
    };
    echo "  {$icono} {$a['tipo']}: {$a['ruta']}\n";
}
echo "\n";
echo "Pasos siguientes:\n";
foreach ($resultado['pasos_siguientes'] as $paso) {
    echo "  • {$paso}\n";
}
echo "\n";
