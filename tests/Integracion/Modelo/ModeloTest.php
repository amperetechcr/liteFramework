<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Modelo;

require_once __DIR__ . '/TestCaseDb.php';

use LiteFramework\Nucleo\Modelo;
use RuntimeException;

// --- Inline model subclasses ---

class TestModelo extends Modelo
{
    protected static string $tabla = 'test_modelo';
    protected static string $idColumna = 'id';
    protected static array $rellenable = [];
    protected static array $tipos = ['edad' => 'int', 'salario' => 'float', 'activo' => 'bool'];
}

class TestRellenable extends Modelo
{
    protected static string $tabla = 'test_modelo';
    protected static string $idColumna = 'id';
    protected static array $rellenable = ['nombre', 'email'];
}

class TestRelacion extends Modelo
{
    protected static string $tabla = 'test_relacion';
    protected static string $idColumna = 'id';
    protected static array $rellenable = ['test_modelo_id', 'descripcion'];
}

class TestEventos extends Modelo
{
    protected static string $tabla = 'test_eventos';
    protected static string $idColumna = 'id';
    protected static array $rellenable = ['nombre', 'orden'];
    public static array $ordenLlamadas = [];

    protected function creating(): void { self::$ordenLlamadas[] = 'creating'; }
    protected function created(): void { self::$ordenLlamadas[] = 'created'; }
    protected function updating(): void { self::$ordenLlamadas[] = 'updating'; }
    protected function updated(): void { self::$ordenLlamadas[] = 'updated'; }
    protected function deleting(): void { self::$ordenLlamadas[] = 'deleting'; }
    protected function deleted(): void { self::$ordenLlamadas[] = 'deleted'; }
}

class TestEager extends Modelo
{
    protected static string $tabla = 'test_eager';
    protected static string $idColumna = 'id';
    protected static array $rellenable = ['nombre', 'id_operador'];
}

class TestPertenece extends Modelo
{
    protected static string $tabla = 'test_pertenece';
    protected static string $idColumna = 'id';
    protected static array $rellenable = ['nombre', 'rol_id'];
}

class ModeloTest extends TestCaseDb
{
    private int $idAlice;
    private int $idBob;
    private int $idCharlie;

    public static function setUpBeforeClass(): void
    {
        TestEventos::$ordenLlamadas = [];
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->crearTablasTemporales();
        $this->insertarDatosIniciales();
    }

    private function autoIncrement(): string
    {
        $driver = $this->bd->getAttribute(\PDO::ATTR_DRIVER_NAME);
        return $driver === 'mysql' || $driver === 'mariadb' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
    }

    private function ejecutarSql(string $sql): void
    {
        $this->bd->exec($sql);
    }

    private function crearTablasTemporales(): void
    {
        $ai = $this->autoIncrement();
        $this->ejecutarSql("CREATE TABLE IF NOT EXISTS test_modelo (id INTEGER PRIMARY KEY $ai, nombre TEXT NOT NULL DEFAULT '', edad INTEGER DEFAULT 0, email TEXT DEFAULT '', salario REAL DEFAULT 0.0, activo INTEGER DEFAULT 1, id_operador INTEGER DEFAULT NULL, rol_id INTEGER DEFAULT NULL)");
        $this->ejecutarSql("CREATE TABLE IF NOT EXISTS test_relacion (id INTEGER PRIMARY KEY $ai, test_modelo_id INTEGER NOT NULL DEFAULT 0, descripcion TEXT DEFAULT '')");
        $this->ejecutarSql("CREATE TABLE IF NOT EXISTS test_eventos (id INTEGER PRIMARY KEY $ai, nombre TEXT DEFAULT '', orden INTEGER DEFAULT 0)");
        $this->ejecutarSql("CREATE TABLE IF NOT EXISTS test_eager (id INTEGER PRIMARY KEY $ai, nombre TEXT DEFAULT '', id_operador INTEGER DEFAULT NULL)");
        $this->ejecutarSql("CREATE TABLE IF NOT EXISTS test_pertenece (id INTEGER PRIMARY KEY $ai, nombre TEXT DEFAULT '', rol_id INTEGER DEFAULT NULL)");
    }

