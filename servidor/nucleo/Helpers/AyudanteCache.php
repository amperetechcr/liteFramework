<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

class AyudanteCache extends Helper
{
    private const DIRECTORIO_CACHE = DIRECTORIO_RAIZ . '/storage/cache';
    private const ARCHIVO_INDICE = 'indice.json';
    private const TTL_DEFECTO = 300;

    private static array $memoria = [];
    private static array $indiceArchivo = [];
    private static bool $indiceCargado = false;
    private static ?bool $apcuDisponible = null;

    public static function recordar(string $clave, callable $generar, int $ttl = self::TTL_DEFECTO): mixed
    {
        $valor = self::obtener($clave);
        if ($valor !== null) {
            return $valor;
        }

        $valor = $generar();
        self::guardar($clave, $valor, $ttl);
        return $valor;
    }

    public static function recordarJson(string $clave, callable $generar, int $ttl = self::TTL_DEFECTO): array
    {
        $valor = self::recordar($clave, $generar, $ttl);
        return is_array($valor) ? $valor : [];
    }

    public static function obtener(string $clave): mixed
    {
        $clave = self::claveFormateada($clave);

        if (array_key_exists($clave, self::$memoria)) {
            $entrada = self::$memoria[$clave];
            if ($entrada['expiracion'] === 0 || $entrada['expiracion'] > time()) {
                return $entrada['valor'];
            }
            unset(self::$memoria[$clave]);
        }

        if (self::apcuDisponible()) {
            $valor = apcu_fetch('lf_cache_' . $clave, $exito);
            if ($exito) {
                self::$memoria[$clave] = ['valor' => $valor, 'expiracion' => 0];
                return $valor;
            }
        }

        return self::obtenerDeArchivo($clave);
    }

    public static function guardar(string $clave, mixed $valor, int $ttl = self::TTL_DEFECTO): bool
    {
        $clave = self::claveFormateada($clave);
        $expiracion = $ttl > 0 ? time() + $ttl : 0;

        self::$memoria[$clave] = ['valor' => $valor, 'expiracion' => $expiracion];

        $ok = true;

        if (self::apcuDisponible()) {
            if (!apcu_store('lf_cache_' . $clave, $valor, $ttl)) {
                $ok = false;
            }
        }

        if (!self::guardarEnArchivo($clave, $valor, $expiracion)) {
            $ok = false;
        }

        return $ok;
    }

    public static function olvidar(string $clave): bool
    {
        $clave = self::claveFormateada($clave);

        unset(self::$memoria[$clave]);

        if (self::apcuDisponible()) {
            apcu_delete('lf_cache_' . $clave);
        }

        return self::eliminarDeArchivo($clave);
    }

    public static function limpiar(): bool
    {
        self::$memoria = [];

        if (self::apcuDisponible()) {
            apcu_clear_cache();
        }

        return self::limpiarArchivos();
    }

    public static function tiene(string $clave): bool
    {
        return self::obtener($clave) !== null;
    }

    public static function recordarResultadosPaginados(
        string $prefijo,
        int $pagina,
        int $porPagina,
        callable $generar,
        int $ttl = self::TTL_DEFECTO
    ): array {
        $clave = $prefijo . '_p' . $pagina . '_pp' . $porPagina;
        return self::recordarJson($clave, $generar, $ttl);
    }

    public static function olvidarPorPrefijo(string $prefijo): int
    {
        $prefijo = self::claveFormateada($prefijo);
        $clavesVistas = [];
        $eliminadas = 0;

        foreach (array_keys(self::$memoria) as $clave) {
            if (str_starts_with($clave, $prefijo)) {
                unset(self::$memoria[$clave]);
                if (!isset($clavesVistas[$clave])) {
                    $clavesVistas[$clave] = true;
                    $eliminadas++;
                }
            }
        }

        if (self::apcuDisponible()) {
            $indice = new \APCUIterator('/^lf_cache_' . preg_quote($prefijo, '/') . '/');
            foreach ($indice as $entrada) {
                apcu_delete($entrada['key']);
                $claveBase = substr($entrada['key'], 9);
                if (!isset($clavesVistas[$claveBase])) {
                    $clavesVistas[$claveBase] = true;
                    $eliminadas++;
                }
            }
        }

        $indice = self::cargarIndice();
        foreach ($indice as $archClave => $meta) {
            if (str_starts_with($archClave, $prefijo)) {
                $ruta = self::DIRECTORIO_CACHE . '/' . $archClave . '.cache';
                if (file_exists($ruta)) {
                    unlink($ruta);
                }
                unset($indice[$archClave]);
                if (!isset($clavesVistas[$archClave])) {
                    $clavesVistas[$archClave] = true;
                    $eliminadas++;
                }
            }
        }
        self::guardarIndice($indice);

        return $eliminadas;
    }

