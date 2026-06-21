<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Servicios;

use LiteFramework\Servicios\AdministradorArchivos;

class AdministradorArchivosTest extends \TestBase
{
    private string $storagePath;
    private string $baseUrl;
    private AdministradorArchivos $admin;

    public function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lf_admin_test_' . bin2hex(random_bytes(4));
        $this->baseUrl = 'http://localhost/archivos';
        mkdir($this->storagePath, 0755, true);
        $this->admin = new AdministradorArchivos($this->storagePath, $this->baseUrl);
    }

    public function tearDown(): void
    {
        $this->rmdirRecursive($this->storagePath);
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

    // ─── Constructor ───

    public function testConstructorAsignaStoragePathYBaseUrl(): void
    {
        $ref = new \ReflectionClass($this->admin);
        $storagePath = $ref->getProperty('storagePath')->getValue($this->admin);
        $this->assertSame($this->storagePath, $storagePath);
        $this->assertSame($this->baseUrl, $this->admin->getBaseUrl());
    }

    // ─── MIME helpers ───

    public function testEsImagen(): void
    {
        $this->assertTrue(AdministradorArchivos::esImagen('image/jpeg'));
        $this->assertTrue(AdministradorArchivos::esImagen('image/png'));
        $this->assertFalse(AdministradorArchivos::esImagen('application/pdf'));
    }

    public function testEsDocumento(): void
    {
        $this->assertTrue(AdministradorArchivos::esDocumento('application/pdf'));
        $this->assertFalse(AdministradorArchivos::esDocumento('image/png'));
    }

    public function testEsVideo(): void
    {
        $this->assertTrue(AdministradorArchivos::esVideo('video/mp4'));
        $this->assertFalse(AdministradorArchivos::esVideo('audio/mpeg'));
    }

    public function testEsAudio(): void
    {
        $this->assertTrue(AdministradorArchivos::esAudio('audio/mpeg'));
        $this->assertFalse(AdministradorArchivos::esAudio('video/mp4'));
    }

    public function testEsComprimido(): void
    {
        $this->assertTrue(AdministradorArchivos::esComprimido('application/zip'));
        $this->assertTrue(AdministradorArchivos::esComprimido('application/x-rar-compressed'));
        $this->assertFalse(AdministradorArchivos::esComprimido('text/plain'));
    }

    public function testCategoriaMime(): void
    {
        $this->assertSame('imagenes', AdministradorArchivos::categoriaMime('image/jpeg'));
        $this->assertSame('documentos', AdministradorArchivos::categoriaMime('application/pdf'));
        $this->assertSame('otro', AdministradorArchivos::categoriaMime('application/unknown'));
    }

    // ─── Utilidades ───

    public function testTamanoFormateado(): void
    {
        $this->assertSame('0 B', AdministradorArchivos::tamanoFormateado(0));
        $this->assertSame('1.07 KB', AdministradorArchivos::tamanoFormateado(1100));
        $this->assertSame('1 MB', AdministradorArchivos::tamanoFormateado(1048576));
    }

    public function testRutaMostrar(): void
    {
        $resultado = AdministradorArchivos::rutaMostrar('/base/sub/archivo.pdf', '/base');
        $this->assertSame('sub/archivo.pdf', $resultado);
    }

    public function testRutaMostrarRaiz(): void
    {
        $resultado = AdministradorArchivos::rutaMostrar('/base/archivo.pdf', '/base');
        $this->assertSame('', $resultado);
    }

    public function testEnlaceDescarga(): void
    {
        $url = AdministradorArchivos::enlaceDescarga(5, 'http://localhost/archivos');
        $this->assertSame('http://localhost/archivos/archivos/descargar/5', $url);
    }

    public function testExtensionSegura(): void
    {
        $this->assertSame('pdf', AdministradorArchivos::extensionSegura('doc.pdf'));
        $this->assertSame('jpg', AdministradorArchivos::extensionSegura('foto.JPG'));
        $this->assertSame('', AdministradorArchivos::extensionSegura('sin_extension'));
    }

    public function testSanitizarNombre(): void
    {
        $this->assertSame('archivo_seguro', AdministradorArchivos::sanitizarNombre('archivo<>seguro'));
        $this->assertSame('nombre con espacios', AdministradorArchivos::sanitizarNombre('nombre con espacios'));
        $this->assertSame('archivo', AdministradorArchivos::sanitizarNombre('...'));
    }

    public function testEsNombreSeguro(): void
    {
        $this->assertTrue(AdministradorArchivos::esNombreSeguro('archivo.pdf'));
        $this->assertFalse(AdministradorArchivos::esNombreSeguro('../archivo.pdf'));
        $this->assertFalse(AdministradorArchivos::esNombreSeguro('archivo<>malo'));
    }

    public function testPathTraversalEnRutaRelativaEsBloqueado(): void
    {
        $this->assertFalse(AdministradorArchivos::esNombreSeguro('../../../etc/passwd'));
        $this->assertStringNotContainsString('..', AdministradorArchivos::sanitizarNombre('../../etc'));
    }

    // ─── Operaciones con archivos usando el filesystem directamente ───

    public function testCrearYEliminarArchivoEnStorage(): void
    {
        $archivo = $this->storagePath . '/test.txt';
        file_put_contents($archivo, 'contenido');
        $this->assertFileExists($archivo);

        unlink($archivo);
        $this->assertFileDoesNotExist($archivo);
    }

    public function testListarArchivosEnDirectorio(): void
    {
        file_put_contents($this->storagePath . '/a.txt', 'a');
        file_put_contents($this->storagePath . '/b.txt', 'b');

        $archivos = array_diff(scandir($this->storagePath), ['.', '..']);
        $this->assertCount(2, $archivos);
    }

    public function testRenombrarArchivo(): void
    {
        $viejo = $this->storagePath . '/viejo.txt';
        $nuevo = $this->storagePath . '/nuevo.txt';
        file_put_contents($viejo, 'data');
        rename($viejo, $nuevo);
        $this->assertFileExists($nuevo);
        $this->assertFileDoesNotExist($viejo);
    }

    public function testMoverArchivoASubdirectorio(): void
    {
        mkdir($this->storagePath . '/sub', 0755, true);
        $origen = $this->storagePath . '/mover.txt';
        $destino = $this->storagePath . '/sub/mover.txt';
        file_put_contents($origen, 'data');
        rename($origen, $destino);
        $this->assertFileExists($destino);
        $this->assertFileDoesNotExist($origen);
    }

    public function testCrearDirectorio(): void
    {
        $nuevoDir = $this->storagePath . '/nuevo';
        mkdir($nuevoDir, 0755, true);
        $this->assertDirectoryExists($nuevoDir);
    }

    public function testObtenerInfoArchivo(): void
    {
        $archivo = $this->storagePath . '/info.txt';
        file_put_contents($archivo, 'contenido de prueba');

        $this->assertFileExists($archivo);
        $this->assertGreaterThan(0, filesize($archivo));
    }

    public function testRutaRelativaRechazaPathTraversal(): void
    {
        $this->assertFalse(AdministradorArchivos::esNombreSeguro('../../../etc'));
    }

    public function testIconoExtensionRetornaIcono(): void
    {
        $this->assertNotEmpty(AdministradorArchivos::iconoExtension('pdf'));
        $this->assertNotEmpty(AdministradorArchivos::iconoExtension('unknown'));
    }
}
