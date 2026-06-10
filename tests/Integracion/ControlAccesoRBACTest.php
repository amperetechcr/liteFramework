<?php
use PHPUnit\Framework\TestCase;

class ControlAccesoRBACTest extends TestCase {
    private array $sesionOriginal;

    protected function setUp(): void {
        $this->sesionOriginal = $_SESSION ?? [];
        $_SESSION = [];
    }

    protected function tearDown(): void {
        $_SESSION = $this->sesionOriginal;
        ConexionBaseDatos::resetearInstancia();
    }

    public function testCargarPermisosEnMemoria(): void {
        $conector = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        ControlAccesoRBAC::cargarPermisosEnMemoria($conector, 1);
        $this->assertNotEmpty($_SESSION['matriz_permisos']);
    }

    public function testCargarPermisosDevuelveArregloNoVacio(): void {
        $conector = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        ControlAccesoRBAC::cargarPermisosEnMemoria($conector, 1);
        $permisos = $_SESSION['matriz_permisos'] ?? [];
        $this->assertNotEmpty($permisos);
    }

    public function testTienePrimerPermisoCargado(): void {
        $conector = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        ControlAccesoRBAC::cargarPermisosEnMemoria($conector, 1);
        $permisos = $_SESSION['matriz_permisos'] ?? [];
        $this->assertNotEmpty($permisos);
        $this->assertTrue(ControlAccesoRBAC::tienePermiso($permisos[0]));
    }

    public function testTienePermisoInexistente(): void {
        $conector = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        ControlAccesoRBAC::cargarPermisosEnMemoria($conector, 1);
        $this->assertFalse(ControlAccesoRBAC::tienePermiso('permiso.inexistente'));
    }

    public function testTienePermisoSinPermisos(): void {
        $conector = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        ControlAccesoRBAC::cargarPermisosEnMemoria($conector, 2);
        $this->assertFalse(ControlAccesoRBAC::tienePermiso('operadores.eliminar'));
    }
}
