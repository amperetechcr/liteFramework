<?php
use PHPUnit\Framework\TestCase;

class AyudanteCadenaTest extends TestCase {
    public function testLimitar(): void {
        $this->assertEquals('H...', Cadena::limitar('Hola mundo', 4));
        $this->assertEquals('Hola mundo', Cadena::limitar('Hola mundo', 20));
        $this->assertEquals('test', Cadena::limitar('test', 4, ''));
    }

    public function testTruncar(): void {
        $this->assertEquals('Hola...', Cadena::truncar('Hola mundo', 6));
        $this->assertEquals('Hola mundo', Cadena::truncar('Hola mundo', 20));
    }

    public function testSlug(): void {
        $this->assertEquals('hola-mundo', Cadena::slug('Hola Mundo'));
        $this->assertEquals('hola-mundo', Cadena::slug('Hola   Mundo!'));
    }

    public function testCapitalizar(): void {
        $this->assertEquals('Hola', Cadena::capitalizar('hola'));
        $this->assertEquals('Hola', Cadena::capitalizar('HOLA'));
    }

    public function testMinusculas(): void {
        $this->assertEquals('hola mundo', Cadena::minusculas('HOLA Mundo'));
    }

    public function testMayusculas(): void {
        $this->assertEquals('HOLA MUNDO', Cadena::mayusculas('Hola Mundo'));
    }

    public function testContiene(): void {
        $this->assertTrue(Cadena::contiene('Hola mundo', 'mundo'));
        $this->assertFalse(Cadena::contiene('Hola mundo', 'Mundo', true));
        $this->assertTrue(Cadena::contiene('Hola mundo', 'Mundo'));
    }

    public function testIniciar(): void {
        $this->assertEquals('Hol', Cadena::iniciar('Hola', 3));
    }

    public function testTerminar(): void {
        $this->assertEquals('ola', Cadena::terminar('Hola', 3));
    }

    public function testAleatorio(): void {
        $this->assertEquals(16, strlen(Cadena::aleatorio(16)));
        $this->assertEquals(32, strlen(Cadena::aleatorio(32)));
    }

    public function testAleatorioEsAlfanumerico(): void {
        $token = Cadena::aleatorio(64);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $token);
    }

    public function testEspaciar(): void {
        $this->assertEquals('hola mundo', Cadena::espaciar('hola   mundo'));
        $this->assertEquals('a b c', Cadena::espaciar("a\nb\nc"));
    }

    public function testEsEmail(): void {
        $this->assertTrue(Cadena::esEmail('user@example.com'));
        $this->assertFalse(Cadena::esEmail('no-email'));
        $this->assertFalse(Cadena::esEmail(''));
    }

    public function testEsUrl(): void {
        $this->assertTrue(Cadena::esUrl('https://example.com'));
        $this->assertFalse(Cadena::esUrl('not-a-url'));
    }

    public function testEnmascarar(): void {
        $enmascarado = Cadena::enmascarar('1234567890', '*', 4, 4);
        $this->assertEquals('1234**7890', $enmascarado);
    }

    public function testHash(): void {
        $this->assertEquals(8, strlen(Cadena::hash('test')));
        $this->assertEquals(Cadena::hash('test'), Cadena::hash('test'));
    }

    public function testNormalizar(): void {
        $this->assertEquals('nino', Cadena::normalizar('niño'));
        $this->assertEquals('cancion', Cadena::normalizar('canción'));
    }

    public function testContarCaracteres(): void {
        $this->assertEquals(9, Cadena::contarCaracteres('hola mundo'));
    }

    public function testPalabras(): void {
        $this->assertEquals(2, Cadena::palabras('hola mundo'));
        $this->assertEquals(0, Cadena::palabras(''));
    }
}
