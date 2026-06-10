<?php
use PHPUnit\Framework\TestCase;

class PoliticaContrasenaTest extends TestCase {
    public function testClaveValida(): void {
        $this->assertTrue(PoliticaContrasena::validar('Admin123!'));
    }

    public function testClaveConFormatoValido(): void {
        $this->assertTrue(PoliticaContrasena::validar('MyP@ssw0rd!'));
    }

    public function testClaveDemasiadoCorta(): void {
        $this->assertIsString(PoliticaContrasena::validar('Ab1!'));
    }

    public function testClaveSinMayuscula(): void {
        $resultado = PoliticaContrasena::validar('admin123!');
        $this->assertIsString($resultado);
    }

    public function testClaveSinNumero(): void {
        $resultado = PoliticaContrasena::validar('AdminPass!');
        $this->assertIsString($resultado);
    }

    public function testClaveSinSimbolo(): void {
        $resultado = PoliticaContrasena::validar('Admin1234');
        $this->assertIsString($resultado);
    }

    public function testClaveVacia(): void {
        $resultado = PoliticaContrasena::validar('');
        $this->assertIsString($resultado);
    }

    public function testClaveConCaracteresEspecialesPermitidos(): void {
        $this->assertTrue(PoliticaContrasena::validar('Test@123!'));
    }
}
