<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo;

class RutaBuilder
{
    private Enrutador $enrutador;
    private int $indice;

    public function __construct(Enrutador $enrutador, int $indice)
    {
        $this->enrutador = $enrutador;
        $this->indice = $indice;
    }

    public function nombre(string $nombre): self
    {
        $this->enrutador->asignarNombre($this->indice, $nombre);
        return $this;
    }

    public function interceptor(string|array $interceptor): self
    {
        $this->enrutador->asignarInterceptor($this->indice, (array)$interceptor);
        return $this;
    }
}
