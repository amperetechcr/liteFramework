<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Excepciones;

class ErrorAutenticacion extends \RuntimeException
{
    public function __construct(
        string $mensaje = 'Autenticación requerida',
        int $codigoHttp = 401,
        ?\Throwable $anterior = null
    ) {
        parent::__construct($mensaje, $codigoHttp, $anterior);
    }
}