    public static function info(): array
    {
        $archivos = 0;
        if (is_dir(self::DIRECTORIO_CACHE)) {
            $archivos = count(glob(self::DIRECTORIO_CACHE . '/*.cache') ?: []);
        }

        return [
            'apcu' => self::apcuDisponible(),
            'memoria' => count(self::$memoria),
            'archivos' => $archivos,
        ];
    }

    private static function claveFormateada(string $clave): string
    {
        $clave = str_replace(['/', '\\', ':', '.'], '_', $clave);
        $clave = preg_replace('/[^a-zA-Z0-9_\-]/', '', $clave);
        return substr($clave, 0, 120) ?: 'cache_' . md5($clave);
    }

    private static function apcuDisponible(): bool
    {
        if (self::$apcuDisponible === null) {
            self::$apcuDisponible = function_exists('apcu_enabled') && apcu_enabled();
        }
        return self::$apcuDisponible;
    }

    private static function asegurarDirectorio(): void
    {
        if (!is_dir(self::DIRECTORIO_CACHE)) {
            $padres = dirname(self::DIRECTORIO_CACHE);
            if (!is_dir($padres)) {
                mkdir($padres, 0755, true);
            }
            mkdir(self::DIRECTORIO_CACHE, 0755, true);
        }
    }

    private static function guardarEnArchivo(string $clave, mixed $valor, int $expiracion): bool
    {
        self::asegurarDirectorio();

        $datos = [
            'v' => $valor,
            'e' => $expiracion,
            'c' => time(),
        ];

        $ruta = self::DIRECTORIO_CACHE . '/' . $clave . '.cache';
        $contenido = serialize($datos);

        if (file_put_contents($ruta, $contenido, LOCK_EX) === false) {
            return false;
        }

        $indice = self::cargarIndice();
        $indice[$clave] = [
            'e' => $expiracion,
            't' => time(),
        ];
        self::guardarIndice($indice);

        return true;
    }

    private static function obtenerDeArchivo(string $clave): mixed
    {
        $indice = self::cargarIndice();
        $meta = $indice[$clave] ?? null;

        if ($meta === null) {
            return null;
        }

        if ($meta['e'] > 0 && $meta['e'] < time()) {
            self::eliminarDeArchivo($clave);
            return null;
        }

        $ruta = self::DIRECTORIO_CACHE . '/' . $clave . '.cache';
        if (!file_exists($ruta)) {
            unset($indice[$clave]);
            self::guardarIndice($indice);
            return null;
        }

        $contenido = @file_get_contents($ruta);
        if ($contenido === false) {
            return null;
        }

        $datos = @unserialize($contenido);
        if (!is_array($datos) || !array_key_exists('v', $datos)) {
            return null;
        }

        self::$memoria[$clave] = [
            'valor' => $datos['v'],
            'expiracion' => $datos['e'],
        ];

        return $datos['v'];
    }

    private static function eliminarDeArchivo(string $clave): bool
    {
        $ruta = self::DIRECTORIO_CACHE . '/' . $clave . '.cache';
        if (file_exists($ruta)) {
            unlink($ruta);
        }

        $indice = self::cargarIndice();
        unset($indice[$clave]);
        self::guardarIndice($indice);

        return true;
    }

    private static function limpiarArchivos(): bool
    {
        if (!is_dir(self::DIRECTORIO_CACHE)) {
            return true;
        }

        $archivos = glob(self::DIRECTORIO_CACHE . '/*.cache');
        if ($archivos === false) {
            return false;
        }

        foreach ($archivos as $archivo) {
            unlink($archivo);
        }

        $indicePath = self::DIRECTORIO_CACHE . '/' . self::ARCHIVO_INDICE;
        if (file_exists($indicePath)) {
            unlink($indicePath);
        }

        self::$indiceCargado = false;
        self::$indiceArchivo = [];

        return true;
    }

    private static function cargarIndice(): array
    {
        if (self::$indiceCargado) {
            return self::$indiceArchivo;
        }

        $ruta = self::DIRECTORIO_CACHE . '/' . self::ARCHIVO_INDICE;
        if (!file_exists($ruta)) {
            self::$indiceCargado = true;
            self::$indiceArchivo = [];
            return [];
        }

        $contenido = @file_get_contents($ruta);
        if ($contenido === false) {
            self::$indiceCargado = true;
            self::$indiceArchivo = [];
            return [];
        }

        $indice = @json_decode($contenido, true);
        self::$indiceArchivo = is_array($indice) ? $indice : [];
        self::$indiceCargado = true;

        return self::$indiceArchivo;
    }

    private static function guardarIndice(array $indice): void
    {
        self::asegurarDirectorio();
        self::$indiceArchivo = $indice;

        $ruta = self::DIRECTORIO_CACHE . '/' . self::ARCHIVO_INDICE;
        $contenido = json_encode($indice, JSON_UNESCAPED_UNICODE);
        if ($contenido !== false) {
            file_put_contents($ruta, $contenido, LOCK_EX);
        }
    }
}
