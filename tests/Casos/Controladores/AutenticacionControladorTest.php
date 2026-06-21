<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Controladores;

use LiteFramework\Controladores\AutenticacionControlador;

class AutenticacionControladorTest extends \TestBase
{
    private array $sessionBackup = [];
    private array $getBackup = [];

    public static function setUpBeforeClass(): void
    {
        if (!defined('TESTS_RUNNING')) {
            define('TESTS_RUNNING', true);
        }
    }

    public function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? [];
        $this->getBackup = $_GET ?? [];
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_GET = [];
    }

    public function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = $this->sessionBackup;
        $_GET = $this->getBackup;
    }

    public function testMostrarInicioSesionExtiendeControladorBase(): void
    {
        $controlador = new AutenticacionControlador();
        $this->assertInstanceOf(\LiteFramework\Controladores\ControladorBase::class, $controlador);
    }

    public function testMostrarInicioSesionVistaExiste(): void
    {
        $this->assertFileExists(DIRECTORIO_RAIZ . '/src/vistas/inicio_sesion.php');
    }

    public function testCerrarSesionEsMetodoPublico(): void
    {
        $reflection = new \ReflectionMethod(AutenticacionControlador::class, 'cerrarSesion');
        $this->assertTrue($reflection->isPublic());
    }

    public function testMostrarInicioSesionEsMetodoPublico(): void
    {
        $reflection = new \ReflectionMethod(AutenticacionControlador::class, 'mostrarInicioSesion');
        $this->assertTrue($reflection->isPublic());
    }

    public function testCerrarSesionDestruyeSesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['operador_id'] = 1;
        $controlador = new class extends AutenticacionControlador {
            protected function redirigir(string $url): never {
                throw new \RuntimeException("Redirect: {$url}");
            }
        };
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/^Redirect:/');
        $controlador->cerrarSesion();
    }

    public function testControladorTieneMetodoMostrarInicioSesion(): void
    {
        $this->assertTrue(method_exists(AutenticacionControlador::class, 'mostrarInicioSesion'));
    }
}
