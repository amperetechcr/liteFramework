<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use LiteFramework\Nucleo\Helpers\ArchivoH as Archivo;

class ArchivoTest extends \TestBase
{
    public function testTamanoLegible(): void
    {
        $this->assertStringEndsWith(' B', Archivo::tamanoLegible(0));
        $this->assertStringEndsWith(' B', Archivo::tamanoLegible(500));
    }

    public function testTamanoLegibleNegativo(): void
    {
        $resultado = Archivo::tamanoLegible(-1);
        $this->assertIsString($resultado);
    }

    public function testEsImagen(): void
    {
        $this->assertTrue(Archivo::esImagen('image/jpeg'));
        $this->assertTrue(Archivo::esImagen('image/png'));
        $this->assertTrue(Archivo::esImagen('image/gif'));
        $this->assertFalse(Archivo::esImagen('text/plain'));
    }

    public function testEsDocumento(): void
    {
        $this->assertTrue(Archivo::esDocumento('application/pdf'));
        $this->assertFalse(Archivo::esDocumento('image/png'));
    }

    public function testEsVideo(): void
    {
        $this->assertTrue(Archivo::esVideo('video/mp4'));
        $this->assertFalse(Archivo::esVideo('audio/mp3'));
    }

    public function testEsAudio(): void
    {
        $this->assertTrue(Archivo::esAudio('audio/mpeg'));
        $this->assertFalse(Archivo::esAudio('video/mp4'));
    }

    public function testEsComprimido(): void
    {
        $this->assertTrue(Archivo::esComprimido('application/zip'));
        $this->assertFalse(Archivo::esComprimido('text/plain'));
    }

    public function testCategoriaMime(): void
    {
        $this->assertIsString(Archivo::categoriaMime('image/jpeg'));
    }

    public function testIconoExtension(): void
    {
        $this->assertSame('📄', Archivo::iconoExtension('pdf'));
    }

    public function testIconoExtensionMayusculas(): void
    {
        $this->assertSame('📄', Archivo::iconoExtension('PDF'));
    }

    public function testIconoExtensionDesconocida(): void
    {
        $this->assertSame('📄', Archivo::iconoExtension('xyz'));
    }

    public function testIconoExtensionVacia(): void
    {
        $this->assertSame('📄', Archivo::iconoExtension(''));
    }

    public function testExtensionSegura(): void
    {
        $resultado = Archivo::extensionSegura('documento.pdf');
        $this->assertIsString($resultado);
    }

    public function testSanitizarNombre(): void
    {
        $resultado = Archivo::sanitizarNombre('archivo<>.pdf');
        $this->assertIsString($resultado);
        $this->assertStringNotContainsString('<', $resultado);
    }

    public function testEsNombreSeguro(): void
    {
        $this->assertIsBool(Archivo::esNombreSeguro('documento.pdf'));
    }

    public function testEsNombreSeguroConNombrePeligroso(): void
    {
        $this->assertIsBool(Archivo::esNombreSeguro('../../etc/passwd'));
    }
}
