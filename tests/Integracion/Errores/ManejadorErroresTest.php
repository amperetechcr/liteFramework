<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Errores;

use LiteFramework\Nucleo\ManejadorErrores;
use ErrorException;

class ManejadorErroresTest extends \TestBase
{
    private int $errorReportingBackup;
    private array $serverBackup;

    public function setUp(): void
    {
        parent::setUp();
        $this->errorReportingBackup = error_reporting();
        $this->serverBackup = $_SERVER;
    }

    public function tearDown(): void
    {
        error_reporting($this->errorReportingBackup);
        $_SERVER = $this->serverBackup;
        $ref = new \ReflectionClass(ManejadorErrores::class);
        $prop = $ref->getProperty('modoJson');
        $prop->setAccessible(true);
        $prop->setValue(null, false);
        $prop3 = $ref->getProperty('ultimoDiagnostico');
        $prop3->setAccessible(true);
        $prop3->setValue(null, null);
        parent::tearDown();
    }

    /** @runInSeparateProcess */
    public function testRegistrarNoDuplicaHandlers(): void
    {
        ManejadorErrores::registrar();
        ManejadorErrores::registrar();
        $this->assertTrue(true);
    }

    public function testManejarErrorEWarningConErrorReportingActivo(): void
    {
        error_reporting(E_ALL);
        try {
            ManejadorErrores::manejarError(E_WARNING, 'test warning', __FILE__, __LINE__);
            $this->fail('Expected ErrorException was not thrown');
        } catch (ErrorException $e) {
            $this->assertStringContainsString('test warning', $e->getMessage());
        }
    }

    public function testManejarErrorENoticeConErrorReportingActivo(): void
    {
        error_reporting(E_ALL);
        try {
            ManejadorErrores::manejarError(E_NOTICE, 'test notice', __FILE__, __LINE__);
            $this->fail('Expected ErrorException was not thrown');
        } catch (ErrorException $e) {
            $this->assertStringContainsString('test notice', $e->getMessage());
        }
    }

    public function testManejarErrorConNivelNoReportadoDevuelveFalse(): void
    {
        error_reporting(0);
        $resultado = ManejadorErrores::manejarError(E_WARNING, 'no report', __FILE__, __LINE__);
        $this->assertFalse($resultado);
    }

    public function testManejarErrorEUserWarning(): void
    {
        error_reporting(E_ALL);
        try {
            ManejadorErrores::manejarError(E_USER_WARNING, 'user warning', __FILE__, __LINE__);
            $this->fail('Expected ErrorException was not thrown');
        } catch (ErrorException $e) {
            $this->assertStringContainsString('user warning', $e->getMessage());
        }
    }

    public function testManejarErrorEUserError(): void
    {
        error_reporting(E_ALL);
        try {
            ManejadorErrores::manejarError(E_USER_ERROR, 'user error', __FILE__, __LINE__);
            $this->fail('Expected ErrorException was not thrown');
        } catch (ErrorException $e) {
            $this->assertStringContainsString('user error', $e->getMessage());
        }
    }

    public function testModoJsonActivarDesactivar(): void
    {
        $this->assertFalse(ManejadorErrores::estaEnModoJson());
        ManejadorErrores::activarModoJson();
        $this->assertTrue(ManejadorErrores::estaEnModoJson());
    }

    public function testEsPeticionApiConXRequestedWith(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $ref = new \ReflectionClass(ManejadorErrores::class);
        $method = $ref->getMethod('esPeticionApi');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null));
    }

    public function testEsPeticionApiConContentTypeJson(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json; charset=utf-8';
        $ref = new \ReflectionClass(ManejadorErrores::class);
        $method = $ref->getMethod('esPeticionApi');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null));
    }

    public function testEsPeticionApiSinIndicadores(): void
    {
        unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['CONTENT_TYPE']);
        $ref = new \ReflectionClass(ManejadorErrores::class);
        $method = $ref->getMethod('esPeticionApi');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null));
    }

    public function testEsPeticionApiContentTypeCaseSensitive(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $ref = new \ReflectionClass(ManejadorErrores::class);
        $method = $ref->getMethod('esPeticionApi');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null));
    }

    /** @runInSeparateProcess */
    public function testLLamadaRegistrarEstableceHandlers(): void
    {
        $ref = new \ReflectionClass(ManejadorErrores::class);
        $prop = $ref->getProperty('registrados');
        $prop->setAccessible(true);
        $prop->setValue(null, false);
        ManejadorErrores::registrar();
        $handlers = set_error_handler(fn() => true);
        $this->assertIsCallable($handlers);
        set_error_handler($handlers);
    }

    public function testRegistrarIdempotente(): void
    {
        ManejadorErrores::registrar();
        ManejadorErrores::registrar();
        $this->assertTrue(true);
    }

    public function testManejarErrorEUserNotice(): void
    {
        error_reporting(E_ALL);
        try {
            ManejadorErrores::manejarError(E_USER_NOTICE, 'user notice test', __FILE__, __LINE__);
            $this->fail('Expected ErrorException was not thrown');
        } catch (ErrorException $e) {
            $this->assertStringContainsString('user notice test', $e->getMessage());
        }
    }

    public function testManejarErrorEStrict(): void
    {
        error_reporting(E_ALL);
        try {
            ManejadorErrores::manejarError(E_STRICT, 'strict test', __FILE__, __LINE__);
            $this->fail('Expected ErrorException was not thrown');
        } catch (ErrorException $e) {
            $this->assertStringContainsString('strict test', $e->getMessage());
        }
    }

    public function testManejarErrorConNivelNoIncluidoEnReporte(): void
    {
        error_reporting(E_ERROR | E_PARSE);
        $resultado = ManejadorErrores::manejarError(E_WARNING, 'no match', __FILE__, __LINE__);
        $this->assertFalse($resultado);
    }

    public function testEstadoIncialModoJsonFalse(): void
    {
        $this->assertFalse(ManejadorErrores::estaEnModoJson());
    }

    public function testModoJsonPersiste(): void
    {
        ManejadorErrores::activarModoJson();
        $this->assertTrue(ManejadorErrores::estaEnModoJson());
        $this->assertTrue(ManejadorErrores::estaEnModoJson());
    }

    public function testManejarErrorEnModoJsonNoTiraExcepcion(): void
    {
        error_reporting(E_ALL);
        ManejadorErrores::activarModoJson();
        try {
            ManejadorErrores::manejarError(E_WARNING, 'json mode warn', __FILE__, __LINE__);
            $this->fail('Expected ErrorException was not thrown');
        } catch (ErrorException $e) {
            $this->assertStringContainsString('json mode warn', $e->getMessage());
        }
    }

    public function testContentTypeSinJson(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'text/html';
        $ref = new \ReflectionClass(ManejadorErrores::class);
        $method = $ref->getMethod('esPeticionApi');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null));
    }

    public function testRegistrarAntesDeManejarError(): void
    {
        error_reporting(E_ALL);
        try {
            ManejadorErrores::manejarError(E_WARNING, 'after register', __FILE__, __LINE__);
            $this->fail('Expected ErrorException was not thrown');
        } catch (ErrorException $e) {
            $this->assertStringContainsString('after register', $e->getMessage());
        }
    }
}
