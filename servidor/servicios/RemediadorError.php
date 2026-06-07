<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

use LiteFramework\Servicios\Verificadores\VerificadorBaseDatos;
use LiteFramework\Servicios\Verificadores\VerificadorArchivos;
use LiteFramework\Servicios\Verificadores\VerificadorSeguridad;
use LiteFramework\Servicios\Verificadores\VerificadorSistema;

class RemediadorError
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

    public static function intentar(ContextoError $ctx, array $diagnostico): array
    {
        self::asegurarVerificadores();

        $ejecutados = [];
        $pendientes = [];
        $errores = [];

        foreach ($diagnostico['diagnosticos'] ?? [] as $diag) {
            $verificadorTipo = $diag['verificador'] ?? '';
            $v = self::encontrarVerificador($verificadorTipo);
            if ($v === null || !$v->tieneRemedioAutomatico()) {
                $pendientes[] = $diag;
                continue;
            }
            try {
                $resultado = $v->ejecutarRemedio($diag);
                if (!empty($resultado['exito'])) {
                    $ejecutados[] = [
                        'tipo' => $diag['tipo'] ?? '',
                        'mensaje' => $resultado['mensaje'] ?? '',
                        'reintentar' => !empty($resultado['reintentar']),
                    ];
                } else {
                    $pendientes[] = $diag;
                }
            } catch (\Throwable $e) {
                $errores[] = [
                    'tipo' => $diag['tipo'] ?? '',
                    'mensaje' => $e->getMessage(),
                ];
            }
        }

        return [
            'ejecutados' => $ejecutados,
            'pendientes' => $pendientes,
            'errores' => $errores,
        ];
    }

    public static function ejecutarReparacion(string $tipo, array $params): array
    {
        self::asegurarVerificadores();

        foreach (self::$verificadores as $v) {
            if (!$v->tieneRemedioAutomatico()) {
                continue;
            }
            try {
                $resultado = $v->ejecutarRemedio(['tipo' => $tipo] + $params);
                return $resultado;
            } catch (\Throwable $e) {
                return ['exito' => false, 'mensaje' => 'Error al ejecutar reparación: ' . $e->getMessage()];
            }
        }

        return ['exito' => false, 'mensaje' => 'No se encontró un verificador para este problema.'];
    }

    private static function encontrarVerificador(string $tipo): ?object
    {
        foreach (self::$verificadores as $v) {
            if ($v->tipo() === $tipo) {
                return $v;
            }
        }
        return null;
    }
}
