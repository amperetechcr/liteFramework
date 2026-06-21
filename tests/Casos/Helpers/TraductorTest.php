<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use LiteFramework\Nucleo\Helpers\Traductor;

class TraductorTest extends \TestBase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('TESTS_RUNNING')) {
            define('TESTS_RUNNING', true);
        }
        Traductor::limpiarCache();
    }

    public function testDetectarCategoriaGenerarModulo(): void
    {
        $resultado = Traductor::detectarCategoria('Crear un modulo CRUD para Producto con campos: nombre:string, precio:decimal');
        $this->assertNotNull($resultado);
        $this->assertSame('generar_modulo', $resultado['categoria']);
    }

    public function testDetectarCategoriaSeguridadCsrf(): void
    {
        $resultado = Traductor::detectarCategoria('Validar token CSRF: abc123');
        $this->assertNotNull($resultado);
        $this->assertSame('seguridad', $resultado['categoria']);
    }

    public function testDetectarCategoriaFallbackGeneral(): void
    {
        $resultado = Traductor::detectarCategoria('texto sin coincidencia exacta');
        $this->assertNotNull($resultado);
    }

    public function testExtraerParametrosConUnParametro(): void
    {
        $parametros = Traductor::extraerParametros(
            'Validar token CSRF: token123',
            'Validar token CSRF: %s'
        );
        $this->assertNotNull($parametros);
        $this->assertCount(1, $parametros);
        $this->assertSame('token123', $parametros[0]);
    }

    public function testExtraerParametrosConMultiples(): void
    {
        $parametros = Traductor::extraerParametros(
            'Crear un modulo CRUD para Producto con campos: nombre:string',
            'Crear un modulo CRUD para %s con campos: %s'
        );
        $this->assertNotNull($parametros);
        $this->assertCount(2, $parametros);
        $this->assertSame('Producto', $parametros[0]);
    }

    public function testLlenarPlantillaIA(): void
    {
        $resultado = Traductor::llenarPlantillaIA(
            'generar modulo CRUD para ENTIDAD con campos: CAMPOS',
            ['Producto', 'nombre:string'],
            ['ENTIDAD', 'CAMPOS']
        );
        $this->assertSame('generar modulo CRUD para Producto con campos: nombre:string', $resultado);
    }

    public function testTraducirPromptCompleto(): void
    {
        $resultado = Traductor::traducir('Crear un modulo CRUD para Producto con campos: nombre:string');
        $this->assertTrue($resultado['exito']);
        $this->assertSame('generar_modulo', $resultado['categoria']);
        $this->assertStringContainsString('Producto', $resultado['prompt_traducido']);
    }

    public function testTraducirPromptGenerico(): void
    {
        $resultado = Traductor::traducir('esto es una consulta de prueba');
        $this->assertTrue($resultado['exito']);
        $this->assertStringContainsString($resultado['prompt_original'], $resultado['prompt_traducido']);
    }

    public function testCalibrar(): void
    {
        $resultado = Traductor::calibrar();
        $this->assertTrue($resultado['exito']);
        $this->assertIsInt($resultado['total_pruebas']);
        $this->assertIsFloat($resultado['precision_general']);
    }

    public function testHumanoAIaCache(): void
    {
        Traductor::limpiarCache();
        $resultado = Traductor::humanoAIa('Validar token CSRF: mitoken');
        $this->assertTrue($resultado['exito']);
        $this->assertSame('seguridad', $resultado['categoria']);

        $segundo = Traductor::humanoAIa('Validar token CSRF: mitoken');
        $this->assertTrue($segundo['exito']);
        $this->assertSame($resultado['prompt_traducido'], $segundo['prompt_traducido']);
    }

    public function testIaAHumano(): void
    {
        $resultado = Traductor::iaAHumano('generar_modulo', 'Crear modulo CRUD', ['Producto', 'nombre:string']);
        $this->assertStringContainsString('Producto', $resultado);
        $this->assertStringContainsString('nombre:string', $resultado);
    }

    public function testLimpiarCache(): void
    {
        Traductor::humanoAIa('test prompt');
        Traductor::limpiarCache();
        $this->assertTrue(true);
    }
}
