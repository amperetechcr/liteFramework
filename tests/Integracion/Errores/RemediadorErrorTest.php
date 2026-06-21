<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Integracion\Errores;

use LiteFramework\Servicios\ContextoError;
use LiteFramework\Servicios\RemediadorError;
use LiteFramework\Servicios\DiagnosticoError;

class RemediadorErrorTest extends \TestBase
{
    public function testIntentarConDiagnosticoValido(): void
    {
        $ctx = ContextoError::capturar('error', "Table 'lite.test_table' doesn't exist", __FILE__, __LINE__);
        $diagnostico = DiagnosticoError::diagnosticar($ctx);

        $resultado = RemediadorError::intentar($ctx, $diagnostico);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('ejecutados', $resultado);
        $this->assertArrayHasKey('pendientes', $resultado);
        $this->assertArrayHasKey('errores', $resultado);
    }

    public function testIntentarConDiagnosticoVacio(): void
    {
        $ctx = ContextoError::capturar('error', 'No pattern match', __FILE__, __LINE__);
        $diagnostico = ['diagnosticos' => []];

        $resultado = RemediadorError::intentar($ctx, $diagnostico);

        $this->assertEmpty($resultado['ejecutados']);
        $this->assertEmpty($resultado['pendientes']);
        $this->assertEmpty($resultado['errores']);
    }

    public function testIntentarConDiagnosticoSinDiagnosticos(): void
    {
        $ctx = ContextoError::capturar('error', 'test', __FILE__, __LINE__);
        $resultado = RemediadorError::intentar($ctx, []);

        $this->assertEmpty($resultado['ejecutados']);
    }

    public function testEjecutarReparacionConTipoConocido(): void
    {
        $resultado = RemediadorError::ejecutarReparacion('base_datos', ['param' => 'value']);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('exito', $resultado);
    }

    public function testEjecutarReparacionConTipoDesconocido(): void
    {
        $resultado = RemediadorError::ejecutarReparacion('tipo_inexistente', []);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['exito']);
        $this->assertStringContainsString('No se encontr', $resultado['mensaje']);
    }

    public function testEjecutarReparacionConArchivos(): void
    {
        $resultado = RemediadorError::ejecutarReparacion('archivos', ['tipo' => 'tmp_dir_faltante']);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('exito', $resultado);
        $this->assertArrayHasKey('mensaje', $resultado);
    }

    public function testEjecutarReparacionConSeguridad(): void
    {
        $resultado = RemediadorError::ejecutarReparacion('seguridad', ['param' => 'x']);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('exito', $resultado);
    }

    public function testEjecutarReparacionConSistema(): void
    {
        $resultado = RemediadorError::ejecutarReparacion('sistema', []);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['exito']);
    }

    public function testRemediacionParcialDeadlockViaIntentar(): void
    {
        $ctx = ContextoError::capturar('error', 'Deadlock found when trying to get lock', __FILE__, __LINE__);
        $diagnostico = DiagnosticoError::diagnosticar($ctx);

        $resultado = RemediadorError::intentar($ctx, $diagnostico);

        $this->assertNotEmpty($resultado['ejecutados']);
        $this->assertSame('deadlock', $resultado['ejecutados'][0]['tipo']);
    }

    public function testRemediacionSesionExpiradaViaIntentar(): void
    {
        $ctx = ContextoError::capturar('sesion_expirada', 'Su sesion ha expirado', __FILE__, __LINE__);
        $diagnostico = DiagnosticoError::diagnosticar($ctx);

        $resultado = RemediadorError::intentar($ctx, $diagnostico);

        $this->assertNotEmpty($resultado['ejecutados']);
    }

    public function testIntentarEstructuraCompleta(): void
    {
        $ctx = ContextoError::capturar('error', 'Some error', __FILE__, __LINE__);
        $diagnostico = [
            'diagnosticos' => [
                ['verificador' => 'base_datos', 'tipo' => 'tabla_faltante', 'tabla' => 'x'],
                ['verificador' => 'archivos', 'tipo' => 'archivo_demasiado_grande'],
            ],
        ];

        $resultado = RemediadorError::intentar($ctx, $diagnostico);

        $this->assertArrayHasKey('ejecutados', $resultado);
        $this->assertArrayHasKey('pendientes', $resultado);
        $this->assertArrayHasKey('errores', $resultado);
    }
}
