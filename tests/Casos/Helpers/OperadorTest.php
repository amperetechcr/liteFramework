<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use LiteFramework\Nucleo\Helpers\OperadorH as Operador;

class OperadorTest extends \TestBase
{
    private array $sessionBackup = [];

    public function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->sessionBackup = $_SESSION;
    }

    public function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
    }

    public function testEstadoEtiquetaActivo(): void
    {
        $this->assertSame('<span class="etiqueta etiqueta-exito">Activo</span>', Operador::estadoEtiqueta(1));
    }

    public function testEstadoEtiquetaSuspendido(): void
    {
        $this->assertSame('<span class="etiqueta etiqueta-peligro">Suspendido</span>', Operador::estadoEtiqueta(0));
    }

    public function testEstadoEtiquetaCualquierEnteroNoUno(): void
    {
        $this->assertSame('<span class="etiqueta etiqueta-peligro">Suspendido</span>', Operador::estadoEtiqueta(2));
        $this->assertSame('<span class="etiqueta etiqueta-peligro">Suspendido</span>', Operador::estadoEtiqueta(-1));
    }

    public function testEstadoTextoActivo(): void
    {
        $this->assertSame('Activo', Operador::estadoTexto(1));
    }

    public function testEstadoTextoSuspendido(): void
    {
        $this->assertSame('Suspendido', Operador::estadoTexto(0));
    }

    public function testEstadoTextoCualquierEnteroNoUno(): void
    {
        $this->assertSame('Suspendido', Operador::estadoTexto(2));
    }

    public function testNombreActualConSesion(): void
    {
        $_SESSION['operador_nombre'] = 'Carlos';
        $this->assertSame('Carlos', Operador::nombreActual());
    }

    public function testNombreActualSinSesion(): void
    {
        unset($_SESSION['operador_nombre']);
        $this->assertSame('Invitado', Operador::nombreActual());
    }

    public function testIdActualConSesion(): void
    {
        $_SESSION['operador_id'] = 5;
        $this->assertSame(5, Operador::idActual());
    }

    public function testIdActualSinSesion(): void
    {
        unset($_SESSION['operador_id']);
        $this->assertSame(0, Operador::idActual());
    }

    public function testRolActualConSesion(): void
    {
        $_SESSION['operador_rol'] = 2;
        $this->assertSame(2, Operador::rolActual());
    }

    public function testRolActualSinSesion(): void
    {
        unset($_SESSION['operador_rol']);
        $this->assertSame(0, Operador::rolActual());
    }

    public function testPermisosActualesConSesion(): void
    {
        $permisos = ['usuarios.ver', 'usuarios.editar'];
        $_SESSION['matriz_permisos'] = $permisos;
        $this->assertSame($permisos, Operador::permisosActuales());
    }

    public function testPermisosActualesSinSesion(): void
    {
        unset($_SESSION['matriz_permisos']);
        $this->assertSame([], Operador::permisosActuales());
    }

    public function testNombreRolSinBaseDeDatos(): void
    {
        $this->assertSame('—', Operador::nombreRol(999));
    }

    public function testEstaActivoConIdInvalido(): void
    {
        $this->assertFalse(Operador::estaActivo(-1));
    }
}
