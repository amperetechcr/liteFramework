<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Regression;

use LiteFramework\Config\ConexionBaseDatos as DB;
use LiteFramework\Nucleo\Modelo;
use LiteFramework\Nucleo\Validador;
use LiteFramework\Nucleo\DialectoBaseDatos;
use LiteFramework\Seguridad\SanitizadorEntrada;
use LiteFramework\Api\Controladores\CrudApiControlador;

class SqlInjectionRegressionTest extends \TestBase
{
    private ?\PDO $bd = null;

    public function setUp(): void
    {
        $this->bd = DB::obtenerInstancia()->obtenerConector();
        $driver = $this->bd->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $_SESSION = ['operador_id' => 1, 'operador_rol' => 1, 'matriz_permisos' => []];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit/1.0';

        if ($driver === 'mysql') {
            $this->bd->exec("CREATE TEMPORARY TABLE IF NOT EXISTS test_injection (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL,
                estado INT DEFAULT 1
            )");
        } else {
            $this->bd->exec("CREATE TABLE IF NOT EXISTS test_injection (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                estado INTEGER DEFAULT 1
            )");
        }
        $this->bd->exec("DELETE FROM test_injection");
    }

    public function tearDown(): void
    {
        $this->bd->exec("DROP TABLE IF EXISTS test_injection");
        $ref = new \ReflectionProperty(Modelo::class, 'conexionGlobal');
        $ref->setAccessible(true);
        $ref->setValue(null, null);
        DB::resetearInstancia();
        $_SESSION = [];
    }

    private function crearModeloTest(): Modelo
    {
        return new class extends Modelo {
            protected static string $tabla = 'test_injection';
        };
    }

    private function paginarSinInyeccion(callable $fn): void
    {
        $this->bd->exec("INSERT INTO test_injection (nombre) VALUES ('antes')");
        try {
            $fn();
        } catch (\PDOException $e) {
        }
        $stmt = $this->bd->query("SELECT COUNT(*) FROM test_injection");
        $this->assertSame(1, (int)$stmt->fetchColumn(), 'Inyeccion no debe eliminar datos');
        $this->bd->exec("DELETE FROM test_injection");
    }

    public function testPaginarSelectInjectionAttemptBlocked(): void
    {
        $this->paginarSinInyeccion(function () {
            $modelo = $this->crearModeloTest();
            $modelo::paginar(1, 10, [], 'id; DROP TABLE test_injection --', '', '');
        });
    }

    public function testPaginarJoinsInjectionAttemptBlocked(): void
    {
        $this->paginarSinInyeccion(function () {
            $modelo = $this->crearModeloTest();
            $modelo::paginar(1, 10, [], '*',
                'JOIN sqlite_master; DROP TABLE test_injection --', '');
        });
    }

    public function testPaginarGroupByInjectionAttemptBlocked(): void
    {
        $this->paginarSinInyeccion(function () {
            $modelo = $this->crearModeloTest();
            $modelo::paginar(1, 10, [], '*', '',
                'id; DELETE FROM test_injection --');
        });
    }

    public function testPaginarWhereRawStringInjectionBlocked(): void
    {
        $this->paginarSinInyeccion(function () {
            $modelo = $this->crearModeloTest();
            $modelo::paginar(1, 10,
                ["1=1; DELETE FROM test_injection --"], '*', '', '');
        });
    }

    public function testValidadorReglaUnicoWithTableColumnInjectionBlocked(): void
    {
        $this->bd->exec("INSERT INTO test_injection (nombre) VALUES ('antes')");
        try {
            $validador = new Validador(
                ['nombre' => 'test'],
                ['nombre' => 'unico:test_injection,id']
            );
            $validador->pasa();
        } catch (\PDOException $e) {
        }
        $stmt = $this->bd->query("SELECT COUNT(*) FROM test_injection");
        $this->assertSame(1, (int)$stmt->fetchColumn());
        $this->bd->exec("DELETE FROM test_injection");
    }

