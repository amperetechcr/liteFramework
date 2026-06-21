<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Seguridad;

use LiteFramework\Seguridad\SeguridadServidor;
use LiteFramework\Nucleo\Excepciones\ErrorAutenticacion;

class SeguridadServidorTest extends \TestBase
{
    public function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTienePermisoDelegaAControlAccesoRBAC(): void
    {
        $_SESSION['matriz_permisos'] = ['reportes.ver'];
        $this->assertTrue(SeguridadServidor::tienePermiso('reportes.ver'));
        $this->assertFalse(SeguridadServidor::tienePermiso('reportes.eliminar'));
    }

    public function testRequerirPermisoEstrictoDelegaAControlAccesoRBAC(): void
    {
        $_SESSION['matriz_permisos'] = ['solo.lectura'];
        SeguridadServidor::requerirPermisoEstricto('solo.lectura');
        $this->assertTrue(true);
    }

    public function testGenerarTokenAntiFalsificacion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = SeguridadServidor::generarTokenAntiFalsificacion();
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testSanitizarTextoBase(): void
    {
        $resultado = SeguridadServidor::sanitizarTextoBase('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $resultado);
    }

    public function testSanitizarTextoPlano(): void
    {
        $resultado = SeguridadServidor::sanitizarTextoPlano("  Hola Mundo  ");
        $this->assertSame('Hola Mundo', $resultado);
    }

    public function testSanitizarTextoBaseConNull(): void
    {
        $resultado = SeguridadServidor::sanitizarTextoBase(null);
        $this->assertSame('', $resultado);
    }

    public function testVerificarBloqueoAccesoSinIntentos(): void
    {
        $bloqueado = SeguridadServidor::verificarBloqueoAcceso(null, 'test@example.com');
        $this->assertFalse($bloqueado);
    }

    public function testRegistrarIntentoFallidoYLimpiar(): void
    {
        SeguridadServidor::registrarIntentoAccesoFallido(null, 'test@example.com');
        $intentos = SeguridadServidor::contarIntentosAcceso(null, 'test@example.com');
        $this->assertSame(1, $intentos);

        SeguridadServidor::limpiarIntentosAcceso(null, 'test@example.com');
        $intentos = SeguridadServidor::contarIntentosAcceso(null, 'test@example.com');
        $this->assertSame(0, $intentos);
    }

    public function testObtenerIdEmpresaLanzaExcepcionSinSession(): void
    {
        $this->expectException(ErrorAutenticacion::class);
        SeguridadServidor::obtenerIdEmpresa();
    }

    public function testObtenerIdEmpresaConSession(): void
    {
        $_SESSION['id_empresa'] = 42;
        $id = SeguridadServidor::obtenerIdEmpresa();
        $this->assertSame(42, $id);
    }
}
