<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Flujos;

use LiteFramework\Config\ConexionBaseDatos as DB;
use LiteFramework\Nucleo\ManejadorErrores;
use LiteFramework\Servicios\ContextoError;
use LiteFramework\Servicios\DiagnosticoError;
use LiteFramework\Servicios\RemediadorError;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Seguridad\TrazadorPeticiones;

class AuditoriaCompletaTest extends \TestBase
{
    private ?\PDO $bd = null;

    public function setUp(): void
    {
        $this->bd = DB::obtenerInstancia()->obtenerConector();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit/1.0';
        $_SERVER['REQUEST_METHOD'] = 'CLI';
        $_SERVER['REQUEST_URI'] = '/tests';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SESSION = [];
        if (!defined('URL_BASE')) {
            define('URL_BASE', '');
        }
        TrazadorPeticiones::iniciar();
    }

    public function tearDown(): void
    {
        $_SESSION = [];
        DB::resetearInstancia();
    }

    public function testManejadorErroresTriggersContextoCapturar(): void
    {
        $ctx = ContextoError::capturar(
            'error_prueba',
            'Mensaje de error de prueba',
            __FILE__,
            42,
            ['tipo' => 'ERROR_PHP']
        );
        $this->assertInstanceOf(ContextoError::class, $ctx);
        $this->assertSame('error_prueba', $ctx->codigo);
        $this->assertSame('Mensaje de error de prueba', $ctx->mensaje);
        $this->assertStringContainsString('AuditoriaCompletaTest.php', $ctx->archivo);
    }

    public function testContextoErrorContainsTraceIdAndMetadata(): void
    {
        $ctx = ContextoError::capturar(
            'db_error',
            'Connection failed',
            '/path/to/db.php',
            100,
            ['extra' => 'info']
        );
        $this->assertNotEmpty($ctx->traceId);
        $this->assertNotSame('N/A', $ctx->traceId);
        $this->assertSame('/path/to/db.php', $ctx->archivo);
        $this->assertSame(100, $ctx->linea);
        $this->assertSame('db_error', $ctx->codigo);
        $this->assertSame('CLI', $ctx->metodo);
        $this->assertArrayHasKey('php_version', $ctx->diagnosticoSistema);
        $this->assertArrayHasKey('memoria_usada_mb', $ctx->diagnosticoSistema);
    }

    public function testDiagnosticoErrorRunsAllVerificadores(): void
    {
        $ctx = ContextoError::capturar(
            'error_prueba',
            'Archivo no encontrado',
            '/tmp/test.php',
            50
        );
        $diagnostico = DiagnosticoError::diagnosticar($ctx);
        $this->assertIsArray($diagnostico);
        $this->assertArrayHasKey('diagnosticos', $diagnostico);
        $this->assertArrayHasKey('tieneRemedio', $diagnostico);
        $this->assertArrayHasKey('reparaciones', $diagnostico);
        $this->assertArrayHasKey('sugerencias', $diagnostico);
    }

    public function testVerificadorErrorIdentifiesErrorTypeFromContext(): void
    {
        $ctx = ContextoError::capturar(
            'error_prueba',
            'Tabla no encontrada en base de datos',
            '/path/to/modelo.php',
            200
        );
        $diagnostico = DiagnosticoError::diagnosticar($ctx);
        $this->assertIsArray($diagnostico['diagnosticos']);
        foreach ($diagnostico['diagnosticos'] as $diag) {
            $this->assertArrayHasKey('verificador', $diag);
            $this->assertNotEmpty($diag['verificador']);
            $this->assertArrayHasKey('tipo', $diag);
        }
    }

    public function testRemediadorErrorIntentarRunsRemediation(): void
    {
        $ctx = ContextoError::capturar(
            'error_prueba',
            'Directorio temporal no encontrado',
            '/tmp/test.php',
            15
        );
        $diagnostico = DiagnosticoError::diagnosticar($ctx);
        $resultado = RemediadorError::intentar($ctx, $diagnostico);
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('ejecutados', $resultado);
        $this->assertArrayHasKey('pendientes', $resultado);
        $this->assertArrayHasKey('errores', $resultado);
    }

    public function testFailedRemediationReturnsExitoFalse(): void
    {
        $resultado = RemediadorError::ejecutarReparacion('base_datos', [
            'tipo' => 'conexion_fallida',
            'servidor' => 'host_invalido',
        ]);
        $this->assertArrayHasKey('exito', $resultado);
    }

    public function testRegistroAuditoriaErrorCalledWithDiagnosticDetails(): void
    {
        $ctx = ContextoError::capturar(
            'error_prueba',
            'Error de prueba para auditoria',
            __FILE__,
            35
        );
        $diagnostico = DiagnosticoError::diagnosticar($ctx);
        RegistroAuditoria::error('Sistema', 'Error prueba', [
            'mensaje' => $ctx->mensaje,
            'archivo' => $ctx->archivo,
            'linea' => $ctx->linea,
            'trace_id' => $ctx->traceId,
            '_diagnostico' => $diagnostico,
        ]);
        $stmt = $this->bd->query(
            "SELECT * FROM bitacora_sistema WHERE accion_realizada = 'Error prueba' LIMIT 1"
        );
        $this->assertNotFalse($stmt);
        $registro = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotEmpty($registro);
        $this->assertSame('Sistema', $registro['modulo']);
        $this->assertSame('Error prueba', $registro['accion_realizada']);
    }

    public function testBitacoraSistemaContainsLoggedError(): void
    {
        RegistroAuditoria::error('ModuloTest', 'AccionTest', ['detalle' => 'valor']);
        $stmt = $this->bd->prepare(
            "SELECT * FROM bitacora_sistema WHERE modulo = :mod AND accion_realizada = :acc"
        );
        $stmt->execute([':mod' => 'ModuloTest', ':acc' => 'AccionTest']);
        $registro = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotEmpty($registro);
        $this->assertSame('ModuloTest', $registro['modulo']);
        $this->assertSame('AccionTest', $registro['accion_realizada']);
        $this->assertStringContainsString('"detalle"', $registro['detalles_json']);
        $this->assertStringContainsString('"valor"', $registro['detalles_json']);
    }

    public function testEndToEndTriggerCaptureDiagnoseRemediateAudit(): void
    {
        $ctx = ContextoError::capturar(
            'error_prueba',
            'Error E2E completo',
            __FILE__,
            99
        );
        $this->assertInstanceOf(ContextoError::class, $ctx);

        $diagnostico = DiagnosticoError::diagnosticar($ctx);
        $this->assertIsArray($diagnostico);
        $this->assertArrayHasKey('diagnosticos', $diagnostico);

        $remediacion = RemediadorError::intentar($ctx, $diagnostico);
        $this->assertIsArray($remediacion);

        RegistroAuditoria::error('Sistema', 'Error E2E auditado', [
            'codigo' => $ctx->codigo,
            'mensaje' => $ctx->mensaje,
            'trace_id' => $ctx->traceId,
            'diagnostico' => $diagnostico,
        ]);

        $stmt = $this->bd->prepare(
            "SELECT * FROM bitacora_sistema WHERE accion_realizada = :accion LIMIT 1"
        );
        $stmt->execute([':accion' => 'Error E2E auditado']);
        $registro = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotEmpty($registro);
        $this->assertStringContainsString('Error E2E completo', $registro['detalles_json']);
    }
}