    private function insertarDatosIniciales(): void
    {
        $this->ejecutarSql("DELETE FROM test_modelo");
        $this->ejecutarSql("DELETE FROM test_relacion");
        $this->ejecutarSql("DELETE FROM test_eventos");
        $this->ejecutarSql("DELETE FROM test_eager");
        $this->ejecutarSql("DELETE FROM test_pertenece");
        $this->ejecutarSql("INSERT INTO test_modelo (nombre, edad, email, salario, activo) VALUES ('Alice', 30, 'alice@test.com', 50000.0, 1)");
        $this->idAlice = (int)$this->bd->lastInsertId();
        $this->ejecutarSql("INSERT INTO test_modelo (nombre, edad, email, salario, activo) VALUES ('Bob', 25, 'bob@test.com', 45000.0, 1)");
        $this->idBob = (int)$this->bd->lastInsertId();
        $this->ejecutarSql("INSERT INTO test_modelo (nombre, edad, email, salario, activo) VALUES ('Charlie', 35, 'charlie@test.com', 60000.0, 0)");
        $this->idCharlie = (int)$this->bd->lastInsertId();
    }

    // --- CRUD ---

    public function testCrear(): void
    {
        $m = TestModelo::crear(['nombre' => 'Diana', 'edad' => 28]);
        $this->assertNotNull($m->id);
        $this->assertSame('Diana', $m->nombre);
        $this->assertSame(28, $m->edad);
    }

    public function testBuscarPorId(): void
    {
        $m = TestModelo::buscar($this->idAlice);
        $this->assertNotNull($m);
        $this->assertSame('Alice', $m->nombre);
    }

    public function testBuscarIdInexistenteDevuelveNull(): void
    {
        $this->assertNull(TestModelo::buscar(999999));
    }

    public function testGuardarInsert(): void
    {
        $m = new TestModelo();
        $m->nombre = 'Eve';
        $m->edad = 22;
        $result = $m->guardar();
        $this->assertTrue($result);
        $this->assertNotNull($m->id);
        $recargado = TestModelo::buscar($m->id);
        $this->assertSame('Eve', $recargado->nombre);
    }

    public function testGuardarUpdate(): void
    {
        $m = TestModelo::buscar($this->idAlice);
        $this->assertNotNull($m);
        $m->nombre = 'Alice Modificada';
        $m->guardar();
        $recargado = TestModelo::buscar($this->idAlice);
        $this->assertSame('Alice Modificada', $recargado->nombre);
    }

    public function testEliminar(): void
    {
        $m = TestModelo::buscar($this->idAlice);
        $this->assertNotNull($m);
        $result = $m->eliminar();
        $this->assertTrue($result);
        $this->assertNull(TestModelo::buscar($this->idAlice));
    }

    public function testEliminarRegistroInexistenteDevuelveFalse(): void
    {
        $m = new TestModelo();
        $this->assertFalse($m->eliminar());
    }

    // --- Query builder ---

    public function testDondeIgual(): void
    {
        $resultados = TestModelo::donde('nombre', 'Alice')->obtener();
        $this->assertCount(1, $resultados);
        $this->assertSame('Alice', $resultados[0]->nombre);
    }

    public function testDondeMayorQue(): void
    {
        $resultados = TestModelo::donde('edad', '>', 28)->obtener();
        $this->assertCount(2, $resultados);
    }

    public function testDondeMenorQue(): void
    {
        $resultados = TestModelo::donde('edad', '<', 30)->obtener();
        $this->assertCount(1, $resultados);
        $this->assertSame('Bob', $resultados[0]->nombre);
    }

    public function testDondeMayorIgual(): void
    {
        $resultados = TestModelo::donde('edad', '>=', 30)->obtener();
        $this->assertCount(2, $resultados);
    }

    public function testDondeMenorIgual(): void
    {
        $resultados = TestModelo::donde('edad', '<=', 25)->obtener();
        $this->assertCount(1, $resultados);
        $this->assertSame('Bob', $resultados[0]->nombre);
    }

