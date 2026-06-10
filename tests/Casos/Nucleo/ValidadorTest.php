<?php
use PHPUnit\Framework\TestCase;

class ValidadorTest extends TestCase {
    public function testRequeridoConValor(): void {
        $validador = new Validador(['campo' => 'texto'], ['campo' => 'requerido']);
        $this->assertTrue($validador->pasa());
    }

    public function testRequeridoVacio(): void {
        $validador = new Validador(['campo' => ''], ['campo' => 'requerido']);
        $this->assertTrue($validador->falla());
    }

    public function testRequeridoNulo(): void {
        $validador = new Validador(['campo' => null], ['campo' => 'requerido']);
        $this->assertTrue($validador->falla());
    }

    public function testCorreoValido(): void {
        $validador = new Validador(['email' => 'user@example.com'], ['email' => 'correo']);
        $this->assertTrue($validador->pasa());
    }

    public function testCorreoInvalido(): void {
        $validador = new Validador(['email' => 'no-email'], ['email' => 'correo']);
        $this->assertTrue($validador->falla());
    }

    public function testMinimo(): void {
        $validador = new Validador(['campo' => 'hola'], ['campo' => 'minimo:5']);
        $this->assertTrue($validador->falla());
        $validador2 = new Validador(['campo' => 'hola mundo'], ['campo' => 'minimo:5']);
        $this->assertTrue($validador2->pasa());
    }

    public function testMaximo(): void {
        $validador = new Validador(['campo' => 'hola mundo'], ['campo' => 'maximo:5']);
        $this->assertTrue($validador->falla());
        $validador2 = new Validador(['campo' => 'hola'], ['campo' => 'maximo:5']);
        $this->assertTrue($validador2->pasa());
    }

    public function testNumero(): void {
        $validador = new Validador(['campo' => '123.45'], ['campo' => 'numero']);
        $this->assertTrue($validador->pasa());
        $validador2 = new Validador(['campo' => 'abc'], ['campo' => 'numero']);
        $this->assertTrue($validador2->falla());
    }

    public function testEntero(): void {
        $validador = new Validador(['campo' => '123'], ['campo' => 'entero']);
        $this->assertTrue($validador->pasa());
        $validador2 = new Validador(['campo' => '123.45'], ['campo' => 'entero']);
        $this->assertTrue($validador2->falla());
    }

    public function testRegex(): void {
        $validador = new Validador(['campo' => 'ABC123'], ['campo' => 'regex:/^[A-Z0-9]+$/']);
        $this->assertTrue($validador->pasa());
        $validador2 = new Validador(['campo' => 'abc-123'], ['campo' => 'regex:/^[A-Z0-9]+$/']);
        $this->assertTrue($validador2->falla());
    }

    public function testConfirmado(): void {
        $validador = new Validador([
            'clave' => '123',
            'clave_confirmacion' => '123',
        ], ['clave' => 'confirmado']);
        $this->assertTrue($validador->pasa());
    }

    public function testConfirmadoFallido(): void {
        $validador = new Validador([
            'clave' => '123',
            'clave_confirmacion' => '456',
        ], ['clave' => 'confirmado']);
        $this->assertTrue($validador->falla());
    }

    public function testDiferente(): void {
        $validador = new Validador([
            'campo1' => 'valor1',
            'campo2' => 'valor2',
        ], ['campo1' => 'diferente:campo2']);
        $this->assertTrue($validador->pasa());
    }

    public function testDiferenteFallido(): void {
        $validador = new Validador([
            'campo1' => 'mismo',
            'campo2' => 'mismo',
        ], ['campo1' => 'diferente:campo2']);
        $this->assertTrue($validador->falla());
    }

    public function testEn(): void {
        $validador = new Validador(['campo' => 'a'], ['campo' => 'en:a,b,c']);
        $this->assertTrue($validador->pasa());
        $validador2 = new Validador(['campo' => 'z'], ['campo' => 'en:a,b,c']);
        $this->assertTrue($validador2->falla());
    }

    public function testMultiplesReglas(): void {
        $validador = new Validador(
            ['campo' => ''],
            ['campo' => 'requerido|minimo:3|maximo:10']
        );
        $this->assertTrue($validador->falla());
    }

    public function testMultiplesReglasValidas(): void {
        $validador = new Validador(
            ['campo' => 'hola'],
            ['campo' => 'requerido|minimo:3|maximo:10']
        );
        $this->assertTrue($validador->pasa());
    }

    public function testErrores(): void {
        $validador = new Validador(['campo' => ''], ['campo' => 'requerido']);
        $this->assertIsArray($validador->errores());
        $this->assertNotEmpty($validador->errores());
    }

    public function testPrimerError(): void {
        $validador = new Validador(['campo' => ''], ['campo' => 'requerido']);
        $this->assertIsString($validador->primerError());
    }

    public function testErroresComoString(): void {
        $validador = new Validador(['campo' => ''], ['campo' => 'requerido']);
        $this->assertIsString($validador->erroresComoString());
    }

    public function testCampoInexistenteNoFalla(): void {
        $validador = new Validador([], ['campo' => 'numero']);
        $this->assertTrue($validador->pasa());
    }

    public function testReglaDesconocidaNoRompe(): void {
        $validador = new Validador(['campo' => 'x'], ['campo' => 'regla_inexistente']);
        $this->assertTrue($validador->pasa());
    }
}
