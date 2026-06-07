<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

class ServicioPaginacion
{
    public int $paginaActual;
    public int $porPagina;
    public int $totalRegistros;
    public int $totalPaginas;
    public int $paginasVisibles;
    public string $urlBase;
    public string $parametroPagina;
    public array $enlaces;

    public static function crear(
        int $totalRegistros,
        int $porPagina = 15,
        ?string $urlBase = null,
        int $paginasVisibles = 5
    ): static {
        $instancia = new static();

        $instancia->totalRegistros = max(0, $totalRegistros);
        $instancia->porPagina = max(1, $porPagina);
        $instancia->paginasVisibles = max(1, $paginasVisibles);
        $instancia->parametroPagina = 'pagina';

        $instancia->totalPaginas = (int)ceil($instancia->totalRegistros / $instancia->porPagina);
        $instancia->totalPaginas = max(1, $instancia->totalPaginas);

        $paginaParam = $_GET[$instancia->parametroPagina] ?? 1;
        $instancia->paginaActual = max(1, min((int)$paginaParam, $instancia->totalPaginas));

        $instancia->urlBase = $urlBase ?? self::generarUrlBase();
        $instancia->enlaces = $instancia->generarEnlaces();

        return $instancia;
    }

    private static function generarUrlBase(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        $query = $_GET;
        unset($query['pagina'], $query['ajax'], $query['partial']);

        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        return $uri;
    }

    private function generarEnlaces(): array
    {
        $enlaces = [];

        $enlaces['primera'] = $this->paginaActual > 1
            ? ['numero' => 1, 'url' => $this->enlace(1), 'tipo' => 'primera']
            : ['numero' => 1, 'url' => null, 'tipo' => 'primera', 'deshabilitado' => true];

        $enlaces['anterior'] = $this->paginaActual > 1
            ? ['numero' => $this->paginaActual - 1, 'url' => $this->enlace($this->paginaActual - 1), 'tipo' => 'anterior']
            : ['numero' => 1, 'url' => null, 'tipo' => 'anterior', 'deshabilitado' => true];

        $paginas = $this->generarPaginasVisibles();
        $enlaces['paginas'] = $paginas;

        $enlaces['siguiente'] = $this->paginaActual < $this->totalPaginas
            ? ['numero' => $this->paginaActual + 1, 'url' => $this->enlace($this->paginaActual + 1), 'tipo' => 'siguiente']
            : ['numero' => $this->totalPaginas, 'url' => null, 'tipo' => 'siguiente', 'deshabilitado' => true];

        $enlaces['ultima'] = $this->paginaActual < $this->totalPaginas
            ? ['numero' => $this->totalPaginas, 'url' => $this->enlace($this->totalPaginas), 'tipo' => 'ultima']
            : ['numero' => $this->totalPaginas, 'url' => null, 'tipo' => 'ultima', 'deshabilitado' => true];

        return $enlaces;
    }

    private function generarPaginasVisibles(): array
    {
        $paginas = [];
        $actual = $this->paginaActual;
        $total = $this->totalPaginas;
        $visibles = $this->paginasVisibles;

        $mitad = (int)floor($visibles / 2);
        $inicio = max(1, $actual - $mitad);
        $fin = min($total, $inicio + $visibles - 1);

        if ($fin - $inicio + 1 < $visibles) {
            $inicio = max(1, $fin - $visibles + 1);
        }

        if ($inicio > 1) {
            $paginas[] = ['numero' => 1, 'url' => $this->enlace(1), 'actual' => false];
            if ($inicio > 2) {
                $paginas[] = ['numero' => '...', 'url' => null, 'elipsis' => true];
            }
        }

        for ($i = $inicio; $i <= $fin; $i++) {
            $paginas[] = [
                'numero' => $i,
                'url' => $this->enlace($i),
                'actual' => $i === $actual
            ];
        }

        if ($fin < $total) {
            if ($fin < $total - 1) {
                $paginas[] = ['numero' => '...', 'url' => null, 'elipsis' => true];
            }
            $paginas[] = ['numero' => $total, 'url' => $this->enlace($total), 'actual' => false];
        }

        return $paginas;
    }

    public function enlace(int $pagina): string
    {
        $pagina = max(1, min($pagina, $this->totalPaginas));

        $query = $_GET;
        unset($query['pagina'], $query['ajax'], $query['partial']);
        $query[$this->parametroPagina] = $pagina;

        $url = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $queryString = http_build_query($query);
        $url = rtrim($url, '/');
        if ($url === '') {
            $url = '/';
        }

        return $url . '?' . $queryString;
    }

    public function esPaginaActual(int $pagina): bool
    {
        return $pagina === $this->paginaActual;
    }

    public function anterior(): ?int
    {
        return $this->paginaActual > 1 ? $this->paginaActual - 1 : null;
    }

    public function siguiente(): ?int
    {
        return $this->paginaActual < $this->totalPaginas ? $this->paginaActual + 1 : null;
    }

    public function primera(): int
    {
        return 1;
    }

    public function ultima(): int
    {
        return $this->totalPaginas;
    }

    public function offset(): int
    {
        return ($this->paginaActual - 1) * $this->porPagina;
    }

    public function tieneAnterior(): bool
    {
        return $this->paginaActual > 1;
    }

    public function tieneSiguiente(): bool
    {
        return $this->paginaActual < $this->totalPaginas;
    }

    public function esPrimera(): bool
    {
        return $this->paginaActual === 1;
    }

    public function esUltima(): bool
    {
        return $this->paginaActual === $this->totalPaginas;
    }

    public function aArreglo(): array
    {
        return [
            'pagina_actual' => $this->paginaActual,
            'por_pagina' => $this->porPagina,
            'total_registros' => $this->totalRegistros,
            'total_paginas' => $this->totalPaginas,
            'tiene_anterior' => $this->tieneAnterior(),
            'tiene_siguiente' => $this->tieneSiguiente(),
            'es_primera' => $this->esPrimera(),
            'es_ultima' => $this->esUltima(),
            'offset' => $this->offset()
        ];
    }
}
