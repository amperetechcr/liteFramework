<?php

declare(strict_types=1);

$dirRaiz = dirname(__DIR__, 2);
$testsDir = $dirRaiz . '/tests';
$phpunitPhar = $testsDir . '/phpunit.phar';
$configXml = $testsDir . '/phpunit.xml';

$colores = [
    'verde' => "\033[32m",
    'amarillo' => "\033[33m",
    'rojo' => "\033[31m",
    'cyan' => "\033[36m",
    'reset' => "\033[0m",
];

function color(string $txt, string $c): string
{
    global $colores;
    return $colores[$c] . $txt . $colores['reset'];
}

echo color("=== liteFramework - Ejecutor de Pruebas ===\n", 'cyan');

if (!file_exists($phpunitPhar)) {
    echo color("[!] PHPUnit phar no encontrado en: $phpunitPhar\n", 'amarillo');
    echo "\n";
    echo "Descargalo primero:\n";
    echo "  php -r \"copy('https://phar.phpunit.de/phpunit-11.phar', '$phpunitPhar');\"\n";
    echo "  O via wget:\n";
    echo "  wget https://phar.phpunit.de/phpunit-11.phar -O $phpunitPhar\n";
    echo "\n";
    echo "Tambien puedes usar Composer si lo prefieres:\n";
    echo "  composer require --dev phpunit/phpunit\n";
    exit(1);
}

$args = $_SERVER['argv'] ?? [];
$argsOpciones = [];

foreach ($args as $i => $a) {
    if ($i === 0) {
        continue;
    }
    $argsOpciones[] = $a;
}

$comando = PHP_BINARY . ' ' . escapeshellarg($phpunitPhar)
    . ' -c ' . escapeshellarg($configXml);

if (!empty($argsOpciones)) {
    $comando .= ' ' . implode(' ', array_map('escapeshellarg', $argsOpciones));
}

echo color("Ejecutando: phpunit", 'verde');
if (!empty($argsOpciones)) {
    echo ' ' . implode(' ', $argsOpciones);
}
echo "\n\n";

passthru($comando, $codigoSalida);
exit($codigoSalida);
