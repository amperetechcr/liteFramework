<?php
use PHPUnit\Framework\TestCase;

class ConsistenciaTest extends TestCase {
    public function testValoresUiPhpTienenEstructuraCompleta(): void {
        $configUI = require DIRECTORIO_RAIZ . '/servidor/config/ui.php';
        $this->assertArrayHasKey('paletas_validas', $configUI);
        $this->assertArrayHasKey('estilos_validos', $configUI);
        $this->assertArrayHasKey('fondos_validos', $configUI);
        $this->assertArrayHasKey('fuentes_validas', $configUI);
        $this->assertArrayHasKey('espaciados_validos', $configUI);
        $this->assertArrayHasKey('tamanos_validos', $configUI);
    }

    public function testPaletasContienenValoresEsperados(): void {
        $configUI = require DIRECTORIO_RAIZ . '/servidor/config/ui.php';
        $this->assertContains('indigo', $configUI['paletas_validas']);
        $this->assertContains('azul', $configUI['paletas_validas']);
        $this->assertContains('fucsia', $configUI['paletas_validas']);
        $this->assertCount(13, $configUI['paletas_validas']);
    }

    public function testEstilosContienenNuevosValores(): void {
        $configUI = require DIRECTORIO_RAIZ . '/servidor/config/ui.php';
        $this->assertContains('3d-moderno', $configUI['estilos_validos']);
        $this->assertContains('jugueton', $configUI['estilos_validos']);
        $this->assertContains('corporativo', $configUI['estilos_validos']);
    }

    public function testEncabezadoInyectaVALORES_UI(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php');
        $this->assertStringContainsString('window.VALORES_UI', $contenido);
        $this->assertStringContainsString('json_encode', $contenido);
    }

    public function testIndexPhpUsaConfigUIEnLugarDeListasDirectas(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/index.php');
        $this->assertStringContainsString("\$configUI['paletas_validas']", $contenido);
        $this->assertStringNotContainsString("\$paletasValidas = ['", $contenido);
    }

    public function testIndexPhpMergeaPersonalizacionSession(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/index.php');
        $this->assertStringContainsString("\$_SESSION['personalizacion_ui']", $contenido);
        $this->assertStringContainsString('array_merge', $contenido);
    }

    public function testAparienciaJsUsaVALORES_UI(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/js/modulos/apariencia.js');
        $this->assertStringContainsString('window.VALORES_UI', $contenido);
        $this->assertStringNotContainsString('window.FONDOS_DISPONIBLES', $contenido);
    }

    public function testLimpiarTodosErroresUsaRemoveEnVezDeTextContent(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/js/seguridad.js');
        $this->assertStringContainsString("el.remove()", $contenido);
        $this->assertStringContainsString(
            "function limpiarTodosErrores",
            $contenido
        );
    }

    public function testFormularioAutenticacionTokenSeActualizaEnCatch(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/js/api/formularioAutenticacion.js');
        $this->assertStringContainsString('e.respuesta.nuevo_token', $contenido);
        $this->assertStringContainsString("[name=\"token_peticion\"]", $contenido);
    }

    public function testSeguridadTieneFuncionLimpiarTodosErrores(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/js/seguridad.js');
        $this->assertStringContainsString('function limpiarTodosErrores', $contenido);
    }

    public function testFormularioAutenticacionTieneVentanaDeGraciaToken(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/js/api/formularioAutenticacion.js');
        $this->assertStringContainsString("nuevo_token", $contenido);
        $this->assertStringContainsString("forEach", $contenido);
    }

    public function testPaginaInicioCargaMismosCssQueEncabezado(): void {
        $encabezado = file_get_contents(DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php');
        $inicio = file_get_contents(DIRECTORIO_RAIZ . '/src/modulos/inicio/inicio.php');

        preg_match_all('/href="[^"]+\/src\/css\/([^"]+\.css)"/', $encabezado, $cssEncabezado);
        preg_match_all('/href="[^"]+\/src\/css\/([^"]+\.css)"/', $inicio, $cssInicio);

        $faltantes = array_diff($cssEncabezado[1], $cssInicio[1]);
        $this->assertEmpty(
            $faltantes,
            'A inicio.php le faltan estos CSS: ' . implode(', ', $faltantes)
        );
    }

    public function testPaginaInicioSesionCargaCssNecesarios(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/vistas/inicio_sesion.php');
        $this->assertStringContainsString('modales.css', $contenido);
        $this->assertStringContainsString('paletas.css', $contenido);
        $this->assertStringContainsString('apariencia.css', $contenido);
    }

    public function testFaviconPresenteEnTodasLasPaginas(): void {
        $archivos = [
            '/src/plantillas/encabezado.php',
            '/src/vistas/inicio_sesion.php',
            '/src/vistas/mantenimiento.php',
            '/src/error.php',
            '/src/modulos/inicio/inicio.php',
        ];
        foreach ($archivos as $ruta) {
            $contenido = file_get_contents(DIRECTORIO_RAIZ . $ruta);
            $this->assertStringContainsString(
                'favicon.png',
                $contenido,
                "Falta favicon en $ruta"
            );
        }
    }

    public function testProcesarPeticionPostSiempreIncluyeNuevoToken(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php');
        $this->assertStringContainsString("\$respuestaServidor['nuevo_token']", $contenido);
        $this->assertStringContainsString("\$datos['nuevo_token']", $contenido);
        $this->assertStringContainsString("nuevoTokenRotado", $contenido);
    }
}
