<?php
use PHPUnit\Framework\TestCase;

class AdministradorArchivosTest extends TestCase {

    // ─── MIME detection ───

    public function testEsImagen(): void {
        $this->assertTrue(AdministradorArchivos::esImagen('image/jpeg'));
        $this->assertTrue(AdministradorArchivos::esImagen('image/png'));
        $this->assertFalse(AdministradorArchivos::esImagen('application/pdf'));
    }

    public function testEsDocumento(): void {
        $this->assertTrue(AdministradorArchivos::esDocumento('application/pdf'));
        $this->assertTrue(AdministradorArchivos::esDocumento('text/plain'));
        $this->assertFalse(AdministradorArchivos::esDocumento('image/jpeg'));
    }

    public function testEsVideo(): void {
        $this->assertTrue(AdministradorArchivos::esVideo('video/mp4'));
        $this->assertFalse(AdministradorArchivos::esVideo('audio/mpeg'));
    }

    public function testEsAudio(): void {
        $this->assertTrue(AdministradorArchivos::esAudio('audio/mpeg'));
        $this->assertFalse(AdministradorArchivos::esAudio('video/mp4'));
    }

    public function testEsComprimido(): void {
        $this->assertTrue(AdministradorArchivos::esComprimido('application/zip'));
        $this->assertTrue(AdministradorArchivos::esComprimido('application/x-rar-compressed'));
        $this->assertFalse(AdministradorArchivos::esComprimido('image/jpeg'));
    }

    public function testCategoriaMime(): void {
        $this->assertEquals('imagenes', AdministradorArchivos::categoriaMime('image/png'));
        $this->assertEquals('documentos', AdministradorArchivos::categoriaMime('application/pdf'));
        $this->assertEquals('videos', AdministradorArchivos::categoriaMime('video/mp4'));
        $this->assertEquals('audio', AdministradorArchivos::categoriaMime('audio/mpeg'));
        $this->assertEquals('comprimidos', AdministradorArchivos::categoriaMime('application/zip'));
        $this->assertEquals('codigo', AdministradorArchivos::categoriaMime('text/javascript'));
        $this->assertEquals('datos', AdministradorArchivos::categoriaMime('application/octet-stream'));
        $this->assertEquals('otro', AdministradorArchivos::categoriaMime('application/x-fake'));
    }

    // ─── Formateo ───

    public function testTamanoFormateado(): void {
        $this->assertStringContainsString('B', AdministradorArchivos::tamanoFormateado(500));
        $this->assertStringContainsString('KB', AdministradorArchivos::tamanoFormateado(2048));
        $this->assertStringContainsString('MB', AdministradorArchivos::tamanoFormateado(1048576 * 2));
        $this->assertStringContainsString('GB', AdministradorArchivos::tamanoFormateado(1073741824));
        $this->assertEquals('0 B', AdministradorArchivos::tamanoFormateado(0));
    }

    // ─── Ruta relativa ───

    public function testRutaMostrar(): void {
        $storage = '/var/www/storage/archivos';
        $this->assertEquals(
            'fotos/img.jpg',
            AdministradorArchivos::rutaMostrar('/var/www/storage/archivos/fotos/img.jpg', $storage)
        );
        $this->assertEquals(
            '',
            AdministradorArchivos::rutaMostrar('/var/www/storage/archivos/imagen.png', $storage)
        );
        $this->assertEquals(
            '/fuera/del/storage/doc.pdf',
            AdministradorArchivos::rutaMostrar('/fuera/del/storage/doc.pdf', $storage)
        );
    }

    // ─── Enlace de descarga ───

    public function testEnlaceDescarga(): void {
        $this->assertEquals(
            '/base/archivos/descargar/42',
            AdministradorArchivos::enlaceDescarga(42, '/base')
        );
        $this->assertEquals(
            '/base/archivos/descargar/1',
            AdministradorArchivos::enlaceDescarga(1, '/base/')
        );
    }

    // ─── Extension segura ───

    public function testExtensionSegura(): void {
        $this->assertEquals('jpg', AdministradorArchivos::extensionSegura('foto.JPG'));
        $this->assertEquals('pdf', AdministradorArchivos::extensionSegura('documento.PDF'));
        $this->assertEquals('', AdministradorArchivos::extensionSegura('sin_extension'));
    }

    // ─── Sanitizar nombre ───

    public function testSanitizarNombre(): void {
        $this->assertStringNotContainsString('/', AdministradorArchivos::sanitizarNombre('../malicioso'));
        $this->assertStringNotContainsString('..', AdministradorArchivos::sanitizarNombre('../malicioso'));
        $this->assertEquals('archivo', AdministradorArchivos::sanitizarNombre(''));
    }

    // ─── Nombre seguro ───

    public function testEsNombreSeguro(): void {
        $this->assertTrue(AdministradorArchivos::esNombreSeguro('documento.pdf'));
        $this->assertFalse(AdministradorArchivos::esNombreSeguro('../malicioso.php'));
        $this->assertTrue(AdministradorArchivos::esNombreSeguro('foto vacaciones 2024.jpg'));
    }

    // ─── Icono extension ───

    public function testIconoExtension(): void {
        $this->assertIsString(AdministradorArchivos::iconoExtension('pdf'));
        $this->assertIsString(AdministradorArchivos::iconoExtension('jpg'));
        $this->assertIsString(AdministradorArchivos::iconoExtension('desconocido'));
        $this->assertIsString(AdministradorArchivos::iconoExtension(''));
    }
}
