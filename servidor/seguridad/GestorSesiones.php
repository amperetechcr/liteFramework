<?php

declare(strict_types=1);

namespace LiteFramework\Seguridad;

use LiteFramework\Seguridad\TrazadorPeticiones;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Config\GestorEntorno;
use LiteFramework\Nucleo\Excepciones\ErrorSeguridad;
use LiteFramework\Nucleo\Excepciones\ErrorAutenticacion;

class GestorSesiones
{
    public static function establecerCabecerasSeguras(): void
    {
        if (!headers_sent()) {
            header('X-Frame-Options: DENY');
            header('X-Content-Type-Options: nosniff');
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
            }
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }

    public static function iniciarSesionEstricta(): void
    {
        self::establecerCabecerasSeguras();
        self::filtrarAgentesMaliciosos();
        TrazadorPeticiones::iniciar();

        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_only_cookies', 1);
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                ini_set('session.cookie_secure', 1);
            }
            session_start();
        }
    }

    public static function validarSesionExpirada(): void
    {
        $duracion = (int)GestorEntorno::obtener('SESION_DURACION_MINUTOS', 60);
        $tiempoLimite = $duracion * 60;
        if (isset($_SESSION['_ultimo_acceso']) && (time() - $_SESSION['_ultimo_acceso']) > $tiempoLimite) {
            self::destruirSesionCompletamente();
            RegistroAuditoria::seguridad('Sesion expirada por inactividad', [
                'tiempo_maximo' => $tiempoLimite,
            ]);
        }
        $_SESSION['_ultimo_acceso'] = time();
    }

    public static function regenerarSesionSegura(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destruirSesionCompletamente(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $parametros = session_get_cookie_params();
                setcookie(
                    session_name() ?: '',
                    '',
                    time() - 42000,
                    $parametros["path"],
                    $parametros["domain"],
                    $parametros["secure"],
                    $parametros["httponly"]
                );
            }
            session_destroy();
        }
    }

    private static function obtenerSubredIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $pos = strrpos($ip, '.');
            return $pos !== false ? substr($ip, 0, $pos) : $ip;
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $bloques = explode(':', $ip);
            return implode(':', array_slice($bloques, 0, 4));
        }
        return 'IP_DESCONOCIDA';
    }

    public static function vincularHuellaCliente(): void
    {
        $subredIp = self::obtenerSubredIp();
        $agenteUsuario = $_SERVER['HTTP_USER_AGENT'] ?? 'AGENTE_DESCONOCIDO';
        $_SESSION['huella_seguridad_cliente'] = hash('sha256', $subredIp . $agenteUsuario);
    }

    public static function validarHuellaCliente(): void
    {
        if (isset($_SESSION['operador_id'])) {
            $subredIp = self::obtenerSubredIp();
            $agenteUsuario = $_SERVER['HTTP_USER_AGENT'] ?? 'AGENTE_DESCONOCIDO';
            $huellaActual = hash('sha256', $subredIp . $agenteUsuario);

            if (empty($_SESSION['huella_seguridad_cliente']) || !hash_equals($_SESSION['huella_seguridad_cliente'], $huellaActual)) {
                RegistroAuditoria::seguridad('Posible secuestro de sesion detectado', [
                    'id_operador' => (int)$_SESSION['operador_id'],
                    'huella_esperada' => $_SESSION['huella_seguridad_cliente'] ?? 'vacia',
                    'huella_actual' => $huellaActual,
                    'ip_actual' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
                self::destruirSesionCompletamente();
                throw new ErrorAutenticacion('Sesión invalidada por cambio de huella digital');
            }
        }
    }

    public static function filtrarAgentesMaliciosos(): void
    {
        $agente = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $botsNegros = ['curl', 'python', 'perl', 'wget', 'sqlmap', 'nmap', 'burp', 'scan'];

        foreach ($botsNegros as $bot) {
            if (stripos($agente, $bot) !== false) {
                RegistroAuditoria::seguridad('Bot malicioso bloqueado', [
                    'agente_usuario' => $agente,
                    'herramienta_detectada' => $bot,
                ]);
                throw new ErrorSeguridad('Bot malicioso bloqueado: ' . $bot);
            }
        }
    }

    public static function registrarIncidenteSeguridad(array|string $detalle): void
    {
        RegistroAuditoria::seguridad('Incidente de seguridad', [
            'detalle' => $detalle,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'desconocida',
        ]);
    }
}
