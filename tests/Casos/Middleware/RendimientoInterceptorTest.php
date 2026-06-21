<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Middleware;

use LiteFramework\Middleware\RendimientoInterceptor;

class RendimientoInterceptorTest extends \TestBase
{
    private array $serverBackup = [];

    public function setUp(): void
    {
        $this->serverBackup = $_SERVER ?? [];
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        \LiteFramework\Nucleo\Helpers\AyudanteRendimiento::limpiar();
    }

    public function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        \LiteFramework\Nucleo\Helpers\AyudanteRendimiento::limpiar();
    }

    public function testManejarMideDuracion(): void
    {
        $interceptor = new RendimientoInterceptor();
        $next = function () {
            usleep(1000);
            return 'resultado';
        };
        $resultado = $interceptor->manejar([], $next);
        $this->assertSame('resultado', $resultado);
    }

    public function testManejarLlamaNext(): void
    {
        $interceptor = new RendimientoInterceptor();
        $llamado = false;
        $next = function () use (&$llamado) {
            $llamado = true;
            return 'ok';
        };
        $this->assertSame('ok', $interceptor->manejar([], $next));
        $this->assertTrue($llamado);
    }

    public function testManejarPasaParametros(): void
    {
        $interceptor = new RendimientoInterceptor();
        $next = function (array $params) {
            return $params['x'];
        };
        $this->assertSame(10, $interceptor->manejar(['x' => 10], $next));
    }

    public function testManejarUmbralLentoPersonalizado(): void
    {
        $interceptor = new RendimientoInterceptor(1, false);
        $next = function () {
            usleep(2000);
            return 'lento';
        };
        $this->assertSame('lento', $interceptor->manejar([], $next));
    }

    public function testManejarLoggearSiempre(): void
    {
        $interceptor = new RendimientoInterceptor(500, true);
        $next = function () {
            return 'rapido';
        };
        $this->assertSame('rapido', $interceptor->manejar([], $next));
    }

    public function testManejarImplementaInterceptor(): void
    {
        $interceptor = new RendimientoInterceptor();
        $this->assertInstanceOf(\LiteFramework\Nucleo\Interceptor::class, $interceptor);
    }

    public function testBytesLegiblesCero(): void
    {
        $reflection = new \ReflectionMethod(RendimientoInterceptor::class, 'bytesLegibles');
        $reflection->setAccessible(true);
        $resultado = $reflection->invoke(null, 0);
        $this->assertSame('0 B', $resultado);
    }

    public function testBytesLegiblesValores(): void
    {
        $reflection = new \ReflectionMethod(RendimientoInterceptor::class, 'bytesLegibles');
        $reflection->setAccessible(true);
        $this->assertStringContainsString('KB', $reflection->invoke(null, 2048));
        $this->assertStringContainsString('MB', $reflection->invoke(null, 2097152));
        $this->assertStringContainsString('GB', $reflection->invoke(null, 1073741824));
    }
}
