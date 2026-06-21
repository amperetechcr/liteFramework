<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Nucleo;

use LiteFramework\Seguridad\PoliticaContrasena;

class PoliticaContrasenaTest extends \TestBase
{
    public function testClaveMenor8CaracteresDevuelveError(): void
    {
        $resultado = PoliticaContrasena::validar('Ab1@x');
        $this->assertIsString($resultado);
        $this->assertStringContainsString('8 caracteres', $resultado);
    }

    public function testClaveSinMayusculaDevuelveError(): void
    {
        $resultado = PoliticaContrasena::validar('ab1@defgh');
        $this->assertIsString($resultado);
        $this->assertStringContainsString('mayúscula', $resultado);
    }

    public function testClaveSinDigitoDevuelveError(): void
    {
        $resultado = PoliticaContrasena::validar('Abcdefgh@');
        $this->assertIsString($resultado);
        $this->assertStringContainsString('número', $resultado);
    }

    public function testClaveSinSimboloEspecialDevuelveError(): void
    {
        $resultado = PoliticaContrasena::validar('Abcdefgh1');
        $this->assertIsString($resultado);
        $this->assertStringContainsString('símbolo', $resultado);
    }

    public function testClaveCumpleTodosRequisitosDevuelveTrue(): void
    {
        $this->assertTrue(PoliticaContrasena::validar('Segura@123'));
    }

    public function testClaveConCaracteresUnicodeCuentaBytes(): void
    {
        $clave = 'Ñ1@';
        $resultado = PoliticaContrasena::validar($clave);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('8 caracteres', $resultado);
    }

    public function testCadenaVaciaDevuelveError(): void
    {
        $resultado = PoliticaContrasena::validar('');
        $this->assertIsString($resultado);
    }

    public function testClaveSoloUnTipoCaracterDevuelveError(): void
    {
        $resultado = PoliticaContrasena::validar('abcdefgh');
        $this->assertIsString($resultado);
    }

    public function testClaveSoloLetrasYDigitosSinSimboloDevuelveError(): void
    {
        $resultado = PoliticaContrasena::validar('Abcdefg1');
        $this->assertIsString($resultado);
    }

    public function testReturnTypeEsTrueOString(): void
    {
        $this->assertTrue(is_bool(PoliticaContrasena::validar('Segura@123')) || is_string(PoliticaContrasena::validar('Segura@123')));
        $r1 = PoliticaContrasena::validar('Segura@123');
        $r2 = PoliticaContrasena::validar('corto');
        $this->assertTrue($r1 === true);
        $this->assertIsString($r2);
    }
}
