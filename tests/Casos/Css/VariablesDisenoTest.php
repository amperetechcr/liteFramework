<?php
use PHPUnit\Framework\TestCase;

class VariablesDisenoTest extends TestCase {

    private static ?string $temaCss = null;

    public static function setUpBeforeClass(): void {
        self::$temaCss = file_get_contents(DIRECTORIO_RAIZ . '/src/css/tema.css');
    }

    public function testVariablesTamanoExisten(): void {
        $vars = [
            '--tamano-2xs', '--tamano-xs', '--tamano-sm', '--tamano-md',
            '--tamano-base', '--tamano-lg', '--tamano-xl', '--tamano-2xl',
            '--tamano-3xl', '--tamano-4xl',
        ];
        foreach ($vars as $var) {
            $this->assertStringContainsString(
                $var,
                self::$temaCss,
                "La variable $var debe estar definida en tema.css"
            );
        }
    }

    public function testVariablesEspacioExisten(): void {
        $vars = [
            '--espacio-minimo', '--espacio-pequeno', '--espacio-normal',
            '--espacio-mediano', '--espacio-grande', '--espacio-gigante',
        ];
        foreach ($vars as $var) {
            $this->assertStringContainsString(
                $var,
                self::$temaCss,
                "La variable $var debe estar definida en tema.css"
            );
        }
    }

    public function testVariablesColorExisten(): void {
        $vars = [
            '--color-marca', '--color-marca-hover', '--color-marca-claro',
            '--color-exito', '--color-advertencia', '--color-peligro', '--color-info',
        ];
        foreach ($vars as $var) {
            $this->assertStringContainsString(
                $var,
                self::$temaCss,
                "La variable $var debe estar definida en tema.css"
            );
        }
    }

    public function testVariablesUsanRem(): void {
        $lines = explode("\n", self::$temaCss);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^--tamano-[^:]+:\s*([^;]+)/', $line, $m)) {
                $this->assertStringContainsString(
                    'rem',
                    $m[1],
                    "La variable $line debe usar rem, no px"
                );
            }
        }
    }

    public function testVariablesSombraExisten(): void {
        $vars = ['--sombra-suave', '--sombra-flotante', '--sombra-elevada'];
        foreach ($vars as $var) {
            $this->assertStringContainsString(
                $var,
                self::$temaCss,
                "La variable $var debe estar definida en tema.css"
            );
        }
    }
}
