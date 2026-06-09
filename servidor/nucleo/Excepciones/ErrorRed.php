<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Excepciones;

class ErrorRed extends \RuntimeException
{
    private int $codigoCurl;

    public function __construct(
        string $mensaje = 'Error de red',
        int $codigoCurl = 0,
        ?\Throwable $anterior = null
    ) {
        parent::__construct($mensaje, 0, $anterior);
        $this->codigoCurl = $codigoCurl;
    }

    public function obtenerCodigoCurl(): int
    {
        return $this->codigoCurl;
    }
}
