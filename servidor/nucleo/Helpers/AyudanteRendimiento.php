<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

class AyudanteRendimiento extends Helper
{
    private static array $puntos = [];
    private static array $mediciones = [];

    public static function iniciar(string $nombre): void
    {
        self::$puntos[$nombre] = [
            'tiempo' => microtime(true),
            'memoria' => memory_get_usage(),
            'memoriaPico' => memory_get_peak_usage(),
        ];
    }

    public static function detener(string $nombre): array
    {
        $inicio = self::$puntos[$nombre] ?? null;
        if ($inicio === null) {
            return [
                'nombre' => $nombre,
                'tiempo' => 0.0,
                'memoria' => 0,
                'memoriaPico' => 0,
                'error' => 'No se encontro punto de inicio',
            ];
        }

        $resultado = [
            'nombre' => $nombre,
            'tiempo' => round((microtime(true) - $inicio['tiempo']) * 1000, 4),
            'memoria' => memory_get_usage() - $inicio['memoria'],
            'memoriaPico' => memory_get_peak_usage(),
        ];

        self::$mediciones[$nombre] = $resultado;
        unset(self::$puntos[$nombre]);

        return $resultado;
    }

    public static function medir(callable $callback, string $nombre = '', int $iteraciones = 1): array
    {
        if ($nombre === '') {
            $nombre = 'medicion_' . (count(self::$mediciones) + 1);
        }

        if ($iteraciones <= 0) {
            $iteraciones = 1;
        }

        $memoriaAntes = memory_get_usage();
        $picoAntes = memory_get_peak_usage();
        $inicio = microtime(true);

        $resultado = null;
        for ($i = 0; $i < $iteraciones; $i++) {
            $resultado = $callback();
        }

        $tiempoTotal = (microtime(true) - $inicio) * 1000;
        $tiempoPromedio = $tiempoTotal / $iteraciones;

        $medicion = [
            'nombre' => $nombre,
            'tiempo' => round($tiempoPromedio, 4),
            'tiempoTotal' => round($tiempoTotal, 4),
            'iteraciones' => $iteraciones,
            'memoria' => memory_get_usage() - $memoriaAntes,
            'memoriaPico' => memory_get_peak_usage(),
        ];

        self::$mediciones[$nombre] = $medicion;

        return $medicion;
    }

    public static function comparar(array $escenarios, int $iteraciones = 100): array
    {
        $resultados = [];

        foreach ($escenarios as $nombre => $callback) {
            $resultados[$nombre] = self::medir($callback, (string)$nombre, $iteraciones);
        }

        if (count($resultados) < 2) {
            return $resultados;
        }

        $masRapido = null;
        $tiempoMinimo = INF;
        foreach ($resultados as $nombre => $medicion) {
            if ($medicion['tiempo'] < $tiempoMinimo) {
                $tiempoMinimo = $medicion['tiempo'];
                $masRapido = $nombre;
            }
        }

        foreach ($resultados as $nombre => &$medicion) {
            if ($nombre === $masRapido) {
                $medicion['diferencia'] = 0.0;
                $medicion['porcentaje'] = 100.0;
            } else {
                $medicion['diferencia'] = round($medicion['tiempo'] - $tiempoMinimo, 4);
                $medicion['porcentaje'] = $tiempoMinimo > 0
                    ? round(($medicion['tiempo'] / $tiempoMinimo) * 100, 1)
                    : 0.0;
            }
        }
        unset($medicion);

        return $resultados;
    }

    public static function reporte(): array
    {
        $tiempoTotal = 0.0;
        $memoriaMax = 0;

        foreach (self::$mediciones as $m) {
            $tiempoTotal += $m['tiempo'] ?? 0;
            $pico = $m['memoriaPico'] ?? 0;
            if ($pico > $memoriaMax) {
                $memoriaMax = $pico;
            }
        }

        return [
            'mediciones' => self::$mediciones,
            'resumen' => [
                'total' => count(self::$mediciones),
                'tiempoTotal' => round($tiempoTotal, 4),
                'memoriaMax' => $memoriaMax,
                'memoriaMaxLegible' => self::bytesLegibles($memoriaMax),
                'inicio' => self::$mediciones ? reset(self::$mediciones)['nombre'] ?? '' : '',
                'fin' => self::$mediciones ? end(self::$mediciones)['nombre'] ?? '' : '',
            ],
        ];
    }

    public static function formatearTexto(): string
    {
        $reporte = self::reporte();
        $lineas = [];
        $lineas[] = '=== Perfil de Rendimiento ===';
        $lineas[] = '';

        foreach ($reporte['mediciones'] as $medicion) {
            $nombre = str_pad($medicion['nombre'], 40);
            $tiempo = str_pad(number_format($medicion['tiempo'], 4) . ' ms', 16);
            $memoria = self::bytesLegibles($medicion['memoria'] ?? 0);
            $pico = self::bytesLegibles($medicion['memoriaPico'] ?? 0);
            $lineas[] = " {$nombre} {$tiempo} mem:{$memoria} pico:{$pico}";

            if (isset($medicion['iteraciones']) && $medicion['iteraciones'] > 1) {
                $lineas[] = str_repeat(' ', 42) . '(' . $medicion['iteraciones'] . ' iteraciones, ' . number_format($medicion['tiempoTotal'], 4) . ' ms total)';
            }

            if (isset($medicion['porcentaje']) && $medicion['porcentaje'] > 0) {
                $lineas[] = str_repeat(' ', 42) . ($medicion['porcentaje'] > 100 ? ($medicion['porcentaje'] - 100) . '% mas lento' : 'referencia');
            }
        }

        $lineas[] = '';
        $lineas[] = '--- Resumen ---';
        $lineas[] = ' Mediciones: ' . $reporte['resumen']['total'];
        $lineas[] = ' Tiempo total: ' . number_format($reporte['resumen']['tiempoTotal'], 4) . ' ms';
        $lineas[] = ' Memoria pico: ' . $reporte['resumen']['memoriaMaxLegible'];

        return implode("\n", $lineas);
    }

    public static function loggear(string $archivo = ''): bool
    {
        if ($archivo === '') {
            $archivo = DIRECTORIO_RAIZ . '/storage/logs/rendimiento.log';
        }

        $directorio = dirname($archivo);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $contenido = '[' . date('Y-m-d H:i:s') . "]\n";
        $contenido .= self::formatearTexto();
        $contenido .= "\n\n";

        return file_put_contents($archivo, $contenido, FILE_APPEND | LOCK_EX) !== false;
    }

    public static function limpiar(): void
    {
        self::$puntos = [];
        self::$mediciones = [];
    }

    public static function cabeceras(): array
    {
        $reporte = self::reporte();
        return [
            'X-Lite-Tiempo' => number_format($reporte['resumen']['tiempoTotal'], 2) . 'ms',
            'X-Lite-Memoria' => $reporte['resumen']['memoriaMaxLegible'],
            'X-Lite-Mediciones' => (string)$reporte['resumen']['total'],
        ];
    }

    private static function bytesLegibles(int $bytes): string
    {
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $potencia = floor(($bytes ? log($bytes) : 0) / log(1024));
        $potencia = min($potencia, count($unidades) - 1);
        $valor = $bytes / pow(1024, $potencia);
        return round($valor, 2) . ' ' . $unidades[$potencia];
    }
}
