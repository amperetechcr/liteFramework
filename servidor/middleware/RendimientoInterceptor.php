<?php

declare(strict_types=1);

namespace LiteFramework\Middleware;

use LiteFramework\Nucleo\Interceptor;
use LiteFramework\Nucleo\Helpers\AyudanteRendimiento;

class RendimientoInterceptor implements Interceptor
{
    private int $umbralLento;
    private bool $loggearSiempre;

    public function __construct(int $umbralLento = 500, bool $loggearSiempre = false)
    {
        $this->umbralLento = $umbralLento;
        $this->loggearSiempre = $loggearSiempre;
    }

    public function manejar(array $params, callable $siguiente): mixed
    {
        AyudanteRendimiento::iniciar('request_completo');

        $inicioMemoria = memory_get_usage();
        $inicio = microtime(true);

        $resultado = $siguiente($params);

        $tiempo = round((microtime(true) - $inicio) * 1000, 2);
        $memoriaUsada = memory_get_usage() - $inicioMemoria;
        $memoriaPico = memory_get_peak_usage();

        if ($tiempo >= $this->umbralLento || $this->loggearSiempre) {
            $uri = $_SERVER['REQUEST_URI'] ?? 'desconocida';
            $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $linea = sprintf(
                "[%s] %s %s - %.2fms - %s pico:%s\n",
                date('Y-m-d H:i:s'),
                $metodo,
                $uri,
                $tiempo,
                self::bytesLegibles($memoriaUsada),
                self::bytesLegibles($memoriaPico)
            );
            error_log($linea);
        }

        if (!headers_sent()) {
            header('X-Lite-Tiempo: ' . number_format($tiempo, 2) . 'ms');
            header('X-Lite-Memoria: ' . self::bytesLegibles($memoriaUsada));
            header('X-Lite-Memoria-Pico: ' . self::bytesLegibles($memoriaPico));
        }

        return $resultado;
    }

    private static function bytesLegibles(int $bytes): string
    {
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $potencia = (int)floor(($bytes ? log($bytes) : 0) / log(1024));
        $potencia = min($potencia, count($unidades) - 1);
        $valor = $bytes / pow(1024, $potencia);
        return round($valor, 2) . ' ' . $unidades[$potencia];
    }
}