    public function testDondeDistinto(): void
    {
        $resultados = TestModelo::donde('edad', '<>', 30)->obtener();
        $this->assertCount(2, $resultados);
    }

    public function testDondeLike(): void
    {
        $resultados = TestModelo::donde('nombre', 'LIKE', '%lice')->obtener();
        $this->assertCount(1, $resultados);
        $this->assertSame('Alice', $resultados[0]->nombre);
    }

    public function testDondeEn(): void
    {
        $resultados = TestModelo::dondeEn('edad', [25, 35])->obtener();
        $this->assertCount(2, $resultados);
    }

    public function testDondeEnVacio(): void
    {
        $resultados = TestModelo::dondeEn('edad', [])->obtener();
        $this->assertCount(3, $resultados);
    }

    public function testDondeNulo(): void
    {
        $resultados = TestModelo::dondeNulo('email')->obtener();
        $this->assertCount(0, $resultados);
    }

    public function testDondeNoNulo(): void
    {
        $resultados = TestModelo::dondeNoNulo('email')->obtener();
        $this->assertCount(3, $resultados);
    }

    public function testODonde(): void
    {
        $resultados = TestModelo::donde('nombre', 'Alice')->oDonde('nombre', 'Bob')->obtener();
        $this->assertCount(2, $resultados);
    }

    public function testOrdenarPor(): void
    {
        $resultados = TestModelo::donde('edad', '>', 0)->ordenarPor('edad', 'DESC')->obtener();
        $this->assertCount(3, $resultados);
        $this->assertSame('Charlie', $resultados[0]->nombre);
    }

    public function testLimite(): void
    {
        $resultados = TestModelo::donde('edad', '>', 0)->limite(2)->obtener();
        $this->assertCount(2, $resultados);
    }

    public function testLimiteSaltar(): void
    {
        $resultados = TestModelo::donde('edad', '>', 0)->ordenarPor('edad', 'ASC')->limite(1)->saltar(1)->obtener();
        $this->assertCount(1, $resultados);
        $this->assertSame('Alice', $resultados[0]->nombre);
    }

    public function testSeleccionar(): void
    {
        $resultados = TestModelo::seleccionar(['nombre'])->donde('nombre', 'Alice')->obtener();
        $this->assertCount(1, $resultados);
        $this->assertSame('Alice', $resultados[0]->nombre);
    }

    // --- obtener twice ---

    public function testObtenerSegundaVezDevuelveArrayVacio(): void
    {
        $consulta = TestModelo::donde('nombre', 'Alice');
        $primera = $consulta->obtener();
        $this->assertCount(1, $primera);
        $segunda = $consulta->obtener();
        $this->assertSame([], $segunda);
    }

    // --- Aggregate functions ---

    public function testContar(): void
    {
        $this->assertSame(3, TestModelo::contar());
    }

    public function testContarDonde(): void
    {
        $count = TestModelo::donde('edad', '>', 25)->contarDonde();
        $this->assertSame(2, $count);
    }

    public function testSumar(): void
    {
        $total = TestModelo::sumar('edad');
        $this->assertSame(90, $total);
    }

    public function testPromediar(): void
    {
        $prom = TestModelo::promediar('edad');
        $this->assertEquals(30.0, $prom, '', 0.01);
    }

    public function testMinimo(): void
    {
        $this->assertSame(25, TestModelo::minimo('edad'));
    }

    public function testMaximo(): void
    {
        $this->assertSame(35, TestModelo::maximo('edad'));
    }

    // --- primero / primeroOExcepcion / primeroOCrear / crearOActualizar ---

    public function testPrimero(): void
    {
        $m = TestModelo::donde('nombre', 'Alice')->primero();
        $this->assertNotNull($m);
        $this->assertSame('Alice', $m->nombre);
    }

    public function testPrimeroDevuelveNull(): void
    {
        $this->assertNull(TestModelo::donde('nombre', 'Inexistente')->primero());
    }

