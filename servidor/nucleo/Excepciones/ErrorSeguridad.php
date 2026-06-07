<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Excepciones;

class ErrorSeguridad extends \RuntimeException
{
    public function __construct(
        string $mensaje = 'Violación de seguridad',
        int $codigoHttp = 403,
        ?\Throwable $anterior = null
    ) {
        parent::__construct($mensaje, $codigoHttp, $anterior);
    }
}
