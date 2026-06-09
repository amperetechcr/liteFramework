<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Excepciones;

class ErrorHttp extends \RuntimeException
{
    private int $codigoHttp;
    private ?string $cuerpoRespuesta;
    private array $cabeceras;

    public function __construct(
        string $mensaje = 'Error HTTP',
        int $codigoHttp = 500,
        ?string $cuerpoRespuesta = null,
        array $cabeceras = [],
        ?\Throwable $anterior = null
    ) {
        parent::__construct($mensaje, $codigoHttp, $anterior);
        $this->codigoHttp = $codigoHttp;
        $this->cuerpoRespuesta = $cuerpoRespuesta;
        $this->cabeceras = $cabeceras;
    }

    public function obtenerCodigoHttp(): int
    {
        return $this->codigoHttp;
    }

    public function obtenerCuerpoRespuesta(): ?string
    {
        return $this->cuerpoRespuesta;
    }

    public function obtenerCabeceras(): array
    {
        return $this->cabeceras;
    }
}
