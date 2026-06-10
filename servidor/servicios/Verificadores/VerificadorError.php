<?php

declare(strict_types=1);

namespace LiteFramework\Servicios\Verificadores;

use LiteFramework\Servicios\ContextoError;

interface VerificadorError
{
    public function tipo(): string;
    public function diagnosticar(ContextoError $ctx): ?array;
    public function tieneRemedioAutomatico(): bool;
    public function ejecutarRemedio(array $diagnostico): array;
    public function obtenerSugerencias(array $diagnostico): array;
}
