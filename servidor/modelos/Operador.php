<?php

declare(strict_types=1);

namespace LiteFramework\Modelos;

use PDO;
use LiteFramework\Seguridad\ControlAccesoRBAC;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Nucleo\Modelo;

/**
 * @property int $id_operador
 * @property string $nombre_completo
 * @property string $correo_electronico
 * @property string $clave_acceso
 * @property int $id_rol
 * @property int $estado_cuenta
 * @property string|null $fecha_registro
 */
class Operador extends Modelo
{
    protected static string $tabla = 'operador';
    protected static string $idColumna = 'id_operador';
    protected static array $rellenable = ['nombre_completo', 'correo_electronico', 'clave_acceso', 'id_rol', 'estado_cuenta'];

    public function rol(): ?Rol
    {
        return Rol::buscar($this->id_rol);
    }

    public function tienePermiso(string $clavePermiso): bool
    {
        return ControlAccesoRBAC::tienePermiso($clavePermiso);
    }

    public function estaActivo(): bool
    {
        return (int)$this->estado_cuenta === 1;
    }

    public function activar(): bool
    {
        $this->estado_cuenta = 1;
        return $this->guardar();
    }

    public function suspender(): bool
    {
        $this->estado_cuenta = 0;
        return $this->guardar();
    }

    public static function contarActivos(): int
    {
        return self::donde('estado_cuenta', 1)->contarDonde();
    }

    public static function contarSuspendidos(): int
    {
        return self::donde('estado_cuenta', 0)->contarDonde();
    }

    public static function obtenerPerfil(int $idOperador): ?array
    {
        $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $stmt = $bd->prepare("
            SELECT o.id_operador, o.nombre_completo, o.correo_electronico,
                   o.estado_cuenta, o.fecha_registro, r.nombre_rol
            FROM operador o
            JOIN rbac_rol r ON o.id_rol = r.id_rol
            WHERE o.id_operador = :id
            LIMIT 1
        ");
        \assert($stmt !== false);
        $stmt->execute([':id' => $idOperador]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    public static function listarConFiltros(
        string $busqueda = '',
        int $filtroRol = 0,
        string $filtroEstado = '',
        int $pagina = 1,
        int $porPagina = 10
    ): array {
        $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $condiciones = [];
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones[] = "(o.nombre_completo LIKE :buscar_nombre OR o.correo_electronico LIKE :buscar_correo)";
            $parametros[':buscar_nombre'] = '%' . $busqueda . '%';
            $parametros[':buscar_correo'] = '%' . $busqueda . '%';
        }
        if ($filtroRol > 0) {
            $condiciones[] = "o.id_rol = :rol";
            $parametros[':rol'] = $filtroRol;
        }
        if ($filtroEstado !== '' && in_array($filtroEstado, ['1', '0'], true)) {
            $condiciones[] = "o.estado_cuenta = :estado";
            $parametros[':estado'] = (int)$filtroEstado;
        }

        $clausulaWhere = !empty($condiciones) ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $sqlTotal = "SELECT COUNT(*) FROM operador o {$clausulaWhere}";
        $stmtTotal = $bd->prepare($sqlTotal);
        \assert($stmtTotal !== false);
        foreach ($parametros as $clave => $valor) {
            $stmtTotal->bindValue($clave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmtTotal->execute();
        $total = (int)$stmtTotal->fetchColumn();

        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }
        $inicio = ($pagina - 1) * $porPagina;

        $sql = "
            SELECT o.id_operador, o.nombre_completo, o.correo_electronico,
                   o.estado_cuenta, o.fecha_registro, o.id_rol, r.nombre_rol
            FROM operador o
            JOIN rbac_rol r ON o.id_rol = r.id_rol
            {$clausulaWhere}
            ORDER BY o.id_operador DESC
            LIMIT :limite OFFSET :inicio
        ";
        $consulta = $bd->prepare($sql);
        \assert($consulta !== false);
        foreach ($parametros as $clave => $valor) {
            $consulta->bindValue($clave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $consulta->bindValue(':inicio', $inicio, PDO::PARAM_INT);
        $consulta->execute();

        return [
            'operadores' => $consulta->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'pagina' => $pagina,
            'total_paginas' => $totalPaginas,
        ];
    }
}
