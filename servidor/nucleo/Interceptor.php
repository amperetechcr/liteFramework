<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo;

interface Interceptor
{
    public function manejar(array $params, callable $siguiente): mixed;
}
