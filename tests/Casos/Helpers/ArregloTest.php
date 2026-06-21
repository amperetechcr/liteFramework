<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use InvalidArgumentException;
use LiteFramework\Nucleo\Helpers\Arreglo;

class ArregloTest extends \TestBase
{
    public function testPrimeroDevuelvePrimerElemento(): void
    {
        $this->assertSame(1, Arreglo::primero([1, 2, 3]));
    }

    public function testPrimeroConArregloVacioDevuelveNull(): void
    {
        $this->assertNull(Arreglo::primero([]));
    }

    public function testPrimeroConArregloAsociativo(): void
    {
        $this->assertSame(1, Arreglo::primero(['a' => 1, 'b' => 2]));
    }

    public function testUltimoDevuelveUltimoElemento(): void
    {
        $this->assertSame(3, Arreglo::ultimo([1, 2, 3]));
    }

    public function testUltimoConArregloVacioDevuelveNull(): void
    {
        $this->assertNull(Arreglo::ultimo([]));
    }

    public function testObtenerDevuelveValorPorClave(): void
    {
        $this->assertSame(42, Arreglo::obtener(['x' => 42], 'x'));
    }

    public function testObtenerConClaveInexistenteDevuelveDefecto(): void
    {
        $this->assertNull(Arreglo::obtener(['x' => 1], 'y'));
        $this->assertSame('predeterminado', Arreglo::obtener(['x' => 1], 'y', 'predeterminado'));
    }

    public function testObtenerConClaveNumerica(): void
    {
        $this->assertSame('b', Arreglo::obtener(['a', 'b', 'c'], 1));
    }

    public function testTomarDevuelvePrimerosNElementos(): void
    {
        $this->assertSame([1, 2], Arreglo::tomar([1, 2, 3, 4], 2));
    }

    public function testTomarConLimiteCeroDevuelveVacio(): void
    {
        $this->assertSame([], Arreglo::tomar([1, 2, 3], 0));
    }

    public function testTomarConLimiteNegativoDevuelveVacio(): void
    {
        $this->assertSame([], Arreglo::tomar([1, 2, 3], -1));
    }

    public function testTomarConLimiteMayorQueArreglo(): void
    {
        $this->assertSame([1, 2], Arreglo::tomar([1, 2], 10));
    }

    public function testIgnorarOmitePrimerosNElementos(): void
    {
        $this->assertSame([3, 4], array_values(Arreglo::ignorar([1, 2, 3, 4], 2)));
    }

    public function testIgnorarConLimiteCeroDevuelveOriginal(): void
    {
        $this->assertSame([1, 2, 3], Arreglo::ignorar([1, 2, 3], 0));
    }

    public function testIgnorarConLimiteNegativoDevuelveOriginal(): void
    {
        $this->assertSame([1, 2, 3], Arreglo::ignorar([1, 2, 3], -1));
    }

    public function testPluckExtraeColumna(): void
    {
        $datos = [
            ['id' => 1, 'nombre' => 'Alice'],
            ['id' => 2, 'nombre' => 'Bob'],
        ];
        $this->assertSame(['Alice', 'Bob'], Arreglo::pluck($datos, 'nombre'));
    }

    public function testPluckConIndice(): void
    {
        $datos = [
            ['id' => 1, 'nombre' => 'Alice'],
            ['id' => 2, 'nombre' => 'Bob'],
        ];
        $this->assertSame([1 => 'Alice', 2 => 'Bob'], Arreglo::pluck($datos, 'nombre', 'id'));
    }

    public function testPluckConObjetos(): void
    {
        $a = (object)['id' => 1, 'nombre' => 'Alice'];
        $b = (object)['id' => 2, 'nombre' => 'Bob'];
        $this->assertSame(['Alice', 'Bob'], Arreglo::pluck([$a, $b], 'nombre'));
    }

    public function testPluckConClaveInexistenteDevuelveNull(): void
    {
        $datos = [['id' => 1], ['id' => 2]];
        $this->assertSame([null, null], Arreglo::pluck($datos, 'inexistente'));
    }

