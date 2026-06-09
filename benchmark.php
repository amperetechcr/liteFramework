<?php

declare(strict_types=1);

require_once __DIR__ . '/tests/bootstrap.php';

use LiteFramework\Nucleo\Helpers\AyudanteRendimiento as R;
use LiteFramework\Nucleo\Helpers\AyudanteCache as C;
use LiteFramework\Nucleo\Helpers\AyudanteHttp as Http;
use LiteFramework\Modelos\Operador;
use LiteFramework\Modelos\Rol;

function separador(): void
{
    echo str_repeat('─', 72) . "\n";
}

separador();
echo "  BENCHMARK: liteFramework Performance Core\n";
echo "  " . date('Y-m-d H:i:s') . " | PHP " . phpversion() . "\n";
separador();

// 1. ORM
echo "\n  📦 ORM\n\n";

$iteraciones = 50;

R::comparar([
    'Modelo::contar()' => fn() => Operador::contar(),
    'Modelo::todos()'  => fn() => Operador::todos(),
    'donde()->obtener()' => fn() => Operador::donde('estado_cuenta', 1)->obtener(),
    'buscar()'         => fn() => Operador::buscar(1),
], $iteraciones);

echo R::formatearTexto();
R::limpiar();

// 2. Cache
echo "\n  📦 CACHE\n\n";

$contador = 0;
$generar = function () use (&$contador) {
    $contador++;
    return ['datos' => range(1, 50), 'total' => 50];
};

R::comparar([
    'Cache::guardar+obtener' => function () {
        C::guardar('b_test', 'valor', 60);
        return C::obtener('b_test');
    },
    'Cache::recordar (nuevo)' => fn() => C::recordar('b_new', function () {
 return range(1, 10);
    }, 60),
    'Cache::recordar (cachead)' => fn() => C::recordar('b_cached', fn() => range(1, 10), 60),
    'Cache::recordarJson' => fn() => C::recordarJson('b_json', fn() => ['ok' => true], 60),
], 100);

echo R::formatearTexto();
R::limpiar();

// 3. PHP nativo
echo "\n  📦 PHP BASICO\n\n";

$arr = range(1, 100);
R::comparar([
    'array_sum' => fn() => array_sum($arr),
    'foreach suma' => function () use ($arr) {
        $s = 0;
        foreach ($arr as $v) {
            $s += $v;
        }
        return $s;
    },
    'json_encode' => fn() => json_encode($arr),
    'json_decode' => fn() => json_decode(json_encode($arr) ?: '[]', true),
], 500);

echo R::formatearTexto();
R::limpiar();

// 4. Informacion del sistema
separador();
echo "\n  📊 INFO DEL SISTEMA\n\n";

$memReal = match (true) {
    function_exists('memory_get_usage') => round(memory_get_peak_usage() / 1048576, 2) . ' MB',
    default => 'N/A',
};

echo "  Memoria pico:     {$memReal}\n";
echo "  OPcache:          " . (function_exists('opcache_get_status') && (opcache_get_status(false)['opcache_enabled'] ?? false) ? '✅ Activo' : '❌ No disponible') . "\n";
echo "  APCu:             " . (function_exists('apcu_enabled') && apcu_enabled() ? '✅ Activo' : '❌ No disponible') . "\n";
echo "  Curl:             " . (function_exists('curl_version') ? '✅ ' . (curl_version()['version'] ?? '') : '❌ No disponible') . "\n";
echo "  Extensiones:      " . implode(', ', array_intersect(['pdo', 'pdo_mysql', 'pdo_sqlite', 'curl', 'mbstring', 'json', 'session', 'fileinfo'], get_loaded_extensions())) . "\n";

// 5. Resumen
separador();
echo "\n  ✅ Benchmarks completados\n";
echo "  Para ver mas: php benchmark.php\n\n";
separador();
