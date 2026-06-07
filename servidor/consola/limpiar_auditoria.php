<?php

declare(strict_types=1);

$opts = getopt('', ['dias::']);
$dias = isset($opts['dias']) ? (int)$opts['dias'] : 90;

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/entorno.php';

use LiteFramework\Seguridad\RegistroAuditoria;

if (PHP_SAPI !== 'cli') {
    echo "Este script solo puede ejecutarse por consola.\n";
    exit(1);
}

echo "[AuditoriaLimpiador] Limpiando eventos anteriores a {$dias} dias...\n";
$eliminados = RegistroAuditoria::limpiarEventosAntiguos($dias);
echo "[AuditoriaLimpiador] Eliminados {$eliminados} registros.\n";
