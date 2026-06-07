<?php
use PHPUnit\Framework\TestCase;

class ModeloTest extends TestCase {
    protected function tearDown(): void {
        ConexionBaseDatos::resetearInstancia();
    }

    public function testBuscarRolExistente(): void {
        $rol = Rol::buscar(1);
        $this->assertNotNull($rol);
        $this->assertEquals(1, $rol->id_rol);
    }

    public function testBuscarRolInexistente(): void {
        $rol = Rol::buscar(999);
        $this->assertNull($rol);
    }

    public function testTodosDevuelveArreglo(): void {
        $roles = Rol::todos();
        $this->assertIsArray($roles);
        $this->assertGreaterThanOrEqual(2, count($roles));
    }

    public function testTodosLosElementosSonObjetos(): void {
        $roles = Rol::todos();
        foreach ($roles as $rol) {
            $this->assertInstanceOf(Rol::class, $rol);
        }
    }

    public function testCrearYBuscarOperador(): void {
        $datos = [
            'nombre_completo' => 'Test User',
            'correo_electronico' => 'test_' . uniqid() . '@test.com',
            'clave_acceso' => password_hash('Test123!', PASSWORD_DEFAULT),
            'id_rol' => 1,
        ];
        $operador = Operador::crear($datos);
        $this->assertNotNull($operador->id_operador);

        $encontrado = Operador::buscar($operador->id_operador);
        $this->assertNotNull($encontrado);
        $this->assertEquals($datos['nombre_completo'], $encontrado->nombre_completo);
    }

    public function testActualizarOperador(): void {
        $datos = [
            'nombre_completo' => 'Original',
            'correo_electronico' => 'update_' . uniqid() . '@test.com',
            'clave_acceso' => password_hash('Test123!', PASSWORD_DEFAULT),
            'id_rol' => 1,
        ];
        $operador = Operador::crear($datos);

        $operador->nombre_completo = 'Actualizado';
        $operador->guardar();

        $refrescado = Operador::buscar($operador->id_operador);
        $this->assertEquals('Actualizado', $refrescado->nombre_completo);
    }

    public function testEliminarOperador(): void {
        $datos = [
            'nombre_completo' => 'To Delete',
            'correo_electronico' => 'delete_' . uniqid() . '@test.com',
            'clave_acceso' => password_hash('Test123!', PASSWORD_DEFAULT),
            'id_rol' => 1,
        ];
        $operador = Operador::crear($datos);
        $id = $operador->id_operador;

        $operador->eliminar();

        $this->assertNull(Operador::buscar($id));
    }

    public function testDondeYObtener(): void {
        $resultados = Operador::donde('estado_cuenta', '=', 1)->obtener();
        $this->assertIsArray($resultados);
    }

    public function testContar(): void {
        $total = Operador::contar();
        $this->assertIsInt($total);
        $this->assertGreaterThanOrEqual(0, $total);
    }

    public function testLlenarYMagicGet(): void {
        $rol = new Rol();
        $rol->llenar(['nombre_rol' => 'Test Rol', 'descripcion_rol' => 'Testing']);

        $this->assertEquals('Test Rol', $rol->nombre_rol);
        $this->assertEquals('Testing', $rol->descripcion_rol);
    }
}
