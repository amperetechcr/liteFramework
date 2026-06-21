<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Controladores;

class ControladorBaseTest extends \TestBase
{
    private array $sessionBackup = [];

    public function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? [];
        $_SESSION = [];
    }

    public function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
    }

    public function testObtenerIdOperadorRetornaEntero(): void
    {
        $controlador = new class extends \LiteFramework\Controladores\ControladorBase {
            public function verificarAutenticacion(): void {}
        };
        $_SESSION['operador_id'] = 42;
        $metodo = new \ReflectionMethod(\LiteFramework\Controladores\ControladorBase::class, 'obtenerIdOperador');
        $metodo->setAccessible(true);
        $this->assertSame(42, $metodo->invoke($controlador));
    }

    public function testObtenerIdOperadorSinSessionRetornaCero(): void
    {
        $controlador = new class extends \LiteFramework\Controladores\ControladorBase {
            public function verificarAutenticacion(): void {}
        };
        unset($_SESSION['operador_id']);
        $metodo = new \ReflectionMethod(\LiteFramework\Controladores\ControladorBase::class, 'obtenerIdOperador');
        $metodo->setAccessible(true);
        $this->assertSame(0, $metodo->invoke($controlador));
    }

    public function testObtenerNombreOperadorRetornaString(): void
    {
        $controlador = new class extends \LiteFramework\Controladores\ControladorBase {
            public function verificarAutenticacion(): void {}
        };
        $_SESSION['operador_nombre'] = 'Juan Perez';
        $metodo = new \ReflectionMethod(\LiteFramework\Controladores\ControladorBase::class, 'obtenerNombreOperador');
        $metodo->setAccessible(true);
        $this->assertSame('Juan Perez', $metodo->invoke($controlador));
    }

    public function testObtenerNombreOperadorSinSessionRetornaVacio(): void
    {
        $controlador = new class extends \LiteFramework\Controladores\ControladorBase {
            public function verificarAutenticacion(): void {}
        };
        unset($_SESSION['operador_nombre']);
        $metodo = new \ReflectionMethod(\LiteFramework\Controladores\ControladorBase::class, 'obtenerNombreOperador');
        $metodo->setAccessible(true);
        $this->assertSame('', $metodo->invoke($controlador));
    }

    public function testObtenerIdRolRetornaEntero(): void
    {
        $controlador = new class extends \LiteFramework\Controladores\ControladorBase {
            public function verificarAutenticacion(): void {}
        };
        $_SESSION['operador_rol'] = 3;
        $metodo = new \ReflectionMethod(\LiteFramework\Controladores\ControladorBase::class, 'obtenerIdRol');
        $metodo->setAccessible(true);
        $this->assertSame(3, $metodo->invoke($controlador));
    }

    public function testObtenerIdRolSinSessionRetornaCero(): void
    {
        $controlador = new class extends \LiteFramework\Controladores\ControladorBase {
            public function verificarAutenticacion(): void {}
        };
        unset($_SESSION['operador_rol']);
        $metodo = new \ReflectionMethod(\LiteFramework\Controladores\ControladorBase::class, 'obtenerIdRol');
        $metodo->setAccessible(true);
        $this->assertSame(0, $metodo->invoke($controlador));
    }

    public function testObtenerPermisosRetornaArray(): void
    {
        $controlador = new class extends \LiteFramework\Controladores\ControladorBase {
            public function verificarAutenticacion(): void {}
        };
        $_SESSION['matriz_permisos'] = ['operador.leer' => true];
        $metodo = new \ReflectionMethod(\LiteFramework\Controladores\ControladorBase::class, 'obtenerPermisos');
        $metodo->setAccessible(true);
        $this->assertSame(['operador.leer' => true], $metodo->invoke($controlador));
    }

    public function testObtenerPermisosSinSessionRetornaArrayVacio(): void
    {
        $controlador = new class extends \LiteFramework\Controladores\ControladorBase {
            public function verificarAutenticacion(): void {}
        };
        unset($_SESSION['matriz_permisos']);
        $metodo = new \ReflectionMethod(\LiteFramework\Controladores\ControladorBase::class, 'obtenerPermisos');
        $metodo->setAccessible(true);
        $this->assertSame([], $metodo->invoke($controlador));
    }
}
