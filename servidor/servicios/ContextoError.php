<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Seguridad\TrazadorPeticiones;
use PDO;

class ContextoError
{
    public string $codigo = 'desconocido';
    public string $mensaje = '';
    public string $archivo = '';
    public int $linea = 0;
    public string $traceId = '';
    public string $modulo = 'Sistema';
    public string $metodo = 'CLI';
    public string $ruta = 'CLI';
    public ?int $idOperador = null;
    public ?string $rolOperador = null;
    public array $diagnosticoSistema = [];
    public string $estadoMySQL = 'desconocido';
    public array $datosExtra = [];

    public static function capturar(string $codigo, string $mensaje, string $archivo, int $linea, array $extra = []): self
    {
        $ctx = new self();
        $ctx->codigo = $codigo;
        $ctx->mensaje = $mensaje;
        $ctx->archivo = $archivo;
        $ctx->linea = $linea;
        $ctx->traceId = self::obtenerTraceId();
        $ctx->metodo = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $ctx->ruta = $_SERVER['REQUEST_URI'] ?? 'CLI';
        $ctx->idOperador = isset($_SESSION['operador_id']) ? (int)$_SESSION['operador_id'] : null;
        $ctx->rolOperador = $_SESSION['rol_nombre'] ?? null;
        $ctx->diagnosticoSistema = self::diagnosticarSistema();
        $ctx->estadoMySQL = self::verificarMySQL();
        $ctx->datosExtra = $extra;
        return $ctx;
    }

    private static function obtenerTraceId(): string
    {
        if (class_exists(TrazadorPeticiones::class)) {
            try {
                return TrazadorPeticiones::obtenerId();
            } catch (\Throwable $e) {
                return 'N/A';
            }
        }
        return 'N/A';
    }

    private static function diagnosticarSistema(): array
    {
        $info = [];
        try {
            $info['php_version'] = PHP_VERSION;
            $info['memoria_usada_mb'] = round(memory_get_usage(true) / 1048576, 1);
            $info['memoria_limite'] = ini_get('memory_limit');
            $info['tiempo_ejecucion'] = ini_get('max_execution_time');
            $info['upload_max'] = ini_get('upload_max_filesize');
            $info['post_max'] = ini_get('post_max_size');
            $ruta = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
            $info['tmp_dir'] = $ruta;
            $info['tmp_dir_escribible'] = is_dir($ruta) && is_writable($ruta);
            $info['disco_libre_mb'] = function_exists('disk_free_space') ? round(disk_free_space(__DIR__) / 1048576, 1) : 0;
            $info['disco_total_mb'] = function_exists('disk_total_space') ? round(disk_total_space(__DIR__) / 1048576, 1) : 0;
            $info['extensiones'] = [];
            foreach (['pdo', 'pdo_mysql', 'pdo_sqlite', 'gd', 'mbstring', 'json', 'openssl'] as $ext) {
                $info['extensiones'][$ext] = extension_loaded($ext);
            }
        } catch (\Throwable $e) {
            $info['error'] = $e->getMessage();
        }
        return $info;
    }

    private static function verificarMySQL(): string
    {
        try {
            $pdo = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $pdo->query('SELECT 1');
                return 'ok';
            }
            return 'sqlite';
        } catch (\Throwable $e) {
            return 'caido';
        }
    }
}
