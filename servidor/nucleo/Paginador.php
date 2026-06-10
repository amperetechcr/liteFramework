<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo;

use LiteFramework\Servicios\ServicioPaginacion;

class Paginador extends ServicioPaginacion
{
    private string $claseContenedor = 'paginador';
    private string $claseEnlace = 'paginador-enlace';
    private string $claseActivo = 'paginador-actual';
    private string $claseDeshabilitado = 'paginador-deshabilitado';

    public function textos(string $primera = '« Primera', string $anterior = '‹ Anterior', string $siguiente = 'Siguiente ›', string $ultima = 'Última »'): self
    {
        $this->enlaces['primera']['texto'] = $primera;
        $this->enlaces['anterior']['texto'] = $anterior;
        $this->enlaces['siguiente']['texto'] = $siguiente;
        $this->enlaces['ultima']['texto'] = $ultima;
        return $this;
    }

    public function render(): string
    {
        if ($this->totalPaginas <= 1) {
            return '';
        }

        $html = '<nav class="' . $this->claseContenedor . ' paginacion" role="navigation" aria-label="Paginación">';

        $html .= $this->renderEnlace($this->enlaces['primera'], '«', 'Primera página');
        $html .= $this->renderEnlace($this->enlaces['anterior'], '‹', 'Página anterior');

        foreach ($this->enlaces['paginas'] as $pagina) {
            if (isset($pagina['elipsis']) && $pagina['elipsis']) {
                $html .= '<span class="' . $this->claseEnlace . ' ' . $this->claseDeshabilitado . '">...</span>';
            } else {
                $html .= $this->renderEnlacePagina($pagina);
            }
        }

        $html .= $this->renderEnlace($this->enlaces['siguiente'], '›', 'Página siguiente');
        $html .= $this->renderEnlace($this->enlaces['ultima'], '»', 'Última página');

        $html .= '</nav>';
        return $html;
    }

    private function renderEnlace(array $datos, string $simbolo, string $titulo): string
    {
        $texto = $datos['texto'] ?? $simbolo;
        $deshabilitado = $datos['deshabilitado'] ?? false;

        if ($deshabilitado) {
            return '<span class="' . $this->claseEnlace . ' ' . $this->claseDeshabilitado . '" title="' . htmlspecialchars($titulo) . '">' . $texto . '</span>';
        }

        return '<a href="' . htmlspecialchars($datos['url']) . '" class="' . $this->claseEnlace . '" title="' . htmlspecialchars($titulo) . '" rel="prev" data-no-spa="1">' . $texto . '</a>';
    }

    private function renderEnlacePagina(array $pagina): string
    {
        if ($pagina['actual']) {
            return '<span class="' . $this->claseEnlace . ' ' . $this->claseActivo . '" aria-current="page">' . $pagina['numero'] . '</span>';
        }

        return '<a href="' . htmlspecialchars($pagina['url']) . '" class="' . $this->claseEnlace . '" data-no-spa="1">' . $pagina['numero'] . '</a>';
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
