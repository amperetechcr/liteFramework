<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Seguridad;

use LiteFramework\Seguridad\ControlAccesoRBAC;
use LiteFramework\Config\ConexionBaseDatos as DB;
use LiteFramework\Nucleo\Excepciones\ErrorSeguridad;

class ControlAccesoRBACTest extends \TestBase
{
    public function setUp(): void
    {
        if (!defined('TESTS_RUNNING')) {
            define('TESTS_RUNNING', true);
        }
        $_SESSION = [];

        $ref = new \ReflectionClass(ControlAccesoRBAC::class);
        $prop = $ref->getProperty('modoIA');
        $prop->setAccessible(true);
        $prop->setValue(null, false);
    }

    public function tearDown(): void
    {
        unset($_SESSION['matriz_permisos']);
        DB::resetearInstancia();
    }

    public function testCargarPermisosEnMemoriaConPdoNull(): void
    {
        ControlAccesoRBAC::cargarPermisosEnMemoria(null, 1);
        $this->assertArrayHasKey('matriz_permisos', $_SESSION);
    }

    public function testTienePermisoConSessionVacia(): void
    {
        $_SESSION['matriz_permisos'] = [];
        $this->assertFalse(ControlAccesoRBAC::tienePermiso('operador.leer'));
    }

    public function testTienePermisoConSessionNoArray(): void
    {
        $_SESSION['matriz_permisos'] = 'invalido';
        $this->assertFalse(ControlAccesoRBAC::tienePermiso('operador.leer'));
    }

    public function testTienePermisoConSessionNoSet(): void
    {
        unset($_SESSION['matriz_permisos']);
        $this->assertFalse(ControlAccesoRBAC::tienePermiso('operador.leer'));
    }

    public function testTienePermisoConClaveValida(): void
    {
        $_SESSION['matriz_permisos'] = ['operador.leer', 'operador.actualizar'];
        $this->assertTrue(ControlAccesoRBAC::tienePermiso('operador.leer'));
        $this->assertTrue(ControlAccesoRBAC::tienePermiso('operador.actualizar'));
    }

    public function testTienePermisoConClaveInvalida(): void
    {
        $_SESSION['matriz_permisos'] = ['operador.leer'];
        $this->assertFalse(ControlAccesoRBAC::tienePermiso('operador.eliminar'));
    }

    public function testRequerirPermisoEstrictoLanzaExcepcion(): void
    {
        $_SESSION['matriz_permisos'] = [];
        $this->expectException(ErrorSeguridad::class);
        $this->expectExceptionMessage('Permiso denegado: operador.eliminar');
        ControlAccesoRBAC::requerirPermisoEstricto('operador.eliminar');
    }

    public function testRequerirPermisoEstrictoNoLanzaConPermiso(): void
    {
        $_SESSION['matriz_permisos'] = ['reportes.ver'];
        ControlAccesoRBAC::requerirPermisoEstricto('reportes.ver');
        $this->assertTrue(true);
    }

    public function testPermisosBloqueadosWorkerDevuelveArray(): void
    {
        $bloqueados = ControlAccesoRBAC::permisosBloqueadosWorker();
        $this->assertIsArray($bloqueados);
        $this->assertContains('operador.crear', $bloqueados);
        $this->assertContains('configuracion.gestionar', $bloqueados);
        $this->assertNotContains('operador.leer', $bloqueados);
    }

    public function testIaWorkerBloqueadoParaPermisosRestringidos(): void
    {
        $ref = new \ReflectionClass(ControlAccesoRBAC::class);
        $propModo = $ref->getProperty('modoIA');
        $propModo->setAccessible(true);
        $propModo->setValue(null, true);
        $propRol = $ref->getProperty('rolIA');
        $propRol->setAccessible(true);
        $propRol->setValue(null, 'worker');

        $_SESSION['matriz_permisos'] = ['operador.crear', 'operador.leer'];
        $this->assertFalse(ControlAccesoRBAC::tienePermiso('operador.crear'));
        $this->assertTrue(ControlAccesoRBAC::tienePermiso('operador.leer'));
    }

    public function testEsModoIa(): void
    {
        $this->assertFalse(ControlAccesoRBAC::esModoIA());
    }

    public function testObjetoRolIa(): void
    {
        $this->assertSame('worker', ControlAccesoRBAC::obtenerRolIA());
    }

    public function testRequiereTokenIaLanzaSiNoAutenticado(): void
    {
        $this->expectException(ErrorSeguridad::class);
        ControlAccesoRBAC::requiereTokenIA();
    }

    public function testEsManager(): void
    {
        $this->assertFalse(ControlAccesoRBAC::esManager());
    }

    public function testAutenticarTokenIaConHashVacioRetornaFalse(): void
    {
        $resultado = ControlAccesoRBAC::autenticarTokenIA('cualquier_token');
        $this->assertFalse($resultado);
    }
}
