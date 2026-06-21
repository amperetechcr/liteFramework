<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Errores;

use LiteFramework\Servicios\ContextoError;
use LiteFramework\Servicios\DiagnosticoError;

class DiagnosticoErrorTest extends \TestBase
{
    private function crearContexto(string $mensaje, string $codigo = 'error'): ContextoError
    {
        return ContextoError::capturar($codigo, $mensaje, __FILE__, __LINE__);
    }

    public function testDiagnosticarDevuelveArrayConEstructuraEsperada(): void
    {
        $ctx = $this->crearContexto('Base table or view not found: test_table');
        $resultado = DiagnosticoError::diagnosticar($ctx);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('diagnosticos', $resultado);
        $this->assertArrayHasKey('tieneRemedio', $resultado);
        $this->assertArrayHasKey('reparaciones', $resultado);
        $this->assertArrayHasKey('sugerencias', $resultado);
        $this->assertArrayHasKey('accion', $resultado);
    }

    public function testDiagnosticarConErrorConocidoDevuelveDiagnosticos(): void
    {
        $ctx = $this->crearContexto("Table 'lite.test_table' doesn't exist");
        $resultado = DiagnosticoError::diagnosticar($ctx);

        $this->assertNotEmpty($resultado['diagnosticos']);
        $this->assertSame('tabla_faltante', $resultado['diagnosticos'][0]['tipo']);
    }

    public function testDiagnosticarConMensajeInexistenteDevuelveArrayVacio(): void
    {
        $ctx = $this->crearContexto('Some random message with no known pattern');
        $resultado = DiagnosticoError::diagnosticar($ctx);

        $this->assertEmpty($resultado['diagnosticos']);
        $this->assertFalse($resultado['tieneRemedio']);
    }

    public function testDiagnosticarConConexionRechazada(): void
    {
        $ctx = $this->crearContexto('SQLSTATE[HY000] [2002] Connection refused');
        $resultado = DiagnosticoError::diagnosticar($ctx);

        $this->assertNotEmpty($resultado['diagnosticos']);
        $this->assertSame('conexion', $resultado['diagnosticos'][0]['tipo']);
    }

    public function testDiagnosticarConColumnaFaltante(): void
    {
        $ctx = $this->crearContexto("Unknown column 'test_col' in 'field list'");
        $resultado = DiagnosticoError::diagnosticar($ctx);

        $this->assertNotEmpty($resultado['diagnosticos']);
        $this->assertSame('columna_faltante', $resultado['diagnosticos'][0]['tipo']);
    }

    public function testDiagnosticarConArchivoGrande(): void
    {
        $ctx = $this->crearContexto('UPLOAD_ERR_INI_SIZE upload_max_filesize exceeded');
        $resultado = DiagnosticoError::diagnosticar($ctx);

        $this->assertNotEmpty($resultado['diagnosticos']);
        $this->assertSame('archivo_demasiado_grande', $resultado['diagnosticos'][0]['tipo']);
    }

    public function testDiagnosticarConTokenCSRF(): void
    {
        $ctx = $this->crearContexto('CSRF token mismatch', 'token_invalido');
        $resultado = DiagnosticoError::diagnosticar($ctx);

        $this->assertNotEmpty($resultado['diagnosticos']);
        $this->assertSame('csrf_expirado', $resultado['diagnosticos'][0]['tipo']);
    }

    public function testDiagnosticarConMemoriaInsuficiente(): void
    {
        $ctx = $this->crearContexto('Allowed memory size of 134217728 bytes exhausted');
        $resultado = DiagnosticoError::diagnosticar($ctx);

        $this->assertNotEmpty($resultado['diagnosticos']);
        $this->assertSame('memoria_insuficiente', $resultado['diagnosticos'][0]['tipo']);
    }

    public function testDiagnosticarDeadlock(): void
    {
        $ctx = $this->crearContexto('Deadlock found when trying to get lock; try restarting transaction');
        $resultado = DiagnosticoError::diagnosticar($ctx);

        $this->assertNotEmpty($resultado['diagnosticos']);
        $this->assertSame('deadlock', $resultado['diagnosticos'][0]['tipo']);
        $this->assertTrue($resultado['tieneRemedio']);
    }

    public function testDiagnosticarConClaseFaltante(): void
    {
        $ctx = $this->crearContexto("Class 'NonExistentClass' not found");
        $resultado = DiagnosticoError::diagnosticar($ctx);

        $this->assertNotEmpty($resultado['diagnosticos']);
        $this->assertSame('clase_faltante', $resultado['diagnosticos'][0]['tipo']);
    }

    public function testDiagnosticarConSesionExpirada(): void
    {
        $ctx = $this->crearContexto('Su sesion ha expirado', 'sesion_expirada');
        $resultado = DiagnosticoError::diagnosticar($ctx);

        $this->assertNotEmpty($resultado['diagnosticos']);
        $this->assertSame('sesion_expirada', $resultado['diagnosticos'][0]['tipo']);
    }

    public function testDiagnosticarContieneVerificadorEnCadaDiagnostico(): void
    {
        $ctx = $this->crearContexto("Table 'lite.test_table' doesn't exist");
        $resultado = DiagnosticoError::diagnosticar($ctx);

        foreach ($resultado['diagnosticos'] as $diag) {
            $this->assertArrayHasKey('verificador', $diag);
        }
    }
}
