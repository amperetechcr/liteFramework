<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    echo "Este script solo puede ejecutarse por consola.\n";
    exit(1);
}

$base = dirname(__DIR__, 2);
require_once $base . '/servidor/autoload.php';

\LiteFramework\Config\GestorEntorno::cargar();

$archivoLog = $base . '/storage/sse/eventos.log';
$archivoUltimoId = $base . '/storage/sse/_ultimo_id';
$maxBytes = 10 * 1024 * 1024; // 10 MB antes de rotar

echo "[SSE Daemon] Iniciado. PID: " . getmypid() . "\n";
echo "[SSE Daemon] Log: {$archivoLog}\n";
echo "[SSE Daemon] Procesando eventos cada 1s...\n\n";

$ultimoId = 0;
if (file_exists($archivoUltimoId)) {
    $ultimoId = (int)file_get_contents($archivoUltimoId);
    echo "[SSE Daemon] Reanudando desde ID {$ultimoId}\n";
}

while (true) {
    try {
        $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $stmt = $bd->prepare("
            SELECT id_evento, id_operador, tipo, datos
            FROM sse_evento
            WHERE id_evento > :ultimo
            ORDER BY id_evento ASC
            LIMIT 200
        ");
        $stmt->execute([':ultimo' => $ultimoId]);
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($eventos)) {
            $lineas = [];
            $maxIdEnLote = $ultimoId;

            foreach ($eventos as $ev) {
                $eid = (int)$ev['id_evento'];
                if ($eid > $maxIdEnLote) {
                    $maxIdEnLote = $eid;
                }
                $lineas[] = json_encode([
                    'id' => $eid,
                    'id_operador' => (int)$ev['id_operador'],
                    'tipo' => $ev['tipo'],
                    'datos' => $ev['datos'],
                    'ts' => time(),
                ], JSON_UNESCAPED_UNICODE);
            }

            $contenido = implode("\n", $lineas) . "\n";

            if (@file_put_contents($archivoLog, $contenido, FILE_APPEND | LOCK_EX) === false) {
                error_log('[SSE Daemon] Error al escribir en eventos.log');
            } else {
                $ultimoId = $maxIdEnLote;
                file_put_contents($archivoUltimoId, (string)$ultimoId, LOCK_EX);
            }

            $stmtDel = $bd->prepare("DELETE FROM sse_evento WHERE id_evento <= :ultimo");
            $stmtDel->execute([':ultimo' => $maxIdEnLote]);

            $count = count($eventos);
            echo "[" . date('H:i:s') . "] Procesados {$count} eventos (ultimo ID: {$ultimoId})\n";
        }

        if (file_exists($archivoLog) && filesize($archivoLog) > $maxBytes) {
            $lineas = file($archivoLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $total = count($lineas);
            if ($total > 1000) {
                $nuevas = array_slice($lineas, -1000);
                file_put_contents($archivoLog, implode("\n", $nuevas) . "\n", LOCK_EX);
                echo "[SSE Daemon] Log rotado: {$total} → " . count($nuevas) . " lineas\n";
            } else {
                file_put_contents($archivoLog, '', LOCK_EX);
                echo "[SSE Daemon] Log rotado: vaciado\n";
            }
        }
    } catch (\Throwable $e) {
        error_log('[SSE Daemon] Error: ' . $e->getMessage());
        echo "[SSE Daemon] Error: {$e->getMessage()}\n";
    }

    sleep(1);
}
