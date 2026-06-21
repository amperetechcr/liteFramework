<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Modelo;

require_once __DIR__ . '/TestCaseDb.php';

use LiteFramework\Nucleo\DialectoBaseDatos;
use PDO;

class DialectoBaseDatosTest extends TestCaseDb
{
    private function driver(): string
    {
        return $this->bd->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function testEsMySQL(): void
    {
        $esperado = $this->driver() === 'mysql';
        $this->assertSame($esperado, DialectoBaseDatos::esMySQL($this->bd));
    }

    public function testEsSQLite(): void
    {
        $esperado = $this->driver() === 'sqlite';
        $this->assertSame($esperado, DialectoBaseDatos::esSQLite($this->bd));
    }

    public function testEsMySQLEsSQLiteSonOpuestos(): void
    {
        $mysql = DialectoBaseDatos::esMySQL($this->bd);
        $sqlite = DialectoBaseDatos::esSQLite($this->bd);
        $this->assertNotSame($mysql, $sqlite);
    }

    public function testFechaAhoraNoVacia(): void
    {
        $expr = DialectoBaseDatos::fechaAhora($this->bd);
        $this->assertNotEmpty($expr);
    }

    public function testFechaAhoraContieneNowOCurrentTimestamp(): void
    {
        $expr = DialectoBaseDatos::fechaAhora($this->bd);
        if ($this->driver() === 'sqlite') {
            $this->assertStringContainsString("datetime", $expr);
        } else {
            $this->assertStringContainsString("NOW", $expr);
        }
    }

    public function testFechaHoyNoVacia(): void
    {
        $expr = DialectoBaseDatos::fechaHoy($this->bd);
        $this->assertNotEmpty($expr);
    }

    public function testFechaRestarDay(): void
    {
        $expr = DialectoBaseDatos::fechaRestar($this->bd, 'day', 7);
        $this->assertNotEmpty($expr);
        if ($this->driver() === 'sqlite') {
            $this->assertStringContainsString("-7 day", $expr);
        } else {
            $this->assertStringContainsStringIgnoringCase("INTERVAL 7 DAY", $expr);
        }
    }

    public function testFechaRestarHour(): void
    {
        $expr = DialectoBaseDatos::fechaRestar($this->bd, 'hour', 2);
        $this->assertNotEmpty($expr);
        if ($this->driver() === 'sqlite') {
            $this->assertStringContainsString("-2 hour", $expr);
        } else {
            $this->assertStringContainsStringIgnoringCase("INTERVAL 2 HOUR", $expr);
        }
    }

    public function testFechaRestarMinute(): void
    {
        $expr = DialectoBaseDatos::fechaRestar($this->bd, 'minute', 30);
        $this->assertNotEmpty($expr);
        if ($this->driver() === 'sqlite') {
            $this->assertStringContainsString("-30 minute", $expr);
        } else {
            $this->assertStringContainsStringIgnoringCase("INTERVAL 30 MINUTE", $expr);
        }
    }

    public function testExtraerFecha(): void
    {
        $expr = DialectoBaseDatos::extraerFecha($this->bd, 'fecha_creacion');
        $this->assertStringContainsString('fecha_creacion', $expr);
        $this->assertStringContainsString('DATE', strtoupper($expr));
    }

    public function testAutoIncremento(): void
    {
        $expr = DialectoBaseDatos::autoIncremento();
        $this->assertStringContainsString('AUTOINCREMENT', strtoupper($expr));
        $this->assertStringContainsString('PRIMARY KEY', strtoupper($expr));
    }

    public function testCrearTablaSufijo(): void
    {
        $this->assertSame('', DialectoBaseDatos::crearTablaSufijo());
    }
}
