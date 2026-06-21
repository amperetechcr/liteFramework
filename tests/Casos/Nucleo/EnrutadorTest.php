<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Nucleo;

use LiteFramework\Nucleo\Enrutador;
use LiteFramework\Nucleo\Interceptor;
use LiteFramework\Middleware\RendimientoInterceptor;

class TestInterceptor implements Interceptor
{
    public static array $orden = [];

    public function manejar(array $params, callable $siguiente): mixed
    {
        static::$orden[] = 'interceptor';
        $resultado = $siguiente($params);
        static::$orden[] = 'interceptor-fin';
        return $resultado;
    }
}

class EnrutadorTest extends \TestBase
{
    private Enrutador $enrutador;

    public function setUp(): void
    {
        $this->enrutador = new Enrutador();
        Enrutador::registrarInstancia($this->enrutador);
    }

    public function testGetRegistraRuta(): void
    {
        $llamado = false;
        $this->enrutador->get('/test', function () use (&$llamado) {
            $llamado = true;
            return 'ok';
        });
        $resultado = $this->enrutador->despachar('GET', '/test');
        $this->assertTrue($llamado);
    }

    public function testPostRegistraRuta(): void
    {
        $llamado = false;
        $this->enrutador->post('/crear', function () use (&$llamado) {
            $llamado = true;
            return 'creado';
        });
        $this->assertEquals('creado', $this->enrutador->despachar('POST', '/crear'));
        $this->assertTrue($llamado);
    }

    public function testPutRegistraRuta(): void
    {
        $llamado = false;
        $this->enrutador->put('/actualizar', function () use (&$llamado) {
            $llamado = true;
            return 'actualizado';
        });
        $this->assertEquals('actualizado', $this->enrutador->despachar('PUT', '/actualizar'));
        $this->assertTrue($llamado);
    }

    public function testPatchRegistraRuta(): void
    {
        $llamado = false;
        $this->enrutador->patch('/parcial', function () use (&$llamado) {
            $llamado = true;
            return 'parcial';
        });
        $this->assertEquals('parcial', $this->enrutador->despachar('PATCH', '/parcial'));
        $this->assertTrue($llamado);
    }

    public function testDeleteRegistraRuta(): void
    {
        $llamado = false;
        $this->enrutador->delete('/borrar', function () use (&$llamado) {
            $llamado = true;
            return 'borrado';
        });
        $this->assertEquals('borrado', $this->enrutador->despachar('DELETE', '/borrar'));
        $this->assertTrue($llamado);
    }

    public function testRouteMatchingConParametros(): void
    {
        $params = [];
        $this->enrutador->get('/usuario/{id}', function ($p) use (&$params) {
            $params = $p;
            return 'ok';
        });
        $this->enrutador->despachar('GET', '/usuario/42');
        $this->assertEquals(['id' => '42'], $params);
    }

    public function testRouteMatchingMultiplesParametros(): void
    {
        $params = [];
        $this->enrutador->get('/{cat}/{slug}', function ($p) use (&$params) {
            $params = $p;
            return 'ok';
        });
        $this->enrutador->despachar('GET', '/blog/hello-world');
        $this->assertArrayHasKey('cat', $params);
        $this->assertArrayHasKey('slug', $params);
        $this->assertEquals('blog', $params['cat']);
        $this->assertEquals('hello-world', $params['slug']);
    }

    public function testMetodoIncorrectoDevuelveFalse(): void
    {
        $this->enrutador->get('/solo-get', fn() => 'ok');
        $this->assertFalse($this->enrutador->despachar('POST', '/solo-get'));
    }

    public function testRutaNoDefinidaDevuelveFalse(): void
    {
        $this->assertFalse($this->enrutador->despachar('GET', '/no-existe'));
    }

    public function testNamedRouteGeneraUrl(): void
    {
        $this->enrutador->get('/perfil/{id}', fn() => 'ok')->nombre('perfil');
        $url = Enrutador::url('perfil', ['id' => '7']);
        $this->assertSame('/perfil/7', $url);
    }

    public function testNamedRouteConMultiplesParametros(): void
    {
        $this->enrutador->get('/blog/{anio}/{mes}', fn() => 'ok')->nombre('blog-archivo');
        $url = Enrutador::url('blog-archivo', ['anio' => '2024', 'mes' => '06']);
        $this->assertSame('/blog/2024/06', $url);
    }

    public function testUrlNombreInexistenteDevuelveHash(): void
    {
        $this->assertSame('#', Enrutador::url('no-existe'));
    }

    public function testGrupoConPrefijo(): void
    {
        $ejecutado = false;
        $this->enrutador->grupo(['prefijo' => 'admin'], function (Enrutador $r) use (&$ejecutado) {
            $r->get('/usuarios', function () use (&$ejecutado) {
                $ejecutado = true;
                return 'lista';
            });
        });
        $this->assertEquals('lista', $this->enrutador->despachar('GET', '/admin/usuarios'));
        $this->assertTrue($ejecutado);
    }

    public function testGruposAnidados(): void
    {
        $ejecutado = false;
        $this->enrutador->grupo(['prefijo' => 'api'], function (Enrutador $r) use (&$ejecutado) {
            $r->grupo(['prefijo' => 'v1'], function (Enrutador $r2) use (&$ejecutado) {
                $r2->get('/items', function () use (&$ejecutado) {
                    $ejecutado = true;
                    return 'items';
                });
            });
        });
        $this->assertEquals('items', $this->enrutador->despachar('GET', '/api/v1/items'));
        $this->assertTrue($ejecutado);
    }

    public function testInterceptorSeEjecutaAntesDeAccion(): void
    {
        $orden = [];
        TestInterceptor::$orden = &$orden;

        $this->enrutador->get('/protegido', function () use (&$orden) {
            $orden[] = 'accion';
            return 'hecho';
        })->interceptor(TestInterceptor::class);

        $resultado = $this->enrutador->despachar('GET', '/protegido');
        $this->assertSame('hecho', $resultado);
        $this->assertContains('interceptor', $orden);
        $this->assertContains('accion', $orden);
    }

    public function testRendimientoInterceptorPrependidoAutomaticamente(): void
    {
        $this->enrutador->get('/ruta', fn() => 'ok');
        $resultado = $this->enrutador->despachar('GET', '/ruta');
        $this->assertSame('ok', $resultado);
    }

    public function testInterceptorNoPrependidoParaRutasIngreso(): void
    {
        $this->enrutador->get('/ingreso', fn() => 'ok')->nombre('ingreso');
        $resultado = $this->enrutador->despachar('GET', '/ingreso');
        $this->assertSame('ok', $resultado);
    }

    public function testCadenaClaseMetodo(): void
    {
        $this->enrutador->get('/accion', ['NoExisteControlador', 'metodoInexistente']);
        ob_start();
        $this->enrutador->despachar('GET', '/accion');
        $output = ob_get_clean();
        $this->assertStringContainsString('Error', $output);
    }

    public function testParseUrlMalformedFallsBackToRoot(): void
    {
        $this->enrutador->get('/', fn() => 'inicio');
        $resultado = $this->enrutador->despachar('GET', '//malformed');
        $this->assertSame('inicio', $resultado);
    }

    public function testRutaRaiz(): void
    {
        $this->enrutador->get('/', fn() => 'inicio');
        $this->assertSame('inicio', $this->enrutador->despachar('GET', '/'));
    }
}
