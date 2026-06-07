<?php

declare(strict_types=1);

namespace LiteFramework\Api\Controladores;

use LiteFramework\Servicios\GeneradorModulo;
use Exception;

class GeneradorModuloApiControlador
{
    public function generarModulo(array $payload): array
    {
        $claseNombre = $payload['clase_nombre'] ?? '';
        $camposRaw = $payload['campos'] ?? [];
        $tabla = $payload['tabla'] ?? null;

        if (empty($claseNombre)) {
            return [400, [
                'estado_operacion' => false,
                'mensaje_error' => 'El nombre de la clase es requerido',
                'codigo_error' => 'datos_invalidos',
            ]];
        }

        if (empty($camposRaw) || !is_array($camposRaw)) {
            return [400, [
                'estado_operacion' => false,
                'mensaje_error' => 'Debe especificar al menos un campo',
                'codigo_error' => 'datos_invalidos',
            ]];
        }

        try {
            $campos = GeneradorModulo::parsearCamposDesdeArgs($camposRaw);
            $resultado = GeneradorModulo::generar($claseNombre, $campos, $tabla);

            $codigo = $resultado['exito'] ? 200 : 500;
            return [$codigo, $resultado];
        } catch (Exception $e) {
            return [500, [
                'estado_operacion' => false,
                'mensaje_error' => 'Error al generar modulo: ' . $e->getMessage(),
                'codigo_error' => 'error_interno',
            ]];
        }
    }
}
