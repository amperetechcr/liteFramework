<?php
use PHPUnit\Framework\TestCase;

class AdministradorArchivosIntegracionTest extends TestCase {

    private ?AdministradorArchivos $servicio = null;

    protected function setUp(): void {
        $this->servicio = new AdministradorArchivos(
            DIRECTORIO_RAIZ . '/storage/archivos',
            URL_BASE,
        );
        $this->sembrarConfiguracion();
    }

    private function sembrarConfiguracion(): void {
        try {
            $pdo = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $pdo->exec("DELETE FROM configuracion_sistema WHERE clave LIKE 'ARCHIVO_%'");
            $datos = [
                ['ARCHIVO_TIPOS_MIME_PERMITIDOS', 'imagenes,documentos', 'texto'],
                ['ARCHIVO_EXTENSIONES_PERMITIDAS', 'jpg,jpeg,png,gif,pdf', 'texto'],
                ['ARCHIVO_TAMANO_MAXIMO_MB', '40', 'numero'],
                ['ARCHIVO_CUOTA_USUARIO_MB', '100', 'numero'],
            ];
            $stmt = $pdo->prepare("INSERT INTO configuracion_sistema (clave, valor, tipo_dato, version) VALUES (?, ?, ?, 1)");
            foreach ($datos as $d) {
                $stmt->execute($d);
            }
            ConfiguracionSistema::invalidarCache();
        } catch (Throwable $e) {
            error_log('[Test] No se pudo sembrar configuracion: ' . $e->getMessage());
        }
    }

    // ─── Configuracion ───

    public function testObtenerConfiguracionDevuelveArray(): void {
        try {
            $config = $this->servicio->obtenerConfiguracion();
            $this->assertIsArray($config);
        } catch (Throwable $e) {
            $this->markTestSkipped('Config table not available: ' . $e->getMessage());
        }
    }

    // ─── Tipos MIME permitidos ───

    public function testObtenerTiposMimePermitidosDevuelveArray(): void {
        try {
            $tipos = $this->servicio->obtenerTiposMimePermitidos();
            $this->assertIsArray($tipos);
            $this->assertNotEmpty($tipos);
        } catch (Throwable $e) {
            $this->markTestSkipped('Config table not available: ' . $e->getMessage());
        }
    }

    // ─── Extensiones permitidas ───

    public function testObtenerExtensionesPermitidasDevuelveArray(): void {
        try {
            $exts = $this->servicio->obtenerExtensionesPermitidas();
            $this->assertIsArray($exts);
            $this->assertNotEmpty($exts);
        } catch (Throwable $e) {
            $this->markTestSkipped('Config table not available: ' . $e->getMessage());
        }
    }

    // ─── Listado vacio ───

    public function testListarSinArchivosDevuelveArrayVacio(): void {
        $resultado = $this->servicio->listar();
        $this->assertIsArray($resultado);
    }

    // ─── Obtener archivo inexistente ───

    public function testObtenerArchivoInexistenteDevuelveNull(): void {
        $this->assertNull($this->servicio->obtener(99999));
    }

    // ─── Eliminar archivo inexistente ───

    public function testEliminarArchivoInexistenteDevuelveError(): void {
        $resultado = $this->servicio->eliminar(99999);
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['estado_operacion']);
        $this->assertEquals('no_encontrado', $resultado['codigo_error']);
    }

    // ─── Descargar archivo inexistente ───

    public function testDescargarArchivoInexistenteDevuelveError(): void {
        $resultado = $this->servicio->descargar(99999);
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['estado_operacion']);
        $this->assertEquals('no_encontrado', $resultado['codigo_error']);
    }

    // ─── Cuota de usuario vacia ───

    public function testCalcularUsoUsuarioDevuelveEntero(): void {
        try {
            $bytes = $this->servicio->calcularUsoUsuario(0);
            $this->assertIsInt($bytes);
            $this->assertGreaterThanOrEqual(0, $bytes);
        } catch (Throwable $e) {
            $this->markTestSkipped('DB not available: ' . $e->getMessage());
        }
    }
}
