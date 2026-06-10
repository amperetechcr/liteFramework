<?php
use PHPUnit\Framework\TestCase;

class AyudanteOperadorTest extends TestCase {
    private array $sesionOriginal;

    protected function setUp(): void {
        $this->sesionOriginal = $_SESSION ?? [];
    }

    protected function tearDown(): void {
        $_SESSION = $this->sesionOriginal;
    }

    public function testEstadoTextoActivo(): void {
        $this->assertEquals('Activo', OperadorH::estadoTexto(1));
    }

    public function testEstadoTextoSuspendido(): void {
        $this->assertEquals('Suspendido', OperadorH::estadoTexto(0));
    }

    public function testEstadoEtiquetaActivo(): void {
        $etiqueta = OperadorH::estadoEtiqueta(1);
        $this->assertStringContainsString('etiqueta-exito', $etiqueta);
        $this->assertStringContainsString('Activo', $etiqueta);
    }

    public function testEstadoEtiquetaSuspendido(): void {
        $etiqueta = OperadorH::estadoEtiqueta(0);
        $this->assertStringContainsString('etiqueta-peligro', $etiqueta);
        $this->assertStringContainsString('Suspendido', $etiqueta);
    }

    public function testNombreActualSinSesion(): void {
        $_SESSION = [];
        $this->assertEquals('Invitado', OperadorH::nombreActual());
    }

    public function testNombreActualConSesion(): void {
        $_SESSION['operador_nombre'] = 'Carlos';
        $this->assertEquals('Carlos', OperadorH::nombreActual());
    }

    public function testIdActualSinSesion(): void {
        $_SESSION = [];
        $this->assertEquals(0, OperadorH::idActual());
    }

    public function testIdActualConSesion(): void {
        $_SESSION['operador_id'] = 5;
        $this->assertEquals(5, OperadorH::idActual());
    }

    public function testRolActualSinSesion(): void {
        $_SESSION = [];
        $this->assertEquals(0, OperadorH::rolActual());
    }

    public function testRolActualConSesion(): void {
        $_SESSION['operador_rol'] = 3;
        $this->assertEquals(3, OperadorH::rolActual());
    }

    public function testPermisosActualesSinSesion(): void {
        $_SESSION = [];
        $this->assertEquals([], OperadorH::permisosActuales());
    }

    public function testPermisosActualesConSesion(): void {
        $_SESSION['matriz_permisos'] = ['usuarios.leer', 'usuarios.crear'];
        $this->assertEquals(['usuarios.leer', 'usuarios.crear'], OperadorH::permisosActuales());
    }
}
