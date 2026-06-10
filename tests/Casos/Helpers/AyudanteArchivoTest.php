<?php
use PHPUnit\Framework\TestCase;

class AyudanteArchivoTest extends TestCase {
    public function testTamanoLegible(): void {
        $this->assertStringContainsString('B', ArchivoH::tamanoLegible(500));
        $this->assertStringContainsString('KB', ArchivoH::tamanoLegible(2048));
        $this->assertStringContainsString('MB', ArchivoH::tamanoLegible(1048576 * 2));
    }

    public function testEsImagen(): void {
        $this->assertTrue(ArchivoH::esImagen('image/jpeg'));
        $this->assertTrue(ArchivoH::esImagen('image/png'));
        $this->assertFalse(ArchivoH::esImagen('application/pdf'));
    }

    public function testEsDocumento(): void {
        $this->assertTrue(ArchivoH::esDocumento('application/pdf'));
        $this->assertTrue(ArchivoH::esDocumento('text/plain'));
        $this->assertFalse(ArchivoH::esDocumento('image/jpeg'));
    }

    public function testEsVideo(): void {
        $this->assertTrue(ArchivoH::esVideo('video/mp4'));
        $this->assertFalse(ArchivoH::esVideo('audio/mpeg'));
    }

    public function testEsAudio(): void {
        $this->assertTrue(ArchivoH::esAudio('audio/mpeg'));
        $this->assertFalse(ArchivoH::esAudio('video/mp4'));
    }

    public function testEsComprimido(): void {
        $this->assertTrue(ArchivoH::esComprimido('application/zip'));
        $this->assertTrue(ArchivoH::esComprimido('application/x-rar-compressed'));
    }

    public function testCategoriaMime(): void {
        $this->assertEquals('imagenes', ArchivoH::categoriaMime('image/png'));
        $this->assertEquals('documentos', ArchivoH::categoriaMime('application/pdf'));
        $this->assertEquals('videos', ArchivoH::categoriaMime('video/mp4'));
        $this->assertEquals('audio', ArchivoH::categoriaMime('audio/mpeg'));
        $this->assertEquals('comprimidos', ArchivoH::categoriaMime('application/zip'));
        $this->assertEquals('datos', ArchivoH::categoriaMime('application/octet-stream'));
    }

    public function testExtensionSegura(): void {
        $this->assertEquals('jpg', ArchivoH::extensionSegura('foto.JPG'));
        $this->assertEquals('pdf', ArchivoH::extensionSegura('documento.PDF'));
        $this->assertEquals('', ArchivoH::extensionSegura('sin_extension'));
    }

    public function testSanitizarNombre(): void {
        $this->assertStringNotContainsString('/', ArchivoH::sanitizarNombre('../malicioso'));
        $this->assertStringNotContainsString('..', ArchivoH::sanitizarNombre('../malicioso'));
    }

    public function testEsNombreSeguro(): void {
        $this->assertTrue(ArchivoH::esNombreSeguro('documento.pdf'));
        $this->assertFalse(ArchivoH::esNombreSeguro('../malicioso.php'));
    }

    public function testIconoExtension(): void {
        $this->assertIsString(ArchivoH::iconoExtension('pdf'));
        $this->assertIsString(ArchivoH::iconoExtension('jpg'));
        $this->assertIsString(ArchivoH::iconoExtension('desconocido'));
    }
}
