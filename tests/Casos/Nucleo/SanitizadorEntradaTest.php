<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Nucleo;

use LiteFramework\Seguridad\SanitizadorEntrada;

class SanitizadorEntradaTest extends \TestBase
{
    public function testNullEntradaDevuelveVacio(): void
    {
        $this->assertSame('', SanitizadorEntrada::sanitizarTextoBase(null));
    }

    public function testXssJavascriptUriBloqueado(): void
    {
        $resultado = SanitizadorEntrada::sanitizarTextoBase('javascript:alert(1)');
        $this->assertStringContainsString('bloqueado:', $resultado);
        $this->assertStringNotContainsString('javascript:', $resultado);
    }

    public function testXssVbscriptUriBloqueado(): void
    {
        $resultado = SanitizadorEntrada::sanitizarTextoBase('vbscript:msgbox(1)');
        $this->assertStringContainsString('bloqueado:', $resultado);
        $this->assertStringNotContainsString('vbscript:', $resultado);
    }

    public function testXssDataUriBloqueado(): void
    {
        $resultado = SanitizadorEntrada::sanitizarTextoBase('data:text/html,<script>alert(1)</script>');
        $this->assertStringContainsString('bloqueado:', $resultado);
        $this->assertStringNotContainsString('data:', $resultado);
    }

    public function testXssEventHandlerAnulado(): void
    {
        $resultado = SanitizadorEntrada::sanitizarTextoBase('<img onerror=alert(1) src=x>');
        $this->assertStringContainsString('x-evento-anulado=', $resultado);
        $this->assertStringNotContainsString('onerror=', $resultado);
    }

    public function testXssOnClickAnulado(): void
    {
        $resultado = SanitizadorEntrada::sanitizarTextoBase('<button onclick="alert(1)">Click</button>');
        $this->assertStringContainsString('x-evento-anulado=', $resultado);
        $this->assertStringNotContainsString('onclick=', $resultado);
    }

    public function testHtmlspecialcharsEscapaCaracteres(): void
    {
        $resultado = SanitizadorEntrada::sanitizarTextoBase('<script>alert("xss")</script>');
        $this->assertStringContainsString('&lt;', $resultado);
        $this->assertStringContainsString('&gt;', $resultado);
        $this->assertStringContainsString('&quot;', $resultado);
    }

    public function testEmailDetectionByKeyName(): void
    {
        $resultado = SanitizadorEntrada::sanitizarArreglo([
            'correo_electronico' => '  Test@Example.COM  ',
        ]);
        $this->assertSame('test@example.com', $resultado['correo_electronico']);
    }

    public function testEmailDetectionByEmailInKey(): void
    {
        $resultado = SanitizadorEntrada::sanitizarArreglo([
            'user_email' => 'User@Domain.COM',
        ]);
        $this->assertSame('user@domain.com', $resultado['user_email']);
    }

    public function testProcesarCorreoElectronicoValido(): void
    {
        $resultado = SanitizadorEntrada::procesarCorreoElectronico('  Test@Example.COM  ');
        $this->assertSame('test@example.com', $resultado);
    }

    public function testProcesarCorreoElectronicoInvalido(): void
    {
        $resultado = SanitizadorEntrada::procesarCorreoElectronico('no-es-correo');
        $this->assertFalse($resultado);
    }

    public function testProcesarCorreoElectronicoInternacionalizado(): void
    {
        $resultado = SanitizadorEntrada::procesarCorreoElectronico('usuario@correo-dominio.com');
        $this->assertSame('usuario@correo-dominio.com', $resultado);
    }

    public function testEncriptarClaveOperadorDevuelveHash(): void
    {
        $hash = SanitizadorEntrada::encriptarClaveOperador('MiClaveSegura@123');
        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    public function testVerificarClaveOperadorCorrecta(): void
    {
        $clave = 'MiClaveSegura@123';
        $hash = SanitizadorEntrada::encriptarClaveOperador($clave);
        $this->assertTrue(SanitizadorEntrada::verificarClaveOperador($clave, $hash));
    }

    public function testVerificarClaveOperadorIncorrecta(): void
    {
        $hash = SanitizadorEntrada::encriptarClaveOperador('clave_correcta');
        $this->assertFalse(SanitizadorEntrada::verificarClaveOperador('clave_incorrecta', $hash));
    }

    public function testSanitizarTextoPlanoStripsTags(): void
    {
        $resultado = SanitizadorEntrada::sanitizarTextoPlano('<p>Hola <b>mundo</b></p>');
        $this->assertStringNotContainsString('<', $resultado);
        $this->assertStringNotContainsString('>', $resultado);
        $this->assertStringContainsString('Hola mundo', $resultado);
    }

    public function testSanitizarTextoPlanoNullDevuelveVacio(): void
    {
        $this->assertSame('', SanitizadorEntrada::sanitizarTextoPlano(null));
    }

    public function testSanitizarTextoPlanoControlCharsRemoved(): void
    {
        $resultado = SanitizadorEntrada::sanitizarTextoPlano("Hola\x00Mundo\x07Test");
        $this->assertSame('HolaMundoTest', $resultado);
    }

    public function testSanitizarArregloRecursivoConEmail(): void
    {
        $entrada = [
            'usuario' => 'Pepe',
            'perfil' => [
                'correo' => '  PEPE@EXAMPLE.ORG  ',
            ],
        ];
        $resultado = SanitizadorEntrada::sanitizarArreglo($entrada);
        $this->assertSame('pepe@example.org', $resultado['perfil']['correo']);
        $this->assertSame('Pepe', $resultado['usuario']);
    }

    public function testSanitizarArregloGlobalConArray(): void
    {
        $entrada = [
            'items' => ['a' => '<b>1</b>', 'b' => '<i>2</i>'],
        ];
        $resultado = SanitizadorEntrada::sanitizarArregloGlobal($entrada);
        $this->assertStringNotContainsString('<b>', $resultado['items']['a']);
        $this->assertStringNotContainsString('<i>', $resultado['items']['b']);
    }

    public function testSanitizarTextoBaseTrim(): void
    {
        $resultado = SanitizadorEntrada::sanitizarTextoBase('  texto con espacios  ');
        $this->assertSame('texto con espacios', $resultado);
    }
}
