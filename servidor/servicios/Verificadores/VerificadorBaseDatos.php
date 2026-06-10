<?php

declare(strict_types=1);

namespace LiteFramework\Servicios\Verificadores;

use LiteFramework\Servicios\ContextoError;

class VerificadorBaseDatos implements VerificadorError
{
    public function tipo(): string
    {
        return 'base_datos';
    }

    public function diagnosticar(ContextoError $ctx): ?array
    {
        $msg = $ctx->mensaje;

        if (str_contains($msg, "Base table or view not found") || preg_match('/Table\s+\S+\.\S+\s+doesn\'t exist/', $msg)) {
            preg_match("/Table\s+'(\S+)\.(\S+)'\s+doesn't exist/", $msg, $m);
            $tabla = $m[2] ?? 'desconocida';
            $db = $m[1] ?? 'desconocida';
            return [
                'tipo' => 'tabla_faltante',
                'detalle' => "La tabla '{$tabla}' no existe en la base de datos '{$db}'.",
                'tabla' => $tabla,
                'bd' => $db,
            ];
        }

        if (str_contains($msg, "Access denied") || str_contains($msg, "1045")) {
            return [
                'tipo' => 'credenciales',
                'detalle' => 'Credenciales de base de datos incorrectas. Verifique DB_USUARIO y DB_CLAVE en el archivo .env.',
                'usuario' => defined('DB_USUARIO') ? DB_USUARIO : 'desconocido',
                'anfitrion' => defined('DB_ANFITRION') ? DB_ANFITRION : 'localhost',
            ];
        }

        if (str_contains($msg, "Unknown database") || str_contains($msg, "1049")) {
            preg_match("/Unknown database\s+'(\S+)'/", $msg, $m);
            $db = $m[1] ?? (defined('DB_NOMBRE') ? DB_NOMBRE : 'desconocida');
            return [
                'tipo' => 'bd_no_existe',
                'detalle' => "La base de datos '{$db}' no existe.",
                'bd' => $db,
            ];
        }

        if (str_contains($msg, "Connection refused") || str_contains($msg, "2002")) {
            $host = defined('DB_ANFITRION') ? DB_ANFITRION : 'localhost';
            $recomendacion = 'Verifique que MySQL esté en ejecución:';
            $recomendacion .= PHP_EOL . "  sudo systemctl start mysql  (Linux)";
            $recomendacion .= PHP_EOL . "  O inicie el servicio MySQL desde XAMPP Control Panel (Windows)";
            $recomendacion .= PHP_EOL . "  Verifique que el puerto 3306 no esté bloqueado.";
            return [
                'tipo' => 'conexion',
                'detalle' => "No se pudo conectar a MySQL en '{$host}'. " . $recomendacion,
                'anfitrion' => $host,
            ];
        }

        if (str_contains($msg, "Unknown column") || str_contains($msg, "1054")) {
            preg_match("/Unknown column\s+'(\S+)'/", $msg, $m);
            $columna = $m[1] ?? 'desconocida';
            return [
                'tipo' => 'columna_faltante',
                'detalle' => "La columna '{$columna}' no existe. Puede faltar una migración.",
                'columna' => $columna,
            ];
        }

        if (str_contains($msg, "Deadlock") || str_contains($msg, "1213")) {
            return [
                'tipo' => 'deadlock',
                'detalle' => 'Se detectó un deadlock en la base de datos. La operación se reintentará automáticamente.',
            ];
        }

        return null;
    }

    public function tieneRemedioAutomatico(): bool
    {
        return true;
    }

    public function ejecutarRemedio(array $diagnostico): array
    {
        $tipo = $diagnostico['tipo'] ?? '';
        if ($tipo === 'deadlock') {
            return ['exito' => true, 'mensaje' => 'Deadlock detectado. Reintente la operación.', 'reintentar' => true];
        }
        return ['exito' => false, 'mensaje' => 'Este problema requiere intervención manual.', 'reintentar' => false];
    }

    public function obtenerSugerencias(array $diagnostico): array
    {
        $tipo = $diagnostico['tipo'] ?? '';
        $sugs = [];

        $bd = $diagnostico['bd'] ?? 'lite';
        $usuario = $diagnostico['usuario'] ?? 'lite';
        $anfitrion = $diagnostico['anfitrion'] ?? 'localhost';
        $columna = $diagnostico['columna'] ?? '';
        $tabla = $diagnostico['tabla'] ?? '';

        switch ($tipo) {
            case 'tabla_faltante':
                $sugs[] = 'Ejecute las migraciones pendientes:';
                $sugs[] = '  php servidor/consola/ejecutar_migraciones.php';
                if ($tabla) {
                    $sugs[] = "Si la tabla '{$tabla}' ya debería existir, verifique que la migración correspondiente esté en servidor/migraciones/.";
                } else {
                    $sugs[] = 'Si la tabla ya debería existir, verifique que la migración correspondiente esté en servidor/migraciones/.';
                }
                break;
            case 'credenciales':
                $sugs[] = 'Revise las credenciales en el archivo .env en la raíz del proyecto.';
                $sugs[] = 'Comando para crear el usuario (ejecutar como root de MySQL):';
                $sugs[] = "  mysql -u root -p -e \"GRANT ALL ON {$bd}.* TO '{$usuario}'@'{$anfitrion}' IDENTIFIED BY 'su_clave';\"";
                break;
            case 'bd_no_existe':
                $sugs[] = "La base de datos '{$bd}' no existe. Creela con:";
                $sugs[] = "  mysql -u root -p -e \"CREATE DATABASE {$bd} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"";
                break;
            case 'conexion':
                $sugs[] = $diagnostico['detalle'] ?? 'Verifique que el servicio MySQL esté activo.';
                $sugs[] = 'Si está usando XAMPP, abra el panel de control e inicie MySQL.';
                $sugs[] = 'Si está en Linux: sudo systemctl start mysql';
                break;
            case 'columna_faltante':
                $sugs[] = "La columna '{$columna}' no existe en la tabla.";
                $sugs[] = 'Verifique que todas las migraciones estén ejecutadas.';
                $sugs[] = '  php servidor/consola/ejecutar_migraciones.php';
                break;
            case 'deadlock':
                $sugs[] = 'Deadlock detectado. El sistema reintentará la operación automáticamente.';
                $sugs[] = 'Si el problema persiste, revise consultas de larga duración con: SHOW PROCESSLIST;';
                break;
        }
        return $sugs;
    }
}
