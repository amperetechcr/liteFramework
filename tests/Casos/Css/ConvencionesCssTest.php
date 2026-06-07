<?php
use PHPUnit\Framework\TestCase;

class ConvencionesCssTest extends TestCase {

    private static array $archivosCss = [];

    public static function setUpBeforeClass(): void {
        $dir = DIRECTORIO_RAIZ . '/src/css';
        foreach (scandir($dir) as $f) {
            if (str_ends_with($f, '.css')) {
                self::$archivosCss[$f] = file_get_contents($dir . '/' . $f);
            }
        }
    }

    public function testVariablesTamanoUsanRem(): void {
        foreach (self::$archivosCss as $nombre => $css) {
            $lineas = explode("\n", $css);
            foreach ($lineas as $num => $linea) {
                $trimmed = trim($linea);
                if (preg_match('/^--tamano-[^:]+:\s*([^;]+)/', $trimmed, $m)) {
                    $this->assertStringContainsString(
                        'rem',
                        $m[1],
                        "En $nombre:" . ($num + 1) . " → $trimmed debe usar rem"
                    );
                }
            }
        }
    }

    public function testVariablesEspacioUsanRem(): void {
        foreach (self::$archivosCss as $nombre => $css) {
            $lineas = explode("\n", $css);
            foreach ($lineas as $num => $linea) {
                $trimmed = trim($linea);
                if (preg_match('/^--espacio-[^:]+:\s*([^;]+)/', $trimmed, $m)) {
                    $this->assertStringContainsString(
                        'rem',
                        $m[1],
                        "En $nombre:" . ($num + 1) . " → $trimmed debe usar rem"
                    );
                }
            }
        }
    }

    public function testColoresHardcodeadosSonIntencionales(): void {
        $excepciones = [
            'generadorPdf.css' => 'Estilos para PDF requieren colores absolutos',
            'generadorProyecto.css' => 'Estilos para proyecto generado',
            'subirArchivos.css' => 'Las etiquetas de tipo usan colores fijos por diseno',
        ];

        foreach (self::$archivosCss as $nombre => $css) {
            if (isset($excepciones[$nombre])) continue;

            $lineas = explode("\n", $css);
            $sospechosos = [];
            foreach ($lineas as $num => $linea) {
                $linea = trim($linea);
                if (preg_match('/color:\s*#[0-9a-fA-F]{3,6}/', $linea) && !str_contains($linea, 'currentColor')) {
                    $sospechosos[] = "$nombre:" . ($num + 1);
                }
            }
            $this->assertEmpty(
                $sospechosos,
                "Colores hardcodeados en $nombre:\n" . implode("\n", array_slice($sospechosos, 0, 10))
            );
        }
    }

    public function testMediaQueriesLayoutUsanMaxWidth(): void {
        foreach (self::$archivosCss as $nombre => $css) {
            preg_match_all('/@media\s+([^{]+)\{/', $css, $m);
            foreach ($m[1] as $media) {
                if (str_contains($media, 'prefers-color-scheme')) continue;
                if (str_contains($media, 'print')) continue;
                $this->assertStringContainsString(
                    'max-width',
                    $media,
                    "En $nombre: usa max-width en vez de min-width para responsive: @media $media"
                );
            }
        }
    }

    public function testNoHayComentariosPendientes(): void {
        foreach (self::$archivosCss as $nombre => $css) {
            $this->assertDoesNotMatchRegularExpression(
                '/\/\*\s*(TODO|FIXME|HACK|XXX|BUG)\b/i',
                $css,
                "En $nombre hay comentarios de codigo pendiente"
            );
        }
    }

    public function testPrefijosWebkitYMozSonValidos(): void {
        $encontrados = 0;
        foreach (self::$archivosCss as $nombre => $css) {
            preg_match_all('/^\s*-(webkit|moz)-[^:]+:/m', $css, $m);
            $encontrados += count($m[0]);
        }
        $this->assertGreaterThan(0, $encontrados, 'Debe haber al menos un prefijo -webkit- o -moz- (para progress bar)');
    }
}
