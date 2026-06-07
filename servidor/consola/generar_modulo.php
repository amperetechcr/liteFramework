<?php

/**
 * Generador de módulos CRUD — liteFramework
 *
 * Uso:
 *   php servidor/consola/generar_modulo.php Producto \
 *       --campos="nombre:string:required,precio:decimal:required,stock:int,categoria_id:int" \
 *       --tabla=producto
 *
 * Genera: modelo, migración SQL, controlador API, módulo vista, JS, rutas, autoload
 */

declare(strict_types=1);

use LiteFramework\Servicios\GeneradorModulo;

require_once __DIR__ . '/../autoload.php';
if ($argc < 2) {
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
