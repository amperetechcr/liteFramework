<?php

declare(strict_types=1);

namespace LiteFramework\Cli\Comandos;

use LiteFramework\Nucleo\Helpers\Traductor;

class TraductorCalibrar
{
    public static function ejecutar(?string $categoria = null): array
    {
        return Traductor::calibrar($categoria);
    }
}
