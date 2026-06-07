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

    public static function emitir(int $idOperador, string $tipo, mixed $datos): void
    {
        $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $stmt = $bd->prepare("INSERT INTO sse_evento (id_operador, tipo, datos) VALUES (:id, :tipo, :datos)");
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
        $stmt->execute([
            ':tipo' => $tipo,
            ':datos' => json_encode($datos, JSON_UNESCAPED_UNICODE),
        ]);
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

    public static function conectar(int $idOperador, int $tiempoLimite = 120): never
    {
        set_time_limit($tiempoLimite + 10);

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        if (ob_get_level()) {
            ob_end_clean();
        }

        if (self::daemonCorriendo()) {
            self::conectarModoArchivo($idOperador, $tiempoLimite);
        } else {
            self::conectarModoDB($idOperador, $tiempoLimite);
        }
        exit;
    }

    private static function conectarModoArchivo(int $idOperador, int $tiempoLimite): void
    {
        echo "event: sse.conectado\n";
        echo "data: {\"id_operador\":{$idOperador},\"modo\":\"archivo\"}\n\n";

        if (ob_get_level() === 0) {
            ob_start();
        }
        ob_flush();
        flush();

        $inicio = time();
        $maxEventosPorCiclo = 50;

        while (true) {
            if (connection_aborted()) {
                break;
            }
            if ((time() - $inicio) >= $tiempoLimite) {
                break;
            }

            try {
                $eventos = self::leerEventosDelArchivo($idOperador, $maxEventosPorCiclo);
                foreach ($eventos as $ev) {
                    echo "id: {$ev['id']}\n";
                    echo "event: {$ev['tipo']}\n";
                    echo "data: {$ev['datos']}\n\n";
                }
            } catch (\Throwable $e) {
                error_log('[SseGestor] Error en modo archivo: ' . $e->getMessage());
            }

            echo ": heartbeat\n\n";
            ob_flush();
            flush();

            usleep(3000000);
        }
    }

    public static function leerEventosDelArchivo(int $idOperador, int $maxEventos = 50): array
    {
        $archivo = self::LOG_FILE;
        if (!file_exists($archivo)) {
            return [];
        }
        $tamano = filesize($archivo);
        if ($tamano === 0) {
            return [];
        }

        $handle = fopen($archivo, 'rb');
        if (!$handle) {
            return [];
        }

        $ultimasLineas = [];
        $buffer = 4096;
        $pos = max(0, $tamano - $buffer);
        $visto = 0;

        while ($pos > 0 && $visto < 50) {
            fseek($handle, $pos);
            $contenido = fread($handle, $buffer);
            $lineas = explode("\n", $contenido);
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

        fclose($handle);

        if ($pos === 0) {
            $handle = fopen($archivo, 'rb');
            fseek($handle, 0);
            $contenido = fread($handle, $tamano);
            fclose($handle);
            $lineas = explode("\n", trim($contenido));
            $ultimasLineas = array_slice($lineas, -50);
        }

        $eventos = [];
        $idsVistos = [];
        foreach ($ultimasLineas as $linea) {
            $ev = json_decode($linea, true);
            if (!$ev || !isset($ev['id'])) {
                continue;
            }

            if (isset($idsVistos[$ev['id']])) {
                continue;
            }
            $idsVistos[$ev['id']] = true;

            if ((int)($ev['id_operador'] ?? -1) === 0 || (int)($ev['id_operador'] ?? -1) === $idOperador) {
                $eventos[] = $ev;
                if (count($eventos) >= $maxEventos) {
                    break;
                }
            }
        }

        return $eventos;
    }

    private static function conectarModoDB(int $idOperador, int $tiempoLimite): void
    {
        echo "event: sse.conectado\n";
        echo "data: {\"id_operador\":{$idOperador},\"modo\":\"db\"}\n\n";

        if (ob_get_level() === 0) {
            ob_start();
        }
        ob_flush();
        flush();

        $ultimoId = 0;
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
                    $bd->prepare("DELETE FROM sse_evento WHERE id_evento <= :ultimo")
                        ->execute([':ultimo' => $ultimoId]);
                    $idleCycles = 0;
                } else {
                    $idleCycles++;
                }

                $sql = "DELETE FROM sse_evento WHERE fecha_creacion < " . DialectoBaseDatos::fechaRestar($bd, 'MINUTE', 5);
                $bd->prepare($sql)->execute();
            } catch (Exception $e) {
                error_log('[SseGestor] Error en modo DB: ' . $e->getMessage());
            }

            echo ": heartbeat\n\n";
            ob_flush();
            flush();

            usleep($idleCycles > 3 ? 5000000 : 2000000);
        }
    }
}
