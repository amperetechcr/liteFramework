<?php

declare(strict_types=1);

$dirRaiz = dirname(__DIR__, 2);
$testsDir = $dirRaiz . '/tests';
$liteTest = $testsDir . '/liteTest.php';

if (!file_exists($liteTest)) {
    echo "[ERROR] liteTest no encontrado en: $liteTest\n";
    exit(1);
}

$args = $_SERVER['argv'] ?? [];
$opciones = [];

foreach ($args as $i => $a) {
    if ($i === 0) continue;
    $opciones[] = $a;
}

$comando = PHP_BINARY . ' ' . escapeshellarg($liteTest);
if (!empty($opciones)) {
    $comando .= ' -- ' . implode(' ', array_map('escapeshellarg', $opciones));
}

echo "Ejecutando: liteTest\n";
passthru($comando, $codigoSalida);
exit($codigoSalida);
