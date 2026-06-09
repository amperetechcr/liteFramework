<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

use LiteFramework\Servicios\Verificadores\VerificadorError;
use LiteFramework\Servicios\Verificadores\VerificadorBaseDatos;
use LiteFramework\Servicios\Verificadores\VerificadorArchivos;
use LiteFramework\Servicios\Verificadores\VerificadorSeguridad;
use LiteFramework\Servicios\Verificadores\VerificadorSistema;

class DiagnosticoError
{
    private static ?array $verificadores = null;

    private static function asegurarVerificadores(): void
    {
        if (self::$verificadores === null) {
            self::$verificadores = [
                new VerificadorBaseDatos(),
                new VerificadorArchivos(),
                new VerificadorSeguridad(),
                new VerificadorSistema(),
            ];
        }
    }

    public static function diagnosticar(ContextoError $ctx): array
    {
        self::asegurarVerificadores();

        $diagnosticos = [];
        $sugerencias = [];
        $accion = null;
        $tieneRemedio = false;
        $reparaciones = [];

        foreach (self::$verificadores as $v) {
            try {
                $diag = $v->diagnosticar($ctx);
                if ($diag !== null) {
                    $diag['verificador'] = $v->tipo();
                    if ($v->tieneRemedioAutomatico()) {
                        $remedio = $v->ejecutarRemedio($diag);
                        $diag['remedio'] = $remedio;
                        if (!empty($remedio['exito'])) {
                            $tieneRemedio = true;
                            $reparaciones[] = [
                                'tipo' => $diag['tipo'] ?? '',
                                'verificador' => $v->tipo(),
                                'mensaje' => $remedio['mensaje'] ?? 'Reparacion automatica aplicada.',
                                'reintentar' => !empty($remedio['reintentar']),
                            ];
                            if (!empty($remedio['accion_frontend'])) {
                                $accion = [
                                    'tipo' => $remedio['accion_frontend'],
                                    'reintentar' => !empty($remedio['reintentar']),
                                ];
                            }
                        }
                    }
                    $sugerencias = array_merge($sugerencias, $v->obtenerSugerencias($diag));
                    $diagnosticos[] = $diag;
                }
            } catch (\Throwable $e) {
                error_log("[DiagnosticoError] Error en verificador '{$v->tipo()}': " . $e->getMessage());
            }
        }

        return [
            'diagnosticos' => $diagnosticos,
            'tieneRemedio' => $tieneRemedio,
            'reparaciones' => $reparaciones,
            'sugerencias' => $sugerencias,
            'accion' => $accion,
        ];
    }
}
