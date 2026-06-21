<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Seguridad;

use LiteFramework\Seguridad\LimitadorPeticiones;
use LiteFramework\Config\ConexionBaseDatos as DB;

class LimitadorPeticionesTest extends \TestBase
{
    private LimitadorPeticiones $limitador;

    public function setUp(): void
    {
        if (!defined('TESTS_RUNNING')) {
            define('TESTS_RUNNING', true);
        }
        $this->limitador = new LimitadorPeticiones();
    }

    public function tearDown(): void
    {
        DB::resetearInstancia();
    }

    public function testHaExcedidoConPocasPeticiones(): void
    {
        $clave = 'test_no_exceed_' . uniqid();
        $this->assertFalse($this->limitador->haExcedido($clave, 5, 60));
    }

    public function testHaExcedidoConMaximoAlcanzado(): void
    {
        $clave = 'test_exceed_' . uniqid();
        for ($i = 0; $i < 6; $i++) {
            $this->limitador->haExcedido($clave, 5, 60);
        }
        $this->assertTrue($this->limitador->haExcedido($clave, 5, 60));
    }

    public function testHaExcedidoDespuesDeReiniciarVentana(): void
    {
        $clave = 'test_window_reset_' . uniqid();
        $this->limitador->haExcedido($clave, 5, 60);
        $this->limitador->reiniciar($clave);
        $this->assertFalse($this->limitador->haExcedido($clave, 5, 60));
    }

    public function testContarIncrementaCorrectamente(): void
    {
        $clave = 'test_count_' . uniqid();
        $v1 = $this->limitador->contar($clave, 60);
        $this->assertSame(1, $v1);
        $v2 = $this->limitador->contar($clave, 60);
        $this->assertSame(2, $v2);
    }

    public function testReiniciarLimpiaContador(): void
    {
        $clave = 'test_reiniciar_' . uniqid();
        $this->limitador->contar($clave, 60);
        $this->limitador->contar($clave, 60);
        $this->limitador->reiniciar($clave);
        $despues = $this->limitador->contar($clave, 60);
        $this->assertSame(1, $despues);
    }

    public function testHashEsDeterminista(): void
    {
        $clave = 'test_determinista_' . uniqid();
        $this->limitador->haExcedido($clave, 10, 60);
        $c1 = $this->limitador->contar($clave, 60);

        $c2 = $this->limitador->contar($clave, 60);
        $this->assertSame($c1 + 1, $c2);
    }

    public function testClavesDiferentesNoInterfieren(): void
    {
        $claveA = 'test_clave_a_' . uniqid();
        $claveB = 'test_clave_b_' . uniqid();

        for ($i = 0; $i < 10; $i++) {
            $this->limitador->haExcedido($claveA, 100, 60);
        }
        $this->assertFalse($this->limitador->haExcedido($claveB, 1, 60));
    }

    public function testHaExcedidoConUnaPeticion(): void
    {
        $clave = 'test_single_' . uniqid();
        $this->assertFalse($this->limitador->haExcedido($clave, 3, 60));
    }

    public function testContarRetornaValorExacto(): void
    {
        $clave = 'test_exact_' . uniqid();
        $v1 = $this->limitador->contar($clave, 60);
        $v2 = $this->limitador->contar($clave, 60);
        $v3 = $this->limitador->contar($clave, 60);
        $this->assertSame(1, $v1);
        $this->assertSame(2, $v2);
        $this->assertSame(3, $v3);
    }

    public function testReiniciarDespuesDeExceder(): void
    {
        $clave = 'test_reiniciar_exceed_' . uniqid();
        for ($i = 0; $i < 6; $i++) {
            $this->limitador->haExcedido($clave, 5, 60);
        }
        $this->assertTrue($this->limitador->haExcedido($clave, 5, 60));
        $this->limitador->reiniciar($clave);
        $this->assertFalse($this->limitador->haExcedido($clave, 5, 60));
    }

    public function testVentanasPequenasFuncionan(): void
    {
        $clave = 'test_small_window_' . uniqid();
        $this->assertFalse($this->limitador->haExcedido($clave, 2, 5));
        $this->assertFalse($this->limitador->haExcedido($clave, 2, 5));
        $this->assertTrue($this->limitador->haExcedido($clave, 2, 5));
    }

    public function testContarDespuesDeReiniciar(): void
    {
        $clave = 'test_reiniciar_count_' . uniqid();
        $this->limitador->contar($clave, 60);
        $this->limitador->contar($clave, 60);
        $this->limitador->reiniciar($clave);
        $v = $this->limitador->contar($clave, 60);
        $this->assertSame(1, $v);
    }
}
