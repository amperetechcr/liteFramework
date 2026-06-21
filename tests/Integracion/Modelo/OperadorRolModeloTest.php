<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Modelo;

require_once __DIR__ . '/TestCaseDb.php';

use LiteFramework\Modelos\Operador;
use LiteFramework\Modelos\Rol;

class OperadorRolModeloTest extends TestCaseDb
{
    public function setUp(): void
    {
        parent::setUp();
        $this->bd->exec("DELETE FROM operador");
    }

    public function testCrearOperador(): void
    {
        $op = Operador::crear([
            'nombre_completo' => 'Test User',
            'correo_electronico' => 'testuser@test.com',
            'clave_acceso' => 'SecurePass1!',
            'id_rol' => 2,
        ]);
        $this->assertNotNull($op->id_operador);
        $this->assertSame('Test User', $op->nombre_completo);
    }

    public function testOperadorPerteneceARol(): void
    {
        $op = Operador::crear([
            'nombre_completo' => 'Rol Test',
            'correo_electronico' => 'roltest@test.com',
            'clave_acceso' => 'SecurePass1!',
            'id_rol' => 2,
        ]);
        $rol = $op->perteneceA(Rol::class, 'id_rol', 'id_rol');
        $this->assertNotNull($rol);
        $this->assertInstanceOf(Rol::class, $rol);
        $this->assertSame('Administrador', $rol->nombre_rol);
    }

    public function testRolTieneMuchosOperadores(): void
    {
        $nombreRol = 'Test Rol ' . uniqid();
        Rol::crear(['nombre_rol' => $nombreRol]);
        $rolCreado = Rol::donde('nombre_rol', $nombreRol)->primero();

        Operador::crear([
            'nombre_completo' => 'Op1',
            'correo_electronico' => 'op1_tm@test.com',
            'clave_acceso' => 'SecurePass1!',
            'id_rol' => $rolCreado->id_rol,
        ]);
        Operador::crear([
            'nombre_completo' => 'Op2',
            'correo_electronico' => 'op2_tm@test.com',
            'clave_acceso' => 'SecurePass1!',
            'id_rol' => $rolCreado->id_rol,
        ]);

        $ops = $rolCreado->tieneMuchos(Operador::class, 'id_rol', 'id_rol');
        $this->assertCount(2, $ops);
    }

    public function testRolOperadoresMethod(): void
    {
        $rol = Rol::buscar(1);
        $ops = $rol->operadores();
        $this->assertIsArray($ops);
    }

    public function testEagerLoadingConOperador(): void
    {
        $op = Operador::crear([
            'nombre_completo' => 'Eager Op',
            'correo_electronico' => 'eager_op_elo@test.com',
            'clave_acceso' => 'SecurePass1!',
            'id_rol' => 2,
        ]);

        $resultados = Rol::donde('id_rol', 2)->con('operador')->obtener();

        $this->assertNotEmpty($resultados);
        foreach ($resultados as $r) {
            $rel = $r->eagerOperador();
            if ($rel !== null) {
                $this->assertInstanceOf(Operador::class, $rel);
            }
        }
    }

    public function testEagerLoadingIdOperadorNull(): void
    {
        $rol = Rol::buscar(1);
        $resultados = Rol::donde('id_rol', 1)->con('operador')->obtener();
        $this->assertIsArray($resultados);
    }

    public function testOperadorBuscarRol(): void
    {
        $op = Operador::crear([
            'nombre_completo' => 'Busca Rol',
            'correo_electronico' => 'buscarol@test.com',
            'clave_acceso' => 'SecurePass1!',
            'id_rol' => 1,
        ]);
        $rol = $op->rol();
        $this->assertNotNull($rol);
        $this->assertSame('Super Administrador', $rol->nombre_rol);
    }

    public function testOperadorEstaActivo(): void
    {
        $op = Operador::crear([
            'nombre_completo' => 'Activo Test',
            'correo_electronico' => 'activo@test.com',
            'clave_acceso' => 'SecurePass1!',
            'id_rol' => 2,
            'estado_cuenta' => 1,
        ]);
        $this->assertTrue($op->estaActivo());
    }

    public function testOperadorSuspender(): void
    {
        $op = Operador::crear([
            'nombre_completo' => 'Suspender Test',
            'correo_electronico' => 'suspender@test.com',
            'clave_acceso' => 'SecurePass1!',
            'id_rol' => 2,
        ]);
        $op->suspender();
        $this->assertFalse($op->estaActivo());
        $this->assertSame(0, $op->estado_cuenta);
    }

    public function testOperadorActivar(): void
    {
        $op = Operador::crear([
            'nombre_completo' => 'Activacion Test',
            'correo_electronico' => 'activacion@test.com',
            'clave_acceso' => 'SecurePass1!',
            'id_rol' => 2,
            'estado_cuenta' => 0,
        ]);
        $op->activar();
        $this->assertTrue($op->estaActivo());
        $this->assertSame(1, $op->estado_cuenta);
    }
}
