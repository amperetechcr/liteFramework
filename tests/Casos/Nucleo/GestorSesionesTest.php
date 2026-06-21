<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Nucleo;

use LiteFramework\Seguridad\GestorSesiones;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Nucleo\Excepciones\ErrorSeguridad;
use LiteFramework\Nucleo\Excepciones\ErrorAutenticacion;

class GestorSesionesTest extends \TestBase
{
    private array $serverRespaldo;
    private array $sesionRespaldo;

    public function setUp(): void
    {
        $this->serverRespaldo = $_SERVER;
        $this->sesionRespaldo = $_SESSION ?? [];
        $_SERVER = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'TestBrowser/1.0',
        ];
        $_SESSION = [];
        RegistroAuditoria::deshabilitarBitacora();
    }

    public function tearDown(): void
    {
        $_SERVER = $this->serverRespaldo;
        $_SESSION = $this->sesionRespaldo;
    }

    public function testEstablecerCabecerasSegurasNoLanzaError(): void
    {
        GestorSesiones::establecerCabecerasSeguras();
        $this->assertTrue(true);
    }

    public function testIniciarSesionEstrictaConSessionNoActiva(): void
    {
        $this->assertSame(PHP_SESSION_NONE, session_status());
        GestorSesiones::iniciarSesionEstricta();
        $this->assertNotSame(PHP_SESSION_NONE, session_status());
    }

    public function testValidarSesionExpiradaSesionActiva(): void
    {
        $_SESSION['_ultimo_acceso'] = time();
        GestorSesiones::validarSesionExpirada();
        $this->assertArrayHasKey('_ultimo_acceso', $_SESSION);
    }

    public function testRegenerarSesionSegura(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $idViejo = session_id();
        GestorSesiones::regenerarSesionSegura();
        $this->assertNotSame($idViejo, session_id());
    }

    public function testDestruirSesionCompletamente(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['dato'] = 'valor';
        GestorSesiones::destruirSesionCompletamente();
        $this->assertEmpty($_SESSION);
    }

    public function testVincularHuellaClienteConIPv4(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        GestorSesiones::vincularHuellaCliente();
        $this->assertArrayHasKey('huella_seguridad_cliente', $_SESSION);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $_SESSION['huella_seguridad_cliente']);
    }

    public function testVincularHuellaClienteConIPv6(): void
    {
        $_SERVER['REMOTE_ADDR'] = '2001:db8::ff00:42:8329';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        GestorSesiones::vincularHuellaCliente();
        $this->assertArrayHasKey('huella_seguridad_cliente', $_SESSION);
    }

    public function testVincularHuellaClienteUserAgentDesconocido(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        GestorSesiones::vincularHuellaCliente();
        $this->assertArrayHasKey('huella_seguridad_cliente', $_SESSION);
    }

    public function testValidarHuellaClienteCoincideNoLanzaExcepcion(): void
    {
        $_SESSION['operador_id'] = 1;
        GestorSesiones::vincularHuellaCliente();
        GestorSesiones::validarHuellaCliente();
        $this->assertTrue(true);
    }

    public function testValidarHuellaClienteNoCoincideLanzaExcepcion(): void
    {
        $_SESSION['operador_id'] = 1;
        $_SESSION['huella_seguridad_cliente'] = hash('sha256', '10.0.0.1' . 'OldAgent');
        $this->expectException(ErrorAutenticacion::class);
        GestorSesiones::validarHuellaCliente();
    }

    public function testFiltrarAgentesMaliciososConCurlLanzaExcepcion(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'curl/7.68.0';
        $this->expectException(ErrorSeguridad::class);
        GestorSesiones::filtrarAgentesMaliciosos();
    }

    public function testFiltrarAgentesMaliciososConSqlmapLanzaExcepcion(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'sqlmap/1.5';
        $this->expectException(ErrorSeguridad::class);
        GestorSesiones::filtrarAgentesMaliciosos();
    }

    public function testFiltrarAgentesMaliciososConNavegadorNormal(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Chrome/120';
        GestorSesiones::filtrarAgentesMaliciosos();
        $this->assertTrue(true);
    }

    public function testFiltrarAgentesMaliciososUserAgentVacio(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = '';
        GestorSesiones::filtrarAgentesMaliciosos();
        $this->assertTrue(true);
    }

    public function testRegistrarIncidenteSeguridad(): void
    {
        GestorSesiones::registrarIncidenteSeguridad('Intento de acceso no autorizado');
        $this->assertTrue(true);
    }
}
