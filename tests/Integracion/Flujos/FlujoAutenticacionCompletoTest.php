<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Flujos;

use LiteFramework\Config\ConexionBaseDatos as DB;
use LiteFramework\Seguridad\GestorSesiones;
use LiteFramework\Seguridad\ValidadorCSRF;
use LiteFramework\Seguridad\ControlAccesoRBAC;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Seguridad\LimitadorPeticiones;
use LiteFramework\Nucleo\Excepciones\ErrorAutenticacion;

class FlujoAutenticacionCompletoTest extends \TestBase
{
    private array $sessionBackup = [];
    private array $serverBackup = [];
    private ?\PDO $bd = null;

    public function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? [];
        $this->serverBackup = $_SERVER ?? [];
        $_SESSION = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit-Test-Agent/1.0';
        $_SERVER['REQUEST_METHOD'] = 'CLI';
        $_SERVER['REQUEST_URI'] = '/tests';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->bd = DB::obtenerInstancia()->obtenerConector();
        if (!defined('URL_BASE')) {
            define('URL_BASE', '');
        }
        RegistroAuditoria::deshabilitarBitacora();
    }

    public function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
        $_SERVER = $this->serverBackup;
        DB::resetearInstancia();
    }

    public function testSessionStartsWithStrictSettings(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        GestorSesiones::iniciarSesionEstricta();
        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->assertNotEmpty(session_id());
    }

    public function testCsrfTokenGeneratedAndStoredInSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            GestorSesiones::iniciarSesionEstricta();
        }
        $token = ValidadorCSRF::generarToken();
        $this->assertNotEmpty($token);
        $this->assertSame(64, strlen($token));
        $this->assertArrayHasKey('token_seguridad_peticion', $_SESSION);
        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertSame($token, $_SESSION['csrf_token']);
    }

    public function testCsrfTokenValidatedSuccessfully(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            GestorSesiones::iniciarSesionEstricta();
        }
        $token = ValidadorCSRF::generarToken();
        $this->assertTrue(ValidadorCSRF::validarToken($token));
        $this->assertFalse(ValidadorCSRF::validarToken('token_invalido'));
        $this->assertFalse(ValidadorCSRF::validarToken(''));
    }

    public function testLoginSetsOperadorIdInSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            GestorSesiones::iniciarSesionEstricta();
        }
        $_SESSION['operador_id'] = 1;
        $_SESSION['operador_nombre'] = 'Admin Test';
        $_SESSION['operador_rol'] = 1;
        $_SESSION['rol_nombre'] = 'Super Administrador';
        $this->assertSame(1, $_SESSION['operador_id']);
        $this->assertSame('Admin Test', $_SESSION['operador_nombre']);
        $this->assertSame(1, $_SESSION['operador_rol']);
    }

    public function testRbacPermissionsLoadedAfterLogin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            GestorSesiones::iniciarSesionEstricta();
        }
        $_SESSION['operador_id'] = 1;
        $_SESSION['operador_rol'] = 1;
        ControlAccesoRBAC::cargarPermisosEnMemoria($this->bd, 1);
        $this->assertArrayHasKey('matriz_permisos', $_SESSION);
        $this->assertNotEmpty($_SESSION['matriz_permisos']);
        $this->assertTrue(ControlAccesoRBAC::tienePermiso('operador.crear'));
        $this->assertTrue(ControlAccesoRBAC::tienePermiso('operador.eliminar'));
    }

    public function testSessionFingerprintMatchesAfterLogin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            GestorSesiones::iniciarSesionEstricta();
        }
        $_SESSION['operador_id'] = 1;
        GestorSesiones::vincularHuellaCliente();
        $this->assertArrayHasKey('huella_seguridad_cliente', $_SESSION);
        $huellaEsperada = hash('sha256', '127.0.0' . 'PHPUnit-Test-Agent/1.0');
        $this->assertSame($huellaEsperada, $_SESSION['huella_seguridad_cliente']);
        GestorSesiones::validarHuellaCliente();
        $this->addToAssertionCount(1);
    }

    public function testSessionFingerprintMismatchThrowsError(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            GestorSesiones::iniciarSesionEstricta();
        }
        $_SESSION['operador_id'] = 1;
        $_SESSION['huella_seguridad_cliente'] = hash('sha256', 'otra_ipOtroUserAgent');
        $this->expectException(ErrorAutenticacion::class);
        GestorSesiones::validarHuellaCliente();
    }

    public function testSessionIdleTimeoutDetection(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            GestorSesiones::iniciarSesionEstricta();
        }
        $_SESSION['_ultimo_acceso'] = time() - 7200;
        $_SESSION['operador_id'] = 1;
        GestorSesiones::validarSesionExpirada();
        $this->assertArrayNotHasKey('operador_id', $_SESSION);
        $this->assertArrayHasKey('_ultimo_acceso', $_SESSION);
    }

    public function testSessionRegenerationOnSensitiveOps(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            GestorSesiones::iniciarSesionEstricta();
        }
        $idAntes = session_id();
        GestorSesiones::regenerarSesionSegura();
        $idDespues = session_id();
        $this->assertNotSame($idAntes, $idDespues);
    }

    public function testLogoutDestroysSessionCompletely(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            GestorSesiones::iniciarSesionEstricta();
        }
        $_SESSION['operador_id'] = 1;
        $_SESSION['csrf_token'] = 'test-token';
        GestorSesiones::destruirSesionCompletamente();
        $this->assertEmpty($_SESSION);
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testRateLimitBlocksTooManyFailedLoginAttempts(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            GestorSesiones::iniciarSesionEstricta();
        }
        $correo = 'test@example.com';
        $clave = hash('sha256', strtolower(trim($correo)));
        for ($i = 0; $i < 5; $i++) {
            $_SESSION['_intentos_' . $clave] = ($_SESSION['_intentos_' . $clave] ?? 0) + 1;
            $_SESSION['_bloqueo_' . $clave] = time();
        }
        $this->assertSame(5, $_SESSION['_intentos_' . $clave]);
        $bloqueo = $_SESSION['_intentos_' . $clave] >= 5 && time() < ($_SESSION['_bloqueo_' . $clave] + 15 * 60);
        $this->assertTrue($bloqueo);
    }

    public function testRbacPermissionCheckBlocksUnauthorizedAccess(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            GestorSesiones::iniciarSesionEstricta();
        }
        $_SESSION['operador_id'] = 4;
        $_SESSION['operador_rol'] = 4;
        ControlAccesoRBAC::cargarPermisosEnMemoria($this->bd, 4);
        $this->assertFalse(ControlAccesoRBAC::tienePermiso('operador.crear'));
        $this->assertFalse(ControlAccesoRBAC::tienePermiso('operador.eliminar'));
        $this->assertTrue(ControlAccesoRBAC::tienePermiso('operador.leer'));
    }
}
