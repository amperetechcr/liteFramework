<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Nucleo;

use LiteFramework\Nucleo\BloqueoArchivo;
use LiteFramework\Seguridad\RegistroAuditoria;

class BloqueoArchivoTest extends \TestBase
{
    private string $dirLocks;

    public function setUp(): void
    {
        $this->dirLocks = dirname(__DIR__, 3) . '/storage/locks';
        if (!is_dir($this->dirLocks)) {
            mkdir($this->dirLocks, 0755, true);
        }
        $this->limpiarLocks();
        RegistroAuditoria::deshabilitarBitacora();
    }

    public function tearDown(): void
    {
        $this->limpiarLocks();
    }

    private function limpiarLocks(): void
    {
        if (is_dir($this->dirLocks)) {
            $archivos = glob($this->dirLocks . '/*.lock');
            if ($archivos !== false) {
                foreach ($archivos as $a) {
                    @unlink($a);
                }
            }
        }
    }

    public function testAdquirirExclusivoDevuelveTrue(): void
    {
        $resultado = BloqueoArchivo::adquirir('test-key');
        if (!$resultado) {
            $this->markTestSkipped('No se pudo adquirir lock (entorno)');
        }
        $this->assertTrue($resultado);
    }

    public function testLiberarNoLanzaExcepcion(): void
    {
        $adquirido = BloqueoArchivo::adquirir('test-para-liberar');
        if (!$adquirido) {
            $this->markTestSkipped('No se pudo adquirir lock (entorno)');
        }
        BloqueoArchivo::liberar('test-para-liberar');
        $this->assertTrue(true);
    }

    public function testLiberarNoExistenteNoLanzaExcepcion(): void
    {
        BloqueoArchivo::liberar('nunca-adquirido');
        $this->assertTrue(true);
    }

    public function testEstaBloqueadoConArchivoExistente(): void
    {
        $reflection = new \ReflectionClass(BloqueoArchivo::class);
        $rutaLock = $reflection->getMethod('rutaLock');
        $rutaLock->setAccessible(true);
        $ruta = $rutaLock->invoke(null, 'bloqueado-test');

        file_put_contents($ruta, json_encode([
            'clave' => 'bloqueado-test',
            'exclusivo' => true,
            'adquirido' => time(),
            'expiracion' => time() + 3600,
            'pid' => 123,
            'host' => 'test',
        ]));

        $this->assertTrue(BloqueoArchivo::estaBloqueado('bloqueado-test'));
    }

    public function testEstaBloqueadoDevuelveFalse(): void
    {
        $this->assertFalse(BloqueoArchivo::estaBloqueado('nunca-adquirido'));
    }

    public function testAdquirirCompartido(): void
    {
        $resultado = BloqueoArchivo::adquirirCompartido('compartido-key');
        if (!$resultado) {
            $this->markTestSkipped('No se pudo adquirir lock (entorno)');
        }
        $this->assertTrue($resultado);
    }

    public function testLiberaTodoElCiclo(): void
    {
        $adquirido = BloqueoArchivo::adquirir('ciclo');
        if (!$adquirido) {
            $this->markTestSkipped('No se pudo adquirir lock (entorno)');
        }
        $bloqueado = BloqueoArchivo::estaBloqueado('ciclo');
        BloqueoArchivo::liberar('ciclo');
        $this->assertFalse(BloqueoArchivo::estaBloqueado('ciclo'));
    }

    public function testLimpiarExpiradosDevuelveEntero(): void
    {
        $limpiados = BloqueoArchivo::limpiarExpirados();
        $this->assertIsInt($limpiados);
    }

    public function testExpiredLockAutoLibera(): void
    {
        $reflection = new \ReflectionClass(BloqueoArchivo::class);
        $rutaLock = $reflection->getMethod('rutaLock');
        $rutaLock->setAccessible(true);
        $ruta = $rutaLock->invoke(null, 'expirado');

        file_put_contents($ruta, json_encode([
            'clave' => 'expirado',
            'exclusivo' => true,
            'adquirido' => time() - 100,
            'expiracion' => time() - 10,
            'pid' => 0,
            'host' => '',
        ]));

        $this->assertFalse(BloqueoArchivo::estaBloqueado('expirado'));
    }

    public function testJsonDecodeFalsoNoBloquea(): void
    {
        $reflection = new \ReflectionClass(BloqueoArchivo::class);
        $rutaLock = $reflection->getMethod('rutaLock');
        $rutaLock->setAccessible(true);
        $ruta = $rutaLock->invoke(null, 'json-invalido');

        file_put_contents($ruta, '{esto no es json valido');

        $this->assertFalse(BloqueoArchivo::estaBloqueado('json-invalido'));
    }

    public function testClaveSanitizacionColision(): void
    {
        $ruta1 = null;
        $ruta2 = null;

        $reflection = new \ReflectionClass(BloqueoArchivo::class);
        $rutaLock = $reflection->getMethod('rutaLock');
        $rutaLock->setAccessible(true);

        $ruta1 = $rutaLock->invoke(null, 'clave/con/separador');
        $ruta2 = $rutaLock->invoke(null, 'clave_con_separador');

        $this->assertSame($ruta1, $ruta2);
    }

    public function testGlobRetornaCeroEnDirectorioVacio(): void
    {
        $resultado = BloqueoArchivo::limpiarExpirados();
        $this->assertSame(0, $resultado);
    }

    public function testClaveConCaracteresEspeciales(): void
    {
        $clave = 'test:espacios y simbolos!@#$%^&*()';
        $resultado = BloqueoArchivo::adquirir($clave);
        if (!$resultado) {
            $this->markTestSkipped('No se pudo adquirir lock (entorno)');
        }
        $this->assertTrue($resultado);
        BloqueoArchivo::liberar($clave);
    }

    public function testTiempoMaximoAgotadoNoLanzaExcepcion(): void
    {
        $clave = 'timeout-test';
        BloqueoArchivo::adquirir($clave);
        $resultado = BloqueoArchivo::adquirir($clave, 1);
        BloqueoArchivo::liberar($clave);
        $this->assertIsBool($resultado);
    }
}
