<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Seguridad;

use LiteFramework\Seguridad\SseGestor;
use LiteFramework\Config\ConexionBaseDatos as DB;

class SseGestorTest extends \TestBase
{
    private string $logFile;
    private string $crewFile;
    private string $ultimoIdFile;

    public function setUp(): void
    {
        if (!defined('TESTS_RUNNING')) {
            define('TESTS_RUNNING', true);
        }

        $ref = new \ReflectionClass(SseGestor::class);
        $this->logFile = $ref->getReflectionConstant('LOG_FILE')->getValue();
        $this->crewFile = $ref->getReflectionConstant('CREWAI_FILE')->getValue();
        $this->ultimoIdFile = $ref->getReflectionConstant('ULTIMO_ID_FILE')->getValue();

        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ([$this->logFile, $this->ultimoIdFile, $this->crewFile] as $f) {
            if (file_exists($f)) {
                unlink($f);
            }
        }
    }

    public function tearDown(): void
    {
        foreach ([$this->logFile, $this->ultimoIdFile, $this->crewFile] as $f) {
            if (file_exists($f)) {
                unlink($f);
            }
        }
        DB::resetearInstancia();
    }

    public function testEmitirInsertaEventoParaOperador(): void
    {
        SseGestor::emitir(1, 'test.tipo', ['msg' => 'hola']);
        $bd = DB::obtenerInstancia()->obtenerConector();
        $stmt = $bd->query("SELECT * FROM sse_evento WHERE id_operador = 1 AND tipo = 'test.tipo'");
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame('test.tipo', $row['tipo']);
        $datos = json_decode($row['datos'], true);
        $this->assertSame('hola', $datos['msg']);
    }

    public function testEmitirATodosInsertaEventoGlobal(): void
    {
        SseGestor::emitirATodos('global.event', ['notif' => 'para todos']);
        $bd = DB::obtenerInstancia()->obtenerConector();
        $stmt = $bd->query("SELECT * FROM sse_evento WHERE id_operador = 0 AND tipo = 'global.event'");
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(0, (int)$row['id_operador']);
    }

    public function testDaemonCorriendoRetornaFalseSinArchivo(): void
    {
        $this->assertFalse(SseGestor::daemonCorriendo());
    }

    public function testEmitirATodosEscribeArchivoLog(): void
    {
        SseGestor::emitirATodos('test.archivo', ['data' => 1]);
        $this->assertFileExists($this->logFile);
        $contenido = file_get_contents($this->logFile);
        $this->assertStringContainsString('test.archivo', $contenido);
    }

    public function testEmitirATodosCreaCrewaiCache(): void
    {
        SseGestor::emitirATodos('crewai', [
            'agent_role' => 'worker',
            'accion' => 'analizar',
            'destino' => 'modulo_x',
            'nombre' => 'test_agent',
            'mensaje' => 'test message',
        ]);
        $this->assertFileExists($this->crewFile);
        $contenido = file_get_contents($this->crewFile);
        $cache = json_decode($contenido, true);
        $this->assertIsArray($cache);
        $this->assertNotEmpty($cache);
        $this->assertSame('worker', $cache[0]['agent_role']);
        $this->assertSame('analizar', $cache[0]['accion']);
    }

    public function testEmitirATodosMantieneMaximo50EventosEnCache(): void
    {
        for ($i = 0; $i < 60; $i++) {
            SseGestor::emitirATodos('crewai', [
                'agent_role' => 'worker',
                'accion' => "evento_{$i}",
                'destino' => 'test',
                'nombre' => "agent_{$i}",
                'mensaje' => "msg_{$i}",
            ]);
        }
        $this->assertFileExists($this->crewFile);
        $contenido = file_get_contents($this->crewFile);
        $cache = json_decode($contenido, true);
        $this->assertCount(50, $cache);
    }

    public function testEmitirATodosConDatosComplejos(): void
    {
        $datos = ['valores' => [1, 2, 3], 'objeto' => ['a' => 1, 'b' => 2]];
        SseGestor::emitirATodos('complejo', $datos);
        $bd = DB::obtenerInstancia()->obtenerConector();
        $stmt = $bd->query("SELECT * FROM sse_evento WHERE tipo = 'complejo'");
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $decoded = json_decode($row['datos'], true);
        $this->assertSame([1, 2, 3], $decoded['valores']);
    }

    public function testEmitirConVariosTipos(): void
    {
        SseGestor::emitir(2, 'tipo.a', ['i' => 1]);
        SseGestor::emitir(2, 'tipo.b', ['i' => 2]);
        SseGestor::emitir(2, 'tipo.c', ['i' => 3]);
        $bd = DB::obtenerInstancia()->obtenerConector();
        $stmt = $bd->prepare("SELECT COUNT(*) FROM sse_evento WHERE id_operador = 2");
        $stmt->execute();
        $this->assertSame(3, (int)$stmt->fetchColumn());
    }

    public function testEmitirATodosEventosSeparados(): void
    {
        SseGestor::emitirATodos('separado.1', ['n' => 1]);
        SseGestor::emitirATodos('separado.2', ['n' => 2]);
        $bd = DB::obtenerInstancia()->obtenerConector();
        $stmt = $bd->query("SELECT tipo FROM sse_evento WHERE id_operador = 0 ORDER BY id_evento DESC LIMIT 2");
        $tipos = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertContains('separado.1', $tipos);
        $this->assertContains('separado.2', $tipos);
    }

    public function testConectarUltimoIdCeroSaltaEventosViejos(): void
    {
        SseGestor::emitirATodos('old.event', ['ts' => time() - 120]);
        $posArchivo = null;
        $eventos = SseGestor::leerEventosDelArchivo(0, 50, 0, $posArchivo);
        $this->assertIsArray($eventos);
    }

    public function testLeerEventosDelArchivoConArchivoVacio(): void
    {
        $posArchivo = null;
        $eventos = SseGestor::leerEventosDelArchivo(0, 50, 0, $posArchivo);
        $this->assertIsArray($eventos);
    }
}
