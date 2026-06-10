<?php
use PHPUnit\Framework\TestCase;

class AyudanteFechaTest extends TestCase {
    public function testAhora(): void {
        $ahora = Fecha::ahora();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $ahora);
    }

    public function testHoy(): void {
        $hoy = Fecha::hoy();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $hoy);
    }

    public function testFormatear(): void {
        $this->assertEquals('01/01/2024', Fecha::formatear('2024-01-01', 'd/m/Y'));
    }

    public function testDiferenciaEnDias(): void {
        $diff = Fecha::diferencia('2024-01-01', '2024-01-11', 'dias');
        $this->assertEquals(10, $diff);
    }

    public function testDiferenciaEnHoras(): void {
        $diff = Fecha::diferencia('2024-01-01 00:00:00', '2024-01-02 00:00:00', 'horas');
        $this->assertEquals(24, $diff);
    }

    public function testDiferenciaEnMinutos(): void {
        $diff = Fecha::diferencia('2024-01-01 00:00:00', '2024-01-01 01:30:00', 'minutos');
        $this->assertEquals(90, $diff);
    }

    public function testEsHoy(): void {
        $this->assertTrue(Fecha::esHoy(date('Y-m-d')));
        $this->assertFalse(Fecha::esHoy('2020-01-01'));
    }

    public function testEsPasado(): void {
        $this->assertTrue(Fecha::esPasado('2020-01-01'));
        $this->assertFalse(Fecha::esPasado(date('Y-m-d', strtotime('+1 day'))));
    }

    public function testEsFuturo(): void {
        $this->assertTrue(Fecha::esFuturo(date('Y-m-d', strtotime('+1 day'))));
        $this->assertFalse(Fecha::esFuturo('2020-01-01'));
    }

    public function testEdad(): void {
        $fechaNac = date('Y-m-d', strtotime('-25 years'));
        $this->assertEquals(25, Fecha::edad($fechaNac));
    }

    public function testSumarDias(): void {
        $fecha = Fecha::sumarDias('2024-01-01', 10);
        $this->assertEquals('2024-01-11', $fecha->format('Y-m-d'));
    }

    public function testRestarDias(): void {
        $fecha = Fecha::restarDias('2024-01-11', 10);
        $this->assertEquals('2024-01-01', $fecha->format('Y-m-d'));
    }

    public function testComparar(): void {
        $this->assertEquals(0, Fecha::comparar('2024-01-01', '2024-01-01'));
        $this->assertEquals(-1, Fecha::comparar('2024-01-01', '2024-01-02'));
        $this->assertEquals(1, Fecha::comparar('2024-01-02', '2024-01-01'));
    }

    public function testEstaEntre(): void {
        $this->assertTrue(Fecha::estaEntre('2024-01-05', '2024-01-01', '2024-01-10'));
        $this->assertFalse(Fecha::estaEntre('2024-01-15', '2024-01-01', '2024-01-10'));
    }

    public function testPrimerDiaMes(): void {
        $primero = Fecha::primerDiaMes('2024-06-15');
        $this->assertEquals('2024-06-01', $primero->format('Y-m-d'));
    }

    public function testUltimoDiaMes(): void {
        $ultimo = Fecha::ultimoDiaMes('2024-06-15');
        $this->assertEquals('2024-06-30', $ultimo->format('Y-m-d'));
    }

    public function testAMySQL(): void {
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', Fecha::aMySQL('2024-01-01'));
    }

    public function testRelativo(): void {
        $relativo = Fecha::relativo(date('Y-m-d H:i:s', strtotime('-2 hours')));
        $this->assertStringContainsString('hora', $relativo);
    }
}
