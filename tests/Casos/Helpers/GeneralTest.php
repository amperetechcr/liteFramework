<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use LiteFramework\Nucleo\Helpers\General;

class GeneralTest extends \TestBase
{
    public function testTieneValorConString(): void
    {
        $this->assertTrue(General::tieneValor('hola'));
    }

    public function testTieneValorConNull(): void
    {
        $this->assertFalse(General::tieneValor(null));
    }

    public function testTieneValorConStringVacio(): void
    {
        $this->assertFalse(General::tieneValor(''));
    }

    public function testTieneValorConEspacios(): void
    {
        $this->assertFalse(General::tieneValor('   '));
    }

    public function testTieneValorConArregloVacio(): void
    {
        $this->assertFalse(General::tieneValor([]));
    }

    public function testTieneValorConArregloNoVacio(): void
    {
        $this->assertTrue(General::tieneValor([1]));
    }

    public function testTieneValorConCero(): void
    {
        $this->assertTrue(General::tieneValor(0));
    }

    public function testTieneValorConFalse(): void
    {
        $this->assertTrue(General::tieneValor(false));
    }

    public function testNoEstaVacio(): void
    {
        $this->assertTrue(General::noEstaVacio('texto'));
        $this->assertFalse(General::noEstaVacio(''));
    }

    public function testEstaVacio(): void
    {
        $this->assertTrue(General::estaVacio(''));
        $this->assertTrue(General::estaVacio(null));
        $this->assertFalse(General::estaVacio('texto'));
    }

    public function testObtenerValor(): void
    {
        $this->assertSame(42, General::obtenerValor(['a' => 42], 'a'));
    }

    public function testObtenerValorConDefecto(): void
    {
        $this->assertSame('predeterminado', General::obtenerValor(['a' => 1], 'b', 'predeterminado'));
    }

    public function testDesde(): void
    {
        $this->assertSame('valor', General::desde(['clave' => 'valor'], 'clave'));
    }

