<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Seguridad;

use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Config\ConexionBaseDatos as DB;

class RegistroAuditoriaTest extends \TestBase
{
    private string $archivoLog;
    private array $requestBackup = [];

    public function setUp(): void
    {
        if (!defined('TESTS_RUNNING')) {
            define('TESTS_RUNNING', true);
        }
        $this->requestBackup = $_REQUEST;
        $_REQUEST = [];

        $dirLogs = __DIR__ . '/../../../storage/logs';
        if (!is_dir($dirLogs)) {
            mkdir($dirLogs, 0755, true);
        }
        $this->archivoLog = $dirLogs . '/trazabilidad.log';
        if (file_exists($this->archivoLog)) {
            unlink($this->archivoLog);
        }

        $ref = new \ReflectionClass(RegistroAuditoria::class);
        $propBitacora = $ref->getProperty('bitacoraHabilitada');
        $propBitacora->setAccessible(true);
        $propBitacora->setValue(null, true);

        $propArchivo = $ref->getProperty('archivoRuta');
        $propArchivo->setAccessible(true);
        $propArchivo->setValue(null, null);

        RegistroAuditoria::habilitarArchivo($this->archivoLog);

        DB::obtenerInstancia();
    }

    public function tearDown(): void
    {
        $_REQUEST = $this->requestBackup;
        if (file_exists($this->archivoLog)) {
            unlink($this->archivoLog);
        }
        DB::resetearInstancia();
    }

    public function testInfoEscribeEnBitacora(): void
    {
        RegistroAuditoria::info('Pruebas', 'test_info', ['msg' => 'info test']);
        $eventos = RegistroAuditoria::consultarEventos(null, 'Pruebas');
        $this->assertNotEmpty($eventos);
        $this->assertSame('Pruebas', $eventos[0]['modulo']);
        $this->assertSame('test_info', $eventos[0]['accion_realizada']);
    }

    public function testAdvertenciaEscribeEnBitacora(): void
    {
        RegistroAuditoria::advertencia('Pruebas', 'test_advertencia', ['msg' => 'warn test']);
        $eventos = RegistroAuditoria::consultarEventos(null, 'Pruebas');
        $this->assertNotEmpty($eventos);
        $this->assertSame('test_advertencia', $eventos[0]['accion_realizada']);
    }

    public function testErrorEscribeEnBitacora(): void
    {
        RegistroAuditoria::error('Pruebas', 'test_error', ['msg' => 'error test']);
        $eventos = RegistroAuditoria::consultarEventos(null, 'Pruebas');
        $this->assertNotEmpty($eventos);
        $this->assertSame('test_error', $eventos[0]['accion_realizada']);
    }

    public function testSeguridadEscribeEnBitacora(): void
    {
        RegistroAuditoria::seguridad('test_seguridad', ['msg' => 'security test']);
        $eventos = RegistroAuditoria::consultarEventos(null, 'Seguridad');
        $this->assertNotEmpty($eventos);
        $this->assertSame('test_seguridad', $eventos[0]['accion_realizada']);
    }

    public function testAuditoriaEscribeEnBitacora(): void
    {
        RegistroAuditoria::auditoria('Pruebas', 'test_auditoria', ['msg' => 'audit test']);
        $eventos = RegistroAuditoria::consultarEventos(null, 'Pruebas');
        $this->assertNotEmpty($eventos);
        $this->assertSame('test_auditoria', $eventos[0]['accion_realizada']);
    }

    public function testTodosLosNivelesDegradanAInfoEnTest(): void
    {
        RegistroAuditoria::error('Pruebas', 'test_deg_error', ['original' => 'ERROR']);
        RegistroAuditoria::seguridad('test_deg_seg', ['original' => 'SEGURIDAD']);
        RegistroAuditoria::advertencia('Pruebas', 'test_deg_warn', ['original' => 'ADVERTENCIA']);

        $eventos = RegistroAuditoria::consultarEventos(null, 'Pruebas', 10, 0, null, null, 'INFO');
        $this->assertGreaterThanOrEqual(2, count($eventos));
        foreach ($eventos as $ev) {
            $detalles = json_decode($ev['detalles_json'] ?? '{}', true);
            $this->assertSame('INFO', $detalles['nivel'] ?? '');
        }
    }

    public function testArchivoLogSeEscribe(): void
    {
        RegistroAuditoria::info('Pruebas', 'test_archivo', ['msg' => 'file write']);
        clearstatcache();
        $this->assertFileExists($this->archivoLog);
        $contenido = file_get_contents($this->archivoLog);
        $this->assertStringContainsString('test_archivo', $contenido);
        $this->assertStringContainsString('[INFO]', $contenido);
    }

