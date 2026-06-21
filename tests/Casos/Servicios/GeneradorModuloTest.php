<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Servicios;

use LiteFramework\Servicios\GeneradorModulo;

class GeneradorModuloTest extends \TestBase
{
    private string $tmpRaiz;

    public function setUp(): void
    {
        $this->tmpRaiz = sys_get_temp_dir() . '/lf_modulo_test_' . bin2hex(random_bytes(4));
        $dirs = [
            $this->tmpRaiz . '/rutas',
            $this->tmpRaiz . '/servidor/migraciones',
            $this->tmpRaiz . '/servidor/modelos',
            $this->tmpRaiz . '/servidor/api/controladores',
            $this->tmpRaiz . '/src/modulos/test',
            $this->tmpRaiz . '/src/js/modulos',
        ];
        foreach ($dirs as $d) {
            mkdir($d, 0755, true);
        }
        file_put_contents($this->tmpRaiz . '/rutas/web.php', "<?php\n\n");
        file_put_contents($this->tmpRaiz . '/servidor/autoload.php', "<?php\n\n\$mapa = [];\n");
    }

    public function tearDown(): void
    {
        $this->rmdirRecursive($this->tmpRaiz);
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $archivo) {
            $archivo->isDir() ? @rmdir($archivo->getRealPath()) : @unlink($archivo->getRealPath());
        }
        @rmdir($dir);
    }

    public function testGenerarCreaMigracionConCreateTable(): void
    {
        $res = GeneradorModulo::generarEn($this->tmpRaiz, 'Producto', [['nombre' => 'nombre', 'tipo' => 'string']]);
        $archivos = array_column($res['archivos'], 'ruta');
        $this->assertNotEmpty($archivos);

        $migracion = glob($this->tmpRaiz . '/servidor/migraciones/*.sql');
        $this->assertNotEmpty($migracion);
        $contenido = file_get_contents($migracion[0]);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS', $contenido);
    }

    public function testGenerarCreaModelo(): void
    {
        GeneradorModulo::generarEn($this->tmpRaiz, 'Categoria', [['nombre' => 'nombre', 'tipo' => 'string']]);
        $archivo = $this->tmpRaiz . '/servidor/modelos/Categoria.php';
        $this->assertFileExists($archivo);
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('class Categoria extends Modelo', $contenido);
    }

    public function testGenerarCreaControlador(): void
    {
        GeneradorModulo::generarEn($this->tmpRaiz, 'Cliente', [['nombre' => 'correo', 'tipo' => 'email']]);
        $archivo = $this->tmpRaiz . '/servidor/api/controladores/ClienteControlador.php';
        $this->assertFileExists($archivo);
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('class ClienteControlador', $contenido);
    }

    public function testGenerarCreaVista(): void
    {
        GeneradorModulo::generarEn($this->tmpRaiz, 'Factura', [['nombre' => 'total', 'tipo' => 'decimal']]);
        $archivo = $this->tmpRaiz . '/src/modulos/factura/factura.php';
        $this->assertFileExists($archivo);
    }

    public function testGenerarCreaJs(): void
    {
        GeneradorModulo::generarEn($this->tmpRaiz, 'Pedido', [['nombre' => 'estado', 'tipo' => 'string']]);
        $archivo = $this->tmpRaiz . '/src/js/modulos/pedido.js';
        $this->assertFileExists($archivo);
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('TABLA', $contenido);
    }

    public function testGenerarConCamposPrefijados(): void
    {
        $campos = [
            ['nombre' => 'campo_extra', 'tipo' => 'text'],
        ];
        $res = GeneradorModulo::generarEn($this->tmpRaiz, 'Extra', $campos);
        $this->assertTrue($res['exito']);
    }

    public function testParsearCamposDesdeArgs(): void
    {
        $ref = new \ReflectionMethod(GeneradorModulo::class, 'parsearCamposDesdeArgs');
        $ref->setAccessible(true);
        $resultado = $ref->invoke(null, ['nombre:string:required', 'edad:int']);
        $this->assertCount(2, $resultado);
        $this->assertSame('nombre', $resultado[0]['nombre']);
        $this->assertSame('string', $resultado[0]['tipo']);
        $this->assertSame('required', $resultado[0]['reglas']);
        $this->assertSame('edad', $resultado[1]['nombre']);
        $this->assertSame('int', $resultado[1]['tipo']);
    }

    public function testCampoConFormatoInvalidoMantieneString(): void
    {
        $ref = new \ReflectionMethod(GeneradorModulo::class, 'parsearCamposDesdeArgs');
        $ref->setAccessible(true);
        $resultado = $ref->invoke(null, ['solo_nombre']);
        $this->assertSame('solo_nombre', $resultado[0]['nombre']);
        $this->assertSame('string', $resultado[0]['tipo']);
    }

    public function testGenerarEnDirectorioRaizFallback(): void
    {
        $res = GeneradorModulo::generarEn($this->tmpRaiz, 'Test', [['nombre' => 'nombre', 'tipo' => 'string']]);
        $this->assertTrue($res['exito']);
    }

    public function testGenerarEnTempVerificaArchivosCreados(): void
    {
        $res = GeneradorModulo::generarEn($this->tmpRaiz, 'Item', [
            ['nombre' => 'nombre', 'tipo' => 'string', 'reglas' => 'required'],
            ['nombre' => 'precio', 'tipo' => 'decimal'],
        ]);
        $this->assertTrue($res['exito']);

        $archivosCreados = array_filter($res['archivos'], fn($a) => $a['exito']);
        $this->assertGreaterThanOrEqual(5, count($archivosCreados));
    }
}
