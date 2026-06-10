<?php
use PHPUnit\Framework\TestCase;

class EnrutadorTest extends TestCase {
    private Enrutador $enrutador;

    protected function setUp(): void {
        $this->enrutador = new Enrutador();
        Enrutador::registrarInstancia($this->enrutador);
    }

    public function testRegistrarYDespacharGet(): void {
        $this->enrutador->get('/test', fn() => 'ok');
        $resultado = $this->enrutador->despachar('GET', '/test');
        $this->assertEquals('ok', $resultado);
    }

    public function testRegistrarYDespacharPost(): void {
        $this->enrutador->post('/test', fn() => 'creado');
        $resultado = $this->enrutador->despachar('POST', '/test');
        $this->assertEquals('creado', $resultado);
    }

    public function testRutaConParametro(): void {
        $this->enrutador->get('/usuario/{id}', fn($params) => "user-" . ($params['id'] ?? ''));
        $resultado = $this->enrutador->despachar('GET', '/usuario/42');
        $this->assertEquals('user-42', $resultado);
    }

    public function testRutaConMultiplesParametros(): void {
        $this->enrutador->get('/categoria/{cat}/producto/{id}', fn($params) => ($params['cat'] ?? '') . '-' . ($params['id'] ?? ''));
        $resultado = $this->enrutador->despachar('GET', '/categoria/electro/producto/5');
        $this->assertEquals('electro-5', $resultado);
    }

    public function testRutaNoEncontrada(): void {
        $resultado = $this->enrutador->despachar('GET', '/no-existe');
        $this->assertFalse($resultado);
    }

    public function testMetodoNoPermitido(): void {
        $this->enrutador->get('/test', fn() => 'ok');
        $resultado = $this->enrutador->despachar('POST', '/test');
        $this->assertFalse($resultado);
    }

    public function testRutaNombrada(): void {
        $this->enrutador->get('/test', fn() => 'ok')->nombre('test.route');
        $url = Enrutador::url('test.route');
        $this->assertStringContainsString('/test', $url);
    }

    public function testRutaNombradaConParametros(): void {
        $this->enrutador->get('/usuario/{id}', fn($id) => "user-$id")->nombre('usuario');
        $url = Enrutador::url('usuario', ['id' => 42]);
        $this->assertStringContainsString('/usuario/42', $url);
    }

    public function testGrupoDeRutas(): void {
        $this->enrutador->grupo(['prefijo' => '/admin'], function($r) {
            $r->get('/dashboard', fn() => 'admin-dashboard');
            $r->get('/usuarios', fn() => 'admin-usuarios');
        });
        $this->assertEquals('admin-dashboard', $this->enrutador->despachar('GET', '/admin/dashboard'));
        $this->assertEquals('admin-usuarios', $this->enrutador->despachar('GET', '/admin/usuarios'));
    }

    public function testRutaConInterceptor(): void {
        $interceptor = new class {
            public function manejar($params, $siguiente) {
                return 'intercepted';
            }
        };
        $this->enrutador->get('/protegida', fn() => 'ok')->interceptor(get_class($interceptor));
        $resultado = $this->enrutador->despachar('GET', '/protegida');
        $this->assertEquals('intercepted', $resultado);
    }

    public function testRutaRaiz(): void {
        $this->enrutador->get('/', fn() => 'home');
        $resultado = $this->enrutador->despachar('GET', '/');
        $this->assertEquals('home', $resultado);
    }

    public function testRutaConSlashFinal(): void {
        $this->enrutador->get('/test', fn() => 'ok');
        $resultado = $this->enrutador->despachar('GET', '/test/');
        $this->assertEquals('ok', $resultado);
    }

    public function testMultiplesRutasDelMismoMetodo(): void {
        $this->enrutador->get('/a', fn() => 'A');
        $this->enrutador->get('/b', fn() => 'B');
        $this->enrutador->get('/c', fn() => 'C');
        $this->assertEquals('A', $this->enrutador->despachar('GET', '/a'));
        $this->assertEquals('B', $this->enrutador->despachar('GET', '/b'));
        $this->assertEquals('C', $this->enrutador->despachar('GET', '/c'));
    }
}
