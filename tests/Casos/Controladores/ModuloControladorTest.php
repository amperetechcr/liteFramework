<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Controladores
{

use LiteFramework\Controladores\ModuloControlador;

class ModuloControladorTest extends \TestBase
{
    private array $sessionBackup = [];

    public function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? [];
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = ['operador_id' => 1];
    }

    public function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = $this->sessionBackup;
    }

    public function testIndiceCargaVistaCorrecta(): void
    {
        $controlador = new class extends ModuloControlador {
            public function verificarAutenticacion(): void {}
        };
        ob_start();
        $controlador->indice('inicio');
        $salida = ob_get_clean();
        $this->assertNotEmpty($salida);
    }

    public function testIndiceArchivoModuloNoExisteMuestraError(): void
    {
        $controlador = new class extends ModuloControlador {
            public function verificarAutenticacion(): void {}
        };
        $moduloInexistente = 'modulo_inexistente_' . uniqid();
        $rutaEsperada = DIRECTORIO_RAIZ . '/src/modulos/' . $moduloInexistente . '/' . $moduloInexistente . '.php';
        $this->assertFileDoesNotExist($rutaEsperada);
        ob_start();
        $controlador->indice($moduloInexistente);
        $salida = ob_get_clean();
        $this->assertNotEmpty($salida);
    }

    public function testIndiceConVistaNoExisteMuestraError(): void
    {
        $controlador = new class extends ModuloControlador {
            public function verificarAutenticacion(): void {}
        };
        $vistaInexistente = 'vista_inexistente_' . uniqid();
        $rutaEsperada = DIRECTORIO_RAIZ . '/src/modulos/inicio/' . $vistaInexistente . '.php';
        $this->assertFileDoesNotExist($rutaEsperada);
        ob_start();
        $controlador->indice('inicio', $vistaInexistente);
        $salida = ob_get_clean();
        $this->assertNotEmpty($salida);
    }

    public function testIndicePathTraversalEnModulo(): void
    {
        $controlador = new class extends ModuloControlador {
            public function verificarAutenticacion(): void {}
        };
        $moduloMalicioso = '../../etc/passwd';
        $rutaConstruida = DIRECTORIO_RAIZ . '/src/modulos/' . $moduloMalicioso . '/' . $moduloMalicioso . '.php';
        $this->assertFileDoesNotExist($rutaConstruida);
        ob_start();
        $controlador->indice($moduloMalicioso);
        $salida = ob_get_clean();
        $this->assertNotEmpty($salida);
    }

    public function testIndicePathTraversalEnVista(): void
    {
        $controlador = new class extends ModuloControlador {
            public function verificarAutenticacion(): void {}
        };
        $vistaMaliciosa = '../../etc/passwd';
        $rutaConstruida = DIRECTORIO_RAIZ . '/src/modulos/inicio/' . $vistaMaliciosa . '.php';
        $this->assertFileDoesNotExist($rutaConstruida);
        ob_start();
        $controlador->indice('inicio', $vistaMaliciosa);
        $salida = ob_get_clean();
        $this->assertNotEmpty($salida);
    }

    public function testIndiceRutaCorrectaSinVista(): void
    {
        $controlador = new class extends ModuloControlador {
            public function verificarAutenticacion(): void {}
        };
        ob_start();
        $controlador->indice('inicio');
        $salida = ob_get_clean();
        $this->assertStringContainsString('inicio', $salida);
    }

    public function testIndiceExtiendeControladorBase(): void
    {
        $controlador = new class extends ModuloControlador {
            public function verificarAutenticacion(): void {}
        };
        $this->assertInstanceOf(\LiteFramework\Controladores\ControladorBase::class, $controlador);
    }
}

}

namespace
{

if (!function_exists('configUI')) {
    $GLOBALS['configUI'] = [
        'paleta' => 'indigo', 'estilo' => 'moderno', 'fondo' => 'blanco',
        'fuente' => 'sistema', 'espaciado' => 'normal', 'tamano' => 'normal',
        'radio' => 'normal', 'animacion' => 'normal', 'grosor' => 'normal',
        'trazo' => 'normal', 'sombra' => 'normal', 'textura' => 'ninguna',
    ];
    function configUI(?string $clave = null): mixed
    {
        if ($clave !== null) {
            return $GLOBALS['configUI'][$clave] ?? null;
        }
        return $GLOBALS['configUI'];
    }
    function claseFondoHTML(): string { return 'fondo-' . (configUI('fondo') ?? 'blanco'); }
    function claseTexturaHTML(): string { $t = configUI('textura'); return $t === 'ninguna' ? '' : 'textura-' . $t; }
    function claseFuenteHTML(): string { $f = configUI('fuente'); return $f === 'sistema' ? '' : 'fuente-' . $f; }
    function claseEspaciadoHTML(): string { $e = configUI('espaciado'); return $e === 'normal' ? '' : 'espaciado-' . $e; }
    function claseTamanoHTML(): string { $t = configUI('tamano'); return $t === 'normal' ? '' : 'tamano-' . $t; }
    function h(string $texto): string { return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); }
}

}
