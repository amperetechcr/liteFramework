<?php

declare(strict_types=1);

namespace LiteFramework\Seguridad;

class TrazadorPeticiones
{
    private static ?string $idTraza = null;
    private static ?float $inicio = null;
    private static bool $finalizado = false;

    public static function iniciar(): string
    {
        if (self::$idTraza !== null) {
            return self::$idTraza;
        }

        self::$idTraza = bin2hex(random_bytes(16));
        self::$inicio = microtime(true);
        self::$finalizado = false;

        if (PHP_SAPI !== 'cli') {
            header('X-Trace-Id: ' . self::$idTraza);
        }

        return self::$idTraza;
    }

    public static function obtenerId(): string
    {
        if (self::$idTraza === null) {
            return self::iniciar();
        }
        return self::$idTraza;
    }

    public static function duracionMilisegundos(): float
    {
        if (self::$inicio === null) {
            return 0;
        }
        return round((microtime(true) - self::$inicio) * 1000, 2);
    }

    public static function finalizar(?int $codigo = null): void
    {
        if (self::$finalizado) {
            return;
        }
        self::$finalizado = true;

        if (self::$inicio === null) {
            return;
        }

        $codigoEstado = $codigo ?? (http_response_code() ?: 200);
        $duracion = self::duracionMilisegundos();

        if (PHP_SAPI !== 'cli') {
            header('X-Trace-Duration-Ms: ' . $duracion);
        }

        if ($duracion > 3000) {
            RegistroAuditoria::advertencia('Rendimiento', 'Peticion lenta', [
                'trace_id' => self::$idTraza,
                'duracion_ms' => $duracion,
                'codigo_http' => $codigoEstado,
                'ruta' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            ]);
        }
    }

    public static function contexto(): array
    {
        return [
            'trace_id' => self::$idTraza,
            'duracion_ms' => self::duracionMilisegundos(),
        ];
    }
}
