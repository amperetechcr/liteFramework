<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Excepciones;

class ErrorValidacion extends \RuntimeException
{
    private array $errores;

    public function __construct(
        string $mensaje = 'Datos inválidos',
        array $errores = [],
        ?\Throwable $anterior = null
    ) {
        parent::__construct($mensaje, 422, $anterior);
        $this->errores = $errores;
    }

    public function obtenerErrores(): array
    {
        return $this->errores;
    }
}
