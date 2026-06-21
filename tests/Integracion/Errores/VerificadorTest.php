<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Errores;

use LiteFramework\Servicios\ContextoError;
use LiteFramework\Servicios\Verificadores\VerificadorBaseDatos;
use LiteFramework\Servicios\Verificadores\VerificadorArchivos;
use LiteFramework\Servicios\Verificadores\VerificadorSeguridad;
use LiteFramework\Servicios\Verificadores\VerificadorSistema;

class VerificadorTest extends \TestBase
{
    // ===================== VerificadorBaseDatos =====================

    public function testBaseDatosTablaFaltante(): void
    {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error', "Table 'lite.usuarios' doesn't exist", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('tabla_faltante', $diag['tipo']);
        $this->assertArrayHasKey('tabla', $diag);
        $this->assertArrayHasKey('bd', $diag);
    }

    public function testBaseDatosConexion(): void
    {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error', 'SQLSTATE[HY000] [2002] Connection refused', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('conexion', $diag['tipo']);
        $this->assertArrayHasKey('anfitrion', $diag);
    }

    public function testBaseDatosCredenciales(): void
    {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error', 'SQLSTATE[HY000] [1045] Access denied for user', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('credenciales', $diag['tipo']);
    }

    public function testBaseDatosBDNoExiste(): void
    {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error', "Unknown database 'test_db'", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('bd_no_existe', $diag['tipo']);
        $this->assertArrayHasKey('bd', $diag);
    }

    public function testBaseDatosColumnaFaltante(): void
    {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error', "Unknown column 'age' in 'field list'", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('columna_faltante', $diag['tipo']);
        $this->assertArrayHasKey('columna', $diag);
    }

    public function testBaseDatosDeadlock(): void
    {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error', 'Deadlock found when trying to get lock', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('deadlock', $diag['tipo']);
    }

    public function testBaseDatosSinCoincidencia(): void
    {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error', 'Some random MySQL error', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNull($diag);
    }

    public function testBaseDatosTieneRemedio(): void
    {
        $v = new VerificadorBaseDatos();
        $this->assertTrue($v->tieneRemedioAutomatico());
    }

    // ===================== VerificadorArchivos =====================

    public function testArchivosArchivoDemasiadoGrande(): void
    {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error', 'UPLOAD_ERR_INI_SIZE upload_max_filesize exceeded', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('archivo_demasiado_grande', $diag['tipo']);
        $this->assertArrayHasKey('limite_actual', $diag);
    }

    public function testArchivosTmpDirFaltante(): void
    {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error', 'UPLOAD_ERR_NO_TMP_DIR', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('tmp_dir_faltante', $diag['tipo']);
        $this->assertArrayHasKey('tmp_dir', $diag);
    }

    public function testArchivosDiscoSinEspacio(): void
    {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error', 'UPLOAD_ERR_CANT_WRITE', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('disco_sin_espacio', $diag['tipo']);
        $this->assertArrayHasKey('espacio_libre_mb', $diag);
    }

    public function testArchivosDirectorioNoCreable(): void
    {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error', "mkdir(): Permission denied", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('directorio_no_creable', $diag['tipo']);
        $this->assertArrayHasKey('ruta', $diag);
    }

    public function testArchivosPermisoEscritura(): void
    {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error', 'move_uploaded_file failed', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('permiso_escritura', $diag['tipo']);
    }

    public function testArchivosSinCoincidencia(): void
    {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error', 'Some file error', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNull($diag);
    }

    // ===================== VerificadorSeguridad =====================

