<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use DateTime;
use LiteFramework\Nucleo\Helpers\Fecha;

class FechaTest extends \TestBase
{
    public function testHoyDevuelveFormatoPorDefecto(): void
    {
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', Fecha::hoy());
    }

    public function testHoyConFormatoPersonalizado(): void
    {
        $this->assertSame(date('d/m/Y'), Fecha::hoy('d/m/Y'));
    }

    public function testAhoraDevuelveFormatoPorDefecto(): void
    {
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', Fecha::ahora());
    }

    public function testFormatearConString(): void
    {
        $this->assertSame('01/01/2023 00:00:00', Fecha::formatear('2023-01-01', 'd/m/Y H:i:s'));
    }

    public function testFormatearConDateTime(): void
    {
        $dt = new DateTime('2023-06-15 14:30:00');
        $this->assertSame('15/06/2023 14:30:00', Fecha::formatear($dt, 'd/m/Y H:i:s'));
    }

    public function testFormatearConFechaVacia(): void
    {
        $this->assertSame('', Fecha::formatear('', 'd/m/Y'));
    }

    public function testFormatearConNull(): void
    {
        $this->assertSame('', Fecha::formatear(null, 'd/m/Y'));
    }

    public function testFormatearConFechaInvalida(): void
    {
        $this->assertSame('', Fecha::formatear('no-es-fecha', 'd/m/Y'));
    }

    public function testDiferenciaEnDias(): void
    {
        $this->assertSame(5, Fecha::diferencia('2023-01-01', '2023-01-06'));
    }

    public function testDiferenciaEnHoras(): void
    {
        $this->assertSame(48, Fecha::diferencia('2023-01-01 00:00:00', '2023-01-03 00:00:00', 'horas'));
    }

    public function testDiferenciaEnMinutos(): void
    {
        $this->assertSame(120, Fecha::diferencia('2023-01-01 00:00:00', '2023-01-01 02:00:00', 'minutos'));
    }

    public function testDiferenciaEnSegundos(): void
    {
        $this->assertSame(3600, Fecha::diferencia('2023-01-01 00:00:00', '2023-01-01 01:00:00', 'segundos'));
    }

    public function testDiferenciaEnSemanas(): void
    {
        $this->assertSame(1, Fecha::diferencia('2023-01-01', '2023-01-08', 'semanas'));
    }

    public function testDiferenciaEnMeses(): void
    {
        $this->assertSame(1, Fecha::diferencia('2023-01-01', '2023-02-01', 'meses'));
    }

    public function testDiferenciaEnAnos(): void
    {
        $this->assertSame(1, Fecha::diferencia('2023-01-01', '2024-01-01', 'anos'));
    }

    public function testRelativoHaceMinutos(): void
    {
        $this->assertMatchesRegularExpression('/^hace \d+ minutos?$/', Fecha::relativo(date('Y-m-d H:i:s', time() - 180)));
    }

    public function testRelativoHaceHoras(): void
    {
        $this->assertMatchesRegularExpression('/^hace \d+ horas?$/', Fecha::relativo(date('Y-m-d H:i:s', time() - 7200)));
    }

    public function testRelativoEnFuturo(): void
    {
        $this->assertMatchesRegularExpression('/^en \d+ minutos?$/', Fecha::relativo(date('Y-m-d H:i:s', time() + 300)));
    }

    public function testRelativoVacio(): void
    {
        $this->assertSame('', Fecha::relativo(''));
    }

    public function testRelativoConNull(): void
    {
        $this->assertSame('', Fecha::relativo(null));
    }

    public function testRelativoConFechaInvalida(): void
    {
        $this->assertSame('', Fecha::relativo('no-valida'));
    }

    public function testEdad(): void
    {
        $this->assertSame(25, Fecha::edad(date('Y-m-d', strtotime('-25 years'))));
    }

    public function testEdadConDateTime(): void
    {
        $dt = new DateTime('-30 years');
        $this->assertSame(30, Fecha::edad($dt));
    }

    public function testEsHoy(): void
    {
        $this->assertTrue(Fecha::esHoy(date('Y-m-d')));
    }

    public function testEsHoyConOtraFecha(): void
    {
        $this->assertFalse(Fecha::esHoy('2020-01-01'));
    }

    public function testEsPasado(): void
    {
        $this->assertTrue(Fecha::esPasado('2020-01-01'));
    }

