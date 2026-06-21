<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Errores;

use LiteFramework\Servicios\ContextoError;

class ContextoErrorTest extends \TestBase
{
    private array $serverBackup;

    public function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test/contexto';
    }

    public function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        parent::tearDown();
    }

    public function testCapturarConCamobosCompletos(): void
    {
        $ctx = ContextoError::capturar('error_test', 'Mensaje de prueba', '/test/archivo.php', 42);

        $this->assertInstanceOf(ContextoError::class, $ctx);
        $this->assertSame('error_test', $ctx->codigo);
        $this->assertSame('Mensaje de prueba', $ctx->mensaje);
        $this->assertSame('/test/archivo.php', $ctx->archivo);
        $this->assertSame(42, $ctx->linea);
        $this->assertSame('GET', $ctx->metodo);
        $this->assertSame('/test/contexto', $ctx->ruta);
    }

    public function testCapturarConDatosExtra(): void
    {
        $extra = ['tipo' => 'ERROR_PHP', 'trace_id' => 'abc123'];
        $ctx = ContextoError::capturar('db_error', 'DB fail', '/db.php', 10, $extra);

        $this->assertSame('db_error', $ctx->codigo);
        $this->assertSame('DB fail', $ctx->mensaje);
        $this->assertArrayHasKey('tipo', $ctx->datosExtra);
        $this->assertSame('ERROR_PHP', $ctx->datosExtra['tipo']);
        $this->assertArrayHasKey('trace_id', $ctx->datosExtra);
        $this->assertSame('abc123', $ctx->datosExtra['trace_id']);
    }

    public function testServerKeysFaltantes(): void
    {
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

        $ctx = ContextoError::capturar('cli_run', 'CLI error', '/cli.php', 5);

        $this->assertSame('CLI', $ctx->metodo);
        $this->assertSame('CLI', $ctx->ruta);
    }

    public function testSesionNoIniciadaNoTiraError(): void
    {
        $ctx = ContextoError::capturar('no_session', 'No session', '/test.php', 1);
        $this->assertNull($ctx->idOperador);
        $this->assertNull($ctx->rolOperador);
    }

    public function testDiagnosticoSistemaContienePhpVersion(): void
    {
        $ctx = ContextoError::capturar('sys', 'sys check', '/test.php', 1);
        $this->assertArrayHasKey('php_version', $ctx->diagnosticoSistema);
        $this->assertSame(PHP_VERSION, $ctx->diagnosticoSistema['php_version']);
    }

    public function testDiagnosticoSistemaContieneExtensiones(): void
    {
        $ctx = ContextoError::capturar('ext', 'ext check', '/test.php', 1);
        $this->assertArrayHasKey('extensiones', $ctx->diagnosticoSistema);
        $this->assertArrayHasKey('pdo', $ctx->diagnosticoSistema['extensiones']);
        $this->assertTrue($ctx->diagnosticoSistema['extensiones']['pdo']);
    }

    public function testDiagnosticoSistemaContieneMemoria(): void
    {
        $ctx = ContextoError::capturar('mem', 'mem check', '/test.php', 1);
        $this->assertArrayHasKey('memoria_usada_mb', $ctx->diagnosticoSistema);
        $this->assertIsFloat($ctx->diagnosticoSistema['memoria_usada_mb']);
    }

    public function testTraceIdPresente(): void
    {
        $ctx = ContextoError::capturar('trace', 'trace test', '/test.php', 1);
        $this->assertNotEmpty($ctx->traceId);
    }

    public function testEstadoMySQLNoTiraError(): void
    {
        $ctx = ContextoError::capturar('mysql_state', 'mysql', '/test.php', 1);
        $this->assertNotEmpty($ctx->estadoMySQL);
    }

    public function testModuloDefaultSistema(): void
    {
        $ctx = ContextoError::capturar('mod', 'mod test', '/test.php', 1);
        $this->assertSame('Sistema', $ctx->modulo);
    }

    public function testDiagnosticoSistemaContieneDiscoLibre(): void
    {
        $ctx = ContextoError::capturar('disk', 'disk check', '/test.php', 1);
        $this->assertArrayHasKey('disco_libre_mb', $ctx->diagnosticoSistema);
    }
}
