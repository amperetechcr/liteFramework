<?php

declare(strict_types=1);

$opts = getopt('', ['dias::']);
$dias = isset($opts['dias']) ? (int)$opts['dias'] : 90;

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/entorno.php';

use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Cli\Consola;

if (PHP_SAPI !== 'cli') {
    echo "Este script solo puede ejecutarse por consola.\n";
    exit(1);
}

$consola = null;
try {
    $consola = Consola::instance();
    $modoJson = $consola && $consola->estaEnModoJson();
} catch (\Throwable) {
    $modoJson = false;
}

$eliminados = RegistroAuditoria::limpiarEventosAntiguos($dias);

if ($modoJson) {
    $consola?->jsonOut([
        'ok' => true,
        'eliminados' => $eliminados,
        'dias' => $dias,
    ], 'auditoria:limpiar');
    exit;
}

echo "[AuditoriaLimpiador] Limpiando eventos anteriores a {$dias} dias...\n";
echo "[AuditoriaLimpiador] Eliminados {$eliminados} registros.\n";
