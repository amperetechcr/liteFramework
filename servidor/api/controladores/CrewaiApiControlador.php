<?php

declare(strict_types=1);

namespace LiteFramework\Api\Controladores;

use LiteFramework\Seguridad\SseGestor;

class CrewaiApiControlador
{
    public function emitirEvento(): array
    {
        $flujo = json_decode(file_get_contents('php://input') ?: '', true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($flujo)) {
            return [400, ['error' => 'JSON invalido']];
        }

        $tokenEsperado = getenv('CREWAI_SSE_TOKEN') ?: '';
        $tokenRecibido = $flujo['token'] ?? '';
        if ($tokenEsperado !== '' && $tokenRecibido !== $tokenEsperado) {
            return [403, ['error' => 'token_invalido']];
        }

        if (isset($flujo['accion']) && $flujo['accion'] === 'ping') {
            return [200, ['estado' => true, 'pong' => true]];
        }

        SseGestor::emitirATodos('crewai', [
            'agent_role' => $flujo['agent_role'] ?? 'desconocido',
            'accion'     => $flujo['accion'] ?? 'programando',
            'emoji'      => $flujo['emoji'] ?? '⌨️',
            'destino'    => $flujo['destino'] ?? 'escritorio',
            'nombre'     => $flujo['nombre'] ?? '',
            'mensaje'    => $flujo['mensaje'] ?? '',
        ]);

        return [200, ['estado' => true]];
    }

    public function metricas(): array
    {
        $path = __DIR__ . '/../../storage/logs/crewai_metricas.log';
        if (!file_exists($path)) {
            return [200, ['metricas' => [], 'resumen' => []]];
        }
        $lineas = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $metricas = array_map(fn($l) => json_decode($l, true), array_slice($lineas, -100));
        $metricas = array_filter($metricas);

        $resumen = [];
        foreach ($metricas as $m) {
            $rol = $m['r'] ?? '?';
            if (!isset($resumen[$rol])) {
                $resumen[$rol] = ['total' => 0, 'suma_ms' => 0, 'max_ms' => 0];
            }
            $resumen[$rol]['total']++;
            $resumen[$rol]['suma_ms'] += $m['d_ms'];
            $resumen[$rol]['max_ms'] = max($resumen[$rol]['max_ms'], $m['d_ms']);
        }
        foreach ($resumen as &$r) {
            $r['promedio_ms'] = round($r['suma_ms'] / max($r['total'], 1), 1);
        }

        return [200, ['metricas' => $metricas, 'resumen' => $resumen]];
    }
}


