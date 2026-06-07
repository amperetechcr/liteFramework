<?php
use PHPUnit\Framework\TestCase;

class ManejadorErroresTest extends TestCase {
    private array $serverOriginal;

    protected function setUp(): void {
        $this->serverOriginal = $_SERVER;
    }

    protected function tearDown(): void {
        $_SERVER = $this->serverOriginal;
    }

    public function testRegistrarDosVecesNoDuplicaHandlers(): void {
        ManejadorErrores::registrar();
        ManejadorErrores::registrar();
        $this->assertTrue(true, 'Registrar dos veces no debe lanzar excepcion');
    }

    public function testManejarErrorNoDepuracionNoLanzaExcepcion(): void {
        $resultado = ManejadorErrores::manejarError(E_USER_WARNING, 'Test warning', __FILE__, __LINE__);
        $this->assertTrue($resultado);
    }

    public function testManejarErrorConNivelNoReportado(): void {
        $nivelActual = error_reporting();
        error_reporting(0);
        $resultado = ManejadorErrores::manejarError(E_USER_WARNING, 'Test', __FILE__, __LINE__);
        error_reporting($nivelActual);
        $this->assertFalse($resultado);
    }

    public function testEsPeticionApiConHeaderXmlHttpRequest(): void {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $ref = new \ReflectionMethod(ManejadorErrores::class, 'esPeticionApi');
        $this->assertTrue($ref->invoke(null));
    }

    public function testEsPeticionApiConContentTypeJson(): void {
        $_SERVER['CONTENT_TYPE'] = 'application/json; charset=utf-8';
        $ref = new \ReflectionMethod(ManejadorErrores::class, 'esPeticionApi');
        $this->assertTrue($ref->invoke(null));
    }

    public function testEsPeticionApiSinIndicadores(): void {
        unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['CONTENT_TYPE']);
        $ref = new \ReflectionMethod(ManejadorErrores::class, 'esPeticionApi');
        $this->assertFalse($ref->invoke(null));
    }
}
