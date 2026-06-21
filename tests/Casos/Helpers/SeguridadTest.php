<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use LiteFramework\Nucleo\Helpers\Seguridad;

class SeguridadTest extends \TestBase
{
    private array $sessionBackup = [];
    private array $serverBackup = [];

    public function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->sessionBackup = $_SESSION;
        $this->serverBackup = $_SERVER;
    }

    public function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
        $_SERVER = $this->serverBackup;
    }

    public function testTokenCSRFConToken(): void
    {
        $_SESSION['csrf_token'] = 'token123';
        $this->assertSame('token123', Seguridad::tokenCSRF());
    }

    public function testTokenCSRFSinToken(): void
    {
        unset($_SESSION['csrf_token']);
        $this->assertSame('', Seguridad::tokenCSRF());
    }

    public function testSesionActivaConOperador(): void
    {
        $_SESSION['operador_id'] = 1;
        $this->assertTrue(Seguridad::sesionActiva());
    }

    public function testSesionActivaSinOperador(): void
    {
        unset($_SESSION['operador_id']);
        $this->assertFalse(Seguridad::sesionActiva());
    }

    public function testIdOperadorConSesion(): void
    {
        $_SESSION['operador_id'] = 3;
        $this->assertSame(3, Seguridad::idOperador());
    }

    public function testIdOperadorSinSesion(): void
    {
        unset($_SESSION['operador_id']);
        $this->assertSame(0, Seguridad::idOperador());
    }

    public function testCsrfMeta(): void
    {
        $_SESSION['csrf_token'] = 'abc123';
        $this->assertSame('<meta name="csrf-token" content="abc123">', Seguridad::csrfMeta());
    }

    public function testCsrfMetaEscapaCaracteres(): void
    {
        $_SESSION['csrf_token'] = '<script>';
        $this->assertStringContainsString('&lt;script&gt;', Seguridad::csrfMeta());
    }

    public function testCsrfMetaSinToken(): void
    {
        unset($_SESSION['csrf_token']);
        $this->assertSame('<meta name="csrf-token" content="">', Seguridad::csrfMeta());
    }

    public function testIpCliente(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $this->assertSame('192.168.1.1', Seguridad::ipCliente());
    }

    public function testIpClienteSinServer(): void
    {
        unset($_SERVER['REMOTE_ADDR']);
        $this->assertSame('0.0.0.0', Seguridad::ipCliente());
    }

    public function testAgenteUsuario(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $this->assertSame('Mozilla/5.0', Seguridad::agenteUsuario());
    }

    public function testAgenteUsuarioSinDatos(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->assertSame('desconocido', Seguridad::agenteUsuario());
    }

    public function testAgenteUsuarioTruncado(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = str_repeat('a', 300);
        $this->assertSame(255, strlen(Seguridad::agenteUsuario()));
    }

    public function testTienePermiso(): void
    {
        $this->assertIsBool(Seguridad::tienePermiso('cualquier.clave'));
    }
}
