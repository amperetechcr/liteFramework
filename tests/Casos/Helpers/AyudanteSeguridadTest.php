<?php
use PHPUnit\Framework\TestCase;

class AyudanteSeguridadTest extends TestCase {
    private array $sesionOriginal;

    protected function setUp(): void {
        $this->sesionOriginal = $_SESSION ?? [];
    }

    protected function tearDown(): void {
        $_SESSION = $this->sesionOriginal;
    }

    public function testSesionActivaSinOperador(): void {
        $_SESSION = [];
        $this->assertFalse(Seguridad::sesionActiva());
    }

    public function testSesionActivaConOperador(): void {
        $_SESSION['operador_id'] = 1;
        $this->assertTrue(Seguridad::sesionActiva());
    }

    public function testIdOperadorSinSesion(): void {
        $_SESSION = [];
        $this->assertEquals(0, Seguridad::idOperador());
    }

    public function testIdOperadorConSesion(): void {
        $_SESSION['operador_id'] = 42;
        $this->assertEquals(42, Seguridad::idOperador());
    }

    public function testTokenCSRF(): void {
        $_SESSION['csrf_token'] = 'abc123';
        $this->assertEquals('abc123', Seguridad::tokenCSRF());
    }

    public function testTokenCSRFVacio(): void {
        $_SESSION = [];
        $this->assertEquals('', Seguridad::tokenCSRF());
    }

    public function testIpCliente(): void {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $this->assertEquals('192.168.1.1', Seguridad::ipCliente());
    }

    public function testIpClientePorDefecto(): void {
        unset($_SERVER['REMOTE_ADDR']);
        $this->assertEquals('0.0.0.0', Seguridad::ipCliente());
    }

    public function testAgenteUsuario(): void {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $this->assertEquals('Mozilla/5.0', Seguridad::agenteUsuario());
    }

    public function testAgenteUsuarioPorDefecto(): void {
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->assertEquals('desconocido', Seguridad::agenteUsuario());
    }

    public function testCsrfMeta(): void {
        $_SESSION['csrf_token'] = 'token123';
        $meta = Seguridad::csrfMeta();
        $this->assertStringContainsString('csrf-token', $meta);
        $this->assertStringContainsString('token123', $meta);
    }
}
