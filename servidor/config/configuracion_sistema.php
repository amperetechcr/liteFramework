<?php

declare(strict_types=1);

namespace LiteFramework\Config;

use PDOException;
use PDO;
use Exception;
use LiteFramework\Nucleo\Helpers\AyudanteCache;
use LiteFramework\Nucleo\Helpers\AyudanteGeneral;

class ConfiguracionSistema
{
    private const CLAVE_CACHE = 'config_sistema';

    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        $cache = self::obtenerCache();
        if (isset($cache[$clave])) {
            $fila = $cache[$clave];
            return self::convertirTipo($fila['valor'], $fila['tipo_dato']);
        }
        return GestorEntorno::obtener($clave, $defecto);
    }

    public static function obtenerFila(string $clave): ?array
    {
        $cache = self::obtenerCache();
        return $cache[$clave] ?? null;
    }

    public static function obtenerTodas(): array
    {
        return self::obtenerCache();
    }

    public static function establecer(string $clave, mixed $valor, string|int $tipoOVersion = 'auto', ?string $descripcion = null, ?int $idOperador = null): array
    {
        if (is_int($tipoOVersion)) {
            return self::establecerConVersion($clave, $valor, $tipoOVersion, $idOperador);
        }

        $tipoDato = $tipoOVersion !== 'auto' ? $tipoOVersion : self::inferirTipo($valor);
        $valorSerializado = self::serializar($valor, $tipoDato);
        $idOperador = $idOperador ?? (int)($_SESSION['operador_id'] ?? 0);

        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $sql = "UPDATE configuracion_sistema
                    SET valor = ?, tipo_dato = ?, version = version + 1, actualizado_por = ?
                    WHERE clave = ?";
            $stmt = $conexion->prepare($sql);
            \assert($stmt !== false);
            $stmt->execute([$valorSerializado, $tipoDato, $idOperador, $clave]);
        } catch (PDOException $e) {
            return [
                'estado' => 'error',
                'mensaje' => 'Error de base de datos: ' . $e->getMessage(),
            ];
        }

        AyudanteCache::olvidar(self::CLAVE_CACHE);
        return [
            'estado' => 'ok',
            'version' => 0,
        ];
    }

    private static function establecerConVersion(string $clave, mixed $valor, int $versionEsperada, ?int $idOperador = null): array
    {
        $tipoDato = self::inferirTipo($valor);
        $valorSerializado = self::serializar($valor, $tipoDato);
        $idOperador = $idOperador ?? (int)($_SESSION['operador_id'] ?? 0);

        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $sql = "UPDATE configuracion_sistema
                    SET valor = ?, tipo_dato = ?, version = version + 1, actualizado_por = ?
                    WHERE clave = ? AND version = ?";
            $stmt = $conexion->prepare($sql);
            \assert($stmt !== false);
            $stmt->execute([$valorSerializado, $tipoDato, $idOperador, $clave, $versionEsperada]);
        } catch (PDOException $e) {
            return [
                'estado' => 'error',
                'mensaje' => 'Error de base de datos: ' . $e->getMessage(),
            ];
        }

        if ($stmt->rowCount() === 0) {
            $actual = self::obtenerFila($clave);
            return [
                'estado' => 'conflicto',
                'valor_actual' => $actual['valor'] ?? null,
                'version_actual' => (int)($actual['version'] ?? 0),
                'actualizado_por' => $actual['actualizado_por'] ?? null,
            ];
        }

        AyudanteCache::olvidar(self::CLAVE_CACHE);
        return [
            'estado' => 'ok',
            'version' => $versionEsperada + 1,
        ];
    }

    public static function forzarEstablecer(string $clave, mixed $valor, ?int $idOperador = null): array
    {
        $tipoDato = self::inferirTipo($valor);
        $valorSerializado = self::serializar($valor, $tipoDato);
        $idOperador = $idOperador ?? (int)($_SESSION['operador_id'] ?? 0);

        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $sql = "UPDATE configuracion_sistema
                    SET valor = ?, tipo_dato = ?, version = version + 1, actualizado_por = ?
                    WHERE clave = ?";
            $stmt = $conexion->prepare($sql);
            \assert($stmt !== false);
            $stmt->execute([$valorSerializado, $tipoDato, $idOperador, $clave]);
        } catch (PDOException $e) {
            return ['estado' => 'error', 'mensaje' => $e->getMessage()];
        }

        AyudanteCache::olvidar(self::CLAVE_CACHE);
        return ['estado' => 'ok'];
    }

    public static function invalidarCache(): void
    {
        AyudanteCache::olvidar(self::CLAVE_CACHE);
    }

    public static function obtenerCache(): array
    {
        $generar = function (): array {
            try {
                $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
                $stmtConfig = $conexion->query("SELECT clave, valor, tipo_dato, version, actualizado_por FROM configuracion_sistema");
                \assert($stmtConfig !== false);
                $filas = $stmtConfig->fetchAll(PDO::FETCH_ASSOC);
                $cache = [];
                foreach ($filas as $fila) {
                    $cache[$fila['clave']] = $fila;
                }
                return $cache;
            } catch (Exception $e) {
                error_log("[ConfiguracionSistema] Error al cargar cache: " . $e->getMessage());
                return [];
            }
        };

        $valor = AyudanteCache::recordar(self::CLAVE_CACHE, $generar, 30);
        return is_array($valor) ? $valor : [];
    }

    private static function inferirTipo(mixed $valor): string
    {
        if (is_bool($valor)) {
            return 'booleano';
        }
        if (is_int($valor) || is_float($valor)) {
            return 'numero';
        }
        if (is_array($valor) || is_object($valor)) {
            return 'json';
        }
        if (is_string($valor)) {
            $lower = strtolower(trim($valor));
            if ($lower === 'true' || $lower === 'false') {
                return 'booleano';
            }
            if (is_numeric($valor)) {
                return 'numero';
            }
        }
        return 'texto';
    }

    private static function serializar(mixed $valor, string $tipoDato): string
    {
        if ($tipoDato === 'json') {
            return json_encode($valor, JSON_UNESCAPED_UNICODE) ?: '';
        }
        if ($tipoDato === 'booleano') {
            return $valor ? '1' : '0';
        }
        return (string)$valor;
    }

    private static function convertirTipo(string $valor, string $tipoDato): mixed
    {
        switch ($tipoDato) {
            case 'numero':
                return is_numeric($valor) ? (strpos($valor, '.') !== false ? (float)$valor : (int)$valor) : $valor;
            case 'booleano':
                return $valor === '1' || strtolower($valor) === 'true';
            case 'json':
                $decoded = json_decode($valor, true);
                return $decoded ?? $valor;
            default:
                return $valor;
        }
    }
}
