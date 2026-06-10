<?php
use PHPUnit\Framework\TestCase;

class RegistroAuditoriaTest extends TestCase {
    protected function tearDown(): void {
        ConexionBaseDatos::resetearInstancia();
    }

    public function testConsultarEventosPorModulo(): void {
        $eventos = RegistroAuditoria::consultarEventos(null, 'RegistroAuditoriaTest');
        $this->assertIsArray($eventos);
    }

    public function testConsultarEventosPorIdOperador(): void {
        $eventos = RegistroAuditoria::consultarEventos(1);
        $this->assertIsArray($eventos);
    }

    public function testObtenerModulosDevuelveArreglo(): void {
        $modulos = RegistroAuditoria::obtenerModulos();
        $this->assertIsArray($modulos);
    }

    public function testLogYConsulta(): void {
        RegistroAuditoria::info('RegistroAuditoriaTest', 'Test message', ['key' => 'value']);
        $eventos = RegistroAuditoria::consultarEventos(null, 'RegistroAuditoriaTest');
        $this->assertIsArray($eventos);
    }
}
