<?php
use PHPUnit\Framework\TestCase;

class AuditoriaClienteTest extends TestCase {
    public function testLiteJsTieneFuncionInicializarCliente(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/js/ui/lite.js');
        $this->assertStringContainsString('function inicializarCliente', $contenido);
        $this->assertStringContainsString('DATOS_CLIENTE', $contenido);
    }

    public function testPrincipalJsImportaEInicializaCliente(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/js/principal.js');
        $this->assertStringContainsString('inicializarCliente', $contenido);
    }

    public function testUtilidadesJsTieneEnriquecerPayload(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/js/api/utilidades.js');
        $this->assertStringContainsString('function enriquecerPayload', $contenido);
        $this->assertStringContainsString('_cliente', $contenido);
    }

    public function testUtilidadesJsParcheaFetch(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/js/api/utilidades.js');
        $this->assertStringContainsString('window.fetch = function', $contenido);
    }

    public function testProcesarPeticionExtraeCliente(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php');
        $this->assertStringContainsString("'_cliente'", $contenido);
        $this->assertStringContainsString('_datos_cliente', $contenido);
    }

    public function testRegistroAuditoriaIncluyeCliente(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/servidor/seguridad/RegistroAuditoria.php');
        $this->assertStringContainsString("'cliente'", $contenido);
        $this->assertStringContainsString('_datos_cliente', $contenido);
    }
}
