<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use PHPUnit\Framework\TestCase;
use LiteFramework\Nucleo\Helpers\AyudanteRendimiento;

class AyudanteRendimientoTest extends TestCase
{
    protected function setUp(): void
    {
        AyudanteRendimiento::limpiar();
    }

    protected function tearDown(): void
    {
        AyudanteRendimiento::limpiar();
    }

    public function testIniciarYDetener(): void
    {
        AyudanteRendimiento::iniciar('test_basico');
        usleep(1000);
        $resultado = AyudanteRendimiento::detener('test_basico');

        $this->assertSame('test_basico', $resultado['nombre']);
        $this->assertGreaterThan(0, $resultado['tiempo']);
        $this->assertArrayHasKey('memoria', $resultado);
        $this->assertArrayHasKey('memoriaPico', $resultado);
    }

    public function testDetenerSinIniciar(): void
    {
        $resultado = AyudanteRendimiento::detener('no_existe');
        $this->assertSame('no_existe', $resultado['nombre']);
        $this->assertSame(0.0, $resultado['tiempo']);
        $this->assertArrayHasKey('error', $resultado);
    }

    public function testMedirFuncionSimple(): void
    {
        $resultado = AyudanteRendimiento::medir(function () {
            $sum = 0;
            for ($i = 0; $i < 100000; $i++) {
                $sum += $i;
            }
            return $sum;
        }, 'suma_larga');

        $this->assertSame('suma_larga', $resultado['nombre']);
        $this->assertGreaterThan(0, $resultado['tiempo']);
        $this->assertSame(1, $resultado['iteraciones']);
    }

    public function testMedirConNombreAutomatico(): void
    {
        $resultado = AyudanteRendimiento::medir(function () {
            return 1;
        });

        $this->assertStringContainsString('medicion_', $resultado['nombre']);
    }

    public function testMedirMultiplesIteraciones(): void
    {
        $contador = 0;
        $resultado = AyudanteRendimiento::medir(function () use (&$contador) {
            $contador++;
            return $contador;
        }, 'contar', 10);

        $this->assertSame(10, $resultado['iteraciones']);
        $this->assertGreaterThan(0, $resultado['tiempoTotal']);
        $this->assertGreaterThan(0, $resultado['tiempo']);
    }

    public function testMedirConCeroIteracionesUsaUna(): void
    {
        $resultado = AyudanteRendimiento::medir(function () {
            return true;
        }, 'cero_iter', 0);

        $this->assertSame(1, $resultado['iteraciones']);
    }

    public function testCompararEscenarios(): void
    {
        $resultados = AyudanteRendimiento::comparar([
            'rapido' => function () {
                return 1 + 1;
            },
            'lento' => function () {
                usleep(500);
                return 1 + 1;
            },
        ], 5);

        $this->assertCount(2, $resultados);
        $this->assertArrayHasKey('rapido', $resultados);
        $this->assertArrayHasKey('lento', $resultados);
        $this->assertArrayHasKey('diferencia', $resultados['lento']);
        $this->assertArrayHasKey('porcentaje', $resultados['lento']);
        $this->assertGreaterThan($resultados['rapido']['tiempo'], $resultados['lento']['tiempo']);
    }

    public function testCompararEscenarioUnico(): void
    {
        $resultados = AyudanteRendimiento::comparar([
            'unico' => function () {
                return 1;
            },
        ], 3);

        $this->assertCount(1, $resultados);
        $this->assertArrayHasKey('unico', $resultados);
        $this->assertArrayNotHasKey('diferencia', $resultados['unico']);
    }

    public function testReporte(): void
    {
        AyudanteRendimiento::iniciar('reporte_test');
        usleep(500);
        AyudanteRendimiento::detener('reporte_test');

        $reporte = AyudanteRendimiento::reporte();

        $this->assertArrayHasKey('mediciones', $reporte);
        $this->assertArrayHasKey('resumen', $reporte);
        $this->assertCount(1, $reporte['mediciones']);
        $this->assertSame(1, $reporte['resumen']['total']);
        $this->assertGreaterThan(0, $reporte['resumen']['tiempoTotal']);
        $this->assertArrayHasKey('memoriaMaxLegible', $reporte['resumen']);
    }

    public function testFormatearTexto(): void
    {
        AyudanteRendimiento::iniciar('formato_test');
        AyudanteRendimiento::detener('formato_test');

        $texto = AyudanteRendimiento::formatearTexto();

        $this->assertStringContainsString('Perfil de Rendimiento', $texto);
        $this->assertStringContainsString('formato_test', $texto);
        $this->assertStringContainsString('Resumen', $texto);
    }

    public function testCabeceras(): void
    {
        AyudanteRendimiento::iniciar('cabeceras_test');
        AyudanteRendimiento::detener('cabeceras_test');

        $cabeceras = AyudanteRendimiento::cabeceras();

        $this->assertArrayHasKey('X-Lite-Tiempo', $cabeceras);
        $this->assertArrayHasKey('X-Lite-Memoria', $cabeceras);
        $this->assertArrayHasKey('X-Lite-Mediciones', $cabeceras);
        $this->assertStringContainsString('ms', $cabeceras['X-Lite-Tiempo']);
    }

    public function testReporteVacio(): void
    {
        $reporte = AyudanteRendimiento::reporte();

        $this->assertEmpty($reporte['mediciones']);
        $this->assertSame(0, $reporte['resumen']['total']);
        $this->assertSame(0.0, $reporte['resumen']['tiempoTotal']);
    }

    public function testLoggear(): void
    {
        $archivoTest = DIRECTORIO_RAIZ . '/storage/logs/test_rendimiento.log';

        AyudanteRendimiento::iniciar('log_test');
        AyudanteRendimiento::detener('log_test');

        $ok = AyudanteRendimiento::loggear($archivoTest);
        $this->assertTrue($ok);
        $this->assertFileExists($archivoTest);

        $contenido = file_get_contents($archivoTest);
        $this->assertStringContainsString('log_test', $contenido);
        $this->assertStringContainsString('Perfil de Rendimiento', $contenido);

        unlink($archivoTest);
    }

    public function testTiempoMilisegundos(): void
    {
        AyudanteRendimiento::iniciar('espera');
        usleep(20000);
        $resultado = AyudanteRendimiento::detener('espera');

        $this->assertGreaterThanOrEqual(15, $resultado['tiempo']);
        $this->assertLessThan(50, $resultado['tiempo']);
    }

    public function testMedicionPreservaOrden(): void
    {
        AyudanteRendimiento::medir(fn() => 1, 'primera');
        AyudanteRendimiento::medir(fn() => 2, 'segunda');
        AyudanteRendimiento::medir(fn() => 3, 'tercera');

        $reporte = AyudanteRendimiento::reporte();
        $nombres = array_keys($reporte['mediciones']);

        $this->assertSame(['primera', 'segunda', 'tercera'], $nombres);
    }

    public function testLoggearCreaDirectorio(): void
    {
        $rutaTemp = DIRECTORIO_RAIZ . '/storage/logs/sub/test_rendimiento.log';

        AyudanteRendimiento::iniciar('dir_test');
        AyudanteRendimiento::detener('dir_test');

        $ok = AyudanteRendimiento::loggear($rutaTemp);
        $this->assertTrue($ok);

        unlink($rutaTemp);
        rmdir(dirname($rutaTemp));
    }
}
