<?php
use PHPUnit\Framework\TestCase;

class GestorSesionesTest extends TestCase {
    private array $serverOriginal;
    private array $sesionOriginal;

    protected function setUp(): void {
        $this->serverOriginal = $_SERVER;
        $this->sesionOriginal = $_SESSION ?? [];
        $_SESSION = [];
    }

    protected function tearDown(): void {
        $_SERVER = $this->serverOriginal;
        $_SESSION = $this->sesionOriginal;
    }

    public function testVincularHuellaClienteAlmacenaHash(): void {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $_SESSION = [];

        GestorSesiones::vincularHuellaCliente();

        $this->assertNotEmpty($_SESSION['huella_seguridad_cliente']);
        $this->assertEquals(64, strlen($_SESSION['huella_seguridad_cliente']));
    }

    public function testVincularHuellaClienteEsDeterminista(): void {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $_SESSION = [];

        GestorSesiones::vincularHuellaCliente();
        $huella1 = $_SESSION['huella_seguridad_cliente'];

        $_SESSION = [];
        GestorSesiones::vincularHuellaCliente();
        $huella2 = $_SESSION['huella_seguridad_cliente'];

        $this->assertEquals($huella1, $huella2);
    }

    public function testVincularHuellaClienteCambiaConIpDistinta(): void {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $_SESSION = [];

        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        GestorSesiones::vincularHuellaCliente();
        $huella1 = $_SESSION['huella_seguridad_cliente'];

        $_SESSION = [];
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        GestorSesiones::vincularHuellaCliente();
        $huella2 = $_SESSION['huella_seguridad_cliente'];

        $this->assertNotEquals($huella1, $huella2);
    }

    public function testValidarHuellaClienteConHuellaCoincidenteNoHaceNada(): void {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $_SESSION['operador_id'] = 1;

        GestorSesiones::vincularHuellaCliente();
        $huella = $_SESSION['huella_seguridad_cliente'];

        $excepcion = null;
        try {
            GestorSesiones::validarHuellaCliente();
        } catch (\Throwable $e) {
            $excepcion = $e;
        }

        $this->assertNull($excepcion, 'No debe lanzar excepcion cuando la huella coincide');
        $this->assertNotEmpty($_SESSION['huella_seguridad_cliente']);
    }

    public function testValidarHuellaClienteSinOperadorNoHaceNada(): void {
        $_SESSION = [];
        $excepcion = null;
        try {
            GestorSesiones::validarHuellaCliente();
        } catch (\Throwable $e) {
            $excepcion = $e;
        }
        $this->assertNull($excepcion);
    }

    public function testFiltrarAgentesMaliciososConAgenteNormal(): void {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; rv:91.0) Gecko/20100101 Firefox/91.0';

        $excepcion = null;
        try {
            GestorSesiones::filtrarAgentesMaliciosos();
        } catch (\Throwable $e) {
            $excepcion = $e;
        }
        $this->assertNull($excepcion, 'No debe bloquear navegadores normales');
    }
}
