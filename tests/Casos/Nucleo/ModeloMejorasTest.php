<?php

use PHPUnit\Framework\TestCase;
use LiteFramework\Nucleo\Modelo;
use LiteFramework\Nucleo\DialectoBaseDatos;
use LiteFramework\Config\ConexionBaseDatos;

class ModeloMejorasTest extends TestCase
{
    private static PDO $pdo;
    private static bool $esMySQL;

    public static function setUpBeforeClass(): void
    {
        ConexionBaseDatos::resetearInstancia();
        $db = ConexionBaseDatos::obtenerInstancia();
        self::$pdo = $db->obtenerConector();
        self::$esMySQL = DialectoBaseDatos::esMySQL(self::$pdo);
    }

    private function ddlTablaMejoras(): string
    {
        if (self::$esMySQL) {
            return "CREATE TABLE IF NOT EXISTS test_mejoras (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL,
                email VARCHAR(255) DEFAULT NULL,
                edad INT DEFAULT 0,
                salario DECIMAL(10,2) DEFAULT 0.00,
                activo TINYINT DEFAULT 1,
                categoria VARCHAR(100) DEFAULT 'general'
            ) ENGINE=InnoDB";
        }
        return "CREATE TABLE IF NOT EXISTS test_mejoras (
            id INTEGER PRIMARY KEY,
            nombre TEXT NOT NULL,
            email TEXT DEFAULT NULL,
            edad INTEGER DEFAULT 0,
            salario REAL DEFAULT 0.0,
            activo INTEGER DEFAULT 1,
            categoria TEXT DEFAULT 'general'
        )";
    }

    protected function setUp(): void
    {
        self::$pdo->exec($this->ddlTablaMejoras());
        $truncar = self::$esMySQL ? "TRUNCATE TABLE test_mejoras" : "DELETE FROM test_mejoras";
        self::$pdo->exec($truncar);
        self::$pdo->exec("INSERT INTO test_mejoras (id, nombre, email, edad, salario, activo, categoria) VALUES
            (1, 'Ana Lopez', 'ana@test.com', 30, 50000, 1, 'premium'),
            (2, 'Luis Perez', 'luis@test.com', 25, 35000, 1, 'standard'),
            (3, 'Carlos Ruiz', 'carlos@test.com', 35, 65000, 1, 'premium'),
            (4, 'Mia Gomez', 'mia@test.com', 28, 42000, 0, 'standard'),
            (5, 'Sofia Diaz', 'sofia@test.com', 32, 55000, 1, 'vip')
        ");
    }

    // --- TestMejoras model inline ---

    private function crearModelo(): TestMejoras
    {
        return new TestMejoras();
    }

    // --- 1. dondeEn() ---

    public function testDondeEn(): void
    {
        $resultados = TestMejoras::dondeEn('id', [1, 3, 5])->obtener();
        $this->assertCount(3, $resultados);
        $this->assertEquals('Ana Lopez', $resultados[0]->nombre);
        $this->assertEquals('Carlos Ruiz', $resultados[1]->nombre);
    }

    public function testDondeEnVacio(): void
    {
        $resultados = TestMejoras::dondeEn('id', [])->obtener();
        $this->assertCount(5, $resultados);
    }

    // --- 2. dondeNulo() / dondeNoNulo() ---

    public function testDondeNulo(): void
    {
        $resultados = TestMejoras::dondeNulo('email')->obtener();
        $this->assertCount(0, $resultados);

        self::$pdo->exec("UPDATE test_mejoras SET email = NULL WHERE id = 2");
        $resultados = TestMejoras::dondeNulo('email')->obtener();
        $this->assertCount(1, $resultados);
    }

    public function testDondeNoNulo(): void
    {
        $resultados = TestMejoras::dondeNoNulo('email')->obtener();
        $this->assertCount(5, $resultados);

        self::$pdo->exec("UPDATE test_mejoras SET email = NULL WHERE id = 2");
        $resultados = TestMejoras::dondeNoNulo('email')->obtener();
        $this->assertCount(4, $resultados);
    }

    // --- 3. contarDonde() ---

    public function testContarDonde(): void
    {
        $total = TestMejoras::donde('activo', 1)->contarDonde();
        $this->assertEquals(4, $total);
    }

    public function testContarDondeSinCondiciones(): void
    {
        $modelo = new TestMejoras();
        $total = $modelo->contarDonde();
        $this->assertEquals(5, $total);
    }

    public function testContarDondeConOr(): void
    {
        $total = TestMejoras::donde('categoria', 'premium')->oDonde('categoria', 'vip')->contarDonde();
        $this->assertEquals(3, $total);
    }

    public function testContarDondeConNulo(): void
    {
        self::$pdo->exec("UPDATE test_mejoras SET email = NULL WHERE id = 2");
        $total = TestMejoras::dondeNulo('email')->contarDonde();
        $this->assertEquals(1, $total);
    }

    // --- 4. sumar(), promediar(), minimo(), maximo() ---

    public function testSumar(): void
    {
        $ddl = self::$esMySQL
            ? "CREATE TABLE IF NOT EXISTS test_suma (id INT AUTO_INCREMENT PRIMARY KEY, valor INT) ENGINE=InnoDB"
            : "CREATE TABLE IF NOT EXISTS test_suma (id INTEGER PRIMARY KEY AUTOINCREMENT, valor INTEGER)";
        self::$pdo->exec($ddl);
        $truncarSuma = self::$esMySQL ? "TRUNCATE TABLE test_suma" : "DELETE FROM test_suma";
        self::$pdo->exec($truncarSuma);
        self::$pdo->exec("INSERT INTO test_suma (valor) VALUES (10), (20), (30), (40), (50)");
        $suma = TestSuma::sumar('valor');
        $this->assertEquals(150, $suma);
        self::$pdo->exec("DROP TABLE IF EXISTS test_suma");
    }

    public function testPromediar(): void
    {
        $ddl = self::$esMySQL
            ? "CREATE TABLE IF NOT EXISTS test_prom (id INT AUTO_INCREMENT PRIMARY KEY, valor DECIMAL(10,2)) ENGINE=InnoDB"
            : "CREATE TABLE IF NOT EXISTS test_prom (id INTEGER PRIMARY KEY AUTOINCREMENT, valor REAL)";
        self::$pdo->exec($ddl);
        $truncarProm = self::$esMySQL ? "TRUNCATE TABLE test_prom" : "DELETE FROM test_prom";
        self::$pdo->exec($truncarProm);
        self::$pdo->exec("INSERT INTO test_prom (valor) VALUES (10.5), (20.5), (30.0)");
        $prom = TestProm::promediar('valor');
        $this->assertEqualsWithDelta(20.333, $prom, 0.01);
        self::$pdo->exec("DROP TABLE IF EXISTS test_prom");
    }

    public function testMinimo(): void
    {
        $min = TestMejoras::minimo('edad');
        $this->assertEquals(25, $min);
    }

    public function testMaximo(): void
    {
        $max = TestMejoras::maximo('edad');
        $this->assertEquals(35, $max);
    }

    // --- 5. seleccionar() ---

    public function testSeleccionar(): void
    {
        $resultados = TestMejoras::seleccionar(['nombre', 'edad'])->obtener();
        $this->assertCount(5, $resultados);
        $this->assertNotNull($resultados[0]->nombre);
        $this->assertNull($resultados[0]->id);
    }

    // --- 6. primeroOExcepcion() ---

    public function testPrimeroOExcepcionExitoso(): void
    {
        $resultado = TestMejoras::donde('id', 1)->primeroOExcepcion();
        $this->assertNotNull($resultado);
        $this->assertEquals('Ana Lopez', $resultado->nombre);
    }

    public function testPrimeroOExcepcionLanzaExcepcion(): void
    {
        $this->expectException(RuntimeException::class);
        TestMejoras::donde('id', 999)->primeroOExcepcion();
    }

    // --- 7. primeroOCrear() ---

    public function testPrimeroOCrearExistente(): void
    {
        $resultado = TestMejoras::primeroOCrear('id', 1);
        $this->assertEquals('Ana Lopez', $resultado->nombre);
    }

    public function testPrimeroOCrearNuevo(): void
    {
        $resultado = TestMejoras::primeroOCrear('email', 'nuevo@test.com', ['nombre' => 'Nuevo Usuario', 'edad' => 40]);
        $this->assertNotNull($resultado->id);
        $this->assertEquals('Nuevo Usuario', $resultado->nombre);
    }

    // --- 8. crearOActualizar() ---

    public function testCrearOActualizarExistente(): void
    {
        $resultado = TestMejoras::crearOActualizar(['id' => 1], ['nombre' => 'Ana Lopez Modificada']);
        $this->assertEquals('Ana Lopez Modificada', $resultado->nombre);
    }

    public function testCrearOActualizarNuevo(): void
    {
        $resultado = TestMejoras::crearOActualizar(['email' => 'nuevo2@test.com'], ['nombre' => 'Nuevo2', 'edad' => 22]);
        $this->assertNotNull($resultado->id);
        $this->assertEquals('Nuevo2', $resultado->nombre);
    }

    // --- 9. donde() ahora es por instancia (no static) ---

    public function testDondeInstanceNoComparteEstado(): void
    {
        $consulta1 = TestMejoras::donde('id', 1);
        $consulta2 = TestMejoras::donde('id', 2);

        $resultado1 = $consulta1->obtener();
        $resultado2 = $consulta2->obtener();

        $this->assertCount(1, $resultado1);
        $this->assertCount(1, $resultado2);
        $this->assertEquals('Ana Lopez', $resultado1[0]->nombre);
        $this->assertEquals('Luis Perez', $resultado2[0]->nombre);
    }

    // --- 10. Eventos ---

    public function testEventoCreating(): void
    {
        $modelo = new TestEventos();
        $modelo->nombre = 'Test Evento';
        $modelo->guardar();
        $this->assertTrue($modelo->creatingDisparado);
    }

    public function testEventoCreated(): void
    {
        $modelo = new TestEventos();
        $modelo->nombre = 'Test Evento 2';
        $modelo->guardar();
        $this->assertTrue($modelo->createdDisparado);
    }

    public function testEventoUpdating(): void
    {
        $ddl = self::$esMySQL
            ? "CREATE TABLE IF NOT EXISTS test_eventos_upd (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(255)) ENGINE=InnoDB"
            : "CREATE TABLE IF NOT EXISTS test_eventos_upd (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT)";
        self::$pdo->exec($ddl);
        self::$pdo->exec("INSERT INTO test_eventos_upd (nombre) VALUES ('Original')");
        $modelo = TestEventosUpd::buscar(1);
        $modelo->nombre = 'Modificado';
        $modelo->guardar();
        $this->assertTrue($modelo->updatingDisparado);
        $this->assertTrue($modelo->updatedDisparado);
        self::$pdo->exec("DROP TABLE IF EXISTS test_eventos_upd");
    }

    // --- 11. paginar() ---

    public function testPaginarBasico(): void
    {
        $resultado = TestMejoras::paginar(1, 2);
        $this->assertEquals(5, $resultado['total']);
        $this->assertEquals(3, $resultado['total_paginas']);
        $this->assertEquals(1, $resultado['pagina']);
        $this->assertCount(2, $resultado['datos']);
    }

    public function testPaginarSegundaPagina(): void
    {
        $resultado = TestMejoras::paginar(2, 2);
        $this->assertEquals(2, $resultado['pagina']);
        $this->assertCount(2, $resultado['datos']);
    }

    public function testPaginarConWhere(): void
    {
        $resultado = TestMejoras::paginar(1, 10, ['activo' => 1]);
        $this->assertEquals(4, $resultado['total']);
    }

    public function testPaginarConLike(): void
    {
        $resultado = TestMejoras::paginar(1, 10, ['nombre LIKE' => '%Ana%']);
        $this->assertEquals(1, $resultado['total']);
    }
}

