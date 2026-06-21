<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use LiteFramework\Nucleo\Helpers\AyudanteRendimiento as Rendimiento;

class RendimientoTest extends \TestBase
{
    public function setUp(): void
    {
        Rendimiento::limpiar();
    }

    public function testIniciarYDetener(): void
    {
        Rendimiento::iniciar('test_op');
        usleep(1000);
        $resultado = Rendimiento::detener('test_op');

        $this->assertIsArray($resultado);
        $this->assertSame('test_op', $resultado['nombre']);
        $this->assertGreaterThan(0, $resultado['tiempo']);
        $this->assertArrayHasKey('memoria', $resultado);
        $this->assertArrayHasKey('memoriaPico', $resultado);
        $this->assertArrayNotHasKey('error', $resultado);
    }

    public function testDetenerSinIniciar(): void
    {
        $resultado = Rendimiento::detener('no_existe');
        $this->assertArrayHasKey('error', $resultado);
        $this->assertSame('No se encontro punto de inicio', $resultado['error']);
    }

    public function testDetenerSinIniciarDevuelveCeros(): void
    {
        $resultado = Rendimiento::detener('fantasma');
        $this->assertSame(0.0, $resultado['tiempo']);
        $this->assertSame(0, $resultado['memoria']);
        $this->assertSame(0, $resultado['memoriaPico']);
    }

    public function testMedir(): void
    {
        $resultado = Rendimiento::medir(fn() => array_sum(range(1, 100)), 'suma');
        $this->assertIsArray($resultado);
        $this->assertSame('suma', $resultado['nombre']);
        $this->assertGreaterThan(0, $resultado['tiempo']);
        $this->assertArrayHasKey('iteraciones', $resultado);
        $this->assertArrayHasKey('tiempoTotal', $resultado);
    }

    public function testMedirConMultiplesIteraciones(): void
    {
        $resultado = Rendimiento::medir(fn() => 1 + 1, 'rapida', 10);
        $this->assertSame(10, $resultado['iteraciones']);
        $this->assertGreaterThanOrEqual($resultado['tiempo'], $resultado['tiempoTotal']);
    }

    public function testMedirConCeroIteracionesUsaUna(): void
    {
        $resultado = Rendimiento::medir(fn() => 42, 'cero_iter', 0);
        $this->assertSame(1, $resultado['iteraciones']);
    }

    public function testMedirDevuelveResultadoDeCallback(): void
    {
        $resultado = Rendimiento::medir(fn() => 'retorno_callback', 'cb');
        $this->assertArrayNotHasKey('resultado', $resultado);
    }

    public function testComparar(): void
    {
        $resultados = Rendimiento::comparar([
            'rapido' => fn() => 1 + 1,
            'lento' => fn() => array_sum(range(1, 1000)),
        ], 5);

        $this->assertCount(2, $resultados);
        $this->assertArrayHasKey('rapido', $resultados);
        $this->assertArrayHasKey('lento', $resultados);

        $this->assertArrayHasKey('diferencia', $resultados['rapido']);
        $this->assertArrayHasKey('porcentaje', $resultados['rapido']);
    }

    public function testCompararConUnSoloEscenario(): void
    {
        $resultados = Rendimiento::comparar([
            'unico' => fn() => 1,
        ]);
        $this->assertCount(1, $resultados);
        $this->assertArrayNotHasKey('diferencia', $resultados['unico']);
    }

    public function testReporte(): void
    {
        Rendimiento::iniciar('a');
        Rendimiento::detener('a');

        $reporte = Rendimiento::reporte();
        $this->assertArrayHasKey('mediciones', $reporte);
        $this->assertArrayHasKey('resumen', $reporte);
        $this->assertArrayHasKey('total', $reporte['resumen']);
        $this->assertArrayHasKey('tiempoTotal', $reporte['resumen']);
        $this->assertArrayHasKey('memoriaMax', $reporte['resumen']);
        $this->assertGreaterThanOrEqual(1, $reporte['resumen']['total']);
    }

    public function testReporteVacio(): void
    {
        $reporte = Rendimiento::reporte();
        $this->assertSame(0, $reporte['resumen']['total']);
        $this->assertSame(0.0, $reporte['resumen']['tiempoTotal']);
    }

    public function testFormatearTexto(): void
    {
        Rendimiento::iniciar('prueba');
        Rendimiento::detener('prueba');

        $texto = Rendimiento::formatearTexto();
        $this->assertStringContainsString('=== Perfil de Rendimiento ===', $texto);
        $this->assertStringContainsString('prueba', $texto);
        $this->assertStringContainsString('--- Resumen ---', $texto);
    }

    public function testFormatearTextoSinMediciones(): void
    {
        $texto = Rendimiento::formatearTexto();
        $this->assertStringContainsString('=== Perfil de Rendimiento ===', $texto);
        $this->assertStringContainsString('0', $texto);
    }

    public function testLoggear(): void
    {
        $archivoTemp = DIRECTORIO_RAIZ . '/storage/logs/test_rendimiento.log';
        @unlink($archivoTemp);

        Rendimiento::iniciar('log_test');
        Rendimiento::detener('log_test');
        $resultado = Rendimiento::loggear($archivoTemp);

        $this->assertTrue($resultado);
        $this->assertFileExists($archivoTemp);
        $contenido = file_get_contents($archivoTemp);
        $this->assertStringContainsString('=== Perfil de Rendimiento ===', $contenido);

        @unlink($archivoTemp);
    }

    public function testLimpiar(): void
    {
        Rendimiento::iniciar('tmp');
        Rendimiento::detener('tmp');
        Rendimiento::limpiar();

        $reporte = Rendimiento::reporte();
        $this->assertSame(0, $reporte['resumen']['total']);
    }

    public function testCabeceras(): void
    {
        Rendimiento::iniciar('h');
        Rendimiento::detener('h');

        $cabeceras = Rendimiento::cabeceras();
        $this->assertArrayHasKey('X-Lite-Tiempo', $cabeceras);
        $this->assertArrayHasKey('X-Lite-Memoria', $cabeceras);
        $this->assertArrayHasKey('X-Lite-Mediciones', $cabeceras);
    }

    public function testCabecerasSinMediciones(): void
    {
        $cabeceras = Rendimiento::cabeceras();
        $this->assertSame('0.00ms', $cabeceras['X-Lite-Tiempo']);
        $this->assertSame('0', $cabeceras['X-Lite-Mediciones']);
    }
}
