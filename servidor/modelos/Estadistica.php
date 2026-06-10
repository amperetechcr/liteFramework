<?php

declare(strict_types=1);

namespace LiteFramework\Modelos;

use PDO;
use PDOException;
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
 * @property int $cache_ttl
 * @property int $en_dashboard
 * @property int $dashboard_orden
 * @property string|null $ultima_ejecucion
 * @property int $id_operador
 * @property string $fecha_creacion
 * @property string|null $fecha_actualizacion
 */
class Estadistica extends Modelo
{
    protected static string $tabla = 'estadistica';
    protected static string $idColumna = 'id_estadistica';
    protected static array $rellenable = ['titulo', 'descripcion', 'consulta_sql', 'tipo_visualizacion', 'columnas_mostrar', 'configuracion_visual', 'cache_ttl', 'en_dashboard', 'dashboard_orden', 'id_operador'];
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

    public function ultimaEjecucionFormateada(): string
    {
        if (!$this->ultima_ejecucion) {
            return '—';
        }
        return date('d/m/Y H:i', (int)strtotime($this->ultima_ejecucion));
    }

    public static function listarConFiltros(
        string $busqueda = '',
        int $pagina = 1,
        int $porPagina = 10
    ): array {
        $where = [];
        if ($busqueda !== '') {
            $where['e.titulo LIKE'] = '%' . $busqueda . '%';
        }

        error_log('[DEBUG_ESTADISTICA] busqueda="' . $busqueda . '" pagina=' . $pagina . ' porPagina=' . $porPagina);

        $resultado = self::paginar(
            $pagina,
            $porPagina,
            $where,
            'e.id_estadistica, e.titulo, e.descripcion, e.consulta_sql, e.tipo_visualizacion, e.columnas_mostrar, e.configuracion_visual, e.cache_ttl, e.en_dashboard, e.dashboard_orden, e.ultima_ejecucion, e.id_operador, e.fecha_creacion, e.fecha_actualizacion, o.nombre_completo',
            'LEFT JOIN operador o ON e.id_operador = o.id_operador'
        );

        $estadisticas = [];
        foreach ($resultado['datos'] as $modelo) {
            $estadisticas[] = $modelo->aArreglo();
        }

        error_log('[DEBUG_ESTADISTICA] resultados=' . count($estadisticas) . ' total=' . $resultado['total']);

        return [
            'estadisticas' => $estadisticas,
            'total' => $resultado['total'],
            'pagina' => $resultado['pagina'],
            'total_paginas' => $resultado['total_paginas'],
        ];
    }

    public static function listarDashboard(): array
    {
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $stmt = $conexion->prepare(
                "SELECT id_estadistica, titulo, descripcion, consulta_sql, tipo_visualizacion, columnas_mostrar, configuracion_visual, cache_ttl, ultima_ejecucion
                 FROM estadistica
                 WHERE en_dashboard = 1
                 ORDER BY dashboard_orden ASC, fecha_creacion DESC"
            );
            \assert($stmt !== false);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
