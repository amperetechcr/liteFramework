<?php

declare(strict_types=1);

namespace LiteFramework\Api\Controladores;

use LiteFramework\Servicios\GeneradorProyecto;

class GeneradorProyectoApiControlador
{
    public function generarProyecto(array $payload): array
    {
        $jsonRaw = $payload['definicion_proyecto'] ?? '';
        $definicion = is_string($jsonRaw) ? json_decode($jsonRaw, true) : $jsonRaw;

        if (empty($definicion) || !is_array($definicion)) {
            return [400, [
                'estado_operacion' => false,
                'mensaje_error' => 'Definicion de proyecto invalida o ausente.',
                'codigo_error' => 'datos_invalidos',
            ]];
        }

        $resultado = GeneradorProyecto::generar($definicion);

        if (!$resultado['exito']) {
            return [500, [
                'estado_operacion' => false,
                'mensaje_error' => $resultado['error'] ?? 'Error al generar proyecto',
                'codigo_error' => 'error_generacion',
                'detalle' => $resultado['errores'] ?? [],
            ]];
        }

        return [200, [
            'estado_operacion' => true,
            'mensaje_exito' => 'Proyecto generado exitosamente',
            'datos' => [
                'directorio' => $resultado['directorio'],
                'archivos_procesados' => $resultado['resumen']['archivos_procesados'],
                'entidades' => $resultado['resumen']['entidades_generadas'],
                'modulos_activados' => $resultado['resumen']['modulos_activados'],
                'pasos_siguientes' => $resultado['resumen']['pasos_siguientes'],
            ],
        ]];
    }
}