    public function testDeshabilitarBitacoraEvitaEscritura(): void
    {
        RegistroAuditoria::info('Pruebas', 'antes_deshabilitar');
        $ref = new \ReflectionClass(RegistroAuditoria::class);
        $prop = $ref->getProperty('bitacoraHabilitada');
        $prop->setAccessible(true);
        $prop->setValue(null, false);

        RegistroAuditoria::info('Pruebas', 'despues_deshabilitar');
        $eventos = RegistroAuditoria::consultarEventos(null, 'Pruebas');
        $this->assertNotEmpty($eventos);
        foreach ($eventos as $ev) {
            $this->assertNotSame('despues_deshabilitar', $ev['accion_realizada']);
        }
    }

    public function testPasswordFiltradaEnRequest(): void
    {
        $_REQUEST = [
            'usuario' => 'admin',
            'clave' => 'supersecret123',
            'clave_acceso' => 'password123',
            'clave_nueva' => 'newpass456',
            'email' => 'test@example.com',
        ];
        RegistroAuditoria::info('Pruebas', 'test_passwords');
        $eventos = RegistroAuditoria::consultarEventos(null, 'Pruebas');
        $this->assertNotEmpty($eventos);
        $detalles = json_decode($eventos[0]['detalles_json'] ?? '{}', true);
        $params = $detalles['parametros_solicitud'] ?? '';
        $this->assertStringNotContainsString('supersecret123', $params);
        $this->assertStringNotContainsString('password123', $params);
        $this->assertStringNotContainsString('newpass456', $params);
        $this->assertStringContainsString('admin', $params);
        $this->assertStringContainsString('test@example.com', $params);
    }

    public function testConsultarEventosConFiltroModulo(): void
    {
        RegistroAuditoria::info('ModuloA', 'accion_a');
        RegistroAuditoria::info('ModuloB', 'accion_b');
        $eventos = RegistroAuditoria::consultarEventos(null, 'ModuloA');
        $this->assertNotEmpty($eventos);
        foreach ($eventos as $ev) {
            $this->assertSame('ModuloA', $ev['modulo']);
        }
    }

    public function testConsultarEventosConRangoFechas(): void
    {
        $desde = date('Y-m-d', strtotime('-1 day'));
        $hasta = date('Y-m-d', strtotime('+1 day'));
        RegistroAuditoria::info('Pruebas', 'test_fechas');
        $eventos = RegistroAuditoria::consultarEventos(null, null, 50, 0, $desde, $hasta);
        $this->assertNotEmpty($eventos);
    }

    public function testConsultarEventosConPaginacion(): void
    {
        for ($i = 0; $i < 5; $i++) {
            RegistroAuditoria::info('PagTest', "accion_{$i}");
        }
        $pagina1 = RegistroAuditoria::consultarEventos(null, 'PagTest', 2, 0);
        $this->assertCount(2, $pagina1);
        $pagina2 = RegistroAuditoria::consultarEventos(null, 'PagTest', 2, 2);
        $this->assertCount(2, $pagina2);
    }

    public function testObtenerModulosRetornaArray(): void
    {
        RegistroAuditoria::info('ModuloX', 'accion_x');
        $modulos = RegistroAuditoria::obtenerModulos();
        $this->assertIsArray($modulos);
        $this->assertContains('ModuloX', $modulos);
    }

    public function testExportarJson(): void
    {
        RegistroAuditoria::info('ExportTest', 'test_export');
        $json = RegistroAuditoria::exportarEventos('json', null, 'ExportTest');
        $this->assertJson($json);
        $datos = json_decode($json, true);
        $this->assertArrayHasKey('eventos', $datos);
        $this->assertArrayHasKey('total', $datos);
    }

    public function testExportarCsv(): void
    {
        RegistroAuditoria::info('CsvTest', 'test_csv');
        $csv = RegistroAuditoria::exportarEventos('csv', null, 'CsvTest');
        $this->assertStringStartsWith('ID;', $csv);
        $this->assertStringContainsString('test_csv', $csv);
    }

    public function testContarEventos(): void
    {
        RegistroAuditoria::info('CountTest', 'evt_1');
        $total = RegistroAuditoria::contarEventos(null, 'CountTest');
        $this->assertGreaterThanOrEqual(1, $total);
    }

    public function testObtenerNiveles(): void
    {
        $niveles = RegistroAuditoria::obtenerNiveles();
        $this->assertIsArray($niveles);
        $this->assertCount(5, $niveles);
        $this->assertContains('INFO', $niveles);
        $this->assertContains('ERROR', $niveles);
        $this->assertContains('SEGURIDAD', $niveles);
    }

