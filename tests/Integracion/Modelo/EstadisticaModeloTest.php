<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Modelo;

require_once __DIR__ . '/TestCaseDb.php';

use LiteFramework\Modelos\Estadistica;
use LiteFramework\Modelos\Operador;

class EstadisticaModeloTest extends TestCaseDb
{
    private int $operadorId;

    public function setUp(): void
    {
        parent::setUp();
        $this->bd->exec("DELETE FROM estadistica");
        foreach (['cache_ttl', 'en_dashboard', 'dashboard_orden', 'ultima_ejecucion'] as $col) {
            try {
                $this->bd->exec("ALTER TABLE estadistica ADD COLUMN {$col} TEXT");
            } catch (\PDOException $e) {
            }
        }
        $op = Operador::crear([
            'nombre_completo' => 'Est Tester',
            'correo_electronico' => 'est_' . uniqid() . '@test.com',
            'clave_acceso' => 'SecurePass1!',
            'id_rol' => 2,
        ]);
        $this->operadorId = $op->id_operador;
    }

    public function testCrearEstadistica(): void
    {
        $est = Estadistica::crear([
            'titulo' => 'Test Stats',
            'consulta_sql' => 'SELECT 1',
            'tipo_visualizacion' => 'tarjetas',
            'id_operador' => $this->operadorId,
        ]);
        $this->assertNotNull($est->id_estadistica);
        $this->assertSame('Test Stats', $est->titulo);
    }

    public function testCrearConTodosLosCampos(): void
    {
        $est = Estadistica::crear([
            'titulo' => 'Full',
            'descripcion' => 'Desc',
            'consulta_sql' => 'SELECT 2',
            'tipo_visualizacion' => 'tabla',
            'columnas_mostrar' => 'col1,col2',
            'configuracion_visual' => '{"color":"red"}',
            'cache_ttl' => 300,
            'en_dashboard' => 1,
            'dashboard_orden' => 1,
            'id_operador' => $this->operadorId,
        ]);
        $this->assertSame('Full', $est->titulo);
        $this->assertSame('Desc', $est->descripcion);
    }

    public function testCacheTtlSeteable(): void
    {
        $est = Estadistica::crear([
            'titulo' => 'Cache Test',
            'consulta_sql' => 'SELECT 1',
            'cache_ttl' => 600,
            'id_operador' => $this->operadorId,
        ]);
        $this->assertSame(600, $est->cache_ttl);
    }

    public function testEnDashboardSeteable(): void
    {
        $est = Estadistica::crear([
            'titulo' => 'Dash Test',
            'consulta_sql' => 'SELECT 1',
            'en_dashboard' => 1,
            'id_operador' => $this->operadorId,
        ]);
        $this->assertSame(1, $est->en_dashboard);
    }

    public function testDashboardOrdenSeteable(): void
    {
        $est = Estadistica::crear([
            'titulo' => 'Orden Test',
            'consulta_sql' => 'SELECT 1',
            'dashboard_orden' => 5,
            'id_operador' => $this->operadorId,
        ]);
        $this->assertSame(5, $est->dashboard_orden);
    }

    public function testUltimaEjecucionSeteable(): void
    {
        $est = new Estadistica();
        $est->ultima_ejecucion = '2024-01-15 10:30:00';
        $this->assertSame('2024-01-15 10:30:00', $est->ultima_ejecucion);
    }

    public function testBuscarEstadistica(): void
    {
        $est = Estadistica::crear([
            'titulo' => 'Buscar',
            'consulta_sql' => 'SELECT 1',
            'id_operador' => $this->operadorId,
        ]);
        $encontrado = Estadistica::buscar($est->id_estadistica);
        $this->assertNotNull($encontrado);
        $this->assertSame('Buscar', $encontrado->titulo);
    }

    public function testGuardarUpdate(): void
    {
        $est = Estadistica::crear([
            'titulo' => 'Orig',
            'consulta_sql' => 'SELECT 1',
            'id_operador' => $this->operadorId,
        ]);
        $est->titulo = 'Modificado';
        $est->guardar();
        $recargado = Estadistica::buscar($est->id_estadistica);
        $this->assertSame('Modificado', $recargado->titulo);
    }

    public function testEliminar(): void
    {
        $est = Estadistica::crear([
            'titulo' => 'Del',
            'consulta_sql' => 'SELECT 1',
            'id_operador' => $this->operadorId,
        ]);
        $id = $est->id_estadistica;
        $est->eliminar();
        $this->assertNull(Estadistica::buscar($id));
    }

    public function testPaginarConAliasEstadistica(): void
    {
        Estadistica::crear(['titulo' => 'E1', 'consulta_sql' => 'SELECT 1', 'id_operador' => $this->operadorId]);
        Estadistica::crear(['titulo' => 'E2', 'consulta_sql' => 'SELECT 2', 'id_operador' => $this->operadorId]);
        $res = Estadistica::paginar(
            1, 10,
            ['titulo LIKE' => '%E%'],
            'estadistica.id_estadistica, estadistica.titulo',
            ''
        );
        $this->assertSame(2, $res['total']);
    }

    public function testUltimaEjecucionFormateada(): void
    {
        $est = new Estadistica();
        $est->ultima_ejecucion = '2024-06-01 12:00:00';
        $this->assertStringMatchesFormat('%d/%d/%d %d:%d', $est->ultimaEjecucionFormateada());
    }

    public function testUltimaEjecucionFormateadaNull(): void
    {
        $est = Estadistica::crear([
            'titulo' => 'NullFmt',
            'consulta_sql' => 'SELECT 1',
            'id_operador' => $this->operadorId,
        ]);
        $this->assertSame("\u{2014}", $est->ultimaEjecucionFormateada());
    }
}