// --- Modelos temporales para testing ---

class TestMejoras extends Modelo
{
    protected static string $tabla = 'test_mejoras';
    protected static string $idColumna = 'id';
}

class TestSuma extends Modelo
{
    protected static string $tabla = 'test_suma';
    protected static string $idColumna = 'id';
}

class TestProm extends Modelo
{
    protected static string $tabla = 'test_prom';
    protected static string $idColumna = 'id';
}

class TestEventos extends Modelo
{
    protected static string $tabla = 'test_mejoras';
    protected static string $idColumna = 'id';
    public bool $creatingDisparado = false;
    public bool $createdDisparado = false;

    protected function creating(): void
    {
        $this->creatingDisparado = true;
    }

    protected function created(): void
    {
        $this->createdDisparado = true;
    }
}

class TestEventosUpd extends Modelo
{
    protected static string $tabla = 'test_eventos_upd';
    protected static string $idColumna = 'id';
    public bool $updatingDisparado = false;
    public bool $updatedDisparado = false;

    protected function updating(): void
    {
        $this->updatingDisparado = true;
    }

    protected function updated(): void
    {
        $this->updatedDisparado = true;
    }
}

class TestEventosNuevo extends Modelo
{
    protected static string $tabla = 'test_eventos_new';
    protected static string $idColumna = 'id';
    public bool $creatingDisparado = false;
    public bool $createdDisparado = false;

    protected function creating(): void
    {
        $this->creatingDisparado = true;
    }

    protected function created(): void
    {
        $this->createdDisparado = true;
    }
}
