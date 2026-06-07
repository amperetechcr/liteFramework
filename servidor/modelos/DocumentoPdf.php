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
        $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();

        $condiciones = [];
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones[] = "d.titulo LIKE :buscar";
            $parametros[':buscar'] = '%' . $busqueda . '%';
        }

        $clausulaWhere = !empty($condiciones) ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $stmtTotal = $bd->prepare("SELECT COUNT(*) FROM documento_pdf {$clausulaWhere}");
        \assert($stmtTotal !== false);
        $stmtTotal->execute($parametros);
        $totalDocumentos = (int)$stmtTotal->fetchColumn();

        $totalPaginas = max(1, (int)ceil($totalDocumentos / $porPagina));
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }
        $inicio = ($pagina - 1) * $porPagina;

        $sql = "
            SELECT d.id_documento, d.titulo, d.contenido_html, d.id_operador,
                   d.fecha_creacion, d.fecha_actualizacion, o.nombre_completo
            FROM documento_pdf d
            LEFT JOIN operador o ON d.id_operador = o.id_operador
            {$clausulaWhere}
            ORDER BY d.fecha_creacion DESC
            LIMIT :limite OFFSET :inicio
        ";
        $consulta = $bd->prepare($sql);
        \assert($consulta !== false);
        foreach ($parametros as $clave => $valor) {
            $consulta->bindValue($clave, $valor);
        }
        $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $consulta->bindValue(':inicio', $inicio, PDO::PARAM_INT);
        $consulta->execute();

        return [
            'documentos' => $consulta->fetchAll(PDO::FETCH_ASSOC),
            'total' => $totalDocumentos,
            'pagina' => $pagina,
            'total_paginas' => $totalPaginas,
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
