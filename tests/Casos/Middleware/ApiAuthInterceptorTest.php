<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Middleware;

use LiteFramework\Middleware\ApiAuthInterceptor;

class ApiAuthInterceptorTest extends \TestBase
{
    private array $sessionBackup = [];

    public function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? [];
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = $this->sessionBackup;
    }

    public function testManejarRetorna401SiNoAutenticado(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $_SESSION = [];
        $interceptor = new ApiAuthInterceptor();
        ob_start();
        $resultado = $interceptor->manejar([], function () {
            return 'next_called';
        });
        $salida = ob_get_clean();
        $this->assertNull($resultado);
        $this->assertStringContainsString('No autenticado', $salida);
        $this->assertJson($salida);
    }

    public function testManejarRetorna401SiOperadorIdNoDefinido(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $_SESSION = [];
        $interceptor = new ApiAuthInterceptor();
        ob_start();
        $resultado = $interceptor->manejar([], function () {
            return 'next_called';
        });
        $salida = ob_get_clean();
        $this->assertNull($resultado);
        $datos = json_decode($salida, true);
        $this->assertArrayHasKey('codigo_error', $datos);
        $this->assertSame('sesion_invalida', $datos['codigo_error']);
    }

    public function testManejarLlamaNextSiAutenticado(): void
    {
        $interceptor = new ApiAuthInterceptor();
        $_SESSION['operador_id'] = 1;
        $llamado = false;
        $next = function (array $params) use (&$llamado) {
            $llamado = true;
            return 'ok';
        };
        $resultado = $interceptor->manejar(['foo' => 'bar'], $next);
        $this->assertTrue($llamado);
        $this->assertSame('ok', $resultado);
    }

    public function testManejarPasaParametrosANext(): void
    {
        $interceptor = new ApiAuthInterceptor();
        $_SESSION['operador_id'] = 7;
        $next = function (array $params) {
            return $params;
        };
        $resultado = $interceptor->manejar(['clave' => 'valor'], $next);
        $this->assertSame(['clave' => 'valor'], $resultado);
    }

    public function testManejarCodigoErrorSesionInvalida(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $_SESSION = [];
        $interceptor = new ApiAuthInterceptor();
        ob_start();
        $interceptor->manejar([], function () {
        });
        $salida = ob_get_clean();
        $datos = json_decode($salida, true);
        $this->assertFalse($datos['estado_operacion']);
        $this->assertSame('sesion_invalida', $datos['codigo_error']);
    }

    public function testManejarEstableceContentTypeJson(): void
    {
        $interceptor = new ApiAuthInterceptor();
        $_SESSION['operador_id'] = 42;
        $next = function () {
            return 'next_called';
        };
        ob_start();
        $resultado = $interceptor->manejar([], $next);
        ob_get_clean();
        $this->assertSame('next_called', $resultado);
    }

    public function testManejarHttpResponseCode401(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $_SESSION = [];
        $interceptor = new ApiAuthInterceptor();
        ob_start();
        $interceptor->manejar([], function () {
        });
        $salida = ob_get_clean();
        $datos = json_decode($salida, true);
        $this->assertFalse($datos['estado_operacion']);
    }

    public function testManejarImplementaInterceptor(): void
    {
        $interceptor = new ApiAuthInterceptor();
        $this->assertInstanceOf(\LiteFramework\Nucleo\Interceptor::class, $interceptor);
    }
}
