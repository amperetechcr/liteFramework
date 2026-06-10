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

        SseGestor::emitirATodos('crewai', [
            'agent_role' => $flujo['agent_role'] ?? 'desconocido',
            'accion'     => $flujo['accion'] ?? 'programando',
            'emoji'      => $flujo['emoji'] ?? "\u{2328}",
            'destino'    => $flujo['destino'] ?? 'escritorio',
        ]);

        return [200, ['estado' => true]];
    }
}