    public function testEsPasadoConFuturo(): void
    {
        $this->assertFalse(Fecha::esPasado(date('Y-m-d', strtotime('+1 year'))));
    }

    public function testEsFuturo(): void
    {
        $this->assertTrue(Fecha::esFuturo(date('Y-m-d', strtotime('+1 year'))));
    }

    public function testEsFuturoConPasado(): void
    {
        $this->assertFalse(Fecha::esFuturo('2020-01-01'));
    }

    public function testSumarDias(): void
    {
        $resultado = Fecha::sumarDias('2023-01-01', 5);
        $this->assertInstanceOf(DateTime::class, $resultado);
        $this->assertSame('2023-01-06', $resultado->format('Y-m-d'));
    }

    public function testSumarDiasConDateTime(): void
    {
        $dt = new DateTime('2023-01-01');
        $resultado = Fecha::sumarDias($dt, 10);
        $this->assertSame('2023-01-11', $resultado->format('Y-m-d'));
        $this->assertNotSame($dt, $resultado, 'Debe retornar un clon');
    }

    public function testRestarDias(): void
    {
        $resultado = Fecha::restarDias('2023-01-10', 5);
        $this->assertSame('2023-01-05', $resultado->format('Y-m-d'));
    }

    public function testPrimerDiaMes(): void
    {
        $resultado = Fecha::primerDiaMes('2023-06-15');
        $this->assertSame('2023-06-01 00:00:00', $resultado->format('Y-m-d H:i:s'));
    }

    public function testPrimerDiaMesSinParametro(): void
    {
        $resultado = Fecha::primerDiaMes();
        $this->assertSame('01', $resultado->format('d'));
    }

    public function testUltimoDiaMes(): void
    {
        $resultado = Fecha::ultimoDiaMes('2023-02-10');
        $this->assertSame('2023-02-28 23:59:59', $resultado->format('Y-m-d H:i:s'));
    }

    public function testUltimoDiaMesBisiesto(): void
    {
        $resultado = Fecha::ultimoDiaMes('2024-02-10');
        $this->assertSame('2024-02-29', $resultado->format('Y-m-d'));
    }

    public function testCompararIguales(): void
    {
        $this->assertSame(0, Fecha::comparar('2023-01-01', '2023-01-01'));
    }

    public function testCompararMayor(): void
    {
        $this->assertSame(1, Fecha::comparar('2023-06-01', '2023-01-01'));
    }

    public function testCompararMenor(): void
    {
        $this->assertSame(-1, Fecha::comparar('2023-01-01', '2023-06-01'));
    }

    public function testEstaEntre(): void
    {
        $this->assertTrue(Fecha::estaEntre('2023-06-15', '2023-06-01', '2023-06-30'));
    }

    public function testEstaEntreFuera(): void
    {
        $this->assertFalse(Fecha::estaEntre('2023-07-01', '2023-06-01', '2023-06-30'));
    }

    public function testEstaEntreEnLimiteInferior(): void
    {
        $this->assertTrue(Fecha::estaEntre('2023-06-01', '2023-06-01', '2023-06-30'));
    }

    public function testATimestamp(): void
    {
        $timestamp = strtotime('2023-01-01');
        $this->assertSame($timestamp, Fecha::aTimestamp('2023-01-01'));
    }

    public function testATimestampConDateTime(): void
    {
        $dt = new DateTime('2023-06-15');
        $this->assertSame($dt->getTimestamp(), Fecha::aTimestamp($dt));
    }

    public function testCrearSinParametro(): void
    {
        $this->assertInstanceOf(DateTime::class, Fecha::crear());
    }

    public function testCrearConString(): void
    {
        $dt = Fecha::crear('2023-01-01');
        $this->assertSame('2023-01-01', $dt->format('Y-m-d'));
    }

    public function testCrearConDateTimeRetornaClon(): void
    {
        $original = new DateTime('2023-01-01');
        $clon = Fecha::crear($original);
        $this->assertNotSame($original, $clon);
        $this->assertSame($original->format('Y-m-d'), $clon->format('Y-m-d'));
    }

    public function testCrearConNull(): void
    {
        $this->assertInstanceOf(DateTime::class, Fecha::crear(null));
    }

    public function testAMySQL(): void
    {
        $this->assertSame('2023-06-15 14:30:00', Fecha::aMySQL('2023-06-15 14:30:00'));
    }
}