    public function testPluckConArregloVacio(): void
    {
        $this->assertSame([], Arreglo::pluck([], 'nombre'));
    }

    public function testAgruparAgrupaPorClave(): void
    {
        $datos = [
            ['tipo' => 'A', 'val' => 1],
            ['tipo' => 'B', 'val' => 2],
            ['tipo' => 'A', 'val' => 3],
        ];
        $resultado = Arreglo::agrupar($datos, 'tipo');
        $this->assertCount(2, $resultado['A']);
        $this->assertCount(1, $resultado['B']);
    }

    public function testAgruparConArregloVacio(): void
    {
        $this->assertSame([], Arreglo::agrupar([], 'tipo'));
    }

    public function testFiltrarAplicaCallback(): void
    {
        $this->assertSame([2, 4], Arreglo::filtrar([1, 2, 3, 4], fn($v) => $v % 2 === 0));
    }

    public function testFiltrarReindexaArreglo(): void
    {
        $resultado = Arreglo::filtrar(['a', 'b', 'c'], fn($v) => $v !== 'b');
        $this->assertSame([0 => 'a', 1 => 'c'], $resultado);
    }

    public function testOrdenarAscendente(): void
    {
        $datos = [
            ['nombre' => 'Zeta'],
            ['nombre' => 'Alpha'],
        ];
        $ordenado = Arreglo::ordenar($datos, 'nombre');
        $this->assertSame('Alpha', $ordenado[0]['nombre']);
        $this->assertSame('Zeta', $ordenado[1]['nombre']);
    }

    public function testOrdenarDescendente(): void
    {
        $datos = [
            ['nombre' => 'Alpha'],
            ['nombre' => 'Zeta'],
        ];
        $ordenado = Arreglo::ordenar($datos, 'nombre', 'DESC');
        $this->assertSame('Zeta', $ordenado[0]['nombre']);
        $this->assertSame('Alpha', $ordenado[1]['nombre']);
    }

    public function testOrdenarConObjetos(): void
    {
        $a = (object)['nombre' => 'Zeta'];
        $b = (object)['nombre' => 'Alpha'];
        $ordenado = Arreglo::ordenar([$a, $b], 'nombre');
        $this->assertSame('Alpha', $ordenado[0]->nombre);
    }

    public function testAplanar(): void
    {
        $this->assertSame([1, 2, 3, 4], Arreglo::aplanar([1, [2, 3], 4]));
    }

    public function testAplanarConProfundidad(): void
    {
        $this->assertSame([1, 2, [3]], Arreglo::aplanar([1, [2, [3]]], 1));
    }

    public function testAplanarArregloVacio(): void
    {
        $this->assertSame([], Arreglo::aplanar([]));
    }

    public function testUnico(): void
    {
        $this->assertSame([1, 2, 3], Arreglo::unico([1, 2, 2, 3, 1]));
    }

    public function testUnicoReindexa(): void
    {
        $resultado = Arreglo::unico(['a', 'b', 'a']);
        $this->assertSame([0 => 'a', 1 => 'b'], $resultado);
    }

    public function testContieneEncuentraValor(): void
    {
        $this->assertTrue(Arreglo::contiene([1, 2, 3], 2));
    }

    public function testContieneNoEncuentraValor(): void
    {
        $this->assertFalse(Arreglo::contiene([1, 2, 3], 4));
    }

    public function testContieneEstrictoConTipos(): void
    {
        $this->assertFalse(Arreglo::contiene(['1', '2'], 2, true));
        $this->assertTrue(Arreglo::contiene(['1', '2'], '2', true));
    }

    public function testIndiceDeEncuentraIndice(): void
    {
        $this->assertSame(2, Arreglo::indiceDe(['a', 'b', 'c'], 'c'));
    }

    public function testIndiceDeNoEncuentraDevuelveNull(): void
    {
        $this->assertNull(Arreglo::indiceDe(['a', 'b'], 'z'));
    }

