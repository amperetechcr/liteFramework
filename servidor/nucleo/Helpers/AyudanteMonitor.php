<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

class AyudanteMonitor extends Helper
{
    private static string $archivoLog = '';

    public static function inicializar(string $archivoLog = ''): void
    {
        if ($archivoLog === '') {
            $archivoLog = DIRECTORIO_RAIZ . '/storage/logs/rendimiento.log';
        }
        self::$archivoLog = $archivoLog;
    }

    public static function obtenerEstadisticas(): array
    {
        self::inicializar();
        $lineas = self::leerLineas();

        if (empty($lineas)) {
            return self::vacio();
        }

        $tiempos = [];
        $memorias = [];
        $picos = [];
        $lentos = 0;
        $distribucion = ['0-50' => 0, '50-100' => 0, '100-200' => 0, '200-500' => 0, '500+' => 0];
        $ultimos = [];

        foreach ($lineas as $linea) {
            if (preg_match('/\[(.*?)\] (GET|POST|PUT|DELETE) (.*?) - ([\d.]+)ms - ([\d.]+ [KMG]?B) pico:([\d.]+ [KMG]?B)/', $linea, $m)) {
                $tiempo = (float)$m[4];
                $tiempos[] = $tiempo;
                $memorias[] = $m[5];
                $picos[] = $m[6];
                $ultimos[] = [
                    'fecha' => $m[1],
                    'metodo' => $m[2],
                    'uri' => $m[3],
                    'tiempo' => $tiempo,
                    'memoria' => $m[5],
                    'pico' => $m[6],
                ];

                if ($tiempo >= 500) {
                    $lentos++;
                }

                if ($tiempo <= 50) {
                    $distribucion['0-50']++;
                } elseif ($tiempo <= 100) {
                    $distribucion['50-100']++;
                } elseif ($tiempo <= 200) {
                    $distribucion['100-200']++;
                } elseif ($tiempo <= 500) {
                    $distribucion['200-500']++;
                } else {
                    $distribucion['500+']++;
                }
            }
        }

        $total = count($tiempos);
        if ($total === 0) {
            return self::vacio();
        }

        $promedio = round(array_sum($tiempos) / $total, 2);
        $maximo = round(max($tiempos), 2);

        rsort($tiempos);
        $p99 = $tiempos[(int)ceil($total * 0.01) - 1] ?? $maximo;
        $p95 = $tiempos[(int)ceil($total * 0.05) - 1] ?? $maximo;
        $mediana = $tiempos[(int)floor($total / 2)] ?? 0;

        $ultimos = array_slice(array_reverse($ultimos), 0, 10);

        return [
            'total' => $total,
            'promedio' => $promedio,
            'maximo' => $maximo,
            'p99' => round($p99, 2),
            'p95' => round($p95, 2),
            'mediana' => round($mediana, 2),
            'lentos' => $lentos,
            'porcentajeLentos' => round(($lentos / max($total, 1)) * 100, 1),
            'distribucion' => $distribucion,
            'ultimos' => $ultimos,
            'memoriaPromedio' => round(array_sum(array_map('floatval', $memorias)) / $total, 2),
            'timestamp' => time(),
        ];
    }

    public static function obtenerUltimos(): array
    {
        $stats = self::obtenerEstadisticas();
        return $stats['ultimos'] ?? [];
    }

    public static function logPath(): string
    {
        self::inicializar();
        return self::$archivoLog;
    }

    private static function leerLineas(): array
    {
        $archivo = self::$archivoLog;
        if (!file_exists($archivo)) {
            return [];
        }

        $tamano = filesize($archivo);
        if ($tamano === 0 || $tamano === false) {
            return [];
        }

        $contenido = @file_get_contents($archivo);
        if ($contenido === false || $contenido === '') {
            return [];
        }

        $lineas = explode("\n", trim($contenido));
        return array_reverse($lineas);
    }

    private static function vacio(): array
    {
        return [
            'total' => 0,
            'promedio' => 0,
            'maximo' => 0,
            'p99' => 0,
            'p95' => 0,
            'mediana' => 0,
            'lentos' => 0,
            'porcentajeLentos' => 0,
            'distribucion' => ['0-50' => 0, '50-100' => 0, '100-200' => 0, '200-500' => 0, '500+' => 0],
            'ultimos' => [],
            'memoriaPromedio' => 0,
            'timestamp' => time(),
        ];
    }
}
