<?php

declare(strict_types=1);

namespace LiteFramework\Servicios\Verificadores;

use LiteFramework\Servicios\ContextoError;

class VerificadorSistema implements VerificadorError
{
    public function tipo(): string
    {
        return 'sistema';
    }

    public function diagnosticar(ContextoError $ctx): ?array
    {
        $msg = $ctx->mensaje;

        if (str_contains($msg, 'Allowed memory size') || str_contains($msg, 'memory_limit')) {
            preg_match('/(\d+)\s*bytes/', $msg, $m);
            $usado = isset($m[1]) ? round((int)$m[1] / 1048576, 1) : round(memory_get_usage(true) / 1048576, 1);
            $limite = ini_get('memory_limit');
            $limiteNum = (int)$limite;
            $recomendado = max($limiteNum * 2, 256);
            return [
                'tipo' => 'memoria_insuficiente',
                'detalle' => "Memoria insuficiente. Usando {$usado} MB de {$limite}.",
                'usado_mb' => $usado,
                'limite_actual' => $limite,
                'recomendado_mb' => $recomendado,
            ];
        }

        if (str_contains($msg, 'Maximum execution time') || str_contains($msg, 'max_execution_time')) {
            $actual = ini_get('max_execution_time');
            $recomendado = max((int)$actual * 2, 120);
            return [
                'tipo' => 'tiempo_agotado',
                'detalle' => "Tiempo de ejecución agotado. Límite actual: {$actual}s.",
                'limite_actual' => $actual,
                'recomendado_s' => $recomendado,
            ];
        }

        if (str_contains($msg, 'Class') && (str_contains($msg, 'not found') || str_contains($msg, "No such file"))) {
            preg_match("/Class\s+\"([^\"]+)\"\s+not found/", $msg, $m);
            if (empty($m[1])) {
                preg_match("/Class\s+'([^']+)'\s+not found/", $msg, $m);
            }
            $clase = $m[1] ?? 'desconocida';
            return [
                'tipo' => 'clase_faltante',
                'detalle' => "La clase '{$clase}' no fue encontrada por el autoloader.",
                'clase' => $clase,
            ];
        }

        if (str_contains($msg, 'Call to undefined function')) {
            preg_match("/Call to undefined function\s+(\S+)/", $msg, $m);
            $funcion = $m[1] ?? 'desconocida';
            $extension = $this->funcionAExtension($funcion);
            return [
                'tipo' => 'extension_faltante',
                'detalle' => "La función '{$funcion}' no está disponible.",
                'funcion' => $funcion,
                'extension' => $extension,
            ];
        }

        return null;
    }

    public function tieneRemedioAutomatico(): bool
    {
        return false;
    }

    public function ejecutarRemedio(array $diagnostico): array
    {
        return ['exito' => false, 'mensaje' => 'Este problema requiere configuración del servidor.', 'reintentar' => false];
    }

    public function obtenerSugerencias(array $diagnostico): array
    {
        $tipo = $diagnostico['tipo'] ?? '';
        $sugs = [];

        switch ($tipo) {
            case 'memoria_insuficiente':
                $actual = $diagnostico['limite_actual'] ?? '128M';
                $recomendado = ($diagnostico['recomendado_mb'] ?? 256) . 'M';
                $sugs[] = "Memoria insuficiente. Límite actual: {$actual}.";
                $sugs[] = "Aumente el límite en php.ini:";
                $sugs[] = "  memory_limit = {$recomendado}";
                $sugs[] = 'También puede ajustar este valor desde Administración → Configuración del servidor.';
                break;
            case 'tiempo_agotado':
                $actual = $diagnostico['limite_actual'] ?? '30';
                $recomendado = ($diagnostico['recomendado_s'] ?? 120) . '';
                $sugs[] = "Tiempo de ejecución agotado. Límite actual: {$actual}s.";
                $sugs[] = "Aumente el límite en php.ini:";
                $sugs[] = "  max_execution_time = {$recomendado}";
                break;
            case 'clase_faltante':
                $clase = $diagnostico['clase'] ?? 'desconocida';
                $sugs[] = "La clase '{$clase}' no fue encontrada.";
                $sugs[] = 'Verifique que:';
                $sugs[] = '  1. El archivo existe en la ruta correcta.';
                $sugs[] = '  2. El namespace coincide con la estructura de directorios.';
                $sugs[] = '  3. Si usa Composer, ejecute: composer dump-autoload';
                break;
            case 'extension_faltante':
                $ext = $diagnostico['extension'] ?? '';
                $funcion = $diagnostico['funcion'] ?? '';
                $sugs[] = "La función '{$funcion}' requiere la extensión PHP '{$ext}'.";
                $sugs[] = "En Windows: descomente extension={$ext} en php.ini";
                $sugs[] = "En Linux: sudo apt install php-{$ext}  o  sudo phpenmod {$ext}";
                break;
        }
        return $sugs;
    }

    private function funcionAExtension(string $funcion): string
    {
        $mapa = [
            'imagecreate' => 'gd',
            'imagepng' => 'gd',
            'imagejpeg' => 'gd',
            'mb_strlen' => 'mbstring',
            'mb_substr' => 'mbstring',
            'json_encode' => 'json',
            'json_decode' => 'json',
            'curl_init' => 'curl',
            'curl_exec' => 'curl',
            'mysqli_connect' => 'mysqli',
            'pg_connect' => 'pgsql',
            'xml_parse' => 'xml',
            'simplexml_load_string' => 'simplexml',
            'openssl_encrypt' => 'openssl',
            'openssl_decrypt' => 'openssl',
            'hash' => 'hash',
            'password_hash' => 'sodium (o nativo desde PHP 5.5)',
            'file_get_contents' => 'json (o estándar)',
            'exif_read_data' => 'exif',
            'zip_open' => 'zip',
            'zip_read' => 'zip',
        ];
        return $mapa[$funcion] ?? $funcion;
    }
}
