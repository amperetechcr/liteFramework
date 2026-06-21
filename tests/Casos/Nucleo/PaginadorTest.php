<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Nucleo;

use LiteFramework\Nucleo\Paginador;

class PaginadorTest extends \TestBase
{
    private array $getRespaldo;
    private array $serverRespaldo;

    public function setUp(): void
    {
        $this->getRespaldo = $_GET;
        $this->serverRespaldo = $_SERVER;
        $_GET = [];
        $_SERVER['REQUEST_URI'] = '/test';
    }

    public function tearDown(): void
    {
        $_GET = $this->getRespaldo;
        $_SERVER = $this->serverRespaldo;
    }

    public function testTotalPaginasMenorOIgual1DevuelveVacio(): void
    {
        $p = Paginador::crear(5, 10);
        $html = $p->render();
        $this->assertSame('', $html);
    }

    public function testRenderIncluyeElementosNavegacion(): void
    {
        $p = Paginador::crear(100, 10);
        $html = $p->render();
        $this->assertStringContainsString('nav', $html);
        $this->assertStringContainsString('aria-label', $html);
        $this->assertStringContainsString('Paginación', $html);
    }

    public function testEllipsisSeRenderiza(): void
    {
        $p = Paginador::crear(200, 10, null, 3);
        $_GET['pagina'] = 10;
        $html = $p->render();
        $this->assertStringContainsString('...', $html);
    }

    public function testAriaCurrentEnPaginaActual(): void
    {
        $p = Paginador::crear(50, 10);
        $_GET['pagina'] = 3;
        $html = $p->render();
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function testXssEnUrlEscapado(): void
    {
        $_SERVER['REQUEST_URI'] = '/<script>alert(1)</script>';
        $p = Paginador::crear(50, 10);
        $html = $p->render();
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testTextosPermiteContenido(): void
    {
        $p = Paginador::crear(50, 10);
        $p->textos('<<', '>>', '>>', '>>');
        $html = $p->render();
        $this->assertStringContainsString('<<', $html);
        $this->assertStringContainsString('>>', $html);
    }

    public function testTextosPersonalizados(): void
    {
        $p = Paginador::crear(50, 10);
        $p->textos('Inicio', 'Atras', 'Adelante', 'Final');
        $html = $p->render();
        $this->assertStringContainsString('Inicio', $html);
        $this->assertStringContainsString('Atras', $html);
        $this->assertStringContainsString('Adelante', $html);
        $this->assertStringContainsString('Final', $html);
    }

    public function testToString(): void
    {
        $p = Paginador::crear(100, 10);
        $this->assertSame($p->render(), (string)$p);
    }

    public function testEnlaceDeshabilitadoNoTieneUrl(): void
    {
        $p = Paginador::crear(50, 10);
        $_GET['pagina'] = 1;
        $html = $p->render();
        $this->assertStringContainsString('paginador-deshabilitado', $html);
    }

    public function testRenderNoContieneEnlacesInvalidos(): void
    {
        $p = Paginador::crear(30, 10);
        $html = $p->render();
        $this->assertDoesNotMatchRegularExpression('/href=""(?!>)/', $html);
    }
}
