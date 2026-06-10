<?php
use PHPUnit\Framework\TestCase;

class SubidaArchivosTest extends TestCase {
    private array $filesOriginal;

    protected function setUp(): void {
        $this->filesOriginal = $_FILES ?? [];
        $_FILES = [];
    }

    protected function tearDown(): void {
        $_FILES = $this->filesOriginal;
    }

    public function testEstablecerExtensionesPermitidasDesdeString(): void {
        $archivo = new SubidaArchivos('test');
        $archivo->establecerExtensionesPermitidas('jpg,png,gif');
        $this->assertInstanceOf(SubidaArchivos::class, $archivo);
    }

    public function testEstablecerExtensionesPermitidasDesdeArray(): void {
        $archivo = new SubidaArchivos('test');
        $archivo->establecerExtensionesPermitidas(['jpg', 'png', 'gif']);
        $this->assertInstanceOf(SubidaArchivos::class, $archivo);
    }

    public function testConstructorConCampoInexistente(): void {
        $archivo = new SubidaArchivos('campo_inexistente');
        $this->assertInstanceOf(SubidaArchivos::class, $archivo);
    }

    public function testConstructorConArchivoValido(): void {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');
        
        $_FILES['documento'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 12,
        ];

        $archivo = new SubidaArchivos('documento');
        $this->assertInstanceOf(SubidaArchivos::class, $archivo);
        unlink($tempFile);
    }

    public function testValidarConMimeValido(): void {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, "\xff\xd8\xff\xe0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xff\xdb\x00C\x00\x08\x06\x06\x07\x06\x05\x08\x07\x07\x07\t\x08\n\x0c\x14\r\x0c\x0b\x0b\x0c\x19\x12\x13\x0f\x14\x1d\x1a\x1f\x1e\x1d\x1a\x1c\x1c $.' \"#*+-/ \x01\x02\x03\x11\x04\x12!3\x141A\x15\"#\$4%5B\x00\x08\x00\x01\x01\x01\x01\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0a\x0b\xff\xc0\x00\x0b\x08\x00\x01\x00\x01\x01\x01\x11\x00\xff\xc4\x00\x1f\x00\x00\x01\x05\x01\x01\x01\x01\x01\x01\x00\x00\x00\x00\x00\x00\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0a\x0b\xff\xc4\x00\xb5\x10\x00\x02\x01\x03\x03\x02\x04\x03\x05\x05\x04\x04\x00\x00\x00\x00\x00\x00\x00\x01\x02\x03\x11\x04\x12!1A\x05\x13\x06\x07\"\x81\x91\x15\x14\xa1\x08#B\xd1\x16\x17\x18\x19\x1a\xc1\xd1\xe1\xff\xda\x00\x08\x01\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xd9");
        
        $_FILES['img'] = [
            'name' => 'foto.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tempFile),
        ];

        $archivo = new SubidaArchivos('img');
        $resultado = $archivo->validar(['image/jpeg'], 50000);
        $this->assertInstanceOf(SubidaArchivos::class, $resultado);
        unlink($tempFile);
    }

    public function testValidarConMimeInvalido(): void {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'plain text content');
        
        $_FILES['doc'] = [
            'name' => 'archivo.txt',
            'type' => 'text/plain',
            'tmp_name' => $tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tempFile),
        ];

        $archivo = new SubidaArchivos('doc');
        $archivo->validar(['image/jpeg'], 50000);
        $this->assertFalse($archivo->guardar(sys_get_temp_dir(), false));
        unlink($tempFile);
    }

    public function testGuardarDevuelveFalseParaArchivosNoSubidos(): void {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'contenido de prueba');
        $destDir = sys_get_temp_dir() . '/subida_test_' . uniqid();
        mkdir($destDir, 0755, true);

        $_FILES['doc'] = [
            'name' => 'documento.txt',
            'type' => 'text/plain',
            'tmp_name' => $tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 19,
        ];

        $archivo = new SubidaArchivos('doc');
        $ruta = $archivo->validar(['text/plain'], 50000)->guardar($destDir, false);
        $this->assertFalse($ruta, 'move_uploaded_file falla para archivos no subidos via HTTP');

        unlink($tempFile);
        rmdir($destDir);
    }
}
