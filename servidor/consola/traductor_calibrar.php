<?php

declare(strict_types=1);

use LiteFramework\Nucleo\Helpers\Traductor;
use LiteFramework\Cli\Consola;

require_once __DIR__ . '/../autoload.php';

$consola = Consola::instance();
$modoJson = $consola && $consola->estaEnModoJson();

$categoria = null;
$modo = 'completo';
for ($i = 1; $i < $argc; $i++) {
    if (str_starts_with($argv[$i], '--categoria=')) {
        $categoria = substr($argv[$i], 12);
    }
    if (str_starts_with($argv[$i], '--modo=')) {
        $modo = substr($argv[$i], 7);
    }
}

$resultado = Traductor::calibrar($categoria, $modo);

if ($modoJson) {
    if (!$resultado['exito']) {
        $consola->jsonResultado($resultado);
    }
    $consola->jsonOut($resultado, 'traductor:calibrar');
    exit(0);
}

if (!$resultado['exito']) {
    echo "Error: {$resultado['error']}\n";
    exit(1);
}

echo "\n=== Calibracion del Traductor ===\n\n";

$detalleModo = match ($resultado['modo']) {
    'exacto' => 'Solo plantillas exactas',
    'variaciones' => 'Solo variaciones',
    'completo' => 'Plantillas exactas + variaciones',
    default => $resultado['modo'],
};
echo "Modo: {$detalleModo}\n";
echo "Total pruebas: {$resultado['total_pruebas']}\n";
echo "Aciertos:      {$resultado['aciertos']}\n";
echo "Precision:     " . round($resultado['precision_general'] * 100, 2) . "%\n";

if (isset($resultado['exactas'])) {
    $p = round($resultado['exactas']['aciertos'] / max($resultado['exactas']['total'], 1) * 100, 1);
    echo "Exactas:       {$resultado['exactas']['aciertos']}/{$resultado['exactas']['total']} ({$p}%)\n";
}
if (isset($resultado['variaciones'])) {
    $p = round($resultado['variaciones']['aciertos'] / max($resultado['variaciones']['total'], 1) * 100, 1);
    echo "Variaciones:   {$resultado['variaciones']['aciertos']}/{$resultado['variaciones']['total']} ({$p}%)\n";
}

if (!empty($resultado['precision_por_categoria'])) {
    echo "\nPor categoria:\n";
    foreach ($resultado['precision_por_categoria'] as $cat => $datos) {
        $prec = $datos['total'] > 0 ? round($datos['aciertos'] / $datos['total'] * 100, 1) : 0;
        echo "  {$cat}: {$datos['aciertos']}/{$datos['total']} ({$prec}%)\n";
    }
}

if (!empty($resultado['variaciones_por_categoria'])) {
    echo "\nVariaciones por categoria:\n";
    foreach ($resultado['variaciones_por_categoria'] as $cat => $datos) {
        $prec = $datos['total'] > 0 ? round($datos['aciertos'] / $datos['total'] * 100, 1) : 0;
        echo "  {$cat}: {$datos['aciertos']}/{$datos['total']} ({$prec}%)\n";
    }
}

echo "\n";
exit(0);
