<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

use LiteFramework\Config\GestorEntorno;

class ReporteroSentry
{
    private static string $dsn = '';
    private static string $entorno = 'desarrollo';
    private static string $release = '';

    public static function iniciar(string $dsn): void
    {
        self::$dsn = $dsn;
        self::$entorno = GestorEntorno::obtener('APP_ENTORNO', 'desarrollo');
        self::$release = GestorEntorno::obtener('APP_RELEASE', '');
    }

    public static function estaActivo(): bool
    {
        return self::$dsn !== '' && str_starts_with(self::$dsn, 'https://');
    }

    public static function capturar(\Throwable $excepcion, array $contextoExtra = []): void
    {
        if (!self::estaActivo()) {
            return;
        }

        try {
            $evento = self::construirEvento($excepcion, $contextoExtra);
            self::enviar($evento);
        } catch (\Throwable $e) {
            error_log('[ReporteroSentry] Error al enviar a Sentry: ' . $e->getMessage());
        }
    }

    private static function construirEvento(\Throwable $excepcion, array $contextoExtra): array
    {
        $frames = [];
        foreach ($excepcion->getTrace() as $i => $frame) {
            $class = $frame['class'] ?? '';
            $type = $frame['type'] ?? '';
            $fn = $frame['function'];
            $file = isset($frame['file']) ? $frame['file'] : '[interno]';
            $line = $frame['line'] ?? 0;

            $frames[] = [
                'filename' => $file,
                'lineno' => $line,
                'function' => $class . $type . $fn,
                'in_app' => $file === '[interno]' || str_contains($file, '/servidor/'),
            ];
        }

        $frames[] = [
            'filename' => $excepcion->getFile(),
            'lineno' => $excepcion->getLine(),
            'function' => '{main}',
            'in_app' => true,
        ];

        $frames = array_reverse($frames);

        $evento = [
            'event_id' => self::generarUuid(),
            'timestamp' => date('Y-m-d\TH:i:s.v'),
            'platform' => 'php',
            'level' => 'error',
            'logger' => $contextoExtra['tipo'] ?? 'EXCEPCION',
            'culprit' => $excepcion->getFile() . ':' . $excepcion->getLine(),
            'exception' => [
                'values' => [
                    [
                        'type' => get_class($excepcion),
                        'value' => $excepcion->getMessage(),
                        'module' => null,
                        'stacktrace' => ['frames' => $frames],
                    ],
                ],
            ],
            'tags' => [
                'entorno' => self::$entorno,
            ],
            'extra' => $contextoExtra,
            'environment' => self::$entorno,
        ];

        if (self::$release !== '') {
            $evento['release'] = self::$release;
        }

        if (PHP_SAPI === 'cli') {
            $evento['extra']['comando'] = implode(' ', $_SERVER['argv'] ?? []);
        } elseif (!empty($_SERVER)) {
            $evento['request'] = [
                'url' => ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? ''),
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                'headers' => self::obtenerCabeceras(),
            ];
            $evento['tags']['ruta'] = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        }

        if (!empty($_SESSION)) {
            $evento['extra']['sesion'] = [
                'id' => session_id() ?: null,
                'usuario_id' => $_SESSION['operador_id'] ?? $_SESSION['usuario_id'] ?? null,
            ];
        }

        return $evento;
    }

    private static function enviar(array $evento): void
    {
        $partes = parse_url(self::$dsn);
        $publicKey = $partes['user'] ?? '';
        $host = $partes['host'] ?? '';
        $projectId = ltrim($partes['path'] ?? '', '/');
        $protocolo = $partes['scheme'] ?? 'https';

        $url = "{$protocolo}://{$host}/api/{$projectId}/store/";

        $payload = json_encode($evento);
        if ($payload === false) {
            return;
        }

        $cabeceras = [
            'Content-Type: application/json',
            'X-Sentry-Auth: Sentry sentry_version=7, sentry_key=' . $publicKey . ', sentry_client=ReporteroSentry/1.0',
            'Content-Length: ' . strlen($payload),
        ];

        $opciones = [
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $cabeceras),
                'content' => $payload,
                'timeout' => 3,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ];

        $contexto = stream_context_create($opciones);
        $respuesta = @file_get_contents($url, false, $contexto);

        if ($respuesta === false) {
            $error = error_get_last();
            error_log('[ReporteroSentry] Error HTTP: ' . ($error['message'] ?? 'desconocido'));
        }
    }

    private static function obtenerCabeceras(): array
    {
        if (!function_exists('getallheaders')) {
            $cabeceras = [];
            foreach ($_SERVER as $clave => $valor) {
                if (str_starts_with($clave, 'HTTP_')) {
                    $nombre = str_replace('_', '-', substr($clave, 5));
                    $cabeceras[$nombre] = $valor;
                }
            }
            return $cabeceras;
        }
        return getallheaders();
    }

    private static function generarUuid(): string
    {
        return bin2hex(random_bytes(16));
    }
}
