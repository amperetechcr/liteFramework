<?php
use PHPUnit\Framework\TestCase;

class EstructuraCssTest extends TestCase {

    private string $cssDir;

    protected function setUp(): void {
        $this->cssDir = DIRECTORIO_RAIZ . '/src/css';
    }

    private function archivosCss(): array {
        return array_values(array_filter(
            scandir($this->cssDir),
            fn($f) => str_ends_with($f, '.css')
        ));
    }

    public function testTodosLosArchivosCssExisten(): void {
        $archivos = $this->archivosCss();
        $this->assertNotEmpty($archivos);
        $this->assertContains('tema.css', $archivos);
        $this->assertContains('componentes.css', $archivos);
        $this->assertContains('maquetacion.css', $archivos);
        $this->assertContains('paletas.css', $archivos);
        $this->assertContains('subirArchivos.css', $archivos);
    }

    public function testNingunArchivoVacio(): void {
        foreach ($this->archivosCss() as $archivo) {
            $ruta = $this->cssDir . '/' . $archivo;
            $this->assertFileExists($ruta);
            $this->assertGreaterThan(
                50,
                filesize($ruta),
                "El archivo $archivo esta vacio o casi vacio"
            );
        }
    }

    public function testTotalArchivosCssCoherente(): void {
        $cantidad = count($this->archivosCss());
        $this->assertGreaterThanOrEqual(10, $cantidad, "Debe haber al menos 10 archivos CSS");
        $this->assertLessThanOrEqual(20, $cantidad, "No deben excederse 20 archivos CSS");
    }
}
