<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Middleware;

use PHPUnit\Framework\TestCase;
use LiteFramework\Middleware\RendimientoInterceptor;

class RendimientoInterceptorTest extends TestCase
{
    public function testManejarEjecutaSiguiente(): void
    {
        $interceptor = new RendimientoInterceptor();
        $ejecutado = false;

        $resultado = $interceptor->manejar(['test' => true], function ($params) use (&$ejecutado) {
            $ejecutado = true;
            $this->assertTrue($params['test']);
            return 'ok';
        });

        $this->assertTrue($ejecutado);
        $this->assertSame('ok', $resultado);
    }

    public function testHeadersNoSentEnCli(): void
    {
        $interceptor = new RendimientoInterceptor();

        $resultado = $interceptor->manejar([], function () {
            return 'resultado';
        });

        $this->assertSame('resultado', $resultado);
        $this->assertFalse(headers_sent());
    }

    public function testMideTiempoReal(): void
    {
        $interceptor = new RendimientoInterceptor();

        $resultado = $interceptor->manejar([], function () {
            usleep(1000);
            return 'lento';
        });

        $this->assertSame('lento', $resultado);
    }

    public function testMultipleLlamadas(): void
    {
        $interceptor = new RendimientoInterceptor();
        $contador = 0;

        for ($i = 0; $i < 3; $i++) {
            $interceptor->manejar([], function () use (&$contador) {
                $contador++;
                return $contador;
            });
        }

        $this->assertSame(3, $contador);
    }

    public function testUmbralLentoNoBloquea(): void
    {
        $interceptor = new RendimientoInterceptor(1);

        $resultado = $interceptor->manejar([], function () {
            usleep(2000);
            return 'ok';
        });

        $this->assertSame('ok', $resultado);
    }

    public function testInterceptorNoModificaParams(): void
    {
        $interceptor = new RendimientoInterceptor();

        $params = ['clave' => 'valor'];
        $interceptor->manejar($params, function ($p) {
            $this->assertSame('valor', $p['clave']);
            return 'ok';
        });

        $this->assertSame('valor', $params['clave']);
    }

    public function testUmbralCeroLoggeaSiempre(): void
    {
        $interceptor = new RendimientoInterceptor(0);

        $resultado = $interceptor->manejar([], function () {
            return 'rapido';
        });

        $this->assertSame('rapido', $resultado);
    }
}
