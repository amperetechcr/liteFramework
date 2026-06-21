<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Nucleo;

use LiteFramework\Seguridad\ValidadorCSRF;

class ValidadorCSRFTest extends \TestBase
{
    private array $sesionRespaldo;

    public function setUp(): void
    {
        $this->sesionRespaldo = $_SESSION ?? [];
        $_SESSION = [];
    }

    public function tearDown(): void
    {
        $_SESSION = $this->sesionRespaldo;
    }

    public function testGenerarTokenDevuelveHex64(): void
    {
        $token = ValidadorCSRF::generarToken();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testGenerarTokenEstableceClavesSession(): void
    {
        ValidadorCSRF::generarToken();
        $this->assertArrayHasKey('token_seguridad_peticion', $_SESSION);
        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertSame($_SESSION['token_seguridad_peticion'], $_SESSION['csrf_token']);
    }

    public function testValidarConTokenCorrectoDevuelveTrue(): void
    {
        $token = ValidadorCSRF::generarToken();
        $this->assertTrue(ValidadorCSRF::validarToken($token));
    }

    public function testValidarConTokenVacioDevuelveFalse(): void
    {
        $this->assertFalse(ValidadorCSRF::validarToken(''));
    }

    public function testValidarConTokenInvalidoDevuelveFalse(): void
    {
        ValidadorCSRF::generarToken();
        $this->assertFalse(ValidadorCSRF::validarToken('a' . str_repeat('b', 62) . 'c'));
    }

    public function testGenerarTokenRotaAnterior(): void
    {
        $token1 = ValidadorCSRF::generarToken();
        $token2 = ValidadorCSRF::generarToken();
        $this->assertNotSame($token1, $token2);
        $this->assertSame($token1, $_SESSION['token_seguridad_previo']);
    }

    public function testValidarConTokenPrevioDentroGraceWindow(): void
    {
        $token1 = ValidadorCSRF::generarToken();
        $_SESSION['tiempo_token_previo'] = time() - 30;
        $token2 = ValidadorCSRF::generarToken();
        $this->assertTrue(ValidadorCSRF::validarToken($token1));
    }

    public function testValidarConTokenPrevioFueraGraceWindow(): void
    {
        $token1 = ValidadorCSRF::generarToken();
        $token2 = ValidadorCSRF::generarToken();
        $_SESSION['tiempo_token_previo'] = time() - 120;
        $this->assertFalse(ValidadorCSRF::validarToken($token1));
    }

    public function testValidarTokenActualRotaTrasValidacionExitosa(): void
    {
        $token1 = ValidadorCSRF::generarToken();
        $this->assertTrue(ValidadorCSRF::validarToken($token1));
        $tokenNuevo = $_SESSION['token_seguridad_peticion'];
        $this->assertNotSame($token1, $tokenNuevo);
        $this->assertSame($token1, $_SESSION['token_seguridad_previo']);
    }

    public function testRotacionMultipleConservaSoloUltimoAnterior(): void
    {
        $t1 = ValidadorCSRF::generarToken();
        $t2 = ValidadorCSRF::generarToken();
        $t3 = ValidadorCSRF::generarToken();
        $this->assertSame($t2, $_SESSION['token_seguridad_previo']);
        $this->assertNotSame($t1, $_SESSION['token_seguridad_previo']);
    }

    public function testRotacionExpiraPrevioTrasTresRotaciones(): void
    {
        $t1 = ValidadorCSRF::generarToken();
        $t2 = ValidadorCSRF::generarToken();
        $_SESSION['tiempo_token_previo'] = time() - 90;
        $t3 = ValidadorCSRF::generarToken();
        $_SESSION['tiempo_token_previo'] = time() - 90;
        $t4 = ValidadorCSRF::generarToken();
        $_SESSION['tiempo_token_previo'] = time() - 90;
        $this->assertFalse(ValidadorCSRF::validarToken($t1));
    }

    public function testGraceWindowExactoSegundos(): void
    {
        $t1 = ValidadorCSRF::generarToken();
        $_SESSION['tiempo_token_previo'] = time() - 60;
        $t2 = ValidadorCSRF::generarToken();
        $this->assertTrue(ValidadorCSRF::validarToken($t1));
    }

    public function testGraceWindowUnSegundoFuera(): void
    {
        $t1 = ValidadorCSRF::generarToken();
        $t2 = ValidadorCSRF::generarToken();
        $_SESSION['tiempo_token_previo'] = time() - 120;
        $this->assertFalse(ValidadorCSRF::validarToken($t1));
    }

    public function testConcurrenteDosTokensValidosSimultaneamente(): void
    {
        $tokenOriginal = ValidadorCSRF::generarToken();
        $tokenRuteo = $_SESSION['token_seguridad_peticion'];
        $_SESSION['tiempo_token_previo'] = time() - 10;
        $tokenSegunda = ValidadorCSRF::generarToken();
        $this->assertTrue(ValidadorCSRF::validarToken($tokenOriginal));
        $this->assertTrue(ValidadorCSRF::validarToken($tokenSegunda));
    }

    public function testValidarSinGenerarToken(): void
    {
        $this->assertFalse(ValidadorCSRF::validarToken('abc'));
    }

    public function testSessionVaciaSinClaves(): void
    {
        $this->assertFalse(ValidadorCSRF::validarToken('abc123'));
    }

    public function testTokenGeneradoEsUnicoCadaVez(): void
    {
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $tokens[] = ValidadorCSRF::generarToken();
        }
        $this->assertCount(10, array_unique($tokens));
    }

    public function testCsrfTokenSincronizado(): void
    {
        ValidadorCSRF::generarToken();
        ValidadorCSRF::validarToken($_SESSION['csrf_token']);
        $this->assertSame($_SESSION['token_seguridad_peticion'], $_SESSION['csrf_token']);
    }

    public function testValidarTokenRotaTokenActivo(): void
    {
        $token = ValidadorCSRF::generarToken();
        $this->assertTrue(ValidadorCSRF::validarToken($token));
        $nuevoToken = $_SESSION['token_seguridad_peticion'];
        $this->assertNotSame($token, $nuevoToken);
    }

    public function testTiempoAnteriorCeroSinRotacion(): void
    {
        ValidadorCSRF::generarToken();
        $this->assertSame(0, $_SESSION['tiempo_token_previo'] ?? 0);
    }
}
