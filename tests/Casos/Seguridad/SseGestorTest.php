<?php
use PHPUnit\Framework\TestCase;

class SseGestorTest extends TestCase {
    private string $sseDir;
    private string $logFile;
    private string $ultimoIdFile;

    protected function setUp(): void {
        $this->sseDir = DIRECTORIO_RAIZ . '/storage/sse';
        $this->logFile = $this->sseDir . '/eventos.log';
        $this->ultimoIdFile = $this->sseDir . '/_ultimo_id';
        @mkdir($this->sseDir, 0755, true);
    }

    protected function tearDown(): void {
        @unlink($this->logFile);
        @unlink($this->ultimoIdFile);
        $this->limpiarEventosSSE();
        ConexionBaseDatos::resetearInstancia();
    }

    private function limpiarEventosSSE(): void {
        try {
            $pdo = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $pdo->exec("DELETE FROM sse_evento");
        } catch (\Throwable $e) {
        }
    }

    // ─── daemonCorriendo ───

    public function testDaemonCorriendoSinArchivoRetornaFalse(): void {
        $this->assertFalse(SseGestor::daemonCorriendo());
    }

    public function testDaemonCorriendoConArchivoRecienteRetornaTrue(): void {
        file_put_contents($this->ultimoIdFile, '42');
        $r = SseGestor::daemonCorriendo();
        $this->assertTrue($r);
    }

    // ─── emitir ───

    public function testEmitirInsertaEnBaseDeDatos(): void {
        SseGestor::emitir(1, 'test_tipo', ['msg' => 'hola']);
        $pdo = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $stmt = $pdo->query("SELECT COUNT(*) FROM sse_evento WHERE tipo = 'test_tipo'");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    public function testEmitirATodosInsertaConIdCero(): void {
        SseGestor::emitirATodos('test_broadcast', ['msg' => 'para todos']);
        $pdo = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $stmt = $pdo->query("SELECT id_operador FROM sse_evento WHERE tipo = 'test_broadcast'");
        $this->assertEquals(0, (int)$stmt->fetchColumn());
    }

    // ─── leerEventosDelArchivo ───

    public function testLeerEventosArchivoVacioRetornaArrayVacio(): void {
        file_put_contents($this->logFile, '');
        $eventos = SseGestor::leerEventosDelArchivo(1, 10);
        $this->assertIsArray($eventos);
        $this->assertEmpty($eventos);
    }

    public function testLeerEventosFiltraPorOperador(): void {
        $lineas = [
            json_encode(['id' => 1, 'id_operador' => 0, 'tipo' => 'broadcast', 'datos' => '{}', 'ts' => time()]),
            json_encode(['id' => 2, 'id_operador' => 1, 'tipo' => 'privado', 'datos' => '{}', 'ts' => time()]),
            json_encode(['id' => 3, 'id_operador' => 2, 'tipo' => 'privado', 'datos' => '{}', 'ts' => time()]),
        ];
        file_put_contents($this->logFile, implode("\n", $lineas) . "\n");

        $eventos = SseGestor::leerEventosDelArchivo(1, 10);
        $this->assertCount(2, $eventos);
        $tipos = array_column($eventos, 'tipo');
        $this->assertContains('broadcast', $tipos);
        $this->assertContains('privado', $tipos);
    }

    public function testLeerEventosRespetaLimite(): void {
        $lineas = [];
        for ($i = 0; $i < 10; $i++) {
            $lineas[] = json_encode(['id' => $i, 'id_operador' => 0, 'tipo' => 'ev', 'datos' => '{}', 'ts' => time()]);
        }
        file_put_contents($this->logFile, implode("\n", $lineas) . "\n");

        $eventos = SseGestor::leerEventosDelArchivo(0, 3);
        $this->assertCount(3, $eventos);
    }

    public function testLeerEventosIgnoraLineasInvalidas(): void {
        $lineas = [
            'esto no es json',
            json_encode(['id' => 1, 'id_operador' => 0, 'tipo' => 'valido', 'datos' => '{}', 'ts' => time()]),
            '',
            '{"id": 2, id_operador: 0, tipo: "mal"}',
        ];
        file_put_contents($this->logFile, implode("\n", $lineas) . "\n");

        $eventos = SseGestor::leerEventosDelArchivo(0, 10);
        $this->assertCount(1, $eventos);
        $this->assertEquals('valido', $eventos[0]['tipo']);
    }

    // ─── Ciclo completo: emitir + daemon escribe archivo ───

    public function testCicloCompletoDaemonProcesaEventos(): void {
        SseGestor::emitirATodos('test_ciclo', ['msg' => 'evento 1']);
        SseGestor::emitirATodos('test_ciclo', ['msg' => 'evento 2']);

        $pdo = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $stmt = $pdo->query("SELECT MIN(id_evento) as ultimo FROM sse_evento WHERE tipo = 'test_ciclo'");
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $ultimoId = (int)$fila['ultimo'];

        $stmt = $pdo->prepare("
            SELECT id_evento, id_operador, tipo, datos
            FROM sse_evento
            WHERE tipo = 'test_ciclo'
            ORDER BY id_evento ASC
        ");
        $stmt->execute();
        $eventosDb = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $eventosDb);

        $lineas = [];
        $maxId = 0;
        foreach ($eventosDb as $ev) {
            $eid = (int)$ev['id_evento'];
            if ($eid > $maxId) $maxId = $eid;
            $lineas[] = json_encode([
                'id' => $eid,
                'id_operador' => (int)$ev['id_operador'],
                'tipo' => $ev['tipo'],
                'datos' => $ev['datos'],
                'ts' => time(),
            ], JSON_UNESCAPED_UNICODE);
        }
        file_put_contents($this->logFile, implode("\n", $lineas) . "\n");
        file_put_contents($this->ultimoIdFile, (string)$maxId);

        $eliminados = $pdo->prepare("DELETE FROM sse_evento WHERE id_evento <= :ultimo");
        $eliminados->execute([':ultimo' => $maxId]);

        $restantes = $pdo->query("SELECT COUNT(*) FROM sse_evento WHERE tipo = 'test_ciclo'")->fetchColumn();
        $this->assertEquals(0, (int)$restantes);

        $eventosArchivo = SseGestor::leerEventosDelArchivo(0, 10);
        $this->assertCount(2, $eventosArchivo);
        $this->assertEquals('test_ciclo', $eventosArchivo[0]['tipo']);
    }
}
