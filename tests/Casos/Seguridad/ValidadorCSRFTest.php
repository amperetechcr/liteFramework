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
}
