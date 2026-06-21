<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Servicios;

use LiteFramework\Servicios\GeneradorProyecto;

class GeneradorProyectoTest extends \TestBase
{
    private string $tmpDir;

    public function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/lf_proy_test_' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir, 0755, true);
    }

    public function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            $this->rmdirRecursive($this->tmpDir);
        }
    }

    private function rmdirRecursive(string $dir): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $archivo) {
            $archivo->isDir() ? @rmdir($archivo->getRealPath()) : @unlink($archivo->getRealPath());
        }
        @rmdir($dir);
    }

    private function definicionMinima(): array
    {
        return [
            'proyecto' => [
                'nombre' => 'TestApp',
                'codigo' => 'testapp',
                'descripcion' => 'App de prueba',
            ],
            'base_datos' => [
                'nombre' => 'test_db',
            ],
            'directorio_salida' => $this->tmpDir . '/salida',
            'modulos_activados' => ['inicio', 'panelControl'],
        ];
    }

    public function testDesdeJsonConArchivoValido(): void
    {
        $jsonFile = $this->tmpDir . '/def.json';
        file_put_contents($jsonFile, json_encode($this->definicionMinima()));

        GeneradorProyecto::desdeJson($jsonFile);
        $this->assertTrue(true);
    }

    public function testDesdeJsonConJsonInvalidoRetornaError(): void
    {
        $jsonFile = $this->tmpDir . '/invalido.json';
        file_put_contents($jsonFile, '{esto no es json}');

        $res = GeneradorProyecto::desdeJson($jsonFile);
        $this->assertFalse($res['exito']);
        $this->assertStringContainsString('JSON', $res['error']);
    }

    public function testDesdeJsonArchivoNoEncontradoRetornaError(): void
    {
        $res = GeneradorProyecto::desdeJson('/ruta/que/no/existe.json');
        $this->assertFalse($res['exito']);
        $this->assertStringContainsString('no encontrado', $res['error']);
    }

    public function testGenerarConDefinicionMinimaDevuelveResultado(): void
    {
        $def = $this->definicionMinima();
        $res = GeneradorProyecto::generar($def);
        $this->assertArrayHasKey('exito', $res);
        $this->assertArrayHasKey('pasos', $res);
        $this->assertArrayHasKey('resumen', $res);
    }

    public function testValidacionSinNombreRetornaError(): void
    {
        $def = $this->definicionMinima();
        unset($def['proyecto']['nombre']);
        $res = GeneradorProyecto::generar($def);
        $this->assertFalse($res['exito']);
    }

    public function testValidacionSinCodigoRetornaError(): void
    {
        $def = $this->definicionMinima();
        unset($def['proyecto']['codigo']);
        $res = GeneradorProyecto::generar($def);
        $this->assertFalse($res['exito']);
    }

    public function testValidacionSinBaseDatosRetornaError(): void
    {
        $def = $this->definicionMinima();
        unset($def['base_datos']);
        $res = GeneradorProyecto::generar($def);
        $this->assertFalse($res['exito']);
    }

    public function testValidacionSinModulosRetornaError(): void
    {
        $def = $this->definicionMinima();
        $def['modulos_activados'] = [];
        $res = GeneradorProyecto::generar($def);
        $this->assertFalse($res['exito']);
    }
}
