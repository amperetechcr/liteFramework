<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use PHPUnit\Framework\TestCase;
use LiteFramework\Nucleo\Helpers\AyudanteCache;

class AyudanteCacheTest extends TestCase
{
    protected function setUp(): void
    {
        AyudanteCache::limpiar();
    }

    protected function tearDown(): void
    {
        AyudanteCache::limpiar();
    }

    public function testGuardarYObtener(): void
    {
        AyudanteCache::guardar('test_clave', 'valor_prueba');
        $this->assertSame('valor_prueba', AyudanteCache::obtener('test_clave'));
    }

    public function testObtenerDevuelveNullSiNoExiste(): void
    {
        $this->assertNull(AyudanteCache::obtener('no_existe'));
    }

    public function testTieneDevuelveTrueSiExiste(): void
    {
        AyudanteCache::guardar('existe_test', 123);
        $this->assertTrue(AyudanteCache::tiene('existe_test'));
    }

    public function testTieneDevuelveFalseSiNoExiste(): void
    {
        $this->assertFalse(AyudanteCache::tiene('no_existe'));
    }

    public function testOlvidarEliminaClave(): void
    {
        AyudanteCache::guardar('eliminar_test', 'valor');
        $this->assertTrue(AyudanteCache::tiene('eliminar_test'));
        AyudanteCache::olvidar('eliminar_test');
        $this->assertFalse(AyudanteCache::tiene('eliminar_test'));
    }

    public function testRecordarEjecutaCallableSiNoExiste(): void
    {
        $ejecutado = false;
        $resultado = AyudanteCache::recordar('generar_test', function () use (&$ejecutado) {
            $ejecutado = true;
            return 'generado';
        });
        $this->assertTrue($ejecutado);
        $this->assertSame('generado', $resultado);
    }

    public function testRecordarNoEjecutaCallableSiYaExiste(): void
    {
        AyudanteCache::guardar('cacheado_test', 'original');
        $ejecutado = false;
        $resultado = AyudanteCache::recordar('cacheado_test', function () use (&$ejecutado) {
            $ejecutado = true;
            return 'nuevo';
        });
        $this->assertFalse($ejecutado);
        $this->assertSame('original', $resultado);
    }

    public function testRecordarJsonDevuelveArray(): void
    {
        $resultado = AyudanteCache::recordarJson('json_test', function () {
            return ['clave' => 'valor', 'numero' => 42];
        });
        $this->assertIsArray($resultado);
        $this->assertSame('valor', $resultado['clave']);
        $this->assertSame(42, $resultado['numero']);
    }

    public function testRecordarJsonDevuelveArrayVacioSiNoEsArray(): void
    {
        $resultado = AyudanteCache::recordarJson('no_array', function () {
            return 'texto';
        });
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function testGuardarYTiposDatos(): void
    {
        AyudanteCache::guardar('int_val', 42);
        AyudanteCache::guardar('float_val', 3.14);
        AyudanteCache::guardar('bool_val', true);
        AyudanteCache::guardar('null_val', null);
        AyudanteCache::guardar('array_val', [1, 2, 3]);

        $this->assertSame(42, AyudanteCache::obtener('int_val'));
        $this->assertSame(3.14, AyudanteCache::obtener('float_val'));
        $this->assertTrue(AyudanteCache::obtener('bool_val'));
        $this->assertNull(AyudanteCache::obtener('null_val'));
        $this->assertSame([1, 2, 3], AyudanteCache::obtener('array_val'));
    }

    public function testSobrescribirClave(): void
    {
        AyudanteCache::guardar('sobre_test', 'primero');
        AyudanteCache::guardar('sobre_test', 'segundo');
        $this->assertSame('segundo', AyudanteCache::obtener('sobre_test'));
    }

    public function testLimpiarEliminaTodo(): void
    {
        AyudanteCache::guardar('clave1', 'valor1');
        AyudanteCache::guardar('clave2', 'valor2');
        AyudanteCache::guardar('clave3', 'valor3');
        $this->assertTrue(AyudanteCache::tiene('clave1'));

        AyudanteCache::limpiar();

        $this->assertFalse(AyudanteCache::tiene('clave1'));
        $this->assertFalse(AyudanteCache::tiene('clave2'));
        $this->assertFalse(AyudanteCache::tiene('clave3'));
    }

    public function testClaveConCaracteresEspeciales(): void
    {
        AyudanteCache::guardar('ruta/archivo:nombre.ext', 'valor_esp');
        $this->assertSame('valor_esp', AyudanteCache::obtener('ruta/archivo:nombre.ext'));
    }

    public function testRecordarResultadosPaginados(): void
    {
        $llamadas = 0;
        $resultado1 = AyudanteCache::recordarResultadosPaginados(
            'lista_usuarios',
            1,
            10,
            function () use (&$llamadas) {
                $llamadas++;
                return ['items' => ['a', 'b'], 'total' => 2];
            }
        );

        $this->assertSame(1, $llamadas);
        $this->assertSame(['items' => ['a', 'b'], 'total' => 2], $resultado1);

        $resultado2 = AyudanteCache::recordarResultadosPaginados(
            'lista_usuarios',
            1,
            10,
            function () use (&$llamadas) {
                $llamadas++;
                return ['items' => ['c', 'd'], 'total' => 2];
            }
        );

        $this->assertSame(1, $llamadas);
    }

    public function testOlvidarPorPrefijo(): void
    {
        AyudanteCache::guardar('user_1_perfil', 'datos1');
        AyudanteCache::guardar('user_2_perfil', 'datos2');
        AyudanteCache::guardar('user_1_lista', 'datos3');
        AyudanteCache::guardar('config_app', 'datos4');

        $eliminadas = AyudanteCache::olvidarPorPrefijo('user_1');
        $this->assertSame(2, $eliminadas);
        $this->assertFalse(AyudanteCache::tiene('user_1_perfil'));
        $this->assertFalse(AyudanteCache::tiene('user_1_lista'));
        $this->assertTrue(AyudanteCache::tiene('user_2_perfil'));
        $this->assertTrue(AyudanteCache::tiene('config_app'));
    }

    public function testInfo(): void
    {
        $info = AyudanteCache::info();
        $this->assertArrayHasKey('apcu', $info);
        $this->assertArrayHasKey('memoria', $info);
        $this->assertArrayHasKey('archivos', $info);
        $this->assertIsBool($info['apcu']);
        $this->assertIsInt($info['memoria']);
        $this->assertIsInt($info['archivos']);
    }

    public function testCachePersisteEnArchivo(): void
    {
        AyudanteCache::guardar('persistencia_test', 'valor_persistente', 60);

        $ref = new \ReflectionClass(AyudanteCache::class);
        $memoriaProp = $ref->getProperty('memoria');
        $memoriaProp->setAccessible(true);
        $memoriaProp->setValue([]);

        $this->assertSame('valor_persistente', AyudanteCache::obtener('persistencia_test'));
    }
}
