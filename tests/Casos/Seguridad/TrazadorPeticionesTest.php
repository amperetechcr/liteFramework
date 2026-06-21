<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Seguridad;

use LiteFramework\Seguridad\TrazadorPeticiones;

class TrazadorPeticionesTest extends \TestBase
{
    private \ReflectionClass $ref;

    public function setUp(): void
    {
        $this->ref = new \ReflectionClass(TrazadorPeticiones::class);

        $props = [
            ['name' => 'idTraza', 'value' => null],
            ['name' => 'inicio', 'value' => null],
            ['name' => 'finalizado', 'value' => false],
        ];
        foreach ($props as $item) {
            $p = $this->ref->getProperty($item['name']);
            $p->setAccessible(true);
            $p->setValue(null, $item['value']);
        }
    }

    public function testIniciarGeneraIdHex32Caracteres(): void
    {
        $id = TrazadorPeticiones::iniciar();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $id);
        $this->assertSame(32, strlen($id));
    }

    public function testIniciarDevuelveMismoIdEnSegundaLlamada(): void
    {
        $id1 = TrazadorPeticiones::iniciar();
        $id2 = TrazadorPeticiones::iniciar();
        $this->assertSame($id1, $id2);
    }

    public function testObtenerIdRetornaIdExistente(): void
    {
        $id = TrazadorPeticiones::iniciar();
        $obtenido = TrazadorPeticiones::obtenerId();
        $this->assertSame($id, $obtenido);
    }

    public function testObtenerIdAutoIniciaSiNoExiste(): void
    {
        $id = TrazadorPeticiones::obtenerId();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $id);
    }

    public function testDuracionMilisegundosRetornaFloat(): void
    {
        TrazadorPeticiones::iniciar();
        $duracion = TrazadorPeticiones::duracionMilisegundos();
        $this->assertIsFloat($duracion);
        $this->assertGreaterThanOrEqual(0, $duracion);
    }

    public function testDuracionMilisegundosSinInicioRetornaCero(): void
    {
        $p = $this->ref->getProperty('inicio');
        $p->setAccessible(true);
        $p->setValue(null, null);

        $this->assertSame(0.0, TrazadorPeticiones::duracionMilisegundos());
    }

    public function testContextoRetornaArrayConTraceId(): void
    {
        TrazadorPeticiones::iniciar();
        $ctx = TrazadorPeticiones::contexto();
        $this->assertIsArray($ctx);
        $this->assertArrayHasKey('trace_id', $ctx);
        $this->assertArrayHasKey('duracion_ms', $ctx);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $ctx['trace_id']);
        $this->assertIsFloat($ctx['duracion_ms']);
    }

    public function testFinalizarNoLanzaExcepcion(): void
    {
        TrazadorPeticiones::iniciar();
        TrazadorPeticiones::finalizar(200);
        $this->assertTrue(true);
    }

    public function testFinalizarLlamadoMultipleNoDuplica(): void
    {
        TrazadorPeticiones::iniciar();
        TrazadorPeticiones::finalizar();
        TrazadorPeticiones::finalizar();
        $this->assertTrue(true);
    }

    public function testDuracionMilisegundosDespuesDeEspera(): void
    {
        TrazadorPeticiones::iniciar();
        usleep(50000);
        $duracion = TrazadorPeticiones::duracionMilisegundos();
        $this->assertGreaterThanOrEqual(10, $duracion);
    }
}
