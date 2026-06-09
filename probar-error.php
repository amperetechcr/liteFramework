<?php
/**
 * Script de prueba del sistema de errores, diagnóstico y reparación automática.
 * Ejecutar: http://localhost/liteFramework/probar-error.php?tipo=csrf
 *
 * Tipos disponibles: csrf, sesion, archivos, deadlock, exception, fatal
 */

require_once __DIR__ . '/servidor/autoload.php';
\LiteFramework\Config\GestorEntorno::cargar();
\LiteFramework\Nucleo\ManejadorErrores::registrar();

$tipo = $_GET['tipo'] ?? 'exception';

echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>
<title>Prueba Error - liteFramework</title>
<style>body{font-family:system-ui;background:#0f172a;color:#e2e8f0;padding:2rem;max-width:800px;margin:0 auto;}
h1{color:#f8fafc;}
pre{background:#1e293b;padding:1rem;border-radius:8px;overflow:auto;}
a{color:#3b82f6;}
hr{border-color:#334155;margin:2rem 0;}
.alerta{padding:0.75rem 1rem;border-radius:8px;margin:1rem 0;border:1px solid;}
.alerta-exito{background:rgba(34,197,94,0.1);border-color:#22c55e;color:#22c55e;}
.alerta-error{background:rgba(239,68,68,0.1);border-color:#ef4444;color:#ef4444;}
code{background:#334155;padding:0.125rem 0.375rem;border-radius:4px;}</style></head><body>";

echo "<h1>Prueba del Sistema de Errores</h1>";
echo "<p>Tipo: <code>" . htmlspecialchars($tipo) . "</code></p>";
echo "<hr>";

switch ($tipo) {
    case 'csrf':
        echo "<div class='alerta alerta-exito'>Probando: CSRF expirado (deberia regenerar token automaticamente)</div>";
        $_SESSION['operador_id'] = 1;
        throw new \RuntimeException('token_invalido: CSRF token mismatch');
        break;

    case 'sesion':
        echo "<div class='alerta alerta-exito'>Probando: Sesion expirada (deberia redirigir al login)</div>";
        throw new \RuntimeException('sesion_expirada: Su sesion ha expirado');

    case 'archivos':
        echo "<div class='alerta alerta-exito'>Probando: Directorio temporal faltante (deberia crear el directorio)</div>";
        $_SESSION['operador_id'] = 1;
        $tmp = sys_get_temp_dir() . '/litetest_' . time();
        throw new \RuntimeException("mkdir(): Permission denied - $tmp");

    case 'deadlock':
        echo "<div class='alerta alerta-exito'>Probando: Deadlock en BD (deberia marcar para reintentar)</div>";
        $_SESSION['operador_id'] = 1;
        throw new \RuntimeException('Deadlock found when trying to get lock; try restarting transaction (1213)');

    case 'exception':
        echo "<div class='alerta alerta-exito'>Probando: Excepcion generica (mostrara diagnostico + sugerencias)</div>";
        $_SESSION['operador_id'] = 1;
        throw new \InvalidArgumentException('Error de prueba: parametro invalido');

    case 'fatal':
        echo "<div class='alerta alerta-exito'>Probando: Error fatal (deberia capturarlo en shutdown)</div>";
        $_SESSION['operador_id'] = 1;
        undefined_function_call();
        break;

    default:
        echo "<div class='alerta alerta-error'>Tipo no valido. Usa: csrf, sesion, archivos, deadlock, exception, fatal</div>";
}

echo "<hr><p><a href='?tipo=csrf'>Probar CSRF</a> | 
<a href='?tipo=sesion'>Probar Sesion</a> | 
<a href='?tipo=archivos'>Probar Archivos</a> | 
<a href='?tipo=deadlock'>Probar Deadlock</a> | 
<a href='?tipo=exception'>Probar Exception</a> | 
<a href='?tipo=fatal'>Probar Fatal</a></p>";
echo "</body></html>";
