<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo;

use PDO;

class DialectoBaseDatos
{
    public static function esMySQL(PDO $conexion): bool
    {
        return $conexion->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    }

    public static function esSQLite(PDO $conexion): bool
    {
        return $conexion->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }

    public static function fechaAhora(PDO $conexion): string
    {
        return self::esSQLite($conexion) ? "datetime('now')" : 'NOW()';
    }

    public static function fechaHoy(PDO $conexion): string
    {
        return self::esSQLite($conexion) ? "date('now')" : 'CURDATE()';
    }

    public static function fechaRestar(PDO $conexion, string $unidad, int $cantidad): string
    {
        if (self::esSQLite($conexion)) {
            return "datetime('now', '-{$cantidad} {$unidad}')";
        }
        return "DATE_SUB(NOW(), INTERVAL {$cantidad} {$unidad})";
    }

    public static function extraerFecha(PDO $conexion, string $columna): string
    {
        if (self::esSQLite($conexion)) {
            return "date({$columna})";
        }
        return "DATE({$columna})";
    }

    public static function autoIncremento(): string
    {
        return 'INTEGER PRIMARY KEY AUTOINCREMENT';
    }

    public static function crearTablaSufijo(): string
    {
        return '';
    }
}
