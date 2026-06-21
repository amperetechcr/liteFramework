<?php

declare(strict_types=1);

namespace LiteFramework\Seguridad;

use PDO;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Nucleo\DialectoBaseDatos;
use Exception;

class SseGestor
{
    private const SSE_DIR = __DIR__ . '/../../storage/sse';
    private const LOG_FILE = self::SSE_DIR . '/eventos.log';
    private const ULTIMO_ID_FILE = self::SSE_DIR . '/_ultimo_id';
    private const CREWAI_FILE = self::SSE_DIR . '/crewai_cache.json';

    public static function emitir(int $idOperador, string $tipo, mixed $datos): void
    {
        $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $stmt = $bd->prepare("INSERT INTO sse_evento (id_operador, tipo, datos) VALUES (:id, :tipo, :datos)");
        \assert($stmt !== false);
        $stmt->execute([
            ':id' => $idOperador,
            ':tipo' => $tipo,
            ':datos' => json_encode($datos, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public static function emitirATodos(string $tipo, mixed $datos): void
    {
        $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $stmt = $bd->prepare("INSERT INTO sse_evento (id_operador, tipo, datos) VALUES (0, :tipo, :datos)");
        \assert($stmt !== false);
        $stmt->execute([
            ':tipo' => $tipo,
            ':datos' => json_encode($datos, JSON_UNESCAPED_UNICODE),
        ]);
        $idEvento = (int)$bd->lastInsertId();

        if ($tipo === 'crewai') {
            $evento = [
                'agent_role' => $datos['agent_role'] ?? $datos['rol'] ?? '',
                'accion' => $datos['accion'] ?? '',
                'emoji' => $datos['emoji'] ?? '⚙️',
                'destino' => $datos['destino'] ?? '',
                'nombre' => $datos['nombre'] ?? '',
                'mensaje' => $datos['mensaje'] ?? '',
                '_id' => $idEvento,
                '_ts' => time(),
            ];

            $lockFile = self::CREWAI_FILE . '.lock';
            $lockFp = fopen($lockFile, 'c');
            if (!$lockFp) {
                return;
            }
            flock($lockFp, LOCK_EX);

            $existentes = [];
            if (file_exists(self::CREWAI_FILE)) {
                $contenido = file_get_contents(self::CREWAI_FILE);
                if ($contenido !== false) {
                    $existentes = json_decode($contenido, true) ?: [];
                }
            }
            array_unshift($existentes, $evento);
            $existentes = array_slice($existentes, 0, 50);

            $tmpFile = self::CREWAI_FILE . '.tmp';
            file_put_contents($tmpFile, json_encode($existentes, JSON_UNESCAPED_UNICODE), LOCK_EX);
            rename($tmpFile, self::CREWAI_FILE);

            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
    }

    public static function daemonCorriendo(): bool
    {
        if (!file_exists(self::ULTIMO_ID_FILE)) {
            return false;
        }
        $mtime = @filemtime(self::ULTIMO_ID_FILE);
        if ($mtime === false) {
            return false;
        }
        return (time() - $mtime) < 15;
    }

    public static function iniciarDaemon(): bool
    {
        if (self::daemonCorriendo()) {
            return true;
        }
        $phpBin = PHP_BINARY;
        if ($phpBin === '') { /** @phpstan-ignore identical.alwaysFalse */
            $phpBin = PHP_SAPI === 'cli' ? $_SERVER['_'] ?? 'php' : 'php';
        }
        $daemonPath = DIRECTORIO_RAIZ . '/servidor/consola/sse_daemon.php';
        if (PHP_OS_FAMILY === 'Windows') {
            exec('start /B "SSE" ' . escapeshellarg($phpBin) . ' ' . escapeshellarg($daemonPath) . ' > NUL 2>&1');
        } else {
            exec(escapeshellarg($phpBin) . ' ' . escapeshellarg($daemonPath) . ' > /dev/null 2>&1 &');
        }
        sleep(1);
        return self::daemonCorriendo();
    }

    public static function conectar(int $idOperador, int $ultimoId = 0): never
    {
        $tiempoLimite = 300;
        set_time_limit($tiempoLimite + 10);

        session_write_close();

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!self::daemonCorriendo()) {
            self::iniciarDaemon();
        }

        if (self::daemonCorriendo() || file_exists(self::LOG_FILE)) {
            self::conectarModoArchivo($idOperador, $tiempoLimite, $ultimoId);
        } else {
            self::conectarModoDB($idOperador, $tiempoLimite, $ultimoId);
        }
        exit;
    }

    private static function conectarModoArchivo(int $idOperador, int $tiempoLimite, int $ultimoId): void
    {
        session_write_close();

        echo "event: sse.conectado\n";
        echo "data: {\"id_operador\":{$idOperador},\"modo\":\"archivo\"}\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();

        $inicio = time();
        $maxEventosPorCiclo = 50;
        $posArchivo = null;
        $ultimoIdVisto = $ultimoId;

        while (true) {
            if (connection_aborted()) {
                break;
            }
            if ((time() - $inicio) >= $tiempoLimite) {
                break;
            }

            try {
                $eventos = self::leerEventosDelArchivo($idOperador, $maxEventosPorCiclo, $ultimoIdVisto, $posArchivo);
                foreach ($eventos as $ev) {
                    $ultimoIdVisto = max($ultimoIdVisto, (int)$ev['id']);
                    echo "id: {$ev['id']}\n";
                    echo "event: {$ev['tipo']}\n";
                    echo "data: {$ev['datos']}\n\n";
                }
            } catch (\Throwable $e) {
                error_log('[SseGestor] Error en modo archivo: ' . $e->getMessage());
            }

            echo ": heartbeat\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            usleep(50000);
        }
    }

    public static function leerEventosDelArchivo(int $idOperador, int $maxEventos = 50, int $ultimoId = 0, ?int &$posArchivo = null): array
    {
        $archivo = self::LOG_FILE;
        if (!file_exists($archivo)) {
            return [];
        }
        $tamano = filesize($archivo);
        if ($tamano === 0 || $tamano === false) {
            return [];
        }

        $handle = fopen($archivo, 'rb');
        if (!$handle) {
            return [];
        }

        $posInicial = $posArchivo;
        if ($posInicial !== null && $posInicial > 0) {
            if ($tamano < $posInicial) {
                $posInicial = 0;
            } else {
                fseek($handle, $posInicial);
            }
        }

        if ($posInicial === null || $posInicial === 0) {
            $buffer = 4096;
            $pos = max(0, $tamano - $buffer);
            $visto = 0;
            $ultimasLineas = [];

            while ($pos > 0 && $visto < 50) {
                fseek($handle, $pos);
                $contenido = fread($handle, $buffer);
                $lineas = explode("\n", $contenido ?: '');
                foreach (array_reverse($lineas) as $l) {
                    $l = trim($l);
                    if ($l === '') {
                        continue;
                    }
                    array_unshift($ultimasLineas, $l);
                    $visto++;
                    if ($visto >= 50) {
                        break;
                    }
                }
                $pos = max(0, $pos - $buffer);
            }

            if ($pos === 0) {
                fseek($handle, 0);
                $contenido = fread($handle, max(1, (int)$tamano));
                \assert($contenido !== false);
                $lineas = explode("\n", trim($contenido));
                $ultimasLineas = array_slice($lineas, -50);
            }

            $lineas = $ultimasLineas;
        } else {
            $contenido = stream_get_contents($handle);
            $lineas = $contenido !== false ? explode("\n", trim($contenido)) : [];
        }

        $posArchivo = ftell($handle); /** @phpstan-ignore parameterByRef.type */
        fclose($handle);

        $eventos = [];
        foreach ($lineas as $linea) {
            $ev = json_decode($linea, true);
            if (!$ev || !isset($ev['id'])) {
                continue;
            }
            if ((int)$ev['id'] <= $ultimoId) {
                continue;
            }
            // Conexión nueva (ultimoId=0): ignorar eventos viejos (>60s)
            if ($ultimoId === 0 && isset($ev['ts']) && (time() - (int)$ev['ts']) > 60) {
                continue;
            }
            if ((int)($ev['id_operador'] ?? -1) !== 0 && (int)($ev['id_operador'] ?? -1) !== $idOperador) {
                continue;
            }
            $eventos[] = $ev;
            if (count($eventos) >= $maxEventos) {
                break;
            }
        }

        return $eventos;
    }

    private static function conectarModoDB(int $idOperador, int $tiempoLimite, int $ultimoId): void
    {
        session_write_close();

        echo "event: sse.conectado\n";
        echo "data: {\"id_operador\":{$idOperador},\"modo\":\"db\"}\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();

        $inicio = time();
        $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $idleCycles = 0;

        while (true) {
            if (connection_aborted()) {
                break;
            }
            if ((time() - $inicio) >= $tiempoLimite) {
                break;
            }

            try {
                $stmt = $bd->prepare("
                    SELECT id_evento, tipo, datos
                    FROM sse_evento
                    WHERE id_evento > :ultimo AND (id_operador = 0 OR id_operador = :operador)
                    ORDER BY id_evento ASC
                    LIMIT 50
                ");
                \assert($stmt !== false);
                $stmt->execute([
                    ':ultimo' => $ultimoId,
                    ':operador' => $idOperador,
                ]);
                $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($eventos)) {
                    foreach ($eventos as $ev) {
                        $ultimoId = (int)$ev['id_evento'];
                        echo "id: {$ev['id_evento']}\n";
                        echo "event: {$ev['tipo']}\n";
                        echo "data: {$ev['datos']}\n\n";
                    }
                    $stmtDelete = $bd->prepare("DELETE FROM sse_evento WHERE id_evento <= :ultimo");
                    \assert($stmtDelete !== false);
                    $stmtDelete->execute([':ultimo' => $ultimoId]);
                    $idleCycles = 0;
                } else {
                    $idleCycles++;
                }

                if ($idleCycles % 5 === 0) {
                    $sql = "DELETE FROM sse_evento WHERE fecha_creacion < " . DialectoBaseDatos::fechaRestar($bd, 'MINUTE', 5);
                    $stmtCleanup = $bd->prepare($sql);
                    \assert($stmtCleanup !== false);
                    $stmtCleanup->execute();
                }
            } catch (Exception $e) {
                error_log('[SseGestor] Error en modo DB: ' . $e->getMessage());
            }

            echo ": heartbeat\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            usleep(50000);
        }
    }
}
