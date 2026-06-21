<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Flujos;

use LiteFramework\Config\ConexionBaseDatos as DB;
use LiteFramework\Nucleo\Modelo;

class ModeloCicloVidaCompletoTest extends \TestBase
{
    private ?\PDO $bd = null;
    private const TABLA_TEST = 'test_ciclo_vida';
    private const TABLA_EVENTOS = 'test_eventos_log';

    public function setUp(): void
    {
        $this->bd = DB::obtenerInstancia()->obtenerConector();
        $this->bd->exec("
            CREATE TEMP TABLE IF NOT EXISTS " . self::TABLA_TEST . " (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                estado INTEGER DEFAULT 1,
                fecha_creacion TEXT,
                fecha_actualizacion TEXT
            )
        ");
        $this->bd->exec("
            CREATE TEMP TABLE IF NOT EXISTS " . self::TABLA_EVENTOS . " (
                id_evento INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_evento TEXT NOT NULL,
                timestamp TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $this->bd->exec("DELETE FROM " . self::TABLA_TEST);
        $this->bd->exec("DELETE FROM " . self::TABLA_EVENTOS);
    }

    public function tearDown(): void
    {
        $this->bd->exec("DROP TABLE IF EXISTS " . self::TABLA_TEST);
        $this->bd->exec("DROP TABLE IF EXISTS " . self::TABLA_EVENTOS);
        $ref = new \ReflectionProperty(Modelo::class, 'conexionGlobal');
        $ref->setAccessible(true);
        $ref->setValue(null, null);
        DB::resetearInstancia();
    }

    private function registrarEvento(string $nombre): void
    {
        $stmt = $this->bd->prepare(
            "INSERT INTO " . self::TABLA_EVENTOS . " (nombre_evento) VALUES (:nom)"
        );
        $stmt->execute([':nom' => $nombre]);
    }

    private function ultimosEventos(): array
    {
        $stmt = $this->bd->query(
            "SELECT nombre_evento FROM " . self::TABLA_EVENTOS . " ORDER BY id_evento ASC"
        );
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function testCrearLlenarGuardarSequence(): void
    {
        $registrosAntes = (int)$this->bd->query("SELECT COUNT(*) FROM " . self::TABLA_TEST)->fetchColumn();
        $modelo = $this->crearModelo();
        $modelo->nombre = 'inicial';
        $modelo->estado = 1;
        $this->assertTrue($modelo->guardar());
        $contar = (int)$this->bd->query("SELECT COUNT(*) FROM " . self::TABLA_TEST)->fetchColumn();
        $this->assertSame($registrosAntes + 1, $contar);
    }

    public function testBuscarRetrievesCreatedModel(): void
    {
        $this->bd->exec("INSERT INTO " . self::TABLA_TEST . " (nombre, estado) VALUES ('buscar_test', 1)");
        $id = (int)$this->bd->lastInsertId();

        $modelo = $this->crearModelo()->buscar($id);

        $this->assertNotNull($modelo);
        $this->assertSame('buscar_test', $modelo->nombre);
        $this->assertSame(1, $modelo->estado);
    }

    private function crearModelo(): Modelo
    {
        return new class extends Modelo {
            protected static string $tabla = 'test_ciclo_vida';
        };
    }

    public function testUpdateViaGuardarChangesAttributes(): void
    {
        $this->bd->exec("INSERT INTO " . self::TABLA_TEST . " (nombre, estado) VALUES ('original', 1)");
        $id = (int)$this->bd->lastInsertId();

        $modelo = $this->crearModelo()->buscar($id);
        $this->assertNotNull($modelo);

        $modelo->nombre = 'modificado';
        $modelo->estado = 0;
        $modelo->guardar();

        $refrescado = $this->crearModelo()->buscar($id);
        $this->assertNotNull($refrescado);
        $this->assertSame('modificado', $refrescado->nombre);
        $this->assertSame(0, $refrescado->estado);
    }

    public function testEliminarRemovesRecord(): void
    {
        $this->bd->exec("INSERT INTO " . self::TABLA_TEST . " (nombre, estado) VALUES ('eliminar_test', 1)");
        $id = (int)$this->bd->lastInsertId();

        $modelo = $this->crearModelo()->buscar($id);
        $this->assertNotNull($modelo);

        $resultado = $modelo->eliminar();
        $this->assertTrue($resultado);

        $despues = $this->crearModelo()->buscar($id);
        $this->assertNull($despues);
    }

    public function testCreatingEventFiresBeforeInsert(): void
    {
        $clase = new class extends Modelo {
            protected static string $tabla = 'test_ciclo_vida';
            protected function creating(): void
            {
                $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $bd->exec("INSERT INTO test_eventos_log (nombre_evento) VALUES ('creating_fired')");
            }
        };
        $modelo = $clase::crear(['nombre' => 'event_test', 'estado' => 1]);
        $eventos = $this->ultimosEventos();
        $this->assertContains('creating_fired', $eventos);
    }

    public function testCreatedEventFiresAfterInsert(): void
    {
        $clase = new class extends Modelo {
            protected static string $tabla = 'test_ciclo_vida';
            protected function created(): void
            {
                $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $bd->exec("INSERT INTO test_eventos_log (nombre_evento) VALUES ('created_fired')");
            }
        };
        $modelo = $clase::crear(['nombre' => 'created_test', 'estado' => 1]);
        $eventos = $this->ultimosEventos();
        $this->assertContains('created_fired', $eventos);
    }

    public function testUpdatingEventFiresBeforeUpdate(): void
    {
        $this->bd->exec("INSERT INTO " . self::TABLA_TEST . " (nombre, estado) VALUES ('upd_test', 1)");
        $id = (int)$this->bd->lastInsertId();

        $clase = new class extends Modelo {
            protected static string $tabla = 'test_ciclo_vida';
            protected function updating(): void
            {
                $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $bd->exec("INSERT INTO test_eventos_log (nombre_evento) VALUES ('updating_fired')");
            }
        };
        $modelo = $clase::buscar($id);
        $this->assertNotNull($modelo);
        $modelo->nombre = 'updated';
        $modelo->guardar();

        $eventos = $this->ultimosEventos();
        $this->assertContains('updating_fired', $eventos);
    }

    public function testUpdatedEventFiresAfterUpdate(): void
    {
        $this->bd->exec("INSERT INTO " . self::TABLA_TEST . " (nombre, estado) VALUES ('updated_test', 1)");
        $id = (int)$this->bd->lastInsertId();

        $clase = new class extends Modelo {
            protected static string $tabla = 'test_ciclo_vida';
            protected function updated(): void
            {
                $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $bd->exec("INSERT INTO test_eventos_log (nombre_evento) VALUES ('updated_fired')");
            }
        };
        $modelo = $clase::buscar($id);
        $this->assertNotNull($modelo);
        $modelo->nombre = 'updated_v2';
        $modelo->guardar();

        $eventos = $this->ultimosEventos();
        $this->assertContains('updated_fired', $eventos);
    }

    public function testDeletingEventFiresBeforeDelete(): void
    {
        $this->bd->exec("INSERT INTO " . self::TABLA_TEST . " (nombre, estado) VALUES ('del_test', 1)");
        $id = (int)$this->bd->lastInsertId();

        $clase = new class extends Modelo {
            protected static string $tabla = 'test_ciclo_vida';
            protected function deleting(): void
            {
                $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $bd->exec("INSERT INTO test_eventos_log (nombre_evento) VALUES ('deleting_fired')");
            }
        };
        $modelo = $clase::buscar($id);
        $this->assertNotNull($modelo);
        $modelo->eliminar();

        $eventos = $this->ultimosEventos();
        $this->assertContains('deleting_fired', $eventos);
    }

    public function testDeletedEventFiresAfterDelete(): void
    {
        $this->bd->exec("INSERT INTO " . self::TABLA_TEST . " (nombre, estado) VALUES ('del_test2', 1)");
        $id = (int)$this->bd->lastInsertId();

        $clase = new class extends Modelo {
            protected static string $tabla = 'test_ciclo_vida';
            protected function deleted(): void
            {
                $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $bd->exec("INSERT INTO test_eventos_log (nombre_evento) VALUES ('deleted_fired')");
            }
        };
        $modelo = $clase::buscar($id);
        $this->assertNotNull($modelo);
        $modelo->eliminar();

        $eventos = $this->ultimosEventos();
        $this->assertContains('deleted_fired', $eventos);
    }

    public function testEventOrderCreateUpdateDelete(): void
    {
        $clase = new class extends Modelo {
            protected static string $tabla = 'test_ciclo_vida';
            protected function creating(): void
            {
                $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $bd->exec("INSERT INTO test_eventos_log (nombre_evento) VALUES ('creating')");
            }
            protected function created(): void
            {
                $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $bd->exec("INSERT INTO test_eventos_log (nombre_evento) VALUES ('created')");
            }
            protected function updating(): void
            {
                $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $bd->exec("INSERT INTO test_eventos_log (nombre_evento) VALUES ('updating')");
            }
            protected function updated(): void
            {
                $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $bd->exec("INSERT INTO test_eventos_log (nombre_evento) VALUES ('updated')");
            }
            protected function deleting(): void
            {
                $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $bd->exec("INSERT INTO test_eventos_log (nombre_evento) VALUES ('deleting')");
            }
            protected function deleted(): void
            {
                $bd = \LiteFramework\Config\ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $bd->exec("INSERT INTO test_eventos_log (nombre_evento) VALUES ('deleted')");
            }
        };

        $modelo = $clase::crear(['nombre' => 'orden_test', 'estado' => 1]);
        $id = $modelo->id;

        $cargado = $clase::buscar($id);
        $cargado->nombre = 'orden_updated';
        $cargado->guardar();

        $cargado2 = $clase::buscar($id);
        $cargado2->eliminar();

        $eventos = $this->ultimosEventos();
        $ordenEsperado = ['creating', 'created', 'updating', 'updated', 'deleting', 'deleted'];
        $this->assertSame($ordenEsperado, $eventos);
    }

    public function testExisteChangesCorrectly(): void
    {
        $clase = new class extends Modelo {
            protected static string $tabla = 'test_ciclo_vida';
        };

        $modelo = $clase::crear(['nombre' => 'existe_test', 'estado' => 1]);
        $this->assertNotNull($modelo->id);

        $id = $modelo->id;

        $cargado = $clase::buscar($id);
        $this->assertNotNull($cargado);
        $this->assertSame('existe_test', $cargado->nombre);

        $cargado->eliminar();

        $despues = $clase::buscar($id);
        $this->assertNull($despues);

        $nuevo = $clase::crear(['nombre' => 'segundo', 'estado' => 1]);
        $this->assertNotNull($nuevo->id);
        $this->assertNotSame($id, $nuevo->id);
    }
}
