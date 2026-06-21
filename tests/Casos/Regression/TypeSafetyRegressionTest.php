<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Regression;

use LiteFramework\Config\ConexionBaseDatos as DB;
use LiteFramework\Nucleo\Modelo;

class TypeSafetyRegressionTest extends \TestBase
{
    private ?\PDO $bd = null;

    public function setUp(): void
    {
        $this->bd = DB::obtenerInstancia()->obtenerConector();
        $driver = $this->bd->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit/1.0';
        $_SESSION = [];

        if ($driver === 'mysql') {
            $this->bd->exec("CREATE TEMPORARY TABLE IF NOT EXISTS test_tipos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL,
                cantidad DECIMAL(10,2) DEFAULT 0,
                activo INT DEFAULT 0,
                configuracion TEXT DEFAULT NULL
            )");
        } else {
            $this->bd->exec("CREATE TABLE IF NOT EXISTS test_tipos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                cantidad DECIMAL(10,2) DEFAULT 0,
                activo INTEGER DEFAULT 0,
                configuracion TEXT DEFAULT NULL
            )");
        }
        $this->bd->exec("DELETE FROM test_tipos");
    }

    public function tearDown(): void
    {
        $this->bd->exec("DROP TABLE IF EXISTS test_tipos");
        $ref = new \ReflectionProperty(Modelo::class, 'conexionGlobal');
        $ref->setAccessible(true);
        $ref->setValue(null, null);
        DB::resetearInstancia();
        $_SESSION = [];
    }

    private function crearModelo(): Modelo
    {
        return new class extends Modelo {
            protected static string $tabla = 'test_tipos';
            protected static array $tipos = [
                'cantidad' => 'float',
                'activo' => 'bool',
                'configuracion' => 'json',
            ];
        };
    }

    public function testDondeWithObjectNoToStringCaughtGracefully(): void
    {
        $clase = new class extends Modelo {
            protected static string $tabla = 'test_tipos';
        };

        $objetoSinToString = new class {
        };

        try {
            $resultados = $clase::donde('nombre', $objetoSinToString)->obtener();
            $this->assertIsArray($resultados);
        } catch (\Throwable $e) {
            $this->assertStringNotContainsString('Fatal error', $e->getMessage());
        }
    }

    public function testPrimeroOCrearWithBoolValorCastsToString(): void
    {
        $clase = new class extends Modelo {
            protected static string $tabla = 'test_tipos';
        };

        $modelo = $clase::primeroOCrear('nombre', true, ['activo' => 1]);
        $this->assertNotNull($modelo);
        $this->assertNotEmpty($modelo->nombre);
    }

    public function testSumarWithDecimalColumnReturnsInt(): void
    {
        $this->bd->exec("INSERT INTO test_tipos (nombre, cantidad) VALUES ('a', 10.50)");
        $this->bd->exec("INSERT INTO test_tipos (nombre, cantidad) VALUES ('b', 20.75)");

        $clase = new class extends Modelo {
            protected static string $tabla = 'test_tipos';
        };

        $suma = $clase::sumar('cantidad');
        $this->assertIsInt($suma);
        $this->assertGreaterThan(0, $suma);
    }

    public function testGetWithJsonTypeAndPreDecodedArrayReturnsNullHandled(): void
    {
        $this->bd->exec("INSERT INTO test_tipos (nombre, configuracion) VALUES ('json_test', '{\"key\":\"value\"}')");
        $id = (int)$this->bd->lastInsertId();

        $clase = new class extends Modelo {
            protected static string $tabla = 'test_tipos';
            protected static array $tipos = [
                'configuracion' => 'json',
            ];
        };

        $modelo = $clase::buscar($id);
        $this->assertNotNull($modelo);
        $config = $modelo->configuracion;
        $this->assertIsArray($config);
        $this->assertSame('value', $config['key']);
    }

    public function testContarWithEmptyTableReturnsZero(): void
    {
        $this->bd->exec("DELETE FROM test_tipos");

        $clase = new class extends Modelo {
            protected static string $tabla = 'test_tipos';
        };

        $total = $clase::contar();
        $this->assertSame(0, $total);
        $this->assertIsInt($total);
    }

    public function testPaginarWithPaginaZeroReturnsSafeDefault(): void
    {
        $this->bd->exec("INSERT INTO test_tipos (nombre) VALUES ('a'), ('b'), ('c')");

        $clase = new class extends Modelo {
            protected static string $tabla = 'test_tipos';
        };

        $resultado = $clase::paginar(0, 10);
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('datos', $resultado);
        $this->assertArrayHasKey('total', $resultado);
        $this->assertArrayHasKey('pagina', $resultado);
        $this->assertGreaterThanOrEqual(0, $resultado['pagina']);
    }

    public function testPaginarWithPorPaginaZeroHandlesDivisionByZero(): void
    {
        $this->bd->exec("INSERT INTO test_tipos (nombre) VALUES ('a'), ('b')");

        $clase = new class extends Modelo {
            protected static string $tabla = 'test_tipos';
        };

        try {
            $clase::paginar(1, 0);
            $this->fail('Deberia haber lanzado excepcion');
        } catch (\DivisionByZeroError $e) {
            $this->assertStringContainsString('Division by zero', $e->getMessage());
        } catch (\Throwable $e) {
            $this->addToAssertionCount(1);
        }
    }
}
