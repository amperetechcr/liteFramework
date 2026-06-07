<?php
use PHPUnit\Framework\TestCase;

class ComponentesCssTest extends TestCase {

    private static ?string $componentes = null;
    private static ?string $subirArchivos = null;
    private static ?string $utilidades = null;

    public static function setUpBeforeClass(): void {
        $base = DIRECTORIO_RAIZ . '/src/css';
        self::$componentes = file_get_contents($base . '/componentes.css');
        self::$subirArchivos = file_get_contents($base . '/subirArchivos.css');
        self::$utilidades = file_get_contents($base . '/utilidades.css');
    }

    // ─── Botón default ───

    public function testBotonDefaultExiste(): void {
        $this->assertStringContainsString('button,', self::$componentes);
    }

    public function testBotonDefaultPadding(): void {
        $this->assertStringContainsString('0.5rem 1rem', self::$componentes);
    }

    public function testBotonDefaultFontSize(): void {
        $this->assertStringContainsString('var(--tamano-base)', self::$componentes);
    }

    public function testBotonDefaultFontWeight(): void {
        $this->assertStringContainsString('font-weight: 600', self::$componentes);
    }

    // ─── Botón pequeño ───

    public function testBotonPequenoExiste(): void {
        $this->assertStringContainsString('tamano="pequeno"', self::$componentes);
    }

    public function testBotonPequenoPadding(): void {
        $this->assertStringContainsString('0.375rem 0.75rem', self::$componentes);
    }

    public function testBotonPequenoFontSize(): void {
        $this->assertStringContainsString('var(--tamano-sm)', self::$componentes);
    }

    // ─── Botón grande ───

    public function testBotonGrandeExiste(): void {
        $this->assertStringContainsString('tamano="grande"', self::$componentes);
    }

    public function testBotonGrandePadding(): void {
        $this->assertStringContainsString('0.625rem 1.5rem', self::$componentes);
    }

    public function testBotonGrandeFontSize(): void {
        $this->assertStringContainsString('var(--tamano-lg)', self::$componentes);
    }

    // ─── Variantes ───

    public function testVarianteBordeExiste(): void {
        $this->assertStringContainsString('variante="borde"', self::$componentes);
    }

    public function testVarianteSolidaExiste(): void {
        $this->assertStringContainsString('variante="solido"', self::$componentes);
    }

    public function testVariantePeligroExiste(): void {
        $this->assertStringContainsString('variante="peligro"', self::$componentes);
    }

    public function testVarianteTextoExiste(): void {
        $this->assertStringContainsString('variante="texto"', self::$componentes);
    }

    // ─── Tarjetas ───

    public function testTarjetaExiste(): void {
        $this->assertStringContainsString('.tarjeta', self::$componentes);
    }

    public function testTarjetaTieneBorderRadius(): void {
        $this->assertStringContainsString('border-radius: var(--radio-redondeado)', self::$componentes);
    }

    // ─── Etiquetas ───

    public function testEtiquetaExiste(): void {
        $this->assertStringContainsString('.etiqueta', self::$componentes);
    }

    public function testEtiquetaTieneBorderRadius(): void {
        $this->assertStringContainsString('border-radius: 20px', self::$componentes);
    }

    public function testEtiquetaTieneVariantes(): void {
        $this->assertStringContainsString('etiqueta-exito', self::$componentes);
        $this->assertStringContainsString('etiqueta-peligro', self::$componentes);
        $this->assertStringContainsString('etiqueta-marca', self::$componentes);
    }

    // ─── Formularios ───

    public function testInputExiste(): void {
        $this->assertStringContainsString('input', self::$componentes);
    }

    public function testSelectExiste(): void {
        $this->assertStringContainsString('select', self::$componentes);
    }

    // ─── SubirArchivos ───

    public function testZonaArrastreExiste(): void {
        $this->assertStringContainsString('#zona-subida', self::$subirArchivos);
    }

    public function testZonaArrastreBorderDashed(): void {
        $this->assertStringContainsString('dashed', self::$subirArchivos);
    }

    public function testTarjetaArchivoExiste(): void {
        $this->assertStringContainsString('.tarjeta-archivo', self::$subirArchivos);
    }

    public function testArchivosProgresoExiste(): void {
        $this->assertStringContainsString('.archivos-progreso-barra', self::$subirArchivos);
    }

    public function testArchivosCuotaExiste(): void {
        $this->assertStringContainsString('.archivos-barra-cuota', self::$subirArchivos);
    }

    // ─── Utilidades ───

    public function testOcultoExiste(): void {
        $this->assertStringContainsString('.oculto', self::$utilidades);
    }

    public function testAnchoCompletoExiste(): void {
        $this->assertStringContainsString('.ancho-completo', self::$utilidades);
    }

    public function testUtilidadesFlexExisten(): void {
        $this->assertStringContainsString('.flex', self::$utilidades);
        $this->assertStringContainsString('.flex-columna', self::$utilidades);
        $this->assertStringContainsString('.flex-envolver', self::$utilidades);
        $this->assertStringContainsString('.brecha-normal', self::$utilidades);
    }
}
