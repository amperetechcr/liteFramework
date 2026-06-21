<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Api;


class ProcesarPeticionPostTest extends \TestBase
{
    private array $sessionBackup = [];
    private array $serverBackup = [];
    private array $postBackup = [];

    public function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? [];
        $this->serverBackup = $_SERVER ?? [];
        $this->postBackup = $_POST ?? [];
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = '/liteFramework/servidor/api/procesarPeticionPost.php';
        if (!defined('URL_BASE')) {
            define('URL_BASE', '');
        }
    }

    public function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
        $_SERVER = $this->serverBackup;
        $_POST = $this->postBackup;
    }

    public function testArchivoIncludeExiste(): void
    {
        $archivo = DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
        $this->assertFileExists($archivo);
    }

    public function testArchivoDefineNamespace(): void
    {
        $archivo = DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('namespace LiteFramework\Api;', $contenido);
    }

    public function testArchivoContieneFuncionResponder(): void
    {
        $archivo = DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('function responder(', $contenido);
    }

    public function testArchivoContieneRuteoCrud(): void
    {
        $archivo = DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('accion_crud', $contenido);
        $this->assertStringContainsString('CrudApiControlador', $contenido);
        $this->assertStringContainsString('iniciar_sesion', $contenido);
    }

    public function testArchivoManejaExcepciones(): void
    {
        $archivo = DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('catch (PDOException', $contenido);
        $this->assertStringContainsString('catch (Exception', $contenido);
    }

    public function testArchivoEstableceCabecerasJson(): void
    {
        $archivo = DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('application/json', $contenido);
        $this->assertStringContainsString('X-Content-Type-Options', $contenido);
        $this->assertStringContainsString('X-Frame-Options', $contenido);
    }

    public function testArchivoContieneTokenRotation(): void
    {
        $archivo = DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('generarTokenAntiFalsificacion', $contenido);
        $this->assertStringContainsString('nuevo_token', $contenido);
    }

    public function testArchivoUsaTrazadorPeticiones(): void
    {
        $archivo = DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('TrazadorPeticiones', $contenido);
        $this->assertStringContainsString('RegistroAuditoria', $contenido);
    }

    public function testArchivoContieneGestorEntorno(): void
    {
        $archivo = DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('GestorEntorno::cargar', $contenido);
    }

    public function testArchivoMapeaRutasCorrectamente(): void
    {
        $archivo = DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('AutenticacionApiControlador', $contenido);
        $this->assertStringContainsString('OperadorApiControlador', $contenido);
        $this->assertStringContainsString('PersonalizacionApiControlador', $contenido);
        $this->assertStringContainsString('MigracionApiControlador', $contenido);
        $this->assertStringContainsString('ConfiguracionApiControlador', $contenido);
        $this->assertStringContainsString('GeneradorModuloApiControlador', $contenido);
        $this->assertStringContainsString('GeneradorProyectoApiControlador', $contenido);
    }

    public function testArchivoContieneSesionEstricta(): void
    {
        $archivo = DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('iniciarSesionEstricta', $contenido);
    }
}
