<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Middleware;

use LiteFramework\Middleware\AutenticacionInterceptor;
use LiteFramework\Seguridad\GestorSesiones;

class AutenticacionInterceptorTest extends \TestBase
{
    private array $sessionBackup = [];
    private array $serverBackup = [];

    public static function setUpBeforeClass(): void
    {
        if (!defined('TESTS_RUNNING')) {
            define('TESTS_RUNNING', true);
        }
    }

    public function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? [];
        $this->serverBackup = $_SERVER ?? [];
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit-Test-Agent';
    }

    public function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = $this->sessionBackup;
        $_SERVER = $this->serverBackup;
    }

    private function establecerHuellaCliente(): void
    {
        $reflection = new \ReflectionMethod(GestorSesiones::class, 'obtenerSubredIp');
        $reflection->setAccessible(true);
        $subredIp = $reflection->invoke(null);
        $agente = $_SERVER['HTTP_USER_AGENT'] ?? 'AGENTE_DESCONOCIDO';
        $_SESSION['huella_seguridad_cliente'] = hash('sha256', $subredIp . $agente);
    }

    public function testManejarLlamaNextSiAutenticado(): void
    {
        $interceptor = new AutenticacionInterceptor();
        $_SESSION['operador_id'] = 1;
        $this->establecerHuellaCliente();
        $next = function () {
            return 'next_called';
        };
        $resultado = $interceptor->manejar([], $next);
        $this->assertSame('next_called', $resultado);
    }

    public function testManejarPasaParametros(): void
    {
        $interceptor = new AutenticacionInterceptor();
        $_SESSION['operador_id'] = 3;
        $this->establecerHuellaCliente();
        $next = function (array $params) {
            return $params['valor'];
        };
        $resultado = $interceptor->manejar(['valor' => 99], $next);
        $this->assertSame(99, $resultado);
    }

    public function testManejarImplementaInterceptor(): void
    {
        $interceptor = new AutenticacionInterceptor();
        $this->assertInstanceOf(\LiteFramework\Nucleo\Interceptor::class, $interceptor);
    }

    public function testManejarMultipleNextLlamadas(): void
    {
        $interceptor = new AutenticacionInterceptor();
        $_SESSION['operador_id'] = 5;
        $this->establecerHuellaCliente();
        $contador = 0;
        $next = function () use (&$contador) {
            $contador++;
            return $contador;
        };
        $this->assertSame(1, $interceptor->manejar([], $next));
        $this->assertSame(2, $interceptor->manejar([], $next));
    }

    public function testManejarHuellaInvalidaLanzaError(): void
    {
        $interceptor = new AutenticacionInterceptor();
        $_SESSION['operador_id'] = 1;
        $_SESSION['huella_seguridad_cliente'] = hash('sha256', 'IP_ERRONEA/AgenteErroneo');
        $this->expectException(\LiteFramework\Nucleo\Excepciones\ErrorAutenticacion::class);
        $interceptor->manejar([], function () {
            return 'next';
        });
    }

    public function testManejarOperadorIdStringNoNumerico(): void
    {
        $interceptor = new AutenticacionInterceptor();
        $_SESSION['operador_id'] = 'abc';
        $this->establecerHuellaCliente();
        $next = function () {
            return 'ok';
        };
        $resultado = $interceptor->manejar([], $next);
        $this->assertSame('ok', $resultado);
    }

    public function testManejarIniciaSesion(): void
    {
        $interceptor = new AutenticacionInterceptor();
        $_SESSION['operador_id'] = 1;
        $this->establecerHuellaCliente();
        $interceptor->manejar([], function () {
            return 'ok';
        });
        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testManejarRedirigeSiNoAutenticado(): void
    {
        $interceptor = new class extends AutenticacionInterceptor {
            protected function redirigir(string $url): never {
                throw new \RuntimeException("Redirect: {$url}");
            }
        };
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/privilegios_insuficientes/');
        $interceptor->manejar([], fn() => 'next');
    }
}