    public function testGenerarToken(): void
    {
        $token = General::generarToken(32);
        $this->assertSame(32, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    public function testGenerarTokenConLongitudCero(): void
    {
        $this->assertSame('', General::generarToken(0));
    }

    public function testGenerarTokenConLongitudNegativa(): void
    {
        $this->assertSame('', General::generarToken(-1));
    }

    public function testGenerarTokenEsUnico(): void
    {
        $this->assertNotSame(General::generarToken(), General::generarToken());
    }

    public function testClonar(): void
    {
        $original = new \stdClass();
        $original->prop = 'valor';
        $clon = General::clonar($original);
        $this->assertNotSame($original, $clon);
        $this->assertSame($original->prop, $clon->prop);
    }

    public function testEsMetodo(): void
    {
        $obj = new class {
            public function foo(): void {}
        };
        $this->assertTrue(General::esMetodo($obj, 'foo'));
        $this->assertFalse(General::esMetodo($obj, 'bar'));
    }

    public function testTienePropiedad(): void
    {
        $obj = new \stdClass();
        $obj->nombre = 'test';
        $this->assertTrue(General::tienePropiedad($obj, 'nombre'));
        $this->assertFalse(General::tienePropiedad($obj, 'inexistente'));
    }

    public function testTipoDe(): void
    {
        $this->assertSame(\stdClass::class, General::tipoDe(new \stdClass()));
    }

    public function testEsObjeto(): void
    {
        $this->assertTrue(General::esObjeto(new \stdClass()));
        $this->assertFalse(General::esObjeto('string'));
        $this->assertFalse(General::esObjeto(123));
        $this->assertFalse(General::esObjeto(null));
    }

    public function testEsArreglo(): void
    {
        $this->assertTrue(General::esArreglo([]));
        $this->assertTrue(General::esArreglo([1, 2]));
        $this->assertFalse(General::esArreglo('string'));
        $this->assertFalse(General::esArreglo(null));
    }

    public function testEsString(): void
    {
        $this->assertTrue(General::esString('hola'));
        $this->assertFalse(General::esString(123));
        $this->assertFalse(General::esString(null));
    }

    public function testEsNumerico(): void
    {
        $this->assertTrue(General::esNumerico(123));
        $this->assertTrue(General::esNumerico('123'));
        $this->assertTrue(General::esNumerico(12.5));
        $this->assertFalse(General::esNumerico('abc'));
    }

    public function testEsBooleano(): void
    {
        $this->assertTrue(General::esBooleano(true));
        $this->assertTrue(General::esBooleano(false));
        $this->assertFalse(General::esBooleano(1));
    }

    public function testABooleano(): void
    {
        $this->assertTrue(General::aBooleano(true));
        $this->assertFalse(General::aBooleano(false));
        $this->assertTrue(General::aBooleano('true'));
        $this->assertTrue(General::aBooleano('1'));
        $this->assertTrue(General::aBooleano('si'));
        $this->assertTrue(General::aBooleano('yes'));
        $this->assertTrue(General::aBooleano('on'));
        $this->assertFalse(General::aBooleano('no'));
        $this->assertFalse(General::aBooleano('false'));
        $this->assertFalse(General::aBooleano(0));
        $this->assertTrue(General::aBooleano(1));
    }

    public function testAEntero(): void
    {
        $this->assertSame(42, General::aEntero(42));
        $this->assertSame(42, General::aEntero('42'));
        $this->assertSame(42, General::aEntero(42.9));
        $this->assertSame(0, General::aEntero('abc'));
        $this->assertSame(5, General::aEntero('abc', 5));
        $this->assertSame(0, General::aEntero(null));
    }

    public function testAFlotante(): void
    {
        $this->assertSame(42.5, General::aFlotante(42.5));
        $this->assertSame(42.0, General::aFlotante('42'));
        $this->assertSame(0.0, General::aFlotante('abc'));
        $this->assertSame(3.14, General::aFlotante('abc', 3.14));
        $this->assertSame(0.0, General::aFlotante(null));
    }

    public function testAString(): void
    {
        $this->assertSame('hola', General::aString('hola'));
        $this->assertSame('', General::aString(null));
        $this->assertSame('42', General::aString(42));
        $this->assertSame('1', General::aString(true));
        $this->assertSame('0', General::aString(false));
        $this->assertSame('3.14', General::aString(3.14));
    }

    public function testAStringConArreglo(): void
    {
        $this->assertSame('{"a":1}', General::aString(['a' => 1]));
    }

    public function testAStringConObjetoSinToString(): void
    {
        $obj = new \stdClass();
        $this->assertSame(\stdClass::class, General::aString($obj));
    }

    public function testAStringConObjetoConToString(): void
    {
        $obj = new class {
            public function __toString(): string
            {
                return 'objeto-toString';
            }
        };
        $this->assertSame('objeto-toString', General::aString($obj));
    }

    public function testAJson(): void
    {
        $this->assertSame('{"a":1}', General::aJson(['a' => 1]));
    }

    public function testAJsonPretty(): void
    {
        $resultado = General::aJson(['a' => 1], true);
        $this->assertStringContainsString("\n", $resultado);
    }

    public function testDesdeJson(): void
    {
        $this->assertSame(['a' => 1], General::desdeJson('{"a":1}'));
    }

    public function testDesdeJsonInvalido(): void
    {
        $this->assertNull(General::desdeJson('{invalido}'));
    }

    public function testDesdeJsonConDefecto(): void
    {
        $this->assertSame([], General::desdeJson('{invalido}', []));
    }

    public function testEsJson(): void
    {
        $this->assertTrue(General::esJson('{"a":1}'));
        $this->assertFalse(General::esJson('no-json'));
    }

    public function testMoneda(): void
    {
        $this->assertSame('$1,234.56', General::moneda(1234.56));
    }

    public function testMonedaNegativa(): void
    {
        $this->assertSame('-$100.00', General::moneda(-100));
    }

    public function testMonedaSinDecimales(): void
    {
        $this->assertSame('$50.00', General::moneda(50));
    }

    public function testMonedaConCero(): void
    {
        $this->assertSame('$0.00', General::moneda(0));
    }

    public function testNumero(): void
    {
        $this->assertSame('1,234', General::numero(1234));
    }

    public function testNumeroConDecimales(): void
    {
        $this->assertSame('1,234.56', General::numero(1234.56));
    }

    public function testNumeroSinSeparadores(): void
    {
        $this->assertSame('1.234,56', General::numero(1234.56, '.', ','));
    }

    public function testTruncarNumero(): void
    {
        $this->assertSame(3.14, General::truncarNumero(3.14159, 2));
    }

    public function testTruncarNumeroSinDecimales(): void
    {
        $this->assertSame(3.0, General::truncarNumero(3.99, 0));
    }

    public function testRedondearNormal(): void
    {
        $this->assertSame(3.14, General::redondear(3.14159, 2));
    }

    public function testRedondearArriba(): void
    {
        $this->assertSame(3.15, General::redondear(3.14159, 2, 'arriba'));
    }

    public function testRedondearAbajo(): void
    {
        $this->assertSame(3.14, General::redondear(3.14159, 2, 'abajo'));
    }

    public function testBytesLegibles(): void
    {
        $this->assertSame('0 B', General::bytesLegibles(0));
        $this->assertSame('1 KB', General::bytesLegibles(1024));
        $this->assertSame('1 MB', General::bytesLegibles(1048576));
    }

    public function testBytesLegiblesNegativo(): void
    {
        $this->assertSame('0 B', General::bytesLegibles(-100));
    }

    public function testUnaVez(): void
    {
        $contador = 0;
        $resultado1 = General::unaVez('unica', function () use (&$contador) {
            $contador++;
            return 42;
        });
        $resultado2 = General::unaVez('unica', function () use (&$contador) {
            $contador++;
            return 99;
        });
        $this->assertSame(42, $resultado1);
        $this->assertSame(42, $resultado2);
        $this->assertSame(1, $contador);
    }

    public function testResetUnaVez(): void
    {
        General::unaVez('reseteable', fn() => 'valor');
        General::resetUnaVez('reseteable');
        $resultado = General::unaVez('reseteable', fn() => 'nuevo');
        $this->assertSame('nuevo', $resultado);
    }

    public function testResetUnaVezTodo(): void
    {
        General::unaVez('a', fn() => 1);
        General::unaVez('b', fn() => 2);
        General::resetUnaVez();
        $this->assertSame(3, General::unaVez('a', fn() => 3));
    }
}
