<?php
use PHPUnit\Framework\TestCase;

class PaginadorTest extends TestCase {
    private array $getOriginal;

    protected function setUp(): void {
        $this->getOriginal = $_GET;
    }

    protected function tearDown(): void {
        $_GET = $this->getOriginal;
    }

    public function testCrearConDatosBasicos(): void {
        $_GET = [];
        $paginador = Paginador::crear(50, 10, '/test');
        $this->assertEquals(5, $paginador->totalPaginas);
        $this->assertEquals(1, $paginador->paginaActual);
        $this->assertEquals(10, $paginador->porPagina);
        $this->assertEquals(50, $paginador->totalRegistros);
    }

    public function testOffset(): void {
        $_GET = ['pagina' => 3];
        $paginador = Paginador::crear(50, 10, '/test');
        $this->assertEquals(20, $paginador->offset());
    }

    public function testPaginaDesdeGet(): void {
        $_GET = ['pagina' => 3];
        $paginador = Paginador::crear(50, 10, '/test');
        $this->assertEquals(3, $paginador->paginaActual);
    }

    public function testPaginaInvalidaSeConvierteEn1(): void {
        $_GET = ['pagina' => -1];
        $paginador = Paginador::crear(50, 10, '/test');
        $this->assertEquals(1, $paginador->paginaActual);
    }

    public function testPaginaExcedenteSeConvierteEnUltima(): void {
        $_GET = ['pagina' => 999];
        $paginador = Paginador::crear(50, 10, '/test');
        $this->assertEquals(5, $paginador->paginaActual);
    }

    public function testAArreglo(): void {
        $_GET = ['pagina' => 2];
        $paginador = Paginador::crear(50, 10, '/test');
        $arr = $paginador->aArreglo();
        $this->assertIsArray($arr);
        $this->assertEquals(2, $arr['pagina_actual']);
        $this->assertEquals(10, $arr['por_pagina']);
        $this->assertEquals(5, $arr['total_paginas']);
        $this->assertEquals(50, $arr['total_registros']);
    }

    public function testSinRegistros(): void {
        $_GET = [];
        $paginador = Paginador::crear(0, 10, '/test');
        $this->assertEquals(1, $paginador->totalPaginas);
        $this->assertEquals(1, $paginador->paginaActual);
        $this->assertEquals(0, $paginador->offset());
    }
}
