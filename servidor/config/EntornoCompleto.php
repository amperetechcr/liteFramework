<?php

declare(strict_types=1);

namespace LiteFramework\Config;

use LiteFramework\Seguridad\RegistroAuditoria;
use Exception;

class EntornoCompleto
{
    private static ?array $pythonEnvs = null;

    public static function obtenerTodo(): array
    {
        return [
            'framework' => self::framework(),
            'php' => self::php(),
            'base_datos' => self::baseDatos(),
            'variables_entorno' => self::variablesEntorno(),
            'python_crewai' => self::pythonCrewai(),
            'herramientas_mcp' => self::herramientasMcp(),
            'agentes' => self::agentes(),
            'archivos_congelados' => self::archivosCongelados(),
            'sistema' => self::sistema(),
        ];
    }

    private static function framework(): array
    {
        $env = GestorEntorno::obtener('APP_ENTORNO', 'desarrollo');
        $debug = GestorEntorno::obtener('APP_DEPURACION', true);
        return [
            'version' => '1.2.1',
            'entorno' => $env,
            'depuracion' => $debug === true || $debug === 'true',
            'ai_agent_role' => GestorEntorno::obtener('AI_AGENT_ROLE', 'worker'),
        ];
    }

    private static function php(): array
    {
        return [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'os' => PHP_OS,
            'memoria_maxima' => ini_get('memory_limit'),
            'timeout' => ini_get('max_execution_time'),
            'extensiones' => [
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'mbstring' => extension_loaded('mbstring'),
                'json' => extension_loaded('json'),
                'gd' => extension_loaded('gd'),
                'curl' => extension_loaded('curl'),
                'openssl' => extension_loaded('openssl'),
            ],
        ];
    }

    private static function baseDatos(): array
    {
        try {
            $conn = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $stmt = $conn->query('SELECT VERSION() as v');
            $version = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC)['v'] ?? '?' : '?';
            return [
                'conectado' => true,
                'version' => $version,
                'nombre' => GestorEntorno::obtener('DB_NOMBRE', 'lite'),
                'anfitrion' => GestorEntorno::obtener('DB_ANFITRION', 'localhost'),
            ];
        } catch (Exception $e) {
            return [
                'conectado' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private static function variablesEntorno(): array
    {
        $vars = [];
        $archivo = __DIR__ . '/../../.env';
        if (file_exists($archivo)) {
            $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if ($linea === '' || str_starts_with($linea, '#')) {
                    continue;
                }
                $partes = explode('=', $linea, 2);
                if (count($partes) !== 2) {
                    continue;
                }
                $clave = trim($partes[0]);
                $valor = trim($partes[1]);
                $vars[$clave] = self::enmascararSiSensible($clave, $valor);
            }
        }
        return $vars;
    }

    private static function pythonCrewai(): array
    {
        if (self::$pythonEnvs !== null) {
            return self::$pythonEnvs;
        }

        $python = 'C:\\Users\\Tech\\.crewai-venv\\Scripts\\python.exe';
        $helper = 'C:\\Users\\Tech\\.config\\crewai\\entorno_helper.py';
        $cmd = sprintf('"%s" "%s" 2>NUL', $python, $helper);
        $out = shell_exec($cmd);
        $data = $out ? json_decode($out, true) : [];

        self::$pythonEnvs = [
            'version_python' => $data['version_python'] ?? '?',
            'crewai_version' => $data['crewai_version'] ?? '?',
            'deepseek' => $data['deepseek'] ?? '?',
            'agentes_registrados' => $data['agentes_registrados'] ?? 0,
            'mcps_registrados' => $data['mcps_registrados'] ?? 0,
            'archivos_congelados' => $data['archivos_congelados'] ?? 0,
            'descongelados' => $data['descongelados'] ?? 0,
            '_raw' => $data,
        ];
        return self::$pythonEnvs;
    }

    private static function herramientasMcp(): array
    {
        $py = self::pythonCrewai();
        $data = $py['_raw'] ?? [];
        return [
            'total' => $data['tools_mcp'] ?? 0,
            'lista' => $data['tools_lista'] ?? [],
        ];
    }

    private static function agentes(): array
    {
        $py = self::pythonCrewai();
        $data = $py['_raw'] ?? [];
        return $data['agentes'] ?? [];
    }

    private static function archivosCongelados(): array
    {
        $py = self::pythonCrewai();
        return [
            'total' => $py['archivos_congelados'] ?? 0,
            'descongelados' => $py['descongelados'] ?? 0,
            'total_raw' => ($py['archivos_congelados'] ?? 0) + ($py['descongelados'] ?? 0),
        ];
    }

    private static function sistema(): array
    {
        return [
            'espacio_disco' => [
                'total' => disk_total_space(__DIR__) ?: 0,
                'libre' => disk_free_space(__DIR__) ?: 0,
            ],
            'memoria_php' => [
                'uso' => memory_get_usage(true),
                'pico' => memory_get_peak_usage(true),
            ],
            'carga' => function_exists('sys_getloadavg') ? sys_getloadavg() : null,
        ];
    }

    private static function enmascararSiSensible(string $clave, string $valor): string
    {
        $sensibles = ['CLAVE', 'PASSWORD', 'SECRET', 'TOKEN', 'KEY', 'DSN'];
        foreach ($sensibles as $s) {
            if (stripos($clave, $s) !== false) {
                if (strlen($valor) <= 4) {
                    return '****';
                }
                return substr($valor, 0, 4) . '****' . substr($valor, -4);
            }
        }
        return $valor;
    }

    public static function obtenerEntornoPython(): array
    {
        return self::pythonCrewai();
    }
}