    public function testObtenerResumen(): void
    {
        $resumen = RegistroAuditoria::obtenerResumen();
        $this->assertIsArray($resumen);
        $this->assertArrayHasKey('total', $resumen);
        $this->assertArrayHasKey('ultima_semana', $resumen);
        $this->assertArrayHasKey('hoy', $resumen);
        $this->assertArrayHasKey('por_modulo', $resumen);
    }

    public function testDetalleConDiagnostico(): void
    {
        $detalle = ['_diagnostico' => 'test_diag', 'code' => 123];
        RegistroAuditoria::info('DiagTest', 'test_diag', $detalle);
        $eventos = RegistroAuditoria::consultarEventos(null, 'DiagTest');
        $this->assertNotEmpty($eventos);
        $jsonDet = json_decode($eventos[0]['detalles_json'] ?? '{}', true);
        $this->assertSame('test_diag', $jsonDet['detalle']['_diagnostico']);
        $this->assertSame(123, $jsonDet['detalle']['code']);
    }

    public function testArchivoLogContieneNivel(): void
    {
        RegistroAuditoria::info('ArchTest', 'nivel_check', ['x' => 1]);
        $this->assertFileExists($this->archivoLog);
        $contenido = file_get_contents($this->archivoLog);
        $this->assertStringContainsString('[INFO]', $contenido);
        $this->assertStringContainsString('[ArchTest]', $contenido);
        $this->assertStringContainsString('nivel_check', $contenido);
    }

    public function testMultiplesEventosMismoModulo(): void
    {
        RegistroAuditoria::info('Multi', 'evt_a');
        RegistroAuditoria::info('Multi', 'evt_b');
        RegistroAuditoria::info('Multi', 'evt_c');
        $eventos = RegistroAuditoria::consultarEventos(null, 'Multi');
        $this->assertCount(3, $eventos);
    }

    public function testEventoSinDetalle(): void
    {
        RegistroAuditoria::info('NoDet', 'sin_detalle');
        $eventos = RegistroAuditoria::consultarEventos(null, 'NoDet');
        $this->assertNotEmpty($eventos);
        $detallesJson = $eventos[0]['detalles_json'] ?? '';
        $contexto = json_decode($detallesJson, true);
        $this->assertArrayNotHasKey('detalle', $contexto);
    }

    public function testArchivoLogConTraceId(): void
    {
        RegistroAuditoria::info('TraceTest', 'check_trace');
        $this->assertFileExists($this->archivoLog);
        $contenido = file_get_contents($this->archivoLog);
        $this->assertStringContainsString('trace=', $contenido);
    }

    public function testConsultarPorNivel(): void
    {
        RegistroAuditoria::info('NivelTest', 'solo_info');
        $eventos = RegistroAuditoria::consultarEventos(null, 'NivelTest', 50, 0, null, null, 'INFO');
        $this->assertNotEmpty($eventos);
    }

    public function testLimpiarEventosAntiguos(): void
    {
        $eliminados = RegistroAuditoria::limpiarEventosAntiguos(0);
        $this->assertIsInt($eliminados);
    }

    public function testLimpiarArchivo(): void
    {
        RegistroAuditoria::info('CleanTest', 'before_clean');
        $this->assertFileExists($this->archivoLog);
        RegistroAuditoria::limpiarArchivo();
        $this->assertFileDoesNotExist($this->archivoLog);
    }

    public function testDetalleConArrayAnidado(): void
    {
        $detalle = ['user' => ['id' => 42, 'roles' => ['admin', 'editor']], '_diagnostico' => 'nested'];
        RegistroAuditoria::info('Nested', 'test_nested', $detalle);
        $eventos = RegistroAuditoria::consultarEventos(null, 'Nested');
        $this->assertNotEmpty($eventos);
        $jsonDet = json_decode($eventos[0]['detalles_json'] ?? '{}', true);
        $this->assertSame(42, $jsonDet['detalle']['user']['id']);
        $this->assertSame('nested', $jsonDet['detalle']['_diagnostico']);
    }

    public function testArchivoLogConModuloLargo(): void
    {
        $moduloLargo = str_repeat('A', 100);
        RegistroAuditoria::info($moduloLargo, 'modulo_largo');
        $this->assertFileExists($this->archivoLog);
        $contenido = file_get_contents($this->archivoLog);
        $this->assertStringContainsString($moduloLargo, $contenido);
    }

    public function testAdvertenciaEnArchivoLog(): void
    {
        RegistroAuditoria::advertencia('WarnFile', 'warn_log', ['level' => 'warning']);
        $this->assertFileExists($this->archivoLog);
        $contenido = file_get_contents($this->archivoLog);
        $this->assertStringContainsString('WarnFile', $contenido);
        $this->assertStringContainsString('warn_log', $contenido);
    }
}
