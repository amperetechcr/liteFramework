<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

use LiteFramework\Seguridad\ControlAccesoRBAC;
use LiteFramework\Config\ConexionBaseDatos;
use Exception;

class AyudanteOperador extends Helper
{
    public static function estadoEtiqueta(int $estado): string
    {
        if ($estado === 1) {
            return '<span class="etiqueta etiqueta-exito">Activo</span>';
        }
        return '<span class="etiqueta etiqueta-peligro">Suspendido</span>';
    }

    public static function estadoTexto(int $estado): string
    {
        return $estado === 1 ? 'Activo' : 'Suspendido';
    }

    public static function nombreRol(int $idRol): string
    {
        try {
            $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $sql = $bd->prepare("SELECT nombre_rol FROM rbac_rol WHERE id_rol = :id LIMIT 1");
            $sql->execute([':id' => $idRol]);
            $nombre = $sql->fetchColumn();
            return $nombre !== false && $nombre !== null ? (string)$nombre : '—';
        } catch (Exception $e) {
            return '—';
        }
    }

    public static function estaActivo(?int $idOperador = null): bool
    {
        if ($idOperador === null) {
            $idOperador = self::idActual();
            if ($idOperador === 0) {
                return false;
            }
        }
        try {
            $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $sql = $bd->prepare("SELECT estado_cuenta FROM operador WHERE id_operador = :id LIMIT 1");
            $sql->execute([':id' => $idOperador]);
            $estado = $sql->fetchColumn();
            return $estado !== false && (int)$estado === 1;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function tienePermiso(string $clave): bool
    {
        return ControlAccesoRBAC::tienePermiso($clave);
    }

    public static function nombreActual(): string
    {
        return $_SESSION['operador_nombre'] ?? 'Invitado';
    }

    public static function idActual(): int
    {
        return (int)($_SESSION['operador_id'] ?? 0);
    }

    public static function rolActual(): int
    {
        return (int)($_SESSION['operador_rol'] ?? 0);
    }

    public static function permisosActuales(): array
    {
        return $_SESSION['matriz_permisos'] ?? [];
    }

    public static function permisoRequerido(string $clave): void
    {
        ControlAccesoRBAC::requerirPermisoEstricto($clave);
    }
}

class OperadorH extends AyudanteOperador
{
}
