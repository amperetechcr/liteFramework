<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use LiteFramework\Nucleo\Helpers\AyudanteCache as Cache;

class CacheTest extends \TestBase
{
    private string $directorioCache;

    public function setUp(): void
    {
        $this->directorioCache = DIRECTORIO_RAIZ . '/storage/cache';
        if (!is_dir($this->directorioCache)) {
            mkdir($this->directorioCache, 0755, true);
        }
        Cache::limpiar();
    }

    public function tearDown(): void
    {
        Cache::limpiar();
    }

    public function testGuardarYObtener(): void
    {
        $this->assertTrue(Cache::guardar('test_clave', 'valor_test'));
        $this->assertSame('valor_test', Cache::obtener('test_clave'));
    }

    public function testGuardarYObtenerEntero(): void
    {
        Cache::guardar('numero', 42, 60);
        $this->assertSame(42, Cache::obtener('numero'));
    }

    public function testGuardarYObtenerArreglo(): void
    {
        $datos = ['a' => 1, 'b' => 2];
        Cache::guardar('arreglo', $datos, 60);
        $this->assertSame($datos, Cache::obtener('arreglo'));
    }

    public function testObtenerClaveInexistente(): void
    {
        $this->assertNull(Cache::obtener('clave_inexistente'));
    }

    public function testTiene(): void
    {
        Cache::guardar('existe', 1);
        $this->assertTrue(Cache::tiene('existe'));
        $this->assertFalse(Cache::tiene('no_existe'));
    }

    public function testOlvidar(): void
    {
        Cache::guardar('temp', 'valor');
        $this->assertTrue(Cache::tiene('temp'));
        $this->assertTrue(Cache::olvidar('temp'));
        $this->assertFalse(Cache::tiene('temp'));
    }

    public function testRecordarEjecutaGenerador(): void
    {
        $contador = 0;
        $resultado = Cache::recordar('rec_test', function () use (&$contador) {
            $contador++;
            return 42;
        });
        $this->assertSame(42, $resultado);
        $this->assertSame(1, $contador);

        $resultado2 = Cache::recordar('rec_test', function () use (&$contador) {
            $contador++;
            return 99;
        });
        $this->assertSame(42, $resultado2);
        $this->assertSame(1, $contador);
    }

    public function testRecordarJson(): void
    {
        $resultado = Cache::recordarJson('json_test', fn() => ['x' => 10]);
        $this->assertSame(['x' => 10], $resultado);
    }

    public function testRecordarJsonConGeneradorNoArray(): void
    {
        $resultado = Cache::recordarJson('noarray', fn() => null);
        $this->assertSame([], $resultado);
    }

    public function testRecordarResultadosPaginados(): void
    {
        $llamadas = 0;
        $resultado = Cache::recordarResultadosPaginados('lista', 1, 20, function () use (&$llamadas) {
            $llamadas++;
            return ['items' => [1, 2]];
        });
        $this->assertSame(['items' => [1, 2]], $resultado);
        $this->assertSame(1, $llamadas);

        Cache::recordarResultadosPaginados('lista', 1, 20, function () use (&$llamadas) {
            $llamadas++;
            return ['items' => [3, 4]];
        });
        $this->assertSame(1, $llamadas);
    }

    public function testOlvidarPorPrefijo(): void
    {
        Cache::guardar('usr_1', 'a');
        Cache::guardar('usr_2', 'b');
        Cache::guardar('cfg_x', 'c');

        $eliminadas = Cache::olvidarPorPrefijo('usr_');
        $this->assertSame(2, $eliminadas);
        $this->assertFalse(Cache::tiene('usr_1'));
        $this->assertFalse(Cache::tiene('usr_2'));
        $this->assertTrue(Cache::tiene('cfg_x'));
    }

    public function testLimpiar(): void
    {
        Cache::guardar('a', 1);
        Cache::guardar('b', 2);
        $this->assertTrue(Cache::limpiar());
        $this->assertFalse(Cache::tiene('a'));
        $this->assertFalse(Cache::tiene('b'));
    }

    public function testInfo(): void
    {
        $info = Cache::info();
        $this->assertArrayHasKey('apcu', $info);
        $this->assertArrayHasKey('memoria', $info);
        $this->assertArrayHasKey('archivos', $info);
        $this->assertIsBool($info['apcu']);
        $this->assertIsInt($info['memoria']);
        $this->assertIsInt($info['archivos']);
    }

    public function testClaveEsSanitizada(): void
    {
        Cache::guardar('ruta/con/separadores', 'valor');
        $this->assertSame('valor', Cache::obtener('ruta/con/separadores'));
    }
}
