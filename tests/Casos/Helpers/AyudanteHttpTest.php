<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use PHPUnit\Framework\TestCase;
use LiteFramework\Nucleo\Helpers\AyudanteHttp;
use LiteFramework\Nucleo\Excepciones\ErrorHttp;
use LiteFramework\Nucleo\Excepciones\ErrorRed;

class AyudanteHttpTest extends TestCase
{
    private static $procesoServidor = null;
    private static string $baseUrl = '';

    public static function setUpBeforeClass(): void
    {
        $puerto = 18987;
        $rutaRouter = realpath(__DIR__ . '/../../servidor_prueba_http.php');
        $dirRouter = dirname($rutaRouter);
        self::$baseUrl = "http://127.0.0.1:$puerto";

        $phpBin = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        if (PHP_OS_FAMILY === 'Windows') {
            $comando = sprintf(
                'start /B %s -S 127.0.0.1:%d -t "%s" "%s"',
                $phpBin, $puerto, $dirRouter, $rutaRouter
            );
            self::$procesoServidor = proc_open(
                $comando,
                [0 => ['pipe', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']],
                $pipes
            );
            if (is_resource(self::$procesoServidor)) {
                fclose($pipes[0]);
            }
        } else {
            $comando = sprintf(
                '%s -S 127.0.0.1:%d -t %s %s 2>/dev/null >/dev/null & echo $!',
                $phpBin, $puerto, escapeshellarg($dirRouter), escapeshellarg($rutaRouter)
            );
            self::$procesoServidor = (int)shell_exec($comando);
        }
        usleep(300000);
    }

    public static function tearDownAfterClass(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            if (is_resource(self::$procesoServidor)) {
                $status = proc_get_status(self::$procesoServidor);
                if ($status !== false && $status['running']) {
                    exec('taskkill /F /PID ' . $status['pid'] . ' 2>NUL');
                }
                proc_close(self::$procesoServidor);
            }
        } else {
            if (is_int(self::$procesoServidor) && self::$procesoServidor > 0) {
                exec('kill ' . self::$procesoServidor . ' 2>/dev/null');
            }
        }
        self::$procesoServidor = null;
    }

    public function testVerificarDisponible(): void
    {
        $this->assertTrue(AyudanteHttp::verificarDisponible());
    }

    public function testCodigoComoTexto(): void
    {
        $this->assertSame('OK', AyudanteHttp::codigoComoTexto(200));
        $this->assertSame('No encontrado', AyudanteHttp::codigoComoTexto(404));
        $this->assertSame('Error interno del servidor', AyudanteHttp::codigoComoTexto(500));
        $this->assertSame('Codigo desconocido', AyudanteHttp::codigoComoTexto(999));
    }

    public function testTimeoutGeneraError(): void
    {
        $resultado = AyudanteHttp::obtener('http://192.0.2.1:9999/prueba', [], 1);
        $this->assertFalse($resultado['exito']);
        $this->assertSame(0, $resultado['codigo']);
        $this->assertNotEmpty($resultado['error']);
    }

    public function testCodigoHttpError(): void
    {
        $resultado = AyudanteHttp::obtener(self::$baseUrl . '/status/404');
        $this->assertFalse($resultado['exito']);
        $this->assertSame(404, $resultado['codigo']);
        $this->assertSame('No encontrado', $resultado['error']);
    }

    public function testObtenerGetExitoso(): void
    {
        $resultado = AyudanteHttp::obtener(self::$baseUrl . '/get');
        $this->assertTrue($resultado['exito']);
        $this->assertSame(200, $resultado['codigo']);
        $this->assertIsArray($resultado['cuerpo']);
        $this->assertStringContainsString('liteframework-test-local', $resultado['cuerpoCrudo']);
        $this->assertGreaterThan(0, $resultado['tiempo']);
    }

    public function testPostJsonExitoso(): void
    {
        $datos = ['nombre' => 'test', 'valor' => 123];
        $resultado = AyudanteHttp::postJson(self::$baseUrl . '/post', $datos);
        $this->assertTrue($resultado['exito']);
        $this->assertSame(200, $resultado['codigo']);
        $this->assertIsArray($resultado['cuerpo']);
        $this->assertSame($datos, $resultado['cuerpo']['json'] ?? null);
    }

    public function testPostFormulario(): void
    {
        $datos = ['campo1' => 'valor1', 'campo2' => 'valor2'];
        $resultado = AyudanteHttp::post(self::$baseUrl . '/post', $datos);
        $this->assertTrue($resultado['exito']);
        $this->assertSame(200, $resultado['codigo']);
        $this->assertIsArray($resultado['cuerpo']);
        $this->assertSame('valor1', $resultado['cuerpo']['form']['campo1'] ?? null);
    }

    public function testParaleloDosPeticiones(): void
    {
        $resultados = AyudanteHttp::paralelo([
            'a' => ['url' => self::$baseUrl . '/get?x=1'],
            'b' => ['url' => self::$baseUrl . '/get?x=2'],
        ]);
        $this->assertCount(2, $resultados);
        $this->assertTrue($resultados['a']['exito']);
        $this->assertTrue($resultados['b']['exito']);
        $this->assertSame(200, $resultados['a']['codigo']);
        $this->assertSame(200, $resultados['b']['codigo']);
    }

    public function testParaleloMixtoGetYPost(): void
    {
        $resultados = AyudanteHttp::paralelo([
            'get' => ['url' => self::$baseUrl . '/get?p=1'],
            'post' => [
                'url' => self::$baseUrl . '/post',
                'metodo' => 'POST',
                'cuerpo' => ['clave' => 'valor'],
            ],
        ]);
        $this->assertTrue($resultados['get']['exito']);
        $this->assertTrue($resultados['post']['exito']);
        $this->assertSame(200, $resultados['get']['codigo']);
        $this->assertSame(200, $resultados['post']['codigo']);
    }

    public function testParaleloConError(): void
    {
        $resultados = AyudanteHttp::paralelo([
            'bien' => ['url' => self::$baseUrl . '/get'],
            'mal' => ['url' => self::$baseUrl . '/status/500'],
        ]);
        $this->assertTrue($resultados['bien']['exito']);
        $this->assertFalse($resultados['mal']['exito']);
        $this->assertSame(500, $resultados['mal']['codigo']);
    }

    public function testCabecerasParseadas(): void
    {
        $resultado = AyudanteHttp::obtener(self::$baseUrl . '/get');
        $this->assertTrue($resultado['exito']);
        $cabeceras = $resultado['cabeceras'];
        $this->assertIsArray($cabeceras);
        $this->assertNotEmpty($cabeceras);
    }

    public function testEnviarConCabecerasCustom(): void
    {
        $resultado = AyudanteHttp::enviar('GET', self::$baseUrl . '/headers', [
            'cabeceras' => ['X-Prueba: valor123', 'Accept: application/json'],
        ]);
        $this->assertTrue($resultado['exito']);
        $this->assertIsArray($resultado['cuerpo']);
        $cabecerasEnviadas = $resultado['cuerpo']['headers'] ?? [];
        $this->assertSame('valor123', $cabecerasEnviadas['X-Prueba'] ?? null);
    }

    public function testParaleloPeticionUnica(): void
    {
        $resultados = AyudanteHttp::paralelo([
            'unica' => ['url' => self::$baseUrl . '/get'],
        ]);
        $this->assertCount(1, $resultados);
        $this->assertTrue($resultados['unica']['exito']);
    }

    public function testTiempoEsNumerico(): void
    {
        $resultado = AyudanteHttp::obtener(self::$baseUrl . '/get');
        $this->assertIsFloat($resultado['tiempo']);
        $this->assertGreaterThan(0, $resultado['tiempo']);
    }
}
