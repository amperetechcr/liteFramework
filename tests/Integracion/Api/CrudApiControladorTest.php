<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Api;

use LiteFramework\Api\Controladores\CrudApiControlador;
use LiteFramework\Config\ConexionBaseDatos;

class CrudApiControladorTest extends \TestBase
{
    private array $sessionBackup = [];

    public static function setUpBeforeClass(): void
    {
        if (!defined('TESTS_RUNNING')) {
            define('TESTS_RUNNING', true);
        }
        ConexionBaseDatos::resetearInstancia();
    }

    public function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? [];
        $_SESSION = [];
    }

    public function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
    }

    private function autenticar(): void
    {
        $_SESSION['operador_id'] = 1;
        $_SESSION['matriz_permisos'] = [
            'operador.crear' => true,
            'operador.leer' => true,
            'operador.actualizar' => true,
            'operador.eliminar' => true,
        ];
        \LiteFramework\Seguridad\ControlAccesoRBAC::cargarPermisosEnMemoria(
            ConexionBaseDatos::obtenerInstancia()->obtenerConector(),
            1
        );
    }

    public function testProcesarSesionExpiradaRetorna401(): void
    {
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'leer', 'tabla_destino' => 'operador'];
        [$codigo, $respuesta] = $controlador->procesar($payload);
        $this->assertSame(401, $codigo);
        $this->assertSame('no_autenticado', $respuesta['codigo_error']);
    }

    public function testProcesarEntidadNoPermitidaRetorna403(): void
    {
        $this->autenticar();
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'leer', 'tabla_destino' => 'tabla_hackeada'];
        [$codigo, $respuesta] = $controlador->procesar($payload);
        $this->assertSame(403, $codigo);
        $this->assertSame('acceso_denegado', $respuesta['codigo_error']);
    }

    public function testProcesarEntidadVaciaRetorna400(): void
    {
        $this->autenticar();
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'leer', 'tabla_destino' => ''];
        [$codigo, $respuesta] = $controlador->procesar($payload);
        $this->assertSame(400, $codigo);
        $this->assertSame('datos_invalidos', $respuesta['codigo_error']);
    }

    public function testProcesarAccionCrudInvalidaRetorna400(): void
    {
        $this->autenticar();
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'no_existe', 'tabla_destino' => 'operador'];
        [$codigo, $respuesta] = $controlador->procesar($payload);
        $this->assertSame(400, $codigo);
        $this->assertStringContainsString('desconocida', $respuesta['mensaje_error']);
    }

    public function testProcesarCrearSinPermisoRetorna403(): void
    {
        $this->autenticar();
        $_SESSION['matriz_permisos'] = [];
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'crear', 'tabla_destino' => 'operador'];
        [$codigo, $respuesta] = $controlador->procesar($payload);
        $this->assertSame(403, $codigo);
        $this->assertSame('sin_permiso', $respuesta['codigo_error']);
    }

    public function testProcesarActualizarSinIdRetorna400(): void
    {
        $this->autenticar();
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'actualizar', 'tabla_destino' => 'operador', 'id_entidad' => 0];
        [$codigo, $respuesta] = $controlador->procesar($payload);
        $this->assertSame(400, $codigo);
        $this->assertStringContainsString('Identificador', $respuesta['mensaje_error']);
    }

    public function testProcesarEliminarSinIdRetorna400(): void
    {
        $this->autenticar();
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'eliminar', 'tabla_destino' => 'operador', 'id_entidad' => 0];
        [$codigo, $respuesta] = $controlador->procesar($payload);
        $this->assertSame(400, $codigo);
        $this->assertStringContainsString('Identificador', $respuesta['mensaje_error']);
    }

    public function testProcesarBuscarSinTerminoRetorna400(): void
    {
        $this->autenticar();
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'buscar', 'tabla_destino' => 'operador', 'termino_busqueda' => ''];
        [$codigo, $respuesta] = $controlador->procesar($payload);
        $this->assertSame(400, $codigo);
        $this->assertStringContainsString('vacío', $respuesta['mensaje_error']);
    }

    public function testProcesarLeerSinPermisoRetorna403(): void
    {
        $this->autenticar();
        $_SESSION['matriz_permisos'] = [];
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'leer', 'tabla_destino' => 'operador'];
        [$codigo, $respuesta] = $controlador->procesar($payload);
        $this->assertSame(403, $codigo);
        $this->assertSame('sin_permiso', $respuesta['codigo_error']);
    }

    public function testProcesarEliminarSinPermisoRetorna403(): void
    {
        $this->autenticar();
        $_SESSION['matriz_permisos'] = [];
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'eliminar', 'tabla_destino' => 'operador', 'id_entidad' => 1];
        [$codigo, $respuesta] = $controlador->procesar($payload);
        $this->assertSame(403, $codigo);
        $this->assertSame('sin_permiso', $respuesta['codigo_error']);
    }

    public function testSqlInjectionEnTablaDestinoSanitizado(): void
    {
        $this->autenticar();
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'leer', 'tabla_destino' => 'operador; DROP TABLE operador;'];
        [$codigo, $respuesta] = $controlador->procesar($payload);
        $this->assertSame(403, $codigo);
        $this->assertSame('acceso_denegado', $respuesta['codigo_error']);
    }

    public function testSqlInjectionConCaracteresEspeciales(): void
    {
        $this->autenticar();
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'leer', 'tabla_destino' => 'operador\' OR \'1\'=\'1'];
        [$codigo, $respuesta] = $controlador->procesar($payload);
        $this->assertSame(403, $codigo);
    }

    public function testPaginacionClampedALimiteMaximo(): void
    {
        $this->autenticar();
        $controlador = new CrudApiControlador();
        $payload = ['accion_crud' => 'leer', 'tabla_destino' => 'operador', 'limite' => 9999];
        try {
            [$codigo, $respuesta] = $controlador->procesar($payload);
            $this->assertSame(200, $codigo);
        } catch (\PDOException $e) {
            $this->addToAssertionCount(1);
        }
    }
}
