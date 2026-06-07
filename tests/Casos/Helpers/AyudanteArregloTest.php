<?php
use PHPUnit\Framework\TestCase;

class AyudanteArregloTest extends TestCase {
    public function testPrimero(): void {
        $this->assertEquals(1, Arreglo::primero([1, 2, 3]));
        $this->assertNull(Arreglo::primero([]));
    }

    public function testUltimo(): void {
        $this->assertEquals(3, Arreglo::ultimo([1, 2, 3]));
        $this->assertNull(Arreglo::ultimo([]));
    }

    public function testObtener(): void {
        $arr = ['a' => 1, 'b' => 2];
        $this->assertEquals(1, Arreglo::obtener($arr, 'a'));
        $this->assertNull(Arreglo::obtener($arr, 'c'));
        $this->assertEquals('def', Arreglo::obtener($arr, 'c', 'def'));
    }

    public function testTomar(): void {
        $this->assertEquals([1, 2], Arreglo::tomar([1, 2, 3], 2));
    }

    public function testIgnorar(): void {
        $this->assertEquals([3], Arreglo::ignorar([1, 2, 3], 2));
    }

    public function testPluck(): void {
        $arr = [
            ['id' => 1, 'nombre' => 'A'],
            ['id' => 2, 'nombre' => 'B'],
        ];
        $this->assertEquals(['A', 'B'], Arreglo::pluck($arr, 'nombre'));
    }

    public function testPluckConIndice(): void {
        $arr = [
            ['id' => 1, 'nombre' => 'A'],
            ['id' => 2, 'nombre' => 'B'],
        ];
        $this->assertEquals([1 => 'A', 2 => 'B'], Arreglo::pluck($arr, 'nombre', 'id'));
    }

    public function testAgrupar(): void {
        $arr = [
            ['tipo' => 'a', 'val' => 1],
            ['tipo' => 'b', 'val' => 2],
            ['tipo' => 'a', 'val' => 3],
        ];
        $agrupado = Arreglo::agrupar($arr, 'tipo');
        $this->assertCount(2, $agrupado['a']);
        $this->assertCount(1, $agrupado['b']);
    }

    public function testFiltrar(): void {
        $arr = [1, 2, 3, 4];
        $result = Arreglo::filtrar($arr, fn($v) => $v > 2);
        $this->assertEquals([3, 4], $result);
    }

    public function testOrdenarAsc(): void {
        $arr = [['n' => 3], ['n' => 1], ['n' => 2]];
        $ordenado = Arreglo::ordenar($arr, 'n', 'ASC');
        $this->assertEquals(1, $ordenado[0]['n']);
        $this->assertEquals(2, $ordenado[1]['n']);
        $this->assertEquals(3, $ordenado[2]['n']);
    }

    public function testOrdenarDesc(): void {
        $arr = [['n' => 1], ['n' => 3], ['n' => 2]];
        $ordenado = Arreglo::ordenar($arr, 'n', 'DESC');
        $this->assertEquals(3, $ordenado[0]['n']);
    }

    public function testAplanar(): void {
        $arr = [1, [2, [3, 4]]];
        $this->assertEquals([1, 2, [3, 4]], Arreglo::aplanar($arr, 1));
        $this->assertEquals([1, 2, 3, 4], Arreglo::aplanar($arr));
    }

    public function testUnico(): void {
        $this->assertEquals([1, 2, 3], Arreglo::unico([1, 2, 2, 3, 1]));
    }

    public function testContiene(): void {
        $this->assertTrue(Arreglo::contiene([1, 2, 3], 2));
        $this->assertFalse(Arreglo::contiene([1, 2, 3], 4));
        $this->assertFalse(Arreglo::contiene([1, 2, 3], '2'));
    }

    public function testContieneNoEstricto(): void {
        $this->assertTrue(Arreglo::contiene([1, 2, 3], '2', false));
    }

    public function testChunks(): void {
        $chunks = Arreglo::chunks([1, 2, 3, 4, 5], 2);
        $this->assertCount(3, $chunks);
        $this->assertEquals([1, 2], $chunks[0]);
    }

    public function testCombinar(): void {
        $this->assertEquals(['a' => 1, 'b' => 2], Arreglo::combinar(['a', 'b'], [1, 2]));
    }

    public function testSumar(): void {
        $arr = [['v' => 10], ['v' => 20], ['v' => 30]];
        $this->assertEquals(60, Arreglo::sumar($arr, 'v'));
    }

    public function testPromedio(): void {
        $arr = [['v' => 10], ['v' => 20], ['v' => 30]];
        $this->assertEquals(20.0, Arreglo::promedio($arr, 'v'));
    }

    public function testContarPor(): void {
        $arr = [['g' => 'a'], ['g' => 'b'], ['g' => 'a']];
        $contado = Arreglo::contarPor($arr, 'g');
        $this->assertEquals(2, $contado['a']);
        $this->assertEquals(1, $contado['b']);
    }

    public function testEstaVacio(): void {
        $this->assertTrue(Arreglo::estaVacio([]));
        $this->assertFalse(Arreglo::estaVacio([1]));
    }

    public function testInvertir(): void {
        $resultado = Arreglo::invertir(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertEquals(['c' => 3, 'b' => 2, 'a' => 1], $resultado);
    }

    public function testClaves(): void {
        $this->assertEquals(['a', 'b'], Arreglo::claves(['a' => 1, 'b' => 2]));
    }

    public function testValores(): void {
        $this->assertEquals([1, 2], Arreglo::valores(['a' => 1, 'b' => 2]));
    }
}
