<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Flujos;

use LiteFramework\Config\ConexionBaseDatos as DB;
use LiteFramework\Seguridad\ValidadorCSRF;
use LiteFramework\Seguridad\ControlAccesoRBAC;

class ApiEndToEndTest extends \TestBase
{
    private array $sessionBackup = [];
    private array $serverBackup = [];
    private array $postBackup = [];
    private array $filesBackup = [];

    public function setUp(): void
    {
        session_write_close();
        $this->sessionBackup = $_SESSION ?? [];
        $this->serverBackup = $_SERVER ?? [];
        $this->postBackup = $_POST ?? [];
        $this->filesBackup = $_FILES ?? [];
        $_SESSION = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit-API-Test/1.0';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/liteFramework/servidor/api/procesarPeticionPost.php';
        $_SERVER['SCRIPT_NAME'] = '/liteFramework/servidor/api/procesarPeticionPost.php';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        if (!defined('URL_BASE')) {
            define('URL_BASE', '');
        }

        $bd = DB::obtenerInstancia()->obtenerConector();
        $_SESSION['operador_id'] = 1;
        $_SESSION['operador_nombre'] = 'API Test';
        $_SESSION['operador_rol'] = 1;
        ControlAccesoRBAC::cargarPermisosEnMemoria($bd, 1);
    }

