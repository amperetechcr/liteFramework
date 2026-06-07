<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

use LiteFramework\Seguridad\SeguridadServidor;
use LiteFramework\Seguridad\ControlAccesoRBAC;

class AyudanteSeguridad extends Helper
{
    public static function tokenCSRF(): string
    {
        return $_SESSION['token_csrf'] ?? '';
    }

    public static function sesionActiva(): bool
    {
        return isset($_SESSION['operador_id']);
    }

    public static function idOperador(): int
    {
        return (int)($_SESSION['operador_id'] ?? 0);
    }

    public static function autenticacionRequerida(): void
    {
        if (!self::sesionActiva()) {
            header('Location: ' . URL_BASE . '/?error=privilegios_insuficientes');
            exit();
        }
    }

    public static function permisoRequerido(string $clave): void
    {
        ControlAccesoRBAC::requerirPermisoEstricto($clave);
    }

    public static function tienePermiso(string $clave): bool
    {
        return ControlAccesoRBAC::tienePermiso($clave);
    }

    public static function validarCSRF(string $token): bool
    {
        return SeguridadServidor::validarTokenAntiFalsificacion($token);
    }

    public static function csrfMeta(): string
    {
        $token = self::tokenCSRF();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function tokenNuevo(): string
    {
        return SeguridadServidor::generarTokenAntiFalsificacion();
    }

    public static function ipCliente(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function agenteUsuario(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? 'desconocido', 0, 255);
    }
}

class Seguridad extends AyudanteSeguridad
{
}
