<?php
use PHPUnit\Framework\TestCase;

class AparienciaTest extends TestCase {
    protected function tearDown(): void {
        ConexionBaseDatos::resetearInstancia();
        $_SESSION = [];
    }

    public function testGuardarYObtenerPersonalizacion(): void {
        $_SESSION['operador_id'] = 1;
        $ctrl = new PersonalizacionApiControlador();

        [$codigo, $resp] = $ctrl->guardar([
            'paleta' => 'azul',
            'estilo' => 'moderno',
            'fondo' => 'carbon',
        ]);
        $this->assertEquals(200, $codigo);
        $this->assertTrue($resp['estado_operacion']);

        [$codigo2, $resp2] = $ctrl->obtener([]);
        $this->assertEquals(200, $codigo2);
        $this->assertEquals('azul', $resp2['datos']['paleta']);
        $this->assertEquals('moderno', $resp2['datos']['estilo']);
    }

    public function testGuardarSinSesionDevuelveError(): void {
        $_SESSION = [];
        $ctrl = new PersonalizacionApiControlador();

        [$codigo, $resp] = $ctrl->guardar(['paleta' => 'azul']);
        $this->assertEquals(401, $codigo);
        $this->assertFalse($resp['estado_operacion']);
    }

    public function testGuardarSanitizaCampos(): void {
        $_SESSION['operador_id'] = 1;
        $ctrl = new PersonalizacionApiControlador();

        [$codigo, $resp] = $ctrl->guardar([
            'paleta' => '<script>malicioso</script>',
        ]);
        $this->assertEquals(200, $codigo);
        $this->assertStringNotContainsString('<', $resp['datos']['paleta']);
    }

    public function testObtenerSinDatosDevuelveVacio(): void {
        $_SESSION['operador_id'] = 999;
        $ctrl = new PersonalizacionApiControlador();

        [$codigo, $resp] = $ctrl->obtener([]);
        $this->assertEquals(200, $codigo);
        $this->assertEquals([], $resp['datos']);
    }

    public function testGuardarTodasLasPropiedades(): void {
        $_SESSION['operador_id'] = 1;
        $ctrl = new PersonalizacionApiControlador();

        $props = [
            'paleta' => 'esmeralda',
            'estilo' => 'minimalista',
            'fondo' => 'medianoche',
            'textura' => 'punto',
            'fuente' => 'serif',
            'espaciado' => 'amplio',
            'tamano' => 'grande',
            'radio' => 'redondeado',
            'animacion' => 'lento',
            'grosor' => 'negrita',
            'sombra' => 'pronunciada',
            'tema' => 'oscuro',
        ];

        [$codigo, $resp] = $ctrl->guardar($props);
        $this->assertEquals(200, $codigo);
        $this->assertTrue($resp['estado_operacion']);

        [$codigo2, $resp2] = $ctrl->obtener([]);
        foreach ($props as $clave => $valor) {
            $this->assertEquals($valor, $resp2['datos'][$clave], "Propiedad $clave no coincide");
        }
    }

    public function testLiteJsNoTieneListasHardcodeadas(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/js/ui/lite.js');
        $this->assertStringNotContainsString('PALETAS_DISPONIBLES', $contenido);
        $this->assertStringNotContainsString('FONDOS_DISPONIBLES', $contenido);
        $this->assertStringNotContainsString('ESTILOS_DISPONIBLES', $contenido);
        $this->assertStringNotContainsString('TEXTURAS_DISPONIBLES', $contenido);
        $this->assertStringNotContainsString('FUENTES_DISPONIBLES', $contenido);
        $this->assertStringNotContainsString('ESPACIADOS_DISPONIBLES', $contenido);
        $this->assertStringNotContainsString('TAMANOS_DISPONIBLES', $contenido);
        $this->assertStringNotContainsString('RADIOS_DISPONIBLES', $contenido);
        $this->assertStringNotContainsString('ANIMACIONES_DISPONIBLES', $contenido);
        $this->assertStringNotContainsString('GROSORES_DISPONIBLES', $contenido);
        $this->assertStringNotContainsString('SOMBRAS_DISPONIBLES', $contenido);
    }

    public function testLiteJsUsaRegexParaLimpiarClases(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/src/js/ui/lite.js');
        $this->assertStringContainsString('className.replace(/\\bpaleta-\\S+/g', $contenido);
        $this->assertStringContainsString('className.replace(/\\bestilo-\\S+/g', $contenido);
        $this->assertStringContainsString('className.replace(/\\bfondo-\\S+/g', $contenido);
    }
}
