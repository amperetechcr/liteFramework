<?php

/**
 * Script de migraciones - Lite Framework
 * Uso: php servidor/migrar.php [list|ejecutar]
 *   list     - Muestra el estado de todas las migraciones
 *   ejecutar - Aplica las migraciones pendientes (por defecto)
 */

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';
GestorEntorno::cargar();
try {
    $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
    $gestor = new GestorMigraciones($conexion);
    $comando = $argv[1] ?? 'ejecutar';
    switch ($comando) {
        case 'listar':
        case 'list':
            $todas = $gestor->listarTodas();
            echo "\n=== Estado de migraciones ===\n\n";
            foreach ($todas as $m) {
                $icono = $m['estado'] === 'aplicada' ? '[OK]' : '[--]';
                $fecha = $m['fecha'] ?? '';
                echo " {$icono} {$m['archivo']} {$fecha}\n";
            }
            echo "\nTotal: " . count($todas) . " migraciones\n";

            break;
        case 'ejecutar':
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       echo "Buscando migraciones pendientes...\n";
            $resultados = $gestor->ejecutarPendientes();
            if (empty($resultados)) {
                echo "Todo al dia. No hay migraciones pendientes.\n";
                break;
            }

            foreach ($resultados as $r) {
                $icono = '[OK]';
                $detalle = $r['mensaje'] ?? '';
                if ($r['estado'] === 'error') {
                    $icono = '[ERROR]';
                }
                echo " {$icono} {$r['archivo']} {$detalle}\n";
            }

            break;
        default:
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       echo "Comando desconocido: {$comando}\n";
            echo "Uso: php servidor/migrar.php [list|ejecutar]\n";

            exit(1);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
