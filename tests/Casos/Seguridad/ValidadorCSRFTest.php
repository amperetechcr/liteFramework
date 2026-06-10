<?php
use PHPUnit\Framework\TestCase;

class ValidadorCSRFTest extends TestCase {
    private array $sesionOriginal;

    protected function setUp(): void {
        $this->sesionOriginal = $_SESSION ?? [];
        $_SESSION = [];
    }

    protected function tearDown(): void {
        $_SESSION = $this->sesionOriginal;
    }

    public function testGenerarTokenDevuelveHex64(): void {
        $token = ValidadorCSRF::generarToken();
        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    public function testValidarTokenPropio(): void {
        $token = ValidadorCSRF::generarToken();
        $this->assertTrue(ValidadorCSRF::validarToken($token));
    }

    public function testValidarTokenInvalido(): void {
        ValidadorCSRF::generarToken();
        $this->assertFalse(ValidadorCSRF::validarToken('token-invalido'));
    }

    public function testValidarTokenVacio(): void {
        ValidadorCSRF::generarToken();
        $this->assertFalse(ValidadorCSRF::validarToken(''));
    }

    public function testRotacionDeToken(): void {
        $token1 = ValidadorCSRF::generarToken();
        $token2 = ValidadorCSRF::generarToken();
        $this->assertNotEquals($token1, $token2);
    }

    public function testTokenAnteriorSigueValido(): void {
        $token1 = ValidadorCSRF::generarToken();
        ValidadorCSRF::generarToken();
        $this->assertTrue(ValidadorCSRF::validarToken($token1));
    }

    public function testGenerarTokenActualizaSesion(): void {
        ValidadorCSRF::generarToken();
        $this->assertNotEmpty($_SESSION['token_seguridad_peticion'] ?? '');
        $this->assertNotEmpty($_SESSION['csrf_token'] ?? '');
    }

    public function testValidarTokenExpirado(): void {
        $token1 = ValidadorCSRF::generarToken();
        ValidadorCSRF::generarToken();
        ValidadorCSRF::generarToken();
        ValidadorCSRF::generarToken();
        $this->assertFalse(ValidadorCSRF::validarToken($token1));
    }

    public function testValidarTokenRotaElTokenActual(): void {
        $token1 = ValidadorCSRF::generarToken();
        $this->assertTrue(ValidadorCSRF::validarToken($token1));
        $token2 = $_SESSION['token_seguridad_peticion'] ?? '';
        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(64, strlen($token2));
    }

    public function testTokenValidadoPasaAPrevio(): void {
        $token1 = ValidadorCSRF::generarToken();
        ValidadorCSRF::validarToken($token1);
        $this->assertEquals($token1, $_SESSION['token_seguridad_previo'] ?? '');
    }

    public function testTokenAnteriorSigueValidoDentroDeVentana(): void {
        $token1 = ValidadorCSRF::generarToken();
        ValidadorCSRF::generarToken();
        $this->assertTrue(ValidadorCSRF::validarToken($token1));
    }

    public function testCadaLlamadaAGenerarRotaElToken(): void {
        $token1 = ValidadorCSRF::generarToken();
        $token2 = ValidadorCSRF::generarToken();
        $token3 = ValidadorCSRF::generarToken();
        $this->assertNotEquals($token1, $token2);
        $this->assertNotEquals($token2, $token3);
        $this->assertNotEquals($token1, $token3);
    }

    public function testSesionContieneLosDosTokensDespuesDeRotacion(): void {
        ValidadorCSRF::generarToken();
        $actual = $_SESSION['token_seguridad_peticion'] ?? '';
        ValidadorCSRF::generarToken();
        $this->assertEquals($actual, $_SESSION['token_seguridad_previo'] ?? '');
        $this->assertNotEquals($actual, $_SESSION['token_seguridad_peticion'] ?? '');
    }
}
