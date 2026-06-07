<?php

declare(strict_types=1);

namespace LiteFramework\Seguridad;

use Exception;
use PDOException;
use PDO;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Nucleo\Excepciones\ErrorSeguridad;

class ControlAccesoRBAC
{
    public static function cargarPermisosEnMemoria(?PDO $conexion, int $idRol): void
    {
        if ($conexion === null) {
            try {
                $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            } catch (Exception $e) {
                $_SESSION['matriz_permisos'] = [];
                return;
            }
        }
        try {
            $consulta = $conexion->prepare("
                SELECT p.clave_permiso
                FROM permisos p
                INNER JOIN permisos_rol pr ON p.id_permiso = pr.id_permiso
                WHERE pr.id_rol = :id_rol
            ");
            $consulta->execute([':id_rol' => $idRol]);
            $_SESSION['matriz_permisos'] = $consulta->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            RegistroAuditoria::error('RBAC', 'Error al cargar matriz de permisos', [
                'id_rol' => $idRol,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['matriz_permisos'] = [];
        }
    }

    public static function tienePermiso(string $clavePermiso): bool
    {
        if (empty($_SESSION['matriz_permisos']) || !is_array($_SESSION['matriz_permisos'])) {
            return false;
        }
        if (isset($_SESSION['matriz_permisos'][$clavePermiso])) {
            return (bool)$_SESSION['matriz_permisos'][$clavePermiso];
        }
        return in_array($clavePermiso, $_SESSION['matriz_permisos'], true);
    }

    public static function requerirPermisoEstricto(string $clavePermiso): void
    {
        if (!self::tienePermiso($clavePermiso)) {
            RegistroAuditoria::seguridad('Permiso denegado por RBAC', [
                'permiso_requerido' => $clavePermiso,
                'operador_id' => $_SESSION['operador_id'] ?? null,
                'rol' => $_SESSION['operador_rol'] ?? null,
            ]);
            throw new ErrorSeguridad('Permiso denegado: ' . $clavePermiso);
        }
    }
}