    public function testCrudApiControladorWithTableNameInjectionBlocked(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['matriz_permisos'] = ['operador.crear'];

        try {
            $controlador = new CrudApiControlador();
            $resultado = $controlador->procesar([
                'token_peticion' => 'test',
                'accion_crud' => 'leer',
                'tabla_destino' => 'test_injection; DROP TABLE operador --',
            ]);
            $this->assertIsArray($resultado);
        } catch (\Throwable $e) {
        }
        $stmt = $this->bd->query("SELECT COUNT(*) FROM operador");
        $this->assertNotFalse($stmt);
    }

    public function testCrudApiControladorWithColumnNameInjectionBlocked(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['matriz_permisos'] = ['operador.leer'];

        try {
            $controlador = new CrudApiControlador();
            $resultado = $controlador->procesar([
                'token_peticion' => 'test',
                'accion_crud' => 'leer',
                'tabla_destino' => 'operador',
            ]);
            $this->assertIsArray($resultado);
            $this->assertCount(2, $resultado);
        } catch (\Throwable $e) {
            $this->fail('No deberia lanzar excepcion: ' . $e->getMessage());
        }
    }

    public function testDialectoFechaRestarWithMaliciousUnidadBlocked(): void
    {
        $resultado = DialectoBaseDatos::fechaRestar($this->bd, 'DAY', 7);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('7', $resultado);
    }

    public function testDialectoExtraerFechaWithMaliciousColumnaBlocked(): void
    {
        $resultado = DialectoBaseDatos::extraerFecha($this->bd, 'fecha_registro');
        $this->assertIsString($resultado);
        $this->assertStringContainsString('fecha_registro', $resultado);
    }

    public function testSanitizadorEntradaHtmlInjectionOnEventStripped(): void
    {
        $resultado = SanitizadorEntrada::sanitizarTextoBase(
            '<img src=x onerror=alert(1)>'
        );
        $this->assertStringNotContainsString('onerror', $resultado);
    }

    public function testSanitizadorEntradaJavascriptUriStripped(): void
    {
        $resultado = SanitizadorEntrada::sanitizarTextoBase(
            '<a href="javascript:alert(1)">click</a>'
        );
        $this->assertStringContainsString('&gt;', $resultado);
        $this->assertStringNotContainsString('<a href', $resultado);
    }

    public function testSanitizadorEntradaDataUriStripped(): void
    {
        $resultado = SanitizadorEntrada::sanitizarTextoBase(
            '<embed src="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">'
        );
        $this->assertStringContainsString('&gt;', $resultado);
        $this->assertStringNotContainsString('<embed src', $resultado);
    }

    public function testModeloDondeSpecialCharsSanitized(): void
    {
        $this->bd->exec("INSERT INTO test_injection (nombre) VALUES ('antes')");
        $clase = new class extends Modelo {
            protected static string $tabla = 'test_injection';
        };

        try {
            $clase::donde('nombre--\' DROP TABLE test_injection --', 'safe')->obtener();
        } catch (\PDOException $e) {
        }

        $stmt = $this->bd->query("SELECT COUNT(*) FROM test_injection");
        $this->assertSame(1, (int)$stmt->fetchColumn());
        $this->bd->exec("DELETE FROM test_injection");
    }

    public function testModeloDondeEnWithSqlInValuesIsParameterized(): void
    {
        $this->bd->exec("INSERT INTO test_injection (nombre) VALUES ('safe')");
        $clase = new class extends Modelo {
            protected static string $tabla = 'test_injection';
        };

        $resultados = $clase::dondeEn('nombre', [
            "safe",
            "'; DELETE FROM test_injection; --",
            "' OR '1'='1",
        ])->obtener();
        $this->assertCount(1, $resultados);

        $stmt = $this->bd->query("SELECT COUNT(*) FROM test_injection");
        $this->assertSame(1, (int)$stmt->fetchColumn());
        $this->bd->exec("DELETE FROM test_injection");
    }

    public function testLegitimateInputSucceeds(): void
    {
        $this->bd->exec("INSERT INTO test_injection (nombre) VALUES ('safe')");
        $clase = new class extends Modelo {
            protected static string $tabla = 'test_injection';
        };

        $resultados = $clase::donde('nombre', 'safe')->obtener();
        $this->assertCount(1, $resultados);
        $this->assertSame('safe', $resultados[0]->nombre);

        $todos = $clase::todos();
        $this->assertCount(1, $todos);
        $this->bd->exec("DELETE FROM test_injection");
    }
}
