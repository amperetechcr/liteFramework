<?php

declare(strict_types=1);

namespace LiteFramework\Modelos;

use PDO;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Nucleo\Modelo;

/**
 * @property int $id_documento
 * @property string $titulo
 * @property string $contenido_html
 * @property int $id_operador
 * @property string $fecha_creacion
 * @property string|null $fecha_actualizacion
 */
class DocumentoPdf extends Modelo
{
    protected static string $tabla = 'documento_pdf';
    protected static string $idColumna = 'id_documento';
    protected static array $rellenable = ['titulo', 'contenido_html', 'id_operador'];
    protected static bool $timestamps = true;

    public function operador(): ?Operador
    {
        return Operador::buscar($this->id_operador);
    }

    public function fechaFormateada(): string
    {
        return date('d/m/Y H:i', (int)strtotime($this->fecha_creacion));
    }

    public static function listarConFiltros(
        string $busqueda = '',
        int $pagina = 1,
        int $porPagina = 10
    ): array {
        $where = [];
        if ($busqueda !== '') {
            $where['titulo LIKE'] = '%' . $busqueda . '%';
        }

        error_log('[DEBUG_DOCPDF] busqueda="' . $busqueda . '" pagina=' . $pagina . ' porPagina=' . $porPagina);

        $resultado = self::paginar(
            $pagina,
            $porPagina,
            $where,
            'd.id_documento, d.titulo, d.contenido_html, d.id_operador, d.fecha_creacion, d.fecha_actualizacion, o.nombre_completo',
            'LEFT JOIN operador o ON d.id_operador = o.id_operador'
        );

        $documentos = [];
        foreach ($resultado['datos'] as $modelo) {
            $documentos[] = $modelo->aArreglo();
        }

        return [
            'documentos' => $documentos,
            'total' => $resultado['total'],
            'pagina' => $resultado['pagina'],
            'total_paginas' => $resultado['total_paginas'],
        ];
    }

    public function aArreglo(): array
    {
        $arr = parent::aArreglo();
        $arr['fecha_formateada'] = $this->fechaFormateada();
        $arr['operador_nombre'] = $this->operador()?->nombre_completo ?: '—';
        return $arr;
    }
}
