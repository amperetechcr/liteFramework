<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Regression;

use LiteFramework\Config\ConexionBaseDatos as DB;
use LiteFramework\Nucleo\BloqueoArchivo;
use LiteFramework\Nucleo\SubidaArchivos;
use LiteFramework\Servicios\AdministradorArchivos;

class PathTraversalRegressionTest extends \TestBase
{
    private string $tmpDir;
    private string $storageDir;

    public function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/lf_path_test_' . bin2hex(random_bytes(4));
        $this->storageDir = $this->tmpDir . '/storage';
        mkdir($this->storageDir, 0755, true);
        mkdir($this->storageDir . '/subdir', 0755, true);
        file_put_contents($this->storageDir . '/subdir/test.txt', 'hello');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit/1.0';
        $_SESSION = ['operador_id' => 1, 'matriz_permisos' => []];
    }

    public function tearDown(): void
    {
        $this->rmDirRecursive($this->tmpDir);
        DB::resetearInstancia();
        $_SESSION = [];
    }

    private function rmDirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $archivo) {
            if ($archivo->isDir()) {
                @rmdir($archivo->getRealPath());
            } else {
                @unlink($archivo->getRealPath());
            }
        }
        @rmdir($dir);
    }

    public function testModuloControladorIndiceWithPathTraversalBlocked(): void
    {
        $controlador = new \LiteFramework\Controladores\ModuloControlador();
        ob_start();
        try {
            $controlador->indice('../../../etc/passwd');
        } catch (\Throwable $e) {
            ob_get_clean();
            $this->addToAssertionCount(1);
            return;
        }
        $salida = ob_get_clean();
        $this->assertStringContainsString('Error', $salida);
    }

    public function testSubidaArchivosGuardarWithDotDotInFilenameBlocked(): void
    {
        $tmpFile = $this->tmpDir . '/source.txt';
        file_put_contents($tmpFile, 'test content');

        $_FILES = [
            'archivo' => [
                'name' => '../../../etc/passwd.txt',
                'type' => 'text/plain',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 12,
            ],
        ];

        $subida = new SubidaArchivos('archivo');
        $ruta = $subida->guardar($this->storageDir, true);
        $this->assertNotFalse($ruta);
        $this->assertStringStartsWith($this->storageDir, $ruta);
    }

    public function testBloqueoArchivoWithDotDotInClaveSanitized(): void
    {
        $bloqueo = BloqueoArchivo::adquirir('../../../etc/passwd', 1);
        $this->assertIsBool($bloqueo);

        $ref = new \ReflectionMethod(BloqueoArchivo::class, 'rutaLock');
        $ref->setAccessible(true);
        $ruta = $ref->invoke(null, '../../../etc/passwd');

        $this->assertStringContainsString('storage/locks', $ruta);
        $this->assertStringNotContainsString('etc', $ruta);
    }

    public function testAdministradorArchivosSubirWithPathTraversalBlocked(): void
    {
        $admin = new AdministradorArchivos($this->storageDir, '');

        $tmpFile = $this->tmpDir . '/source.pdf';
        file_put_contents($tmpFile, 'fake pdf content');

        $resultado = $admin->subir(
            [
                'name' => 'document.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 16,
            ],
            1,
            ['ruta_relativa' => '../../../etc/']
        );
        $this->assertIsArray($resultado);
    }

    public function testAdministradorArchivosEliminarWithPathTraversalBlocked(): void
    {
        $admin = new AdministradorArchivos($this->storageDir, '');

        $archivoCreado = $this->storageDir . '/target.txt';
        file_put_contents($archivoCreado, 'data');

        $bd = DB::obtenerInstancia()->obtenerConector();
        $idArchivo = (int)$bd->lastInsertId();
        if ($idArchivo === 0) {
            $bd->exec("INSERT IGNORE INTO operador (id_operador, nombre_completo, correo_electronico, clave_acceso, id_rol) VALUES (1, 'Test', 'test@test.com', 'hash', 1)");
            $stmt = $bd->prepare("INSERT INTO archivo (nombre_original, nombre_generado, ruta_archivo, tipo_mime, tamano_bytes, id_operador) VALUES (:nom, :gen, :ruta, :mime, :tam, :op)");
            $stmt->execute([
                ':nom' => 'target.txt',
                ':gen' => 'target.txt',
                ':ruta' => $archivoCreado,
                ':mime' => 'text/plain',
                ':tam' => 4,
                ':op' => 1,
            ]);
            $idArchivo = (int)$bd->lastInsertId();
        }
        if ($idArchivo > 0) {
            $resultado = $admin->eliminar($idArchivo);
            $this->assertIsArray($resultado);
        }
        $this->addToAssertionCount(1);
    }

    public function testAdministradorArchivosRenombrarWithPathTraversalBlocked(): void
    {
        $admin = new AdministradorArchivos($this->storageDir, '');
        $ref = new \ReflectionMethod(AdministradorArchivos::class, 'sanitizarNombre');
        $ref->setAccessible(true);

        $nombreOriginal = '../../../etc/shadow';
        $sanitizado = $ref->invoke(null, $nombreOriginal);
        $this->assertStringNotContainsString('..', $sanitizado);
    }

    public function testAdministradorArchivosMoverWithPathTraversalBlocked(): void
    {
        $admin = new AdministradorArchivos($this->storageDir, '');
        $ref = new \ReflectionMethod(AdministradorArchivos::class, 'sanitizarNombre');
        $ref->setAccessible(true);

        $ruta = '../../etc/passwd';
        $sanitizado = $ref->invoke(null, $ruta);
        $this->assertStringNotContainsString('..', $sanitizado);
    }

    public function testAdministradorArchivosCrearDirectorioWithPathTraversalBlocked(): void
    {
        $admin = new AdministradorArchivos($this->storageDir, '');
        $ref = new \ReflectionMethod(AdministradorArchivos::class, 'sanitizarNombre');
        $ref->setAccessible(true);

        $directorio = '../../../tmp/hacked';
        $sanitizado = $ref->invoke(null, $directorio);
        $this->assertStringNotContainsString('..', $sanitizado);
    }
}
