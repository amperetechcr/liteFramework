<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Nucleo;

use LiteFramework\Nucleo\SubidaArchivos;

class SubidaArchivosTest extends \TestBase
{
    private array $filesRespaldo;
    private string $dirTemp;

    public function setUp(): void
    {
        $this->filesRespaldo = $_FILES;
        $_FILES = [];
        $this->dirTemp = sys_get_temp_dir() . '/lite_test_upload_' . bin2hex(random_bytes(4));
        mkdir($this->dirTemp, 0755, true);
    }

    public function tearDown(): void
    {
        $_FILES = $this->filesRespaldo;
        if (is_dir($this->dirTemp)) {
            $archivos = glob($this->dirTemp . '/*');
            foreach ($archivos as $a) {
                is_file($a) && unlink($a);
            }
            rmdir($this->dirTemp);
        }
    }

    public function testFilesNoEstablecido(): void
    {
        $s = new SubidaArchivos('inexistente');
        $this->assertTrue($s->tieneError());
        $this->assertStringContainsString('No se recibió', $s->error());
    }

    public function testSubidaExitosa(): void
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($tmpPath, 'contenido test');
        $_FILES['archivo'] = [
            'name' => 'documento.txt',
            'type' => 'text/plain',
            'tmp_name' => $tmpPath,
            'error' => UPLOAD_ERR_OK,
            'size' => 14,
        ];
        $s = new SubidaArchivos('archivo');
        $this->assertFalse($s->tieneError());
        $this->assertSame('documento.txt', $s->nombreOriginal());
        $this->assertSame('text/plain', $s->tipoMime());
        $this->assertSame(14, $s->tamano());
        unlink($tmpPath);
    }

    public function testErrorNoFileTratadoComoNoError(): void
    {
        $_FILES['campo'] = [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];
        $s = new SubidaArchivos('campo');
        $this->assertFalse($s->tieneError());
        $this->assertSame('', $s->error());
    }

    public function testMultiFileUpload(): void
    {
        $_FILES['fotos'] = [
            'name' => ['f1.txt', 'f2.txt'],
            'type' => ['text/plain', 'text/plain'],
            'tmp_name' => ['', ''],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [1, 1],
        ];
        $s = new SubidaArchivos('fotos');
        $this->assertTrue($s->esMultiple());
    }

    public function testGuardarConMovimientoFallidoDevuelveFalse(): void
    {
        $_FILES['doc'] = [
            'name' => 'informe.pdf',
            'type' => 'application/pdf',
            'tmp_name' => sys_get_temp_dir() . '/no-existe-' . uniqid(),
            'error' => UPLOAD_ERR_OK,
            'size' => 4,
        ];
        $s = new SubidaArchivos('doc');
        $ruta = $s->guardar($this->dirTemp, true);
        $this->assertFalse($ruta);
    }

    public function testTamanoFormateado(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'tf');
        file_put_contents($tmp, str_repeat('x', 2048));
        $_FILES['f'] = [
            'name' => 'f.txt',
            'type' => 'text/plain',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => 2048,
        ];
        $s = new SubidaArchivos('f');
        $this->assertStringContainsString('KB', $s->tamanoFormateado());
        unlink($tmp);
    }

    public function testMimeContentTypeFalso(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mc');
        file_put_contents($tmp, 'data');
        $_FILES['img'] = [
            'name' => 'image.png',
            'type' => 'image/png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => 4,
        ];
        $s = new SubidaArchivos('img');
        $s->validar(['image/png']);
        $this->assertSame('image.png', $s->nombreOriginal());
        unlink($tmp);
    }

    public function testExtensionNoPermitida(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ep');
        file_put_contents($tmp, 'test');
        $_FILES['file'] = [
            'name' => 'shell.php',
            'type' => 'text/plain',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => 4,
        ];
        $s = new SubidaArchivos('file');
        $s->establecerExtensionesPermitidas('jpg,png,gif');
        $s->validar(['text/plain']);
        $this->assertTrue($s->tieneError());
        $this->assertStringContainsString('Extension', $s->error());
        unlink($tmp);
    }

    public function testEstablecerExtensionesPermitidasString(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'es');
        file_put_contents($tmp, 'data');
        $_FILES['f'] = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => 4,
        ];
        $s = new SubidaArchivos('f');
        $s->establecerExtensionesPermitidas('jpg,jpeg,png');
        $s->validar(['image/jpeg']);
        $this->assertFalse($s->tieneError());
        unlink($tmp);
    }

    public function testEstablecerExtensionesPermitidasArray(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ea');
        file_put_contents($tmp, 'data');
        $_FILES['f'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => 4,
        ];
        $s = new SubidaArchivos('f');
        $s->establecerExtensionesPermitidas(['txt', 'csv']);
        $s->validar(['text/plain']);
        $this->assertFalse($s->tieneError());
        unlink($tmp);
    }

    public function testTraducirErrorSubidaIniSize(): void
    {
        $_FILES['f'] = [
            'name' => 'big.txt',
            'type' => 'text/plain',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_INI_SIZE,
            'size' => 0,
        ];
        $s = new SubidaArchivos('f');
        $this->assertStringContainsString('tamaño máximo permitido por el servidor', $s->error());
    }

    public function testTraducirErrorSubidaPartial(): void
    {
        $_FILES['f'] = [
            'name' => 'p.txt',
            'type' => 'text/plain',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_PARTIAL,
            'size' => 0,
        ];
        $s = new SubidaArchivos('f');
        $this->assertStringContainsString('parcialmente', $s->error());
    }

    public function testValidarConTamanoExcede(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'tam');
        file_put_contents($tmp, str_repeat('x', 500000));
        $_FILES['f'] = [
            'name' => 'large.txt',
            'type' => 'text/plain',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => 500000,
        ];
        $s = new SubidaArchivos('f');
        $s->validar(['text/plain'], 100000);
        $this->assertTrue($s->tieneError());
        unlink($tmp);
    }
}
