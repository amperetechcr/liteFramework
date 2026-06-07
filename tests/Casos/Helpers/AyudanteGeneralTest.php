<?php
use PHPUnit\Framework\TestCase;

class AyudanteGeneralTest extends TestCase {
    public function testTieneValor(): void {
        $this->assertTrue(General::tieneValor('texto'));
        $this->assertFalse(General::tieneValor(''));
        $this->assertFalse(General::tieneValor(null));
        $this->assertFalse(General::tieneValor([]));
    }

    public function testGenerarToken(): void {
        $token = General::generarToken(32);
        $this->assertEquals(32, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    public function testGenerarTokenDefaultLength(): void {
        $token = General::generarToken();
        $this->assertEquals(32, strlen($token));
    }

    public function testAbooleano(): void {
        $this->assertTrue(General::aBooleano('true'));
        $this->assertTrue(General::aBooleano('si'));
        $this->assertTrue(General::aBooleano('yes'));
        $this->assertTrue(General::aBooleano('on'));
        $this->assertTrue(General::aBooleano(1));
        $this->assertFalse(General::aBooleano('false'));
        $this->assertFalse(General::aBooleano('no'));
        $this->assertFalse(General::aBooleano(0));
        $this->assertFalse(General::aBooleano(''));
    }

    public function testMoneda(): void {
        $this->assertStringContainsString('$', General::moneda(1234.56));
        $this->assertStringContainsString('1,234.56', General::moneda(1234.56));
    }

    public function testNumero(): void {
        $this->assertEquals('1,234.56', General::numero(1234.56));
        $this->assertEquals('1 234,56', General::numero(1234.56, ' ', ','));
    }

    public function testTruncarNumero(): void {
        $this->assertEquals(3.14, General::truncarNumero(3.14159, 2));
        $this->assertEquals(3.0, General::truncarNumero(3.99, 0));
    }

    public function testRedondearNormal(): void {
        $this->assertEquals(4, General::redondear(3.5));
    }

    public function testRedondearArriba(): void {
        $this->assertEquals(4, General::redondear(3.1, 0, 'arriba'));
    }

    public function testRedondearAbajo(): void {
        $this->assertEquals(3, General::redondear(3.9, 0, 'abajo'));
    }

    public function testBytesLegibles(): void {
        $this->assertStringContainsString('KB', General::bytesLegibles(1024));
        $this->assertStringContainsString('MB', General::bytesLegibles(1024 * 1024));
        $this->assertStringContainsString('GB', General::bytesLegibles(1024 * 1024 * 1024));
        $this->assertEquals('500 B', General::bytesLegibles(500));
    }

    public function testDesdeJson(): void {
        $this->assertEquals(['a' => 1], General::desdeJson('{"a":1}'));
        $this->assertNull(General::desdeJson('invalido'));
        $this->assertEquals('def', General::desdeJson('invalido', 'def'));
    }

    public function testEsJson(): void {
        $this->assertTrue(General::esJson('{"a":1}'));
        $this->assertFalse(General::esJson('not-json'));
    }

    public function testAJson(): void {
        $this->assertEquals('{"a":1}', General::aJson(['a' => 1]));
    }

    public function testUnaVez(): void {
        $contador = 0;
        $fn = function() use (&$contador) {
            $contador++;
            return 'ok';
        };
        $this->assertEquals('ok', General::unaVez('test', $fn));
        $this->assertEquals('ok', General::unaVez('test', $fn));
        $this->assertEquals(1, $contador);
    }

    public function testEsObjeto(): void {
        $this->assertTrue(General::esObjeto(new stdClass()));
        $this->assertFalse(General::esObjeto('texto'));
    }

    public function testEsArreglo(): void {
        $this->assertTrue(General::esArreglo([]));
        $this->assertFalse(General::esArreglo('texto'));
    }

    public function testEsString(): void {
        $this->assertTrue(General::esString('texto'));
        $this->assertFalse(General::esString(123));
    }

    public function testEsNumerico(): void {
        $this->assertTrue(General::esNumerico(123));
        $this->assertTrue(General::esNumerico('123.45'));
        $this->assertFalse(General::esNumerico('abc'));
    }
}
