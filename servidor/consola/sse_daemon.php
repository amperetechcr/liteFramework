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
    $ultimoId = (int) file_get_contents($archivoUltimoId);
    echo "[SSE Daemon] Reanudando desde ID {$ultimoId}\n";
}

/** @phpstan-ignore-next-line - bucle infinito intencional para daemon */
while (true) {
    try {
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
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            $code = $e->getCode();
            if (
                str_contains($msg, 'gone away')
                || $code === 2006
                || $code === 'HY000'
                || str_contains($msg, 'MySQL server has gone away')
            ) {
                echo "[SSE Daemon] Conexion perdida. Reconectando...\n";
                \LiteFramework\Config\ConexionBaseDatos::resetearInstancia();
            } else {
                throw $e;
            }
            continue;
        }

        if (!empty($eventos)) {
            $lineas = [];
            $maxIdEnLote = $ultimoId;

            foreach ($eventos as $ev) {
                $eid = (int) $ev['id_evento'];
                if ($eid > $maxIdEnLote) {
                    $maxIdEnLote = $eid;
                }
                $lineas[] = json_encode([
                    'id' => $eid,
                    'id_operador' => (int) $ev['id_operador'],
                    'tipo' => $ev['tipo'],
                    'datos' => $ev['datos'],
                    'ts' => time(),
                ], JSON_UNESCAPED_UNICODE);
            }

            $contenido = implode("\n", $lineas) . "\n";

            $fpLog = fopen($archivoLog, 'a');
            if ($fpLog && flock($fpLog, LOCK_EX)) {
                fwrite($fpLog, $contenido);
                fflush($fpLog);
                flock($fpLog, LOCK_UN);
            } elseif ($fpLog) {
                error_log('[SSE Daemon] Error al bloquear eventos.log');
            }
            if ($fpLog) {
                fclose($fpLog);
            }

            $ultimoId = $maxIdEnLote;
            file_put_contents($archivoUltimoId, (string) $ultimoId, LOCK_EX);

            $stmtDel = $bd->prepare("DELETE FROM sse_evento WHERE id_evento <= :ultimo");
            $stmtDel->execute([':ultimo' => $maxIdEnLote]);

            $count = count($eventos);
            echo "[" . date('H:i:s') . "] Procesados {$count} eventos (ultimo ID: {$ultimoId})\n";

            continue;
        }

        $tamanoLog = @filesize($archivoLog);
        if ($tamanoLog !== false && $tamanoLog > $maxBytes) {
            $fp = fopen($archivoLog, 'r+');
            if ($fp && flock($fp, LOCK_EX)) {
                $contenido = stream_get_contents($fp);
                if ($contenido === false) {
                    $contenido = '';
                }
                $lineas = explode("\n", $contenido);
                $total = count($lineas);
                $nuevas = $total > 1000 ? array_slice($lineas, -1000) : [];
                ftruncate($fp, 0);
                rewind($fp);
                if (!empty($nuevas)) {
                    fwrite($fp, implode("\n", $nuevas) . "\n");
                }
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
                echo "[SSE Daemon] Log rotado: {$total} → " . count($nuevas) . " lineas\n";
            } elseif ($fp) {
                fclose($fp);
            }
        }
    } catch (\Throwable $e) {
        error_log('[SSE Daemon] Error: ' . $e->getMessage());
        echo "[SSE Daemon] Error: {$e->getMessage()}\n";
    }

    usleep(50000);
}
