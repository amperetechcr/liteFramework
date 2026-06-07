<?php
use PHPUnit\Framework\TestCase;
use LiteFramework\Servicios\ContextoError;
use LiteFramework\Servicios\DiagnosticoError;
use LiteFramework\Servicios\Verificadores\VerificadorBaseDatos;
use LiteFramework\Servicios\Verificadores\VerificadorArchivos;
use LiteFramework\Servicios\Verificadores\VerificadorSeguridad;
use LiteFramework\Servicios\Verificadores\VerificadorSistema;

class DiagnosticoErrorTest extends TestCase {

    public function testContextoErrorCapturaDatos(): void {
        $ctx = ContextoError::capturar('test_error', 'Mensaje de prueba', __FILE__, __LINE__);
        $this->assertEquals('test_error', $ctx->codigo);
        $this->assertEquals('Mensaje de prueba', $ctx->mensaje);
        $this->assertEquals(__FILE__, $ctx->archivo);
        $this->assertIsArray($ctx->diagnosticoSistema);
        $this->assertNotEmpty($ctx->diagnosticoSistema['php_version'] ?? '');
    }

    public function testVerificadorBaseDatosTablaFaltante(): void {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error_interno', "SQLSTATE[42S02]: Base table or view not found: 1146 Table 'lite.operador' doesn't exist", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('tabla_faltante', $diag['tipo']);
        $sugs = $v->obtenerSugerencias($diag);
        $this->assertNotEmpty($sugs);
    }

    public function testVerificadorBaseDatosConexion(): void {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error_interno', "SQLSTATE[HY000] [2002] Connection refused", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('conexion', $diag['tipo']);
    }

    public function testVerificadorArchivosDemasiadoGrande(): void {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error_interno', 'UPLOAD_ERR_INI_SIZE', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('archivo_demasiado_grande', $diag['tipo']);
    }