    public function testPrimeroOExcepcion(): void
    {
        $this->expectException(RuntimeException::class);
        TestModelo::donde('nombre', 'Inexistente')->primeroOExcepcion();
    }

    public function testPrimeroOCrearNuevo(): void
    {
        $m = TestModelo::primeroOCrear('nombre', 'Diana', ['edad' => 28]);
        $this->assertSame('Diana', $m->nombre);
        $this->assertSame(28, $m->edad);
    }

    public function testPrimeroOCrearExistente(): void
    {
        $m = TestModelo::primeroOCrear('nombre', 'Alice', ['edad' => 99]);
        $this->assertSame('Alice', $m->nombre);
        $this->assertSame(30, $m->edad); // original value kept
    }

    public function testCrearOActualizarNuevo(): void
    {
        $m = TestModelo::crearOActualizar(['nombre' => 'Frank'], ['edad' => 40]);
        $this->assertSame('Frank', $m->nombre);
        $this->assertSame(40, $m->edad);
    }

    public function testCrearOActualizarExistente(): void
    {
        $m = TestModelo::crearOActualizar(['nombre' => 'Alice'], ['edad' => 31]);
        $this->assertSame('Alice', $m->nombre);
        $this->assertSame(31, $m->edad);
    }

    // --- Events ---

    public function testEventosCreatingCreated(): void
    {
        TestEventos::$ordenLlamadas = [];
        $m = TestEventos::crear(['nombre' => 'Test', 'orden' => 1]);
        $this->assertSame(['creating', 'created'], TestEventos::$ordenLlamadas);
    }

    public function testEventosUpdatingUpdated(): void
    {
        TestEventos::$ordenLlamadas = [];
        $m = TestEventos::crear(['nombre' => 'Test2', 'orden' => 2]);
        TestEventos::$ordenLlamadas = [];
        $m->nombre = 'Modificado';
        $m->guardar();
        $this->assertSame(['updating', 'updated'], TestEventos::$ordenLlamadas);
    }

    public function testEventosDeletingDeleted(): void
    {
        $m = TestEventos::crear(['nombre' => 'Test3', 'orden' => 3]);
        TestEventos::$ordenLlamadas = [];
        $m->eliminar();
        $this->assertSame(['deleting', 'deleted'], TestEventos::$ordenLlamadas);
    }

    // --- paginar ---

    public function testPaginarNormal(): void
    {
        $res = TestModelo::paginar(1, 2);
        $this->assertArrayHasKey('datos', $res);
        $this->assertArrayHasKey('total', $res);
        $this->assertArrayHasKey('pagina', $res);
        $this->assertArrayHasKey('total_paginas', $res);
        $this->assertSame(2, $res['por_pagina']);
        $this->assertSame(3, $res['total']);
        $this->assertSame(2, $res['total_paginas']);
    }

    public function testPaginarPaginaCeroNoLanzaExcepcion(): void
    {
        $res = TestModelo::paginar(0, 2);
        $this->assertArrayHasKey('datos', $res);
        $this->assertArrayHasKey('total', $res);
    }

    public function testPaginarPorPaginaUno(): void
    {
        $res = TestModelo::paginar(1, 1);
        $this->assertSame(3, $res['total_paginas']);
        $this->assertCount(1, $res['datos']);
    }

    public function testPaginarTotalCero(): void
    {
        $this->ejecutarSql("DELETE FROM test_modelo");
        $res = TestModelo::paginar(1, 10);
        $this->assertSame(0, $res['total']);
        $this->assertSame(1, $res['total_paginas']);
    }

    public function testPaginarWhereLike(): void
    {
        $res = TestModelo::paginar(1, 10, ['nombre LIKE' => '%lice']);
        $this->assertSame(1, $res['total']);
    }

    public function testPaginarWhereNull(): void
    {
        $res = TestModelo::paginar(1, 10, ['id_operador' => null]);
        $this->assertSame(3, $res['total']);
    }

    public function testPaginarWhereArray(): void
    {
        $res = TestModelo::paginar(1, 10, ['edad' => [25, 35]]);
        $this->assertSame(2, $res['total']);
    }

