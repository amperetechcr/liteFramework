<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Modelo;

use LiteFramework\Config\ConexionBaseDatos as DB;

abstract class TestCaseDb extends \TestBase
{
    protected ?\PDO $bd = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->bd = DB::obtenerInstancia()->obtenerConector();
    }

    public function tearDown(): void
    {
        $this->resetModeloConexionGlobal();
        DB::resetearInstancia();
    }

    private function resetModeloConexionGlobal(): void
    {
        $ref = new \ReflectionProperty(\LiteFramework\Nucleo\Modelo::class, 'conexionGlobal');
        $ref->setAccessible(true);
        $ref->setValue(null, null);
    }
}
