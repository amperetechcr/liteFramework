<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo;

use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Config\GestorEntorno;

class BloqueoArchivo
{
    private const DIR_LOCK = __DIR__ . '/../../storage/locks';

    public static function adquirir(string $clave, int $tiempoMaximoSeg = 30, bool $exclusivo = true): bool
    {
        $ruta = self::rutaLock($clave);
        $directorio = dirname($ruta);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $inicio = time();
        while (true) {
            $handle = @fopen($ruta, 'c+');
            if (!$handle) {
                return false;
            }

            $tipoLock = $exclusivo ? LOCK_EX : LOCK_SH;
            if (flock($handle, $tipoLock | LOCK_NB)) {
                $contenido = stream_get_contents($handle) ?: '';
                $meta = !empty($contenido) ? json_decode($contenido, true) : null;

                if ($meta !== null && isset($meta['expiracion']) && $meta['expiracion'] < time()) {
                    ftruncate($handle, 0);
                    rewind($handle);
                    $meta = null;
                }

                if ($meta === null) {
                    ftruncate($handle, 0);
                    rewind($handle);
                    $payload = json_encode([
                        'clave' => $clave,
                        'exclusivo' => $exclusivo,
                        'adquirido' => time(),
                        'expiracion' => time() + $tiempoMaximoSeg,
                        'pid' => getmypid(),
                        'host' => gethostname(),
                    ]);
                    if ($payload !== false) {
                        fwrite($handle, $payload);
                    }
                    fflush($handle);
                    flock($handle, LOCK_UN);
                    fclose($handle);

                    RegistroAuditoria::auditoria('Sistema', "Lock adquirido: {$clave}", [
                        'clave' => $clave,
                        'exclusivo' => $exclusivo,
                        'pid' => getmypid(),
                        'tiempo_maximo' => $tiempoMaximoSeg,
                    ]);
                    return true;
                }

                flock($handle, LOCK_UN);
                fclose($handle);

                if (time() - $inicio >= $tiempoMaximoSeg) {
                    RegistroAuditoria::advertencia('Sistema', "Lock timeout: {$clave}", [
                        'clave' => $clave,
                        'tiempo_espera' => time() - $inicio,
                    ]);
                    return false;
                }

                usleep(50000);
                continue;
            }

            fclose($handle);

            if (time() - $inicio >= $tiempoMaximoSeg) {
                return false;
            }

            usleep(50000);
        }
    }

    public static function adquirirCompartido(string $clave, int $tiempoMaximoSeg = 30): bool
    {
        return self::adquirir($clave, $tiempoMaximoSeg, false);
    }

    public static function liberar(string $clave): void
    {
        $ruta = self::rutaLock($clave);
        if (!file_exists($ruta)) {
            return;
        }

        $handle = @fopen($ruta, 'c+');
        if (!$handle) {
            return;
        }

        if (flock($handle, LOCK_EX | LOCK_NB)) {
            ftruncate($handle, 0);
            flock($handle, LOCK_UN);
            fclose($handle);
            @unlink($ruta);

            RegistroAuditoria::auditoria('Sistema', "Lock liberado: {$clave}", [
                'clave' => $clave,
                'pid' => getmypid(),
            ]);
        } else {
            fclose($handle);
        }
    }

    public static function estaBloqueado(string $clave): bool
    {
        $ruta = self::rutaLock($clave);
        if (!file_exists($ruta)) {
            return false;
        }

        $handle = @fopen($ruta, 'r');
        if (!$handle) {
            return false;
        }

        if (flock($handle, LOCK_SH | LOCK_NB)) {
            $contenido = stream_get_contents($handle) ?: '';
            $meta = !empty($contenido) ? json_decode($contenido, true) : null;
            flock($handle, LOCK_UN);
            fclose($handle);

            if ($meta !== null && isset($meta['expiracion']) && $meta['expiracion'] < time()) {
                @unlink($ruta);
                return false;
            }

            return $meta !== null;
        }

        fclose($handle);
        return true;
    }

    public static function limpiarExpirados(): int
    {
        $limpiados = 0;
        $archivos = glob(self::DIR_LOCK . '/*.lock');
        if ($archivos === false) {
            return 0;
        }

        foreach ($archivos as $ruta) {
            $handle = @fopen($ruta, 'r');
            if (!$handle) {
                continue;
            }
            if (flock($handle, LOCK_SH | LOCK_NB)) {
                $contenido = stream_get_contents($handle) ?: '';
                $meta = !empty($contenido) ? json_decode($contenido, true) : null;
                flock($handle, LOCK_UN);
                fclose($handle);

                if ($meta !== null && isset($meta['expiracion']) && $meta['expiracion'] < time()) {
                    @unlink($ruta);
                    $limpiados++;
                }
            } else {
                fclose($handle);
            }
        }

        return $limpiados;
    }

    private static function rutaLock(string $clave): string
    {
        $segura = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $clave);
        return self::DIR_LOCK . '/' . $segura . '.lock';
    }
}