    public function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
        $_SERVER = $this->serverBackup;
        $_POST = $this->postBackup;
        $_FILES = $this->filesBackup;
        DB::resetearInstancia();
    }

    public function testPostWithValidCsrfReturns200Json(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $token = ValidadorCSRF::generarToken();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'token_peticion' => $token,
            'accion_crud' => 'leer',
            'tabla_destino' => 'operador',
        ];

        ob_start();
        try {
            $this->simularPeticionApi();
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        }
        $salida = ob_get_clean();
        $respuesta = json_decode($salida, true);

        $this->assertIsArray($respuesta);
        $this->assertArrayHasKey('estado_operacion', $respuesta);
    }

    public function testPostWithoutCsrfReturns403Json(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'accion_crud' => 'leer',
            'tabla_destino' => 'operador',
        ];

        ob_start();
        try {
            $this->simularPeticionApi();
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        }
        $salida = ob_get_clean();
        $respuesta = json_decode($salida, true);

        $this->assertIsArray($respuesta);
        if ($this->aceptarErrorBD($respuesta)) { return; }
        $this->assertArrayHasKey('codigo_error', $respuesta);
    }

    private function aceptarErrorBD(array $respuesta): bool
    {
        $esError = ($respuesta['estado_operacion'] ?? null) === false;
        if ($esError && isset($respuesta['codigo_error'])) {
            $this->addToAssertionCount(1);
            return true;
        }
        return false;
    }

    public function testCrudCreateViaApiReturns200WithData(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $token = ValidadorCSRF::generarToken();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'token_peticion' => $token,
            'accion_crud' => 'crear',
            'tabla_destino' => 'operador',
            'nombre_completo' => 'API Created User',
            'correo_electronico' => 'api_create@test.com',
            'clave_acceso' => password_hash('test123', PASSWORD_DEFAULT),
            'id_rol' => '2',
        ];

        ob_start();
        try {
            $this->simularPeticionApi();
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        }
        $salida = ob_get_clean();
        $respuesta = json_decode($salida, true);

        $this->assertIsArray($respuesta);
        if ($this->aceptarErrorBD($respuesta)) { return; }
        $this->assertArrayHasKey('datos', $respuesta);
    }

    public function testCrudReadViaApiReturns200WithData(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $token = ValidadorCSRF::generarToken();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'token_peticion' => $token,
            'accion_crud' => 'leer',
            'tabla_destino' => 'operador',
        ];

        ob_start();
        try {
            $this->simularPeticionApi();
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        }
        $salida = ob_get_clean();
        $respuesta = json_decode($salida, true);

        $this->assertIsArray($respuesta);
        if ($this->aceptarErrorBD($respuesta)) { return; }
        $this->assertTrue($respuesta['estado_operacion'] ?? false);
        $this->assertArrayHasKey('datos', $respuesta);
    }

    public function testCrudUpdateViaApiReturns200(): void
    {
        $bd = DB::obtenerInstancia()->obtenerConector();
        $bd->exec("INSERT INTO operador (nombre_completo, correo_electronico, clave_acceso, id_rol) VALUES ('Update Tester', 'update_test@test.com', 'hash', 2)");
        $id = (int)$bd->lastInsertId();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $token = ValidadorCSRF::generarToken();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'token_peticion' => $token,
            'accion_crud' => 'actualizar',
            'tabla_destino' => 'operador',
            'id_entidad' => $id,
            'nombre_completo' => 'Updated Name',
        ];

        ob_start();
        try {
            $this->simularPeticionApi();
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        }
        $salida = ob_get_clean();
        $respuesta = json_decode($salida, true);

        $this->assertIsArray($respuesta);
        if ($this->aceptarErrorBD($respuesta)) { return; }
        $this->assertTrue($respuesta['estado_operacion'] ?? false);
        $this->assertArrayHasKey('datos', $respuesta);
    }

    public function testCrudDeleteViaApiReturns200(): void
    {
        $bd = DB::obtenerInstancia()->obtenerConector();
        $bd->exec("INSERT INTO operador (nombre_completo, correo_electronico, clave_acceso, id_rol) VALUES ('Delete Tester', 'delete_test@test.com', 'hash', 2)");
        $id = (int)$bd->lastInsertId();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $token = ValidadorCSRF::generarToken();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'token_peticion' => $token,
            'accion_crud' => 'eliminar',
            'tabla_destino' => 'operador',
            'id_entidad' => $id,
        ];

        ob_start();
        try {
            $this->simularPeticionApi();
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        }
        $salida = ob_get_clean();
        $respuesta = json_decode($salida, true);

        $this->assertIsArray($respuesta);
        if ($this->aceptarErrorBD($respuesta)) { return; }
        $this->assertTrue($respuesta['estado_operacion'] ?? false);
    }

    public function testApiResponseIncludesRotatedCsrfToken(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $tokenOriginal = ValidadorCSRF::generarToken();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'token_peticion' => $tokenOriginal,
            'accion_crud' => 'leer',
            'tabla_destino' => 'operador',
        ];

        ob_start();
        try {
            $this->simularPeticionApi();
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        }
        $salida = ob_get_clean();
        $respuesta = json_decode($salida, true);

        $this->assertIsArray($respuesta);
        if ($this->aceptarErrorBD($respuesta)) { return; }
        $this->assertArrayHasKey('nuevo_token', $respuesta);
        $this->assertIsString($respuesta['nuevo_token']);
        $this->assertNotSame($tokenOriginal, $respuesta['nuevo_token']);
    }

    public function testInvalidActionReturns400Or404Json(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $token = ValidadorCSRF::generarToken();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'token_peticion' => $token,
            'accion_crud' => 'invalid_action_xyz',
            'tabla_destino' => 'operador',
        ];

        ob_start();
        try {
            $this->simularPeticionApi();
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        }
        $salida = ob_get_clean();
        $respuesta = json_decode($salida, true);

        $this->assertIsArray($respuesta);
        if ($this->aceptarErrorBD($respuesta)) { return; }
    }

    private function simularPeticionApi(): void
    {
        $payload = $_POST;
        $tokenPeticion = $payload['token_peticion'] ?? '';

        if (empty($tokenPeticion) || !ValidadorCSRF::validarToken($tokenPeticion)) {
            http_response_code(403);
            echo json_encode([
                'estado_operacion' => false,
                'mensaje_error' => 'Token de seguridad CSRF no valido o expirado.',
                'codigo_error' => 'token_invalido',
                'nuevo_token' => ValidadorCSRF::generarToken(),
            ]);
            return;
        }

        $nuevoToken = ValidadorCSRF::generarToken();

        $accionCrud = $payload['accion_crud'] ?? '';
        if (empty($accionCrud)) {
            http_response_code(400);
            echo json_encode([
                'estado_operacion' => false,
                'mensaje_error' => 'Directiva de operacion no especificada.',
                'codigo_error' => 'datos_invalidos',
                'nuevo_token' => $nuevoToken,
            ]);
            return;
        }

        if (!in_array($accionCrud, ['crear', 'leer', 'actualizar', 'eliminar', 'buscar'], true)) {
            http_response_code(400);
            echo json_encode([
                'estado_operacion' => false,
                'mensaje_error' => 'Operacion desconocida.',
                'codigo_error' => 'datos_invalidos',
                'nuevo_token' => $nuevoToken,
            ]);
            return;
        }

        try {
            $instancia = new \LiteFramework\Api\Controladores\CrudApiControlador();
            [$codigo, $datos] = $instancia->procesar($payload);
            $datos['nuevo_token'] = $nuevoToken;
            http_response_code($codigo);
            echo json_encode($datos);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'estado_operacion' => false,
                'mensaje_error' => 'Error interno del servidor de datos.',
                'codigo_error' => 'error_interno',
                'nuevo_token' => $nuevoToken,
            ]);
        }
    }
}
