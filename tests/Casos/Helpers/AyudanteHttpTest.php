<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use PHPUnit\Framework\TestCase;
use LiteFramework\Nucleo\Helpers\AyudanteHttp;
use LiteFramework\Nucleo\Excepciones\ErrorHttp;
use LiteFramework\Nucleo\Excepciones\ErrorRed;

class AyudanteHttpTest extends TestCase
{
    private string $servidorPrueba = '';

    protected function setUp(): void
    {
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
        $resultado = AyudanteHttp::obtener('https://httpbin.org/status/404');
        $this->assertFalse($resultado['exito']);
        $this->assertSame(404, $resultado['codigo']);
        $this->assertSame('No encontrado', $resultado['error']);
    }

    public function testObtenerGetExitoso(): void
    {
        $resultado = AyudanteHttp::obtener('https://httpbin.org/get');
        $this->assertTrue($resultado['exito']);
        $this->assertSame(200, $resultado['codigo']);
        $this->assertIsArray($resultado['cuerpo']);
        $this->assertStringContainsString('httpbin', $resultado['cuerpoCrudo']);
        $this->assertGreaterThan(0, $resultado['tiempo']);
    }

    public function testPostJsonExitoso(): void
    {
        $datos = ['nombre' => 'test', 'valor' => 123];
        $resultado = AyudanteHttp::postJson('https://httpbin.org/post', $datos);
        $this->assertTrue($resultado['exito']);
        $this->assertSame(200, $resultado['codigo']);
        $this->assertIsArray($resultado['cuerpo']);
        $this->assertSame($datos, $resultado['cuerpo']['json'] ?? null);
    }

    public function testPostFormulario(): void
    {
        $datos = ['campo1' => 'valor1', 'campo2' => 'valor2'];
        $resultado = AyudanteHttp::post('https://httpbin.org/post', $datos);
        $this->assertTrue($resultado['exito']);
        $this->assertSame(200, $resultado['codigo']);
        $this->assertIsArray($resultado['cuerpo']);
        $this->assertSame('valor1', $resultado['cuerpo']['form']['campo1'] ?? null);
    }

    public function testParaleloDosPeticiones(): void
    {
        $resultados = AyudanteHttp::paralelo([
            'a' => ['url' => 'https://httpbin.org/get?x=1'],
            'b' => ['url' => 'https://httpbin.org/get?x=2'],
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
            'get' => ['url' => 'https://httpbin.org/get?p=1'],
            'post' => [
                'url' => 'https://httpbin.org/post',
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
            'bien' => ['url' => 'https://httpbin.org/get'],
            'mal' => ['url' => 'https://httpbin.org/status/500'],
        ]);
        $this->assertTrue($resultados['bien']['exito']);
        $this->assertFalse($resultados['mal']['exito']);
        $this->assertSame(500, $resultados['mal']['codigo']);
    }

    public function testCabecerasParseadas(): void
    {
        $resultado = AyudanteHttp::obtener('https://httpbin.org/get');
        $this->assertTrue($resultado['exito']);
        $cabeceras = $resultado['cabeceras'];
        $this->assertIsArray($cabeceras);
        $this->assertNotEmpty($cabeceras);
    }

    public function testEnviarConCabecerasCustom(): void
    {
        $resultado = AyudanteHttp::enviar('GET', 'https://httpbin.org/headers', [
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
            'unica' => ['url' => 'https://httpbin.org/get'],
        ]);
        $this->assertCount(1, $resultados);
        $this->assertTrue($resultados['unica']['exito']);
    }

    public function testTiempoEsNumerico(): void
    {
        $resultado = AyudanteHttp::obtener('https://httpbin.org/get');
        $this->assertIsFloat($resultado['tiempo']);
        $this->assertGreaterThan(0, $resultado['tiempo']);
    }

}
