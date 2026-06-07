<?php

declare(strict_types=1);

namespace LiteFramework\Modelos;

use PDOException;
use PDO;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Nucleo\Modelo;

/**
 * @property int $id_rol
 * @property string $nombre_rol
 * @property string|null $descripcion_rol
 * @property int $estado_rol
 */
class Rol extends Modelo
{
    protected static string $tabla = 'rbac_rol';
    protected static string $idColumna = 'id_rol';
    protected static array $rellenable = ['nombre_rol', 'descripcion_rol', 'estado_rol'];

    public function operadores(): array
    {
        return Operador::donde('id_rol', $this->id_rol)->obtener();
    }

    public function permisos(): array
    {
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $stmt = $conexion->prepare("
                SELECT p.clave_permiso, p.descripcion
                FROM permisos p
                INNER JOIN permisos_rol pr ON p.id_permiso = pr.id_permiso
                WHERE pr.id_rol = :id_rol
            ");
            $stmt->execute([':id_rol' => $this->id_rol]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
