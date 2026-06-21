<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Modelo;

require_once __DIR__ . '/TestCaseDb.php';

use LiteFramework\Modelos\DocumentoPdf;
use LiteFramework\Modelos\Operador;

class DocumentoPdfModeloTest extends TestCaseDb
{
    private int $operadorId;

    public function setUp(): void
    {
        parent::setUp();
        $this->bd->exec("DELETE FROM documento_pdf");
        $op = Operador::crear([
            'nombre_completo' => 'Doc PDF Tester',
            'correo_electronico' => 'docpdf_' . uniqid() . '@test.com',
            'clave_acceso' => 'SecurePass1!',
            'id_rol' => 2,
        ]);
        $this->operadorId = $op->id_operador;
    }

    public function testCrearDocumento(): void
    {
        $doc = DocumentoPdf::crear([
            'titulo' => 'Test PDF',
            'contenido_html' => '<p>Contenido</p>',
            'id_operador' => $this->operadorId,
        ]);
        $this->assertNotNull($doc->id_documento);
        $this->assertSame('Test PDF', $doc->titulo);
    }

    public function testCrearConTodosLosCampos(): void
    {
        $doc = DocumentoPdf::crear([
            'titulo' => 'Full Fields',
            'contenido_html' => '<p>Full</p>',
            'id_operador' => $this->operadorId,
        ]);
        $this->assertNotNull($doc->id_documento);
        $this->assertSame('Full Fields', $doc->titulo);
        $this->assertSame('<p>Full</p>', $doc->contenido_html);
    }

    public function testBuscarDocumento(): void
    {
        $doc = DocumentoPdf::crear([
            'titulo' => 'Buscar Test',
            'contenido_html' => '<p>Buscar</p>',
            'id_operador' => $this->operadorId,
        ]);
        $encontrado = DocumentoPdf::buscar($doc->id_documento);
        $this->assertNotNull($encontrado);
        $this->assertSame('Buscar Test', $encontrado->titulo);
    }

    public function testBuscarInexistente(): void
    {
        $this->assertNull(DocumentoPdf::buscar(9999));
    }

    public function testGuardarInsert(): void
    {
        $doc = new DocumentoPdf();
        $doc->titulo = 'Insert Doc';
        $doc->contenido_html = '<p>Insert</p>';
        $doc->id_operador = $this->operadorId;
        $result = $doc->guardar();
        $this->assertTrue($result);
        $this->assertNotNull($doc->id_documento);
    }

    public function testGuardarUpdate(): void
    {
        $doc = DocumentoPdf::crear([
            'titulo' => 'Original',
            'contenido_html' => '<p>Original</p>',
            'id_operador' => $this->operadorId,
        ]);
        $doc->titulo = 'Actualizado';
        $doc->guardar();
        $recargado = DocumentoPdf::buscar($doc->id_documento);
        $this->assertSame('Actualizado', $recargado->titulo);
    }

    public function testEliminar(): void
    {
        $doc = DocumentoPdf::crear([
            'titulo' => 'Eliminar',
            'contenido_html' => '<p>Eliminar</p>',
            'id_operador' => $this->operadorId,
        ]);
        $id = $doc->id_documento;
        $doc->eliminar();
        $this->assertNull(DocumentoPdf::buscar($id));
    }

    public function testPaginar(): void
    {
        DocumentoPdf::crear(['titulo' => 'A1', 'contenido_html' => '<p>1</p>', 'id_operador' => $this->operadorId]);
        DocumentoPdf::crear(['titulo' => 'A2', 'contenido_html' => '<p>2</p>', 'id_operador' => $this->operadorId]);
        $res = DocumentoPdf::paginar(1, 1);
        $this->assertCount(1, $res['datos']);
        $this->assertSame(2, $res['total']);
    }

    public function testPaginarConAliasDocumentacion(): void
    {
        DocumentoPdf::crear(['titulo' => 'Alias Test', 'contenido_html' => '<p>A</p>', 'id_operador' => $this->operadorId]);
        $res = DocumentoPdf::paginar(
            1, 10,
            ['titulo LIKE' => '%Alias%'],
            'documento_pdf.id_documento, documento_pdf.titulo',
            ''
        );
        $this->assertSame(1, $res['total']);
        $this->assertCount(1, $res['datos']);
    }

    public function testOperadorRelationship(): void
    {
        $doc = DocumentoPdf::crear([
            'titulo' => 'Op Rel',
            'contenido_html' => '<p>Op</p>',
            'id_operador' => $this->operadorId,
        ]);
        $op = $doc->operador();
        $this->assertNotNull($op);
    }

    public function testFechaFormateada(): void
    {
        $doc = DocumentoPdf::crear([
            'titulo' => 'Fecha Test',
            'contenido_html' => '<p>Fecha</p>',
            'id_operador' => $this->operadorId,
        ]);
        $recargado = DocumentoPdf::buscar($doc->id_documento);
        $this->assertNotNull($recargado);
        $this->assertStringMatchesFormat('%d/%d/%d %d:%d', $recargado->fechaFormateada());
    }

    public function testListarConFiltrosConBusqueda(): void
    {
        DocumentoPdf::crear(['titulo' => 'Encontrable', 'contenido_html' => '<p>X</p>', 'id_operador' => $this->operadorId]);
        DocumentoPdf::crear(['titulo' => 'Otro', 'contenido_html' => '<p>Y</p>', 'id_operador' => $this->operadorId]);
        $res = DocumentoPdf::listarConFiltros('Encontrable', 1, 10);
        $this->assertSame(1, $res['total']);
    }

    public function testListarConFiltrosSinBusqueda(): void
    {
        DocumentoPdf::crear(['titulo' => 'Sin1', 'contenido_html' => '<p>S1</p>', 'id_operador' => $this->operadorId]);
        DocumentoPdf::crear(['titulo' => 'Sin2', 'contenido_html' => '<p>S2</p>', 'id_operador' => $this->operadorId]);
        $res = DocumentoPdf::listarConFiltros('', 1, 10);
        $this->assertSame(2, $res['total']);
    }
}