    public function testChunks(): void
    {
        $this->assertCount(2, Arreglo::chunks([1, 2, 3, 4, 5], 3));
        $this->assertSame([[1, 2, 3], [4, 5]], Arreglo::chunks([1, 2, 3, 4, 5], 3));
    }

    public function testChunksConTamanoCero(): void
    {
        $this->assertSame([[1, 2, 3]], Arreglo::chunks([1, 2, 3], 0));
    }

    public function testChunksConTamanoNegativo(): void
    {
        $this->assertSame([[1, 2, 3]], Arreglo::chunks([1, 2, 3], -1));
    }

    public function testCombinar(): void
    {
        $this->assertSame(['a' => 1, 'b' => 2], Arreglo::combinar(['a', 'b'], [1, 2]));
    }

    public function testCombinarConLongitudDiferenteLanzaExcepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Arreglo::combinar(['a', 'b'], [1]);
    }

    public function testInvertir(): void
    {
        $this->assertSame([2 => 3, 1 => 2, 0 => 1], Arreglo::invertir([1, 2, 3]));
    }

    public function testInvertirPreservaClaves(): void
    {
        $this->assertSame(['b' => 2, 'a' => 1], Arreglo::invertir(['a' => 1, 'b' => 2]));
    }

    public function testClaves(): void
    {
        $this->assertSame(['a', 'b'], Arreglo::claves(['a' => 1, 'b' => 2]));
    }

    public function testValores(): void
    {
        $this->assertSame([1, 2], Arreglo::valores(['a' => 1, 'b' => 2]));
    }

    public function testContarPorConCallback(): void
    {
        $resultado = Arreglo::contarPor([1.1, 1.9, 2.5, 3.0], fn($v) => (int)$v);
        $this->assertSame([1 => 2, 2 => 1, 3 => 1], $resultado);
    }

    public function testReducir(): void
    {
        $this->assertSame(10, Arreglo::reducir([1, 2, 3, 4], fn($c, $v) => $c + $v, 0));
    }

    public function testReducirSinInicial(): void
    {
        $this->assertSame(6, Arreglo::reducir([1, 2, 3], fn($c, $v) => $c + $v));
    }

    public function testCadaAplicaCallback(): void
    {
        $resultado = Arreglo::cada([1, 2, 3], fn($v) => $v * 2);
        $this->assertSame([2, 4, 6], $resultado);
    }

    public function testCadaPreservaClaves(): void
    {
        $resultado = Arreglo::cada(['a' => 1], fn($v) => $v + 1);
        $this->assertSame(['a' => 2], $resultado);
    }

    public function testEstaVacioConArregloVacio(): void
    {
        $this->assertTrue(Arreglo::estaVacio([]));
    }

    public function testEstaVacioConArregloNoVacio(): void
    {
        $this->assertFalse(Arreglo::estaVacio([1]));
    }

    public function testConteo(): void
    {
        $this->assertSame(3, Arreglo::conteo([1, 2, 3]));
    }

    public function testConteoArregloVacio(): void
    {
        $this->assertSame(0, Arreglo::conteo([]));
    }

    public function testBuscar(): void
    {
        $resultado = Arreglo::buscar([1, 2, 3, 4, 5], fn($v) => $v > 3);
        $this->assertSame([3 => 4, 4 => 5], $resultado);
    }

    public function testSumar(): void
    {
        $datos = [
            ['valor' => 10],
            ['valor' => 20],
            ['valor' => 30],
        ];
        $this->assertSame(60, Arreglo::sumar($datos, 'valor'));
    }

    public function testSumarConArregloVacio(): void
    {
        $this->assertSame(0, Arreglo::sumar([], 'valor'));
    }

    public function testPromedio(): void
    {
        $datos = [
            ['valor' => 10],
            ['valor' => 20],
            ['valor' => 30],
        ];
        $this->assertSame(20.0, Arreglo::promedio($datos, 'valor'));
    }

    public function testPromedioConArregloVacio(): void
    {
        $this->assertSame(0.0, Arreglo::promedio([], 'valor'));
    }
}
