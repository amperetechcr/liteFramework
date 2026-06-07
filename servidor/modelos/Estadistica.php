<?php

declare(strict_types=1);

namespace LiteFramework\Modelos;

use PDO;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Nucleo\Modelo;

/**
 * @property int $id_estadistica
 * @property string $titulo
 * @property string|null $descripcion
 * @property string $consulta_sql
 * @property string $tipo_visualizacion
 * @property string|null $columnas_mostrar
 * @property string|null $configuracion_visual
 * @property int $id_operador
 * @property string $fecha_creacion
 * @property string|null $fecha_actualizacion
 */
class Estadistica extends Modelo
{
    protected static string $tabla = 'estadistica';
    protected static string $idColumna = 'id_estadistica';
    protected static array $rellenable = ['titulo', 'descripcion', 'consulta_sql', 'tipo_visualizacion', 'columnas_mostrar', 'configuracion_visual', 'id_operador'];
    protected static bool $timestamps = true;

    public function operador(): ?Operador
    {
        return Operador::buscar($this->id_operador);
    }

    public function fechaFormateada(): string
    {
        return date('d/m/Y H:i', (int)strtotime($this->fecha_creacion));
    }

    public function aArreglo(): array
    {
        $arr = parent::aArreglo();
        $arr['fecha_formateada'] = $this->fechaFormateada();
        $conf = $this->configuracion_visual;
        $arr['configuracion_visual'] = is_string($conf) ? json_decode($conf, true) : $conf;
        return $arr;
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

        $resultado = self::paginar(
            $pagina,
            $porPagina,
            $where,
            'e.id_estadistica, e.titulo, e.descripcion, e.consulta_sql, e.tipo_visualizacion, e.columnas_mostrar, e.configuracion_visual, e.id_operador, e.fecha_creacion, e.fecha_actualizacion, o.nombre_completo',
            'LEFT JOIN operador o ON e.id_operador = o.id_operador'
        );

        $estadisticas = [];
        foreach ($resultado['datos'] as $modelo) {
            $estadisticas[] = $modelo->aArreglo();
        }

        return [
            'estadisticas' => $estadisticas,
            'total' => $resultado['total'],
            'pagina' => $resultado['pagina'],
            'total_paginas' => $resultado['total_paginas'],
        ];
    }
}
