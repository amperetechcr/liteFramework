<?php
use PHPUnit\Framework\TestCase;

class GeneradorModuloTest extends TestCase {
    private string $dirTemporal;

    protected function setUp(): void {
        $this->dirTemporal = sys_get_temp_dir() . '/lf_test_' . uniqid();
        $dirs = [
            '/servidor/migraciones',
            '/servidor/modelos',
            '/servidor/api/controladores',
            '/src/modulos',
            '/src/js/modulos',
            '/rutas',
        ];
        foreach ($dirs as $d) {
            mkdir($this->dirTemporal . $d, 0755, true);
        }
        file_put_contents($this->dirTemporal . '/servidor/autoload.php', '<?php $mapa = [];');
        file_put_contents($this->dirTemporal . '/rutas/web.php', '<?php $enrutador = new Enrutador(); return $enrutador;');
    }

    protected function tearDown(): void {
        $this->rmdirRecursivo($this->dirTemporal);
    }

    public function testGenerarProducto(): void {
        $campos = [
            ['nombre' => 'nombre', 'tipo' => 'string', 'reglas' => 'required|unique'],
            ['nombre' => 'precio', 'tipo' => 'decimal', 'reglas' => 'required'],
        ];
        $resultado = GeneradorModulo::generarEn($this->dirTemporal, 'Producto', $campos);

        $this->assertTrue($resultado['exito']);
        $this->assertCount(7, $resultado['archivos']);
        $this->assertFileExists($this->dirTemporal . '/servidor/modelos/Producto.php');
        $this->assertFileExists($this->dirTemporal . '/servidor/api/controladores/ProductoControlador.php');
    }

    public function testGenerarClienteConCamposOpcionales(): void {
        $campos = [
            ['nombre' => 'nombre', 'tipo' => 'string', 'reglas' => 'required'],
            ['nombre' => 'correo', 'tipo' => 'email', 'reglas' => 'required|unique'],
            ['nombre' => 'activo', 'tipo' => 'bool', 'reglas' => ''],
        ];
        $resultado = GeneradorModulo::generarEn($this->dirTemporal, 'Cliente', $campos);

        $this->assertTrue($resultado['exito']);
        $this->assertFileExists($this->dirTemporal . '/servidor/modelos/Cliente.php');
        $this->assertFileExists($this->dirTemporal . '/src/js/modulos/cliente.js');
        $this->assertFileExists($this->dirTemporal . '/src/modulos/cliente/cliente.php');
    }

    public function testGenerarMigracionCreaArchivoSql(): void {
        $campos = [
            ['nombre' => 'titulo', 'tipo' => 'string', 'reglas' => 'required'],
        ];
        $resultado = GeneradorModulo::generarEn($this->dirTemporal, 'Articulo', $campos);

        $this->assertNotEmpty($resultado['archivos']);
        $archivoMigracion = $resultado['archivos'][0];
        $this->assertEquals('Migracion', $archivoMigracion['tipo']);
        $this->assertTrue($archivoMigracion['exito']);
    }

    public function testGenerarConCamposVaciosGeneraMigracionSinColumnasExtra(): void {
        $campos = [
            ['nombre' => 'nombre', 'tipo' => 'string', 'reglas' => 'required'],
        ];
        $resultado = GeneradorModulo::generarEn($this->dirTemporal, 'Simple', $campos);
        $this->assertTrue($resultado['exito']);
        $this->assertFileExists($this->dirTemporal . '/servidor/modelos/Simple.php');
    }

    public function testCodigoJsGeneradoNoContieneAlertNiConfirm(): void {
        $campos = [
            ['nombre' => 'nombre', 'tipo' => 'string', 'reglas' => 'required'],
        ];
        $resultado = GeneradorModulo::generarEn($this->dirTemporal, 'Producto', $campos);
        $this->assertTrue($resultado['exito']);

        $archivoJs = $this->dirTemporal . '/src/js/modulos/producto.js';
        $this->assertFileExists($archivoJs);
        $contenido = file_get_contents($archivoJs);

        $this->assertStringNotContainsString('alert(', $contenido, 'El JS generado no debe usar alert()');
        $this->assertStringNotContainsString('confirm(', $contenido, 'El JS generado no debe usar confirm()');
        $this->assertStringNotContainsString('NotificadorHubble.exito(', $contenido, 'El JS generado no debe usar NotificadorHubble.exito()');
    }

    public function testCodigoJsGeneradoUsaNotificadorYConfirmadorHubble(): void {
        $campos = [
            ['nombre' => 'nombre', 'tipo' => 'string', 'reglas' => 'required'],
        ];
        $resultado = GeneradorModulo::generarEn($this->dirTemporal, 'Producto', $campos);
        $this->assertTrue($resultado['exito']);

        $archivoJs = $this->dirTemporal . '/src/js/modulos/producto.js';
        $this->assertFileExists($archivoJs);
        $contenido = file_get_contents($archivoJs);

        $this->assertStringContainsString('NotificadorHubble.mostrar(', $contenido, 'El JS generado debe usar NotificadorHubble.mostrar()');
        $this->assertStringContainsString('ConfirmadorHubble.mostrar(', $contenido, 'El JS generado debe usar ConfirmadorHubble.mostrar()');
    }

    private function rmdirRecursivo(string $dir): void {
        if (!is_dir($dir)) return;
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