    public function testSeguridadCsrfExpirado(): void
    {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('token_invalido', 'CSRF token mismatch', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('csrf_expirado', $diag['tipo']);
        $this->assertArrayHasKey('sesion_activa', $diag);
    }

    public function testSeguridadRateLimit(): void
    {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('bloqueo_temporal', 'Demasiados intentos. Espere 15 minutos.', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('rate_limit', $diag['tipo']);
        $this->assertArrayHasKey('minutos_restantes', $diag);
    }

    public function testSeguridadPermisoFaltante(): void
    {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('sin_permiso', "No tiene el permiso 'operador.crear'.", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('permiso_faltante', $diag['tipo']);
        $this->assertArrayHasKey('permiso', $diag);
    }

    public function testSeguridadSesionExpirada(): void
    {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('sesion_expirada', 'Su sesion ha expirado', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('sesion_expirada', $diag['tipo']);
    }

    public function testSeguridadSugerenciasCsrf(): void
    {
        $v = new VerificadorSeguridad();
        $sugs = $v->obtenerSugerencias(['tipo' => 'csrf_expirado', 'sesion_activa' => true]);
        $this->assertNotEmpty($sugs);
    }

    // ===================== VerificadorSistema =====================

    public function testSistemaMemoriaInsuficiente(): void
    {
        $v = new VerificadorSistema();
        $ctx = ContextoError::capturar('error', 'Allowed memory size of 134217728 bytes exhausted', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('memoria_insuficiente', $diag['tipo']);
        $this->assertArrayHasKey('limite_actual', $diag);
        $this->assertArrayHasKey('recomendado_mb', $diag);
    }

    public function testSistemaTiempoAgotado(): void
    {
        $v = new VerificadorSistema();
        $ctx = ContextoError::capturar('error', 'Maximum execution time of 30 seconds exceeded', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('tiempo_agotado', $diag['tipo']);
        $this->assertArrayHasKey('limite_actual', $diag);
    }

    public function testSistemaClaseFaltante(): void
    {
        $v = new VerificadorSistema();
        $ctx = ContextoError::capturar('error', "Class 'MiClase' not found", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('clase_faltante', $diag['tipo']);
        $this->assertArrayHasKey('clase', $diag);
    }

    public function testSistemaExtensionFaltante(): void
    {
        $v = new VerificadorSistema();
        $ctx = ContextoError::capturar('error', 'Call to undefined function imagecreate', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNotNull($diag);
        $this->assertSame('extension_faltante', $diag['tipo']);
        $this->assertArrayHasKey('funcion', $diag);
        $this->assertArrayHasKey('extension', $diag);
    }

    public function testSistemaSinCoincidencia(): void
    {
        $v = new VerificadorSistema();
        $ctx = ContextoError::capturar('error', 'Unknown system error', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);

        $this->assertNull($diag);
    }

    public function testSistemaNoTieneRemedioAutomatico(): void
    {
        $v = new VerificadorSistema();
        $this->assertFalse($v->tieneRemedioAutomatico());
    }

    // ===================== Tipo y sugerencias =====================

    public function testVerificadorBaseDatosTipo(): void
    {
        $v = new VerificadorBaseDatos();
        $this->assertSame('base_datos', $v->tipo());
    }

    public function testVerificadorArchivosTipo(): void
    {
        $v = new VerificadorArchivos();
        $this->assertSame('archivos', $v->tipo());
    }

    public function testVerificadorSeguridadTipo(): void
    {
        $v = new VerificadorSeguridad();
        $this->assertSame('seguridad', $v->tipo());
    }

    public function testVerificadorSistemaTipo(): void
    {
        $v = new VerificadorSistema();
        $this->assertSame('sistema', $v->tipo());
    }

    public function testSugerenciasBaseDatosTablaFaltante(): void
    {
        $v = new VerificadorBaseDatos();
        $sugs = $v->obtenerSugerencias(['tipo' => 'tabla_faltante', 'tabla' => 'usuarios']);
        $this->assertNotEmpty($sugs);
    }

    public function testSugerenciasArchivosArchivoGrande(): void
    {
        $v = new VerificadorArchivos();
        $sugs = $v->obtenerSugerencias(['tipo' => 'archivo_demasiado_grande', 'limite_actual' => '40M']);
        $this->assertNotEmpty($sugs);
    }

    public function testSugerenciasSeguridadRateLimit(): void
    {
        $v = new VerificadorSeguridad();
        $sugs = $v->obtenerSugerencias(['tipo' => 'rate_limit', 'minutos_restantes' => 15]);
        $this->assertNotEmpty($sugs);
    }

    public function testSugerenciasSistemaMemoria(): void
    {
        $v = new VerificadorSistema();
        $sugs = $v->obtenerSugerencias(['tipo' => 'memoria_insuficiente', 'limite_actual' => '128M', 'recomendado_mb' => 256]);
        $this->assertNotEmpty($sugs);
    }
}
