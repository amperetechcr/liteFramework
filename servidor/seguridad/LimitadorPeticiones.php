<?php

declare(strict_types=1);

namespace LiteFramework\Seguridad;

use PDO;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Nucleo\DialectoBaseDatos;

class LimitadorPeticiones
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
    }

    public function haExcedido(string $clave, int $maximoPeticiones, int $ventanaSegundos): bool
    {
        $ahora = time();
        $inicioVentana = $ahora - ($ahora % $ventanaSegundos);

        $this->limpiarVentanasExpiradas($ventanaSegundos);

        if (DialectoBaseDatos::esMySQL($this->conexion)) {
            $sql = "INSERT INTO rate_limit (clave_hash, ventana_inicio, contador) VALUES (:clave, :ventana, 1) ON DUPLICATE KEY UPDATE contador = contador + 1";
        } else {
            $sql = "INSERT INTO rate_limit (clave_hash, ventana_inicio, contador) VALUES (:clave, :ventana, 1) ON CONFLICT(clave_hash, ventana_inicio) DO UPDATE SET contador = contador + 1";
        }
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':clave' => $this->hash($clave),
            ':ventana' => $inicioVentana,
        ]);

        $stmt = $this->conexion->prepare("
            SELECT contador FROM rate_limit
            WHERE clave_hash = :clave AND ventana_inicio = :ventana
        ");
        $stmt->execute([
            ':clave' => $this->hash($clave),
            ':ventana' => $inicioVentana,
        ]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($fila && (int)$fila['contador'] > $maximoPeticiones);
    }

    public function contar(string $clave, int $ventanaSegundos): int
    {
        $ahora = time();
        $inicioVentana = $ahora - ($ahora % $ventanaSegundos);

        if (DialectoBaseDatos::esMySQL($this->conexion)) {
            $sql = "INSERT INTO rate_limit (clave_hash, ventana_inicio, contador) VALUES (:clave, :ventana, 1) ON DUPLICATE KEY UPDATE contador = contador + 1";
        } else {
            $sql = "INSERT INTO rate_limit (clave_hash, ventana_inicio, contador) VALUES (:clave, :ventana, 1) ON CONFLICT(clave_hash, ventana_inicio) DO UPDATE SET contador = contador + 1";
        }
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':clave' => $this->hash($clave),
            ':ventana' => $inicioVentana,
        ]);

        $stmt = $this->conexion->prepare("
            SELECT contador FROM rate_limit
            WHERE clave_hash = :clave AND ventana_inicio = :ventana
        ");
        $stmt->execute([
            ':clave' => $this->hash($clave),
            ':ventana' => $inicioVentana,
        ]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? (int)$fila['contador'] : 0;
    }

    public function reiniciar(string $clave): void
    {
        $stmt = $this->conexion->prepare("DELETE FROM rate_limit WHERE clave_hash = :clave");
        $stmt->execute([':clave' => $this->hash($clave)]);
    }

    private function limpiarVentanasExpiradas(int $ventanaSegundos): void
    {
        $limite = time() - ($ventanaSegundos * 2);
        $stmt = $this->conexion->prepare("DELETE FROM rate_limit WHERE ventana_inicio < :limite");
        $stmt->execute([':limite' => $limite]);
    }

    private function hash(string $clave): string
    {
        return hash('sha256', $clave);
    }
}