    // --- Relationships ---

    public function testPerteneceA(): void
    {
        $m = TestPertenece::crear(['nombre' => 'Test', 'rol_id' => 2]);
        $rol = $m->perteneceA(\LiteFramework\Modelos\Rol::class, 'rol_id', 'id_rol');
        $this->assertNotNull($rol);
        $this->assertSame('Administrador', $rol->nombre_rol);
    }

    public function testTieneMuchos(): void
    {
        $padre = TestModelo::crear(['nombre' => 'Padre']);
        TestRelacion::crear(['test_modelo_id' => $padre->id, 'descripcion' => 'hijo1']);
        TestRelacion::crear(['test_modelo_id' => $padre->id, 'descripcion' => 'hijo2']);
        $hijos = $padre->tieneMuchos(TestRelacion::class, 'test_modelo_id', 'id');
        $this->assertCount(2, $hijos);
    }

    // --- Eager loading ---

    public function testEagerLoadingConOperador(): void
    {
        $op = \LiteFramework\Modelos\Operador::crear([
            'id_rol' => 2,
            'nombre_completo' => 'Test Op',
            'correo_electronico' => 'eager_op_test@test.com',
            'clave_acceso' => 'pass',
        ]);
        $eager = TestEager::crear(['nombre' => 'Eager1', 'id_operador' => $op->id_operador]);
        $resultados = TestEager::donde('id', $eager->id)->con('operador')->obtener();
        $this->assertCount(1, $resultados);
        $relacion = $resultados[0]->eagerOperador();
        $this->assertNotNull($relacion);
        $this->assertSame('Test Op', $relacion->nombre_completo);
    }

    public function testEagerLoadingIdOperadorNull(): void
    {
        $eager = TestEager::crear(['nombre' => 'NoOp']);
        $resultados = TestEager::donde('id', $eager->id)->con('operador')->obtener();
        $this->assertCount(1, $resultados);
    }

    // --- llenar ---

    public function testLlenarConFillable(): void
    {
        $m = new TestRellenable();
        $m->llenar(['nombre' => 'Fill', 'edad' => 99]);
        $this->assertSame('Fill', $m->nombre);
        $this->assertNull($m->edad);
    }

    public function testLlenarSinFillableLlenaTodo(): void
    {
        $m = new TestModelo();
        $m->llenar(['nombre' => 'All', 'edad' => 50]);
        $this->assertSame('All', $m->nombre);
        $this->assertSame(50, $m->edad);
    }

    // --- __get / __set type casting ---

    public function testGetTypeCastInt(): void
    {
        $m = TestModelo::buscar($this->idAlice);
        $this->assertNotNull($m);
        $this->assertIsInt($m->edad);
    }

    public function testGetTypeCastFloat(): void
    {
        $m = TestModelo::buscar($this->idAlice);
        $this->assertNotNull($m);
        $this->assertIsFloat($m->salario);
    }

    public function testGetTypeCastBool(): void
    {
        $m = TestModelo::buscar($this->idAlice);
        $this->assertNotNull($m);
        $this->assertIsBool($m->activo);
    }

    public function testSetThenGet(): void
    {
        $m = new TestModelo();
        $m->nombre = 'Setter';
        $this->assertSame('Setter', $m->nombre);
    }

    public function testIsset(): void
    {
        $m = TestModelo::buscar($this->idAlice);
        $this->assertNotNull($m);
        $this->assertTrue(isset($m->nombre));
        $this->assertFalse(isset($m->no_existe));
    }

    // --- todos ---

    public function testTodos(): void
    {
        $todos = TestModelo::todos();
        $this->assertCount(3, $todos);
    }

    // --- aArreglo ---

    public function testAArreglo(): void
    {
        $m = TestModelo::buscar($this->idAlice);
        $this->assertNotNull($m);
        $arr = $m->aArreglo();
        $this->assertIsArray($arr);
        $this->assertArrayHasKey('nombre', $arr);
        $this->assertSame('Alice', $arr['nombre']);
    }
}
