<?php

declare(strict_types=1);

namespace
{
    if (!function_exists('h')) {
        function h(string $texto): string
        {
            return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
        }
    }
}

namespace LiteFramework\Tests\Casos\Servicios
{
        use LiteFramework\Servicios\GeneradorPdf;

    class GeneradorPDFTest extends \TestBase
    {
        public function testConstructorConOrientacionYTamanoValidos(): void
        {
            $pdf = new GeneradorPdf('horizontal', 'Carta');
            $ref = new \ReflectionClass($pdf);
            $this->assertSame('horizontal', $ref->getProperty('orientacion')->getValue($pdf));
            $this->assertSame('Carta', $ref->getProperty('tamanoPagina')->getValue($pdf));
        }

        public function testConstructorConOrientacionInvalidaSeIgnora(): void
        {
            $pdf = new GeneradorPdf('invalida', 'A4');
            $ref = new \ReflectionClass($pdf);
            $this->assertSame('vertical', $ref->getProperty('orientacion')->getValue($pdf));
        }

        public function testEstablecerTituloEncabezadoPie(): void
        {
            $pdf = new GeneradorPdf();
            $pdf->establecerTitulo('Mi Titulo');
            $pdf->establecerEncabezado('<header>Enc</header>');
            $pdf->establecerPie('<footer>Pie</footer>');

            $ref = new \ReflectionClass($pdf);
            $this->assertSame('Mi Titulo', $ref->getProperty('titulo')->getValue($pdf));
            $this->assertSame('<header>Enc</header>', $ref->getProperty('encabezadoHtml')->getValue($pdf));
            $this->assertSame('<footer>Pie</footer>', $ref->getProperty('pieHtml')->getValue($pdf));
        }

        public function testSinEstilosRetornaThis(): void
        {
            $pdf = new GeneradorPdf();
            $ret = $pdf->sinEstilos();
            $this->assertSame($pdf, $ret);

            $ref = new \ReflectionClass($pdf);
            $this->assertFalse($ref->getProperty('conEstilos')->getValue($pdf));
        }

        public function testAgregarTituloConNivel1A6(): void
        {
            $pdf = new GeneradorPdf();
            $pdf->agregarTitulo('H1', 1);
            $pdf->agregarTitulo('H6', 6);
            $this->assertStringContainsString('<h1>H1</h1>', $pdf->obtenerContenido());
            $this->assertStringContainsString('<h6>H6</h6>', $pdf->obtenerContenido());
        }

        public function testAgregarTituloNivelFueraDeRangoSeAjusta(): void
        {
            $pdf = new GeneradorPdf();
            $pdf->agregarTitulo('muy alto', 99);
            $pdf->agregarTitulo('muy bajo', 0);
            $contenido = $pdf->obtenerContenido();
            $this->assertStringContainsString('<h6>muy alto</h6>', $contenido);
            $this->assertStringContainsString('<h1>muy bajo</h1>', $contenido);
        }

        public function testAgregarParrafoHtmlTablaRetornanThis(): void
        {
            $pdf = new GeneradorPdf();
            $this->assertSame($pdf, $pdf->agregarParrafo('texto'));
            $this->assertSame($pdf, $pdf->agregarHtml('<div>html</div>'));
            $this->assertSame($pdf, $pdf->agregarTabla([['a', 'b']], ['Col1', 'Col2']));
            $this->assertSame($pdf, $pdf->agregarLineaSeparadora());
        }

        public function testTablaConAnchosQueNoSuman100(): void
        {
            $pdf = new GeneradorPdf();
            $pdf->agregarTabla([['a', 'b']], ['X', 'Y'], [30, 30]);
            $html = $pdf->generarHtml();
            $this->assertStringContainsString('width:30%', $html);
        }

        public function testContenidoVacioGeneraAlgo(): void
        {
            $pdf = new GeneradorPdf();
            $html = $pdf->generarHtml();
            $this->assertNotEmpty($html);
            $this->assertStringContainsString('<!DOCTYPE html>', $html);
        }

        public function testGenerarRetornaString(): void
        {
            $pdf = new GeneradorPdf();
            $pdf->agregarParrafo('Contenido de prueba');
            $html = $pdf->generarHtml();
            $this->assertIsString($html);
            $this->assertStringContainsString('Contenido de prueba', $html);
        }

        public function testFluentInterfaceRetornaThis(): void
        {
            $pdf = new GeneradorPdf();
            $this->assertSame($pdf, $pdf->establecerTitulo('T'));
            $this->assertSame($pdf, $pdf->establecerEncabezado('E'));
            $this->assertSame($pdf, $pdf->establecerPie('P'));
            $this->assertSame($pdf, $pdf->agregarTitulo('H'));
            $this->assertSame($pdf, $pdf->agregarHtml('<p>p</p>'));
            $this->assertSame($pdf, $pdf->agregarSaltoPagina());
        }
    }
}
