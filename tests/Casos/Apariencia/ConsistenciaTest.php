<?php
use PHPUnit\Framework\TestCase;

class ConsistenciaTest extends TestCase {
    public function testValoresUiPhpTienenEstructuraCompleta(): void {
        $configUI = require DIRECTORIO_RAIZ . '/servidor/config/ui.php';
        $this->assertArrayHasKey('paletas_validas', $configUI);
        $this->assertArrayHasKey('estilos_validos', $configUI);
        $this->assertArrayHasKey('fondos_validos', $configUI);
        $this->assertArrayHasKey('fuentes_validas', $configUI);
        $this->assertArrayHasKey('espaciados_validos', $configUI);
        $this->assertArrayHasKey('tamanos_validos', $configUI);
    }

    public function testPaletasContienenValoresEsperados(): void {
        $configUI = require DIRECTORIO_RAIZ . '/servidor/config/ui.php';
        $this->assertContains('indigo', $configUI['paletas_validas']);
        $this->assertContains('azul', $configUI['paletas_validas']);
        $this->assertContains('fucsia', $configUI['paletas_validas']);
        $this->assertCount(13, $configUI['paletas_validas']);
    }

    public function testEstilosContienenNuevosValores(): void {
        $configUI = require DIRECTORIO_RAIZ . '/servidor/config/ui.php';
        $this->assertContains('3d-moderno', $configUI['estilos_validos']);
        $this->assertContains('jugueton', $configUI['estilos_validos']);
        $this->assertContains('corporativo', $configUI['estilos_validos']);
    }

    public function testEncabezadoInyectaVALORES_UI(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php');
        $this->assertStringContainsString('window.VALORES_UI', $contenido);
        $this->assertStringContainsString('json_encode', $contenido);
    }

    public function testIndexPhpUsaConfigUIEnLugarDeListasDirectas(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/index.php');
        $this->assertStringContainsString("\$configUI['paletas_validas']", $contenido);
        $this->assertStringNotContainsString("\$paletasValidas = ['", $contenido);
    }

    public function testIndexPhpMergeaPersonalizacionSession(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/index.php');
        $this->assertStringContainsString("\$_SESSION['personalizacion_ui']", $contenido);
        $this->assertStringContainsString('array_merge', $contenido);
    }

    public function testAparienciaJsUsaVALORES_UI(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/js/modulos/apariencia.js');
        $this->assertStringContainsString('window.VALORES_UI', $contenido);
        $this->assertStringNotContainsString('window.FONDOS_DISPONIBLES', $contenido);
    }
}
