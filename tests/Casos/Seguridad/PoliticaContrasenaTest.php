<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Seguridad;

use LiteFramework\Seguridad\PoliticaContrasena;

class PoliticaContrasenaTest extends \TestBase
{
    public function testValidaDevuelveTrueParaClaveValida(): void
    {
        $resultado = PoliticaContrasena::validar('Abcdef1@');
        $this->assertTrue($resultado);
    }

    public function testRechazaClaveCorta(): void
    {
        $resultado = PoliticaContrasena::validar('Ab1@');
        $this->assertIsString($resultado);
        $this->assertStringContainsString('8 caracteres', $resultado);
    }

    public function testRechazaClaveSinMayuscula(): void
    {
        $resultado = PoliticaContrasena::validar('abcdef1@a');
        $this->assertIsString($resultado);
        $this->assertStringContainsString('mayúscula', $resultado);
    }

    public function testRechazaClaveSinDigito(): void
    {
        $resultado = PoliticaContrasena::validar('Abcdefg@');
        $this->assertIsString($resultado);
        $this->assertStringContainsString('número', $resultado);
    }

    public function testRechazaClaveSinSimbolo(): void
    {
        $resultado = PoliticaContrasena::validar('Abcdefg1');
        $this->assertIsString($resultado);
        $this->assertStringContainsString('símbolo', $resultado);
    }

    public function testRetornaTrueParaClaveValida(): void
    {
        $this->assertTrue(PoliticaContrasena::validar('Str0ng!Pass'));
    }

    public function testClaveVacia(): void
    {
        $resultado = PoliticaContrasena::validar('');
        $this->assertIsString($resultado);
    }

    public function testSoloLetrasMinusculas(): void
    {
        $resultado = PoliticaContrasena::validar('abcdefgh');
        $this->assertIsString($resultado);
    }

    public function testSoloNumeros(): void
    {
        $resultado = PoliticaContrasena::validar('12345678');
        $this->assertIsString($resultado);
    }

    public function testClaveConTodosLosRequisitosExactos(): void
    {
        $this->assertTrue(PoliticaContrasena::validar('Aa1@5678'));
    }
}
