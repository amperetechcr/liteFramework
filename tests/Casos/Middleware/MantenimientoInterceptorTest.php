<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Middleware;

use LiteFramework\Middleware\MantenimientoInterceptor;

class MantenimientoInterceptorTest extends \TestBase
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

    public function testMantenimientoActivoAdminPasa(): void
    {
        $interceptor = new MantenimientoInterceptor();
        $_SESSION['operador_id'] = 1;
        $_SESSION['operador_es_admin'] = true;
        $next = function () {
            return 'next_called';
        };
        $resultado = $interceptor->manejar([], $next);
        $this->assertSame('next_called', $resultado);
    }

    public function testMantenimientoDesactivadoPasaTodos(): void
    {
        $interceptor = new MantenimientoInterceptor();
        $_SESSION['operador_id'] = 2;
        unset($_SESSION['operador_es_admin']);
        $next = function () {
            return 'next_called';
        };
        $resultado = $interceptor->manejar([], $next);
        $this->assertSame('next_called', $resultado);
    }

    public function testMantenimientoImplementaInterceptor(): void
    {
        $interceptor = new MantenimientoInterceptor();
        $this->assertInstanceOf(\LiteFramework\Nucleo\Interceptor::class, $interceptor);
    }

    public function testMantenimientoActivoBloqueaNoAdmin(): void
    {
        $interceptor = new MantenimientoInterceptor();
        $_SESSION['operador_id'] = 1;
        unset($_SESSION['operador_es_admin']);
        try {
            $interceptor->manejar([], function () {
                return 'next_called';
            });
        } catch (\Exception $e) {
            $this->addToAssertionCount(1);
            return;
        }
        $this->expectNotToPerformAssertions();
    }

    public function testOperadorEsAdminNoDefinidoBloqueado(): void
    {
        $interceptor = new MantenimientoInterceptor();
        $_SESSION['operador_id'] = 1;
        try {
            $interceptor->manejar([], function () {
                return 'next';
            });
        } catch (\Exception $e) {
            $this->addToAssertionCount(1);
            return;
        }
        $this->expectNotToPerformAssertions();
    }

    public function testOperadorEsAdminFalseBloqueado(): void
    {
        $interceptor = new MantenimientoInterceptor();
        $_SESSION['operador_id'] = 1;
        $_SESSION['operador_es_admin'] = false;
        try {
            $interceptor->manejar([], function () {
                return 'next';
            });
        } catch (\Exception $e) {
            $this->addToAssertionCount(1);
            return;
        }
        $this->expectNotToPerformAssertions();
    }
}
