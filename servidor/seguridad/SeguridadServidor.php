<?php

declare(strict_types=1);

namespace LiteFramework\Seguridad;

use PDO;
use LiteFramework\Config\GestorEntorno;
use LiteFramework\Nucleo\Excepciones\ErrorAutenticacion;

class SeguridadServidor
{
    // HTTP Security Headers
    public static function establecerCabecerasSeguras(): void
    {
        GestorSesiones::establecerCabecerasSeguras();
    }

    // Gestion de sesiones
    public static function iniciarSesionEstricta(): void
    {
        GestorSesiones::iniciarSesionEstricta();
    }
    public static function regenerarSesionSegura(): void
    {
        GestorSesiones::regenerarSesionSegura();
    }
    public static function destruirSesionCompletamente(): void
    {
        GestorSesiones::destruirSesionCompletamente();
    }

    // Permisos RBAC
    public static function cargarPermisosEnMemoria(?PDO $conexion, int $idRol): void
    {
        ControlAccesoRBAC::cargarPermisosEnMemoria($conexion, $idRol);
    }
    public static function tienePermiso(string $clavePermiso): bool
    {
        return ControlAccesoRBAC::tienePermiso($clavePermiso);
    }
    public static function requerirPermisoEstricto(string $clavePermiso): void
    {
        ControlAccesoRBAC::requerirPermisoEstricto($clavePermiso);
    }

    // CSRF Protection
    public static function generarTokenAntiFalsificacion(): string
    {
        return ValidadorCSRF::generarToken();
    }
    public static function validarTokenAntiFalsificacion(string $tokenRecibido): bool
    {
        return ValidadorCSRF::validarToken($tokenRecibido);
    }

    // Sanitization & Cryptography
    public static function sanitizarTextoBase(?string $textoCrudo): string
    {
        return SanitizadorEntrada::sanitizarTextoBase($textoCrudo);
    }
    public static function sanitizarTextoPlano(?string $textoCrudo): string
    {
        return SanitizadorEntrada::sanitizarTextoPlano($textoCrudo);
    }
    public static function sanitizarArregloGlobal(array $arregloEntrada): array
    {
        return SanitizadorEntrada::sanitizarArregloGlobal($arregloEntrada);
    }
    public static function procesarCorreoElectronico(string $correoCrudo): string|false
    {
        return SanitizadorEntrada::procesarCorreoElectronico($correoCrudo);
    }
    public static function encriptarClaveOperador(string $contrasenaPlana): string
    {
        return SanitizadorEntrada::encriptarClaveOperador($contrasenaPlana);
    }
    public static function verificarClaveOperador(string $contrasenaIngresada, string $hashAlmacenado): bool
    {
        return SanitizadorEntrada::verificarClaveOperador($contrasenaIngresada, $hashAlmacenado);
    }

    // Session Hijacking Prevention
    public static function vincularHuellaCliente(): void
    {
        GestorSesiones::vincularHuellaCliente();
    }
    public static function validarHuellaCliente(): void
    {
        GestorSesiones::validarHuellaCliente();
    }

    // Limitacion de tasa (sesion-based)
    public static function verificarBloqueoAcceso(?PDO $conexion, string $correo): bool
    {
        self::iniciarSesionEstricta();
        $clave = hash('sha256', strtolower(trim($correo)));
        $intentos = $_SESSION['_intentos_' . $clave] ?? 0;
        $bloqueoHasta = $_SESSION['_bloqueo_' . $clave] ?? 0;
        $maxIntentos = (int)GestorEntorno::obtener('APP_MAX_INTENTOS_ACCESO', 5);
        $minutosBloqueo = (int)GestorEntorno::obtener('APP_BLOQUEO_MINUTOS', 15);
        return ($intentos >= $maxIntentos && time() < ($bloqueoHasta + $minutosBloqueo * 60));
    }

    public static function registrarIntentoAccesoFallido(?PDO $conexion, string $correo): void
    {
        self::iniciarSesionEstricta();
        $clave = hash('sha256', strtolower(trim($correo)));
        $_SESSION['_intentos_' . $clave] = ($_SESSION['_intentos_' . $clave] ?? 0) + 1;
        $_SESSION['_bloqueo_' . $clave] = time();
    }

    public static function limpiarIntentosAcceso(?PDO $conexion, string $correo): void
    {
        self::iniciarSesionEstricta();
        $clave = hash('sha256', strtolower(trim($correo)));
        unset($_SESSION['_intentos_' . $clave], $_SESSION['_bloqueo_' . $clave]);
    }

    public static function contarIntentosAcceso(?PDO $conexion, string $correo): int
    {
        self::iniciarSesionEstricta();
        $clave = hash('sha256', strtolower(trim($correo)));
        return (int)($_SESSION['_intentos_' . $clave] ?? 0);
    }

    // Obtener ID de empresa con proteccion
    public static function obtenerIdEmpresa(): int
    {
        self::establecerCabecerasSeguras();
        self::filtrarAgentesMaliciosos();
        self::validarHuellaCliente();
        if (empty($_SESSION['id_empresa'])) {
            self::destruirSesionCompletamente();
            throw new ErrorAutenticacion('ID de empresa no disponible en la sesión');
        }
        return (int)$_SESSION['id_empresa'];
    }

    // WAF Interno
    public static function filtrarAgentesMaliciosos(): void
    {
        GestorSesiones::filtrarAgentesMaliciosos();
    }
}
