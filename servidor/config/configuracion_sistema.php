<?php

declare(strict_types=1);

namespace LiteFramework\Config;

use PDOException;
use PDO;
use Exception;

class ConfiguracionSistema
{
    private static array $cache = [];
    private static bool $cacheCargado = false;
    private static int $cacheCargadoEn = 0;
    private const TTL_SEGUNDOS = 30;

    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        self::asegurarCache();
        if (isset(self::$cache[$clave])) {
            $fila = self::$cache[$clave];
            return self::convertirTipo($fila['valor'], $fila['tipo_dato']);
        }
        return GestorEntorno::obtener($clave, $defecto);
    }

    public static function obtenerFila(string $clave): ?array
    {
        self::asegurarCache();
        return self::$cache[$clave] ?? null;
    }

    public static function obtenerTodas(): array
    {
        self::asegurarCache();
        return self::$cache;
    }

    public static function establecer(string $clave, mixed $valor, int $versionEsperada, ?int $idOperador = null): array
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

        self::invalidarCache();
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
            $stmt->execute([$valorSerializado, $tipoDato, $idOperador, $clave]);
        } catch (PDOException $e) {
            return ['estado' => 'error', 'mensaje' => $e->getMessage()];
        }

        self::invalidarCache();
        return ['estado' => 'ok'];
    }

    public static function invalidarCache(): void
    {
        self::$cacheCargado = false;
        self::$cache = [];
    }

    private static function asegurarCache(): void
    {
        $expirado = (time() - self::$cacheCargadoEn) > self::TTL_SEGUNDOS;
        if (self::$cacheCargado && !$expirado) {
            return;
        }

        self::$cache = [];
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $filas = $conexion->query("SELECT clave, valor, tipo_dato, version, actualizado_por FROM configuracion_sistema")
                ->fetchAll(PDO::FETCH_ASSOC);
            foreach ($filas as $fila) {
                self::$cache[$fila['clave']] = $fila;
            }
            self::$cacheCargado = true;
            self::$cacheCargadoEn = time();
        } catch (Exception $e) {
            error_log("[ConfiguracionSistema] Error al cargar cache: " . $e->getMessage());
        }
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
            return json_encode($valor, JSON_UNESCAPED_UNICODE);
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