    public function testVerificadorSeguridadCSRF(): void {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('token_invalido', 'Token CSRF invalido', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('csrf_expirado', $diag['tipo']);
    }

    public function testVerificadorSeguridadRateLimit(): void {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('bloqueo_temporal', 'Demasiados intentos. Espere 15 minutos.', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('rate_limit', $diag['tipo']);
    }

    public function testVerificadorSistemaMemoria(): void {
        $v = new VerificadorSistema();
        $ctx = ContextoError::capturar('error_interno', 'Allowed memory size of 134217728 bytes exhausted', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('memoria_insuficiente', $diag['tipo']);
        $sugs = $v->obtenerSugerencias($diag);
        $this->assertNotEmpty($sugs);
    }

    public function testVerificadorNoAplica(): void {
        $v = new VerificadorSistema();
        $ctx = ContextoError::capturar('exito', 'Operacion completada correctamente', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNull($diag);
    }

    public function testDiagnosticoIntegraTodosVerificadores(): void {
        $ctx = ContextoError::capturar('error_interno', "SQLSTATE[42S02]: Table 'lite.operador' doesn't exist", __FILE__, __LINE__);
        $resultado = DiagnosticoError::diagnosticar($ctx);
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('diagnosticos', $resultado);
        $this->assertArrayHasKey('sugerencias', $resultado);
        $this->assertArrayHasKey('accion', $resultado);
        $this->assertArrayHasKey('tieneRemedio', $resultado);
        $this->assertNotEmpty($resultado['diagnosticos']);
        $this->assertNotEmpty($resultado['sugerencias']);
    }

    public function testDiagnosticoSinErrorNoDevuelveNada(): void {
        $ctx = ContextoError::capturar('exito', 'Todo ok', __FILE__, __LINE__);
        $resultado = DiagnosticoError::diagnosticar($ctx);
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado['diagnosticos']);
    }

    // ─── VerificadorBaseDatos: patrones faltantes ────────────────────────

    public function testVerificadorBaseDatosCredenciales(): void {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error_interno', "SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: YES)", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('credenciales', $diag['tipo']);
        $sugs = $v->obtenerSugerencias($diag);
        $this->assertNotEmpty($sugs);
    }

    public function testVerificadorBaseDatosBdNoExiste(): void {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error_interno', "SQLSTATE[HY000] [1049] Unknown database 'lite'", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('bd_no_existe', $diag['tipo']);
        $this->assertEquals('lite', $diag['bd']);
    }

    public function testVerificadorBaseDatosColumnaFaltante(): void {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error_interno', "SQLSTATE[42S22]: [1054] Unknown column 'correo' in 'field list'", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('columna_faltante', $diag['tipo']);
        $this->assertStringContainsString('correo', $diag['columna']);
    }

    public function testVerificadorBaseDatosDeadlock(): void {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('error_interno', 'SQLSTATE[40001] [1213] Deadlock found when trying to get lock; try restarting transaction', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('deadlock', $diag['tipo']);

        $remedio = $v->ejecutarRemedio($diag);
        $this->assertTrue($remedio['exito']);
        $this->assertTrue($remedio['reintentar']);
    }

    public function testVerificadorBaseDatosNoAplica(): void {
        $v = new VerificadorBaseDatos();
        $ctx = ContextoError::capturar('exito', 'Operacion completada', __FILE__, __LINE__);
        $this->assertNull($v->diagnosticar($ctx));
    }

    // ─── VerificadorArchivos: patrones faltantes ──────────────────────────

    public function testVerificadorArchivosFormSize(): void {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error_interno', 'UPLOAD_ERR_FORM_SIZE', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('archivo_demasiado_grande', $diag['tipo']);
    }

    public function testVerificadorArchivosNoTmpDir(): void {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error_interno', 'UPLOAD_ERR_NO_TMP_DIR', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('tmp_dir_faltante', $diag['tipo']);
    }

    public function testVerificadorArchivosCantWrite(): void {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error_interno', 'UPLOAD_ERR_CANT_WRITE', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('disco_sin_espacio', $diag['tipo']);
        $this->assertArrayHasKey('espacio_libre_mb', $diag);
    }

    public function testVerificadorArchivosMkdirPermissionDenied(): void {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error_interno', "mkdir(): Permission denied", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('directorio_no_creable', $diag['tipo']);
    }

    public function testVerificadorArchivosMoveUploadedFile(): void {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error_interno', "move_uploaded_file(/var/www/data/file.pdf): failed to open stream: Permission denied", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('permiso_escritura', $diag['tipo']);
    }

    public function testVerificadorArchivosFilePutContents(): void {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('error_interno', "file_put_contents(/ruta/archivo.txt): failed to open stream: No such file or directory", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('archivo_no_escribible', $diag['tipo']);
        $this->assertStringContainsString('archivo.txt', $diag['ruta']);
    }

    public function testVerificadorArchivosNoAplica(): void {
        $v = new VerificadorArchivos();
        $ctx = ContextoError::capturar('exito', 'Archivo subido correctamente', __FILE__, __LINE__);
        $this->assertNull($v->diagnosticar($ctx));
    }

    // ─── VerificadorSeguridad: patrones faltantes ─────────────────────────

    public function testVerificadorSeguridadCsrfPorMensaje(): void {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('error', 'El token CSRF no es valido', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('csrf_expirado', $diag['tipo']);
    }

    public function testVerificadorSeguridadRateLimitPorBloqueado(): void {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('error', 'Usuario bloqueado temporalmente', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('rate_limit', $diag['tipo']);
    }

    public function testVerificadorSeguridadRateLimitPorDemasiadosIntentos(): void {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('error', 'Demasiados intentos. Espere 5 minutos.', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('rate_limit', $diag['tipo']);
        $this->assertEquals(5, $diag['minutos_restantes']);
    }

    public function testVerificadorSeguridadPermisoFaltante(): void {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('acceso_denegado', 'No tienes permiso', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('permiso_faltante', $diag['tipo']);
    }

    public function testVerificadorSeguridadSesionExpirada(): void {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('no_autenticado', 'Debe iniciar sesion', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('sesion_expirada', $diag['tipo']);
    }

    public function testVerificadorSeguridadSesionExpiradaPorMensaje(): void {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('error', 'Su sesion ha expirado por inactividad', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('sesion_expirada', $diag['tipo']);
    }

    public function testVerificadorSeguridadPosibleHijacking(): void {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('sesion_invalida_o_secuestrada', 'Intento de secuestro detectado', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('posible_hijacking', $diag['tipo']);
    }

    public function testVerificadorSeguridadCuentaSuspendida(): void {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('cuenta_suspendida', 'Cuenta deshabilitada por el administrador', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('cuenta_suspendida', $diag['tipo']);
    }

    public function testVerificadorSeguridadNoAplica(): void {
        $v = new VerificadorSeguridad();
        $ctx = ContextoError::capturar('exito', 'Todo correcto', __FILE__, __LINE__);
        $this->assertNull($v->diagnosticar($ctx));
    }

    // ─── VerificadorSistema: patrones faltantes ───────────────────────────

    public function testVerificadorSistemaTiempoAgotado(): void {
        $v = new VerificadorSistema();
        $ctx = ContextoError::capturar('error_interno', 'Fatal error: Maximum execution time of 30 seconds exceeded', __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('tiempo_agotado', $diag['tipo']);
        $this->assertNotNull($diag['limite_actual']);
    }

    public function testVerificadorSistemaClaseFaltante(): void {
        $v = new VerificadorSistema();
        $ctx = ContextoError::capturar('error_interno', "Class \"ControladorInexistente\" not found", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('clase_faltante', $diag['tipo']);
        $this->assertStringContainsString('ControladorInexistente', $diag['clase']);
    }

    public function testVerificadorSistemaClaseFaltanteComillasSimples(): void {
        $v = new VerificadorSistema();
        $ctx = ContextoError::capturar('error_interno', "Class 'ModeloInexistente' not found", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('clase_faltante', $diag['tipo']);
        $this->assertStringContainsString('ModeloInexistente', $diag['clase']);
    }

    public function testVerificadorSistemaExtensionFaltante(): void {
        $v = new VerificadorSistema();
        $ctx = ContextoError::capturar('error_interno', "Call to undefined function mysqli_connect", __FILE__, __LINE__);
        $diag = $v->diagnosticar($ctx);
        $this->assertNotNull($diag);
        $this->assertEquals('extension_faltante', $diag['tipo']);
        $this->assertStringContainsString('mysqli', $diag['extension']);
    }

    public function testVerificadorSistemaNoAplica(): void {
        $v = new VerificadorSistema();
        $ctx = ContextoError::capturar('exito', 'Sin errores de sistema', __FILE__, __LINE__);
        $this->assertNull($v->diagnosticar($ctx));
    }

    // ─── ContextoError: datos capturados ──────────────────────────────────

    public function testContextoErrorDiagnosticoSistemaTieneCampos(): void {
        $ctx = ContextoError::capturar('test', 'msg', __FILE__, __LINE__);
        $info = $ctx->diagnosticoSistema;

        $this->assertArrayHasKey('php_version', $info);
        $this->assertArrayHasKey('memoria_limite', $info);
        $this->assertArrayHasKey('tiempo_ejecucion', $info);
        $this->assertArrayHasKey('upload_max', $info);
        $this->assertArrayHasKey('post_max', $info);
        $this->assertArrayHasKey('tmp_dir', $info);
        $this->assertArrayHasKey('extensiones', $info);
        $this->assertArrayHasKey('pdo', $info['extensiones']);
        $this->assertArrayHasKey('json', $info['extensiones']);
        $this->assertArrayHasKey('mbstring', $info['extensiones']);
        $this->assertIsBool($info['extensiones']['pdo']);
    }

    public function testContextoErrorDatosExtra(): void {
        $extra = ['clave_extra' => 'valor_extra', 'otro' => 42];
        $ctx = ContextoError::capturar('test', 'msg', __FILE__, __LINE__, $extra);
        $this->assertEquals('valor_extra', $ctx->datosExtra['clave_extra']);
        $this->assertEquals(42, $ctx->datosExtra['otro']);
    }

    // ─── DiagnosticoError integracion con remedio automatico ──────────────

    public function testDiagnosticoConRemedioDevuelveFlag(): void {
        $ctx = ContextoError::capturar('error_interno', 'SQLSTATE[40001] [1213] Deadlock found; try restarting transaction', __FILE__, __LINE__);
        $resultado = DiagnosticoError::diagnosticar($ctx);

        $this->assertTrue($resultado['tieneRemedio'], 'Deadlock debe tener remedio automatico');
        $this->assertNotEmpty($resultado['diagnosticos']);

        $deadlockDiag = current(array_filter($resultado['diagnosticos'], fn($d) => ($d['tipo'] ?? '') === 'deadlock'));
        $this->assertNotFalse($deadlockDiag, 'Debe haber un diagnostico de deadlock');
        $this->assertArrayHasKey('remedio', $deadlockDiag);
        $this->assertTrue($deadlockDiag['remedio']['exito']);
    }
}
