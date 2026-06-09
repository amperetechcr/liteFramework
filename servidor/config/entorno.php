<?php

declare(strict_types=1);

namespace LiteFramework\Config;

class GestorEntorno
{
    private static bool $cargado = false;

    public static function cargar(?string $ruta = null): void
    {
        if (self::$cargado) {
            return;
        }

        if ($ruta === null) {
            $ruta = __DIR__ . '/../../.env';
        }

        if (!file_exists($ruta)) {
            self::definirValoresPorDefecto();
            self::$cargado = true;
            return;
        }

        $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || strpos($linea, '#') === 0) {
                continue;
            }

            $partes = explode('=', $linea, 2);
            if (count($partes) !== 2) {
                continue;
            }

            $clave = trim($partes[0]);
            $valor = trim($partes[1]);

            if (!defined($clave)) {
                define($clave, $valor);
            }
        }

        self::definirValoresPorDefecto();
        self::$cargado = true;
    }

    private static function definirValoresPorDefecto(): void
    {
        if (!defined('APP_ENTORNO')) {
            define('APP_ENTORNO', 'desarrollo');
        }
        if (!defined('APP_DEPURACION')) {
            define('APP_DEPURACION', true);
        }
        if (!defined('DB_ANFITRION')) {
            define('DB_ANFITRION', 'localhost');
        }
        if (!defined('DB_NOMBRE')) {
            define('DB_NOMBRE', 'lite');
        }
        if (!defined('DB_USUARIO')) {
            define('DB_USUARIO', 'root');
        }
        if (!defined('DB_CLAVE')) {
            define('DB_CLAVE', '');
        }
        if (!defined('APP_MAX_INTENTOS_ACCESO')) {
            define('APP_MAX_INTENTOS_ACCESO', 5);
        }
        if (!defined('APP_BLOQUEO_MINUTOS')) {
            define('APP_BLOQUEO_MINUTOS', 15);
        }
        if (!defined('OAUTH_GOOGLE_ID')) {
            define('OAUTH_GOOGLE_ID', '');
        }
        if (!defined('OAUTH_GOOGLE_SECRET')) {
            define('OAUTH_GOOGLE_SECRET', '');
        }
        if (!defined('OAUTH_GITHUB_ID')) {
            define('OAUTH_GITHUB_ID', '');
        }
        if (!defined('OAUTH_GITHUB_SECRET')) {
            define('OAUTH_GITHUB_SECRET', '');
        }
        if (!defined('OAUTH_REDIRECT_BASE')) {
            define('OAUTH_REDIRECT_BASE', '');
        }
        if (!defined('MAIL_ANFITRION')) {
            define('MAIL_ANFITRION', 'localhost');
        }
        if (!defined('MAIL_PUERTO')) {
            define('MAIL_PUERTO', '25');
        }
        if (!defined('MAIL_USUARIO')) {
            define('MAIL_USUARIO', '');
        }
        if (!defined('MAIL_CLAVE')) {
            define('MAIL_CLAVE', '');
        }
        if (!defined('MAIL_REMITENTE')) {
            define('MAIL_REMITENTE', '');
        }
        if (!defined('MAIL_TLS')) {
            define('MAIL_TLS', false);
        }
        if (!defined('SENTRY_DSN')) {
            define('SENTRY_DSN', '');
        }
        if (!defined('AI_CREW_TOKEN_HASH')) {
            define('AI_CREW_TOKEN_HASH', '');
        }
        if (!defined('AI_AGENT_ROLE')) {
            define('AI_AGENT_ROLE', 'worker');
        }
    }

    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        return defined($clave) ? constant($clave) : $defecto;
    }

    public static function esProduccion(): bool
    {
        return self::obtener('APP_ENTORNO') === 'produccion';
    }

    public static function esDepuracion(): bool
    {
        return self::obtener('APP_DEPURACION') === true || self::obtener('APP_DEPURACION') === 'true';
    }
}
