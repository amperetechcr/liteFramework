<?php

declare(strict_types=1);

namespace LiteFramework\Api\Controladores;

use PDO;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Migraciones\GestorMigraciones;
use Exception;

class MigracionApiControlador
{
    private function verificarRolSuperAdmin(array $respuesta): ?array
    {
        if (empty($_SESSION['operador_id'])) {
            $respuesta['mensaje_error'] = 'Su sesion ha expirado. Recargue la pagina.';
            $respuesta['codigo_error'] = 'no_autenticado';
            return [401, $respuesta];
        }
        $idRol = (int)($_SESSION['operador_rol'] ?? 0);
        if ($idRol !== 1) {
            RegistroAuditoria::seguridad('Intento de operacion de migraciones sin permisos', [
                'id_operador' => $_SESSION['operador_id'],
                'id_rol' => $idRol,
            ]);
            $respuesta['mensaje_error'] = 'Solo el Super Administrador puede realizar esta operacion.';
            $respuesta['codigo_error'] = 'sin_permiso';
            return [403, $respuesta];
        }
        return null;
    }

    public function ejecutar(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        $check = $this->verificarRolSuperAdmin($respuesta);
        if ($check !== null) {
            return $check[1];
        }

        $dirLock = DIRECTORIO_RAIZ . '/storage/locks';
        if (!is_dir($dirLock)) {
            mkdir($dirLock, 0755, true);
        }
        $archivoLock = $dirLock . '/migraciones.lock';
        $lock = fopen($archivoLock, 'c');
        \assert($lock !== false);
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            $respuesta['mensaje_error'] = 'Ya hay una ejecucion de migraciones en curso. Espere e intente de nuevo.';
            $respuesta['codigo_error'] = 'bloqueo_activo';
            return [429, $respuesta];
        }

        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();

            $dirBackup = DIRECTORIO_RAIZ . '/storage/backups';
            if (!is_dir($dirBackup)) {
                mkdir($dirBackup, 0755, true);
            }
            $rutaBackup = $dirBackup . '/respaldo_' . date('Ymd_His') . '.sql';
            $lineasBackup = [];
            $lineasBackup[] = '-- Respaldo automatico generado el ' . date('Y-m-d H:i:s');
            $lineasBackup[] = '-- Origen: Migraciones web' . "\n";

            $stmtTablas = $conexion->query("SHOW TABLES");
            \assert($stmtTablas !== false);
            $tablas = $stmtTablas->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tablas as $tabla) {
                if ($tabla === '_migraciones') {
                    continue;
                }
                $stmtCrear = $conexion->query("SHOW CREATE TABLE `{$tabla}`");
                \assert($stmtCrear !== false);
                $crear = $stmtCrear->fetch(PDO::FETCH_ASSOC);
                $lineasBackup[] = $crear ? array_values($crear)[1] . ';' . "\n" : '';

                $stmtFilas = $conexion->query("SELECT * FROM `{$tabla}`");
                \assert($stmtFilas !== false);
                $filas = $stmtFilas->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($filas)) {
                    $columnas = array_keys($filas[0]);
                    $colList = '`' . implode('`, `', $columnas) . '`';
                    foreach ($filas as $fila) {
                        $vals = array_map(function ($v) use ($conexion) {
                            return $v === null ? 'NULL' : $conexion->quote($v);
                        }, array_values($fila));
                        $lineasBackup[] = "INSERT INTO `{$tabla}` ({$colList}) VALUES (" . implode(', ', $vals) . ");";
                    }
                    $lineasBackup[] = '';
                }
            }

            file_put_contents($rutaBackup, implode("\n", $lineasBackup));

            $gestor = new GestorMigraciones($conexion);
            $resultados = $gestor->ejecutarPendientes();

            $aplicadas = 0;
            $errores = 0;
            $detalle = [];

            foreach ($resultados as $r) {
                $detalle[] = [
                    'archivo' => $r['archivo'],
                    'estado' => $r['estado'],
                    'mensaje' => $r['mensaje'] ?? '',
                ];
                if ($r['estado'] === 'aplicada') {
                    $aplicadas++;
                } elseif ($r['estado'] === 'error') {
                    $errores++;
                }
            }

            $infoPendientes = $gestor->obtenerPendientes();
            $restantes = count($infoPendientes['pendientes']);
            $total = count($gestor->listarTodas());
            $aplicadasTotal = $total - $restantes;

            RegistroAuditoria::info('Migraciones', 'ejecutar_pendientes', [
                'aplicadas' => $aplicadas,
                'errores' => $errores,
                'backup' => basename($rutaBackup),
                'detalle' => $detalle,
            ]);

            $respuesta['estado_operacion'] = true;
            $respuesta['mensaje_error'] = $aplicadas . ' migracion(es) aplicada(s).';
            $respuesta['codigo_error'] = null;
            $respuesta['datos'] = [
                'detalle' => $detalle,
                'resumen' => [
                    'total' => (string)$total,
                    'aplicadas' => (string)$aplicadasTotal,
                    'pendientes' => (string)$restantes,
                ],
            ];
            return [200, $respuesta];
        } catch (Exception $e) {
            RegistroAuditoria::error('Migraciones', 'Error al ejecutar pendientes', [
                'error' => $e->getMessage(),
            ]);
            $respuesta['mensaje_error'] = $e->getMessage();
            $respuesta['codigo_error'] = 'error_ejecucion';
            return [500, $respuesta];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function ejecutarIndividual(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        $check = $this->verificarRolSuperAdmin($respuesta);
        if ($check !== null) {
            return $check[1];
        }

        $archivo = trim($payload['archivo'] ?? '');
        if (empty($archivo) || !preg_match('/^\d+_[\w]+\.sql$/', $archivo)) {
            $respuesta['mensaje_error'] = 'Nombre de archivo invalido.';
            $respuesta['codigo_error'] = 'archivo_invalido';
            return [400, $respuesta];
        }

        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $gestor = new GestorMigraciones($conexion);
            $resultado = $gestor->ejecutarIndividual($archivo);

            $infoPendientes = $gestor->obtenerPendientes();
            $restantes = count($infoPendientes['pendientes']);
            $total = count($gestor->listarTodas());
            $aplicadasTotal = $total - $restantes;

            RegistroAuditoria::info('Migraciones', 'ejecutar_individual', [
                'archivo' => $archivo,
                'resultado' => $resultado['estado'],
            ]);

            if ($resultado['estado'] === 'aplicada') {
                $respuesta['estado_operacion'] = true;
                $respuesta['mensaje_error'] = 'Migracion aplicada correctamente.';
                $respuesta['codigo_error'] = null;
            } else {
                $respuesta['estado_operacion'] = false;
                $respuesta['mensaje_error'] = $resultado['mensaje'] ?? 'Error al aplicar migracion.';
                $respuesta['codigo_error'] = 'error_ejecucion';
            }
            $respuesta['datos'] = [
                'detalle' => [$resultado],
                'resumen' => [
                    'total' => (string)$total,
                    'aplicadas' => (string)$aplicadasTotal,
                    'pendientes' => (string)$restantes,
                ],
            ];
            return [200, $respuesta];
        } catch (Exception $e) {
            RegistroAuditoria::error('Migraciones', 'Error al ejecutar individual', [
                'archivo' => $archivo,
                'error' => $e->getMessage(),
            ]);
            $respuesta['mensaje_error'] = $e->getMessage();
            $respuesta['codigo_error'] = 'error_ejecucion';
            return [500, $respuesta];
        }
    }

    public function resetear(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        $check = $this->verificarRolSuperAdmin($respuesta);
        if ($check !== null) {
            return $check[1];
        }

        $archivo = trim($payload['archivo'] ?? '');
        if (empty($archivo) || !preg_match('/^\d+_[\w]+\.sql$/', $archivo)) {
            $respuesta['mensaje_error'] = 'Nombre de archivo invalido.';
            $respuesta['codigo_error'] = 'archivo_invalido';
            return [400, $respuesta];
        }

        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $gestor = new GestorMigraciones($conexion);
            $resultado = $gestor->resetear($archivo);

            $infoPendientes = $gestor->obtenerPendientes();
            $restantes = count($infoPendientes['pendientes']);
            $total = count($gestor->listarTodas());
            $aplicadasTotal = $total - $restantes;

            RegistroAuditoria::info('Migraciones', 'resetear', ['archivo' => $archivo]);

            if ($resultado['estado'] === 'reseteada') {
                $respuesta['estado_operacion'] = true;
                $respuesta['mensaje_error'] = 'Migracion reseteada. Podra ser re-aplicada.';
                $respuesta['codigo_error'] = null;
            } else {
                $respuesta['estado_operacion'] = false;
                $respuesta['mensaje_error'] = $resultado['mensaje'] ?? 'Error al resetear.';
                $respuesta['codigo_error'] = 'error_resetear';
            }
            $respuesta['datos'] = [
                'resumen' => [
                    'total' => (string)$total,
                    'aplicadas' => (string)$aplicadasTotal,
                    'pendientes' => (string)$restantes,
                ],
            ];
            return [200, $respuesta];
        } catch (Exception $e) {
            RegistroAuditoria::error('Migraciones', 'Error al resetear', [
                'archivo' => $archivo,
                'error' => $e->getMessage(),
            ]);
            $respuesta['mensaje_error'] = $e->getMessage();
            $respuesta['codigo_error'] = 'error_resetear';
            return [500, $respuesta];
        }
    }

    public function verSql(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        $check = $this->verificarRolSuperAdmin($respuesta);
        if ($check !== null) {
            return $check[1];
        }

        $archivo = trim($payload['archivo'] ?? '');
        if (empty($archivo) || !preg_match('/^\d+_[\w]+\.sql$/', $archivo)) {
            $respuesta['mensaje_error'] = 'Nombre de archivo invalido.';
            $respuesta['codigo_error'] = 'archivo_invalido';
            return [400, $respuesta];
        }

        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $gestor = new GestorMigraciones($conexion);
            $sql = $gestor->obtenerSql($archivo);

            if ($sql === null) {
                $respuesta['mensaje_error'] = 'Archivo no encontrado.';
                $respuesta['codigo_error'] = 'archivo_no_encontrado';
                return [404, $respuesta];
            }

            $respuesta['estado_operacion'] = true;
            $respuesta['mensaje_error'] = null;
            $respuesta['codigo_error'] = null;
            $respuesta['datos'] = [
                'archivo' => $archivo,
                'sql' => $sql,
            ];
            return [200, $respuesta];
        } catch (Exception $e) {
            $respuesta['mensaje_error'] = $e->getMessage();
            $respuesta['codigo_error'] = 'error_interno';
            return [500, $respuesta];
        }
    }

    public function respaldo(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        $check = $this->verificarRolSuperAdmin($respuesta);
        if ($check !== null) {
            return $check[1];
        }

        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $gestor = new GestorMigraciones($conexion);
            $resultado = $gestor->crearRespaldo();

            RegistroAuditoria::info('Migraciones', 'respaldo_manual', [
                'archivo' => $resultado['archivo'],
                'tamano' => $resultado['tamano'],
            ]);

            $respuesta['estado_operacion'] = true;
            $respuesta['mensaje_error'] = 'Respaldo creado: ' . $resultado['archivo'];
            $respuesta['codigo_error'] = null;
            $respuesta['datos'] = [
                'archivo' => $resultado['archivo'],
                'tamano' => $resultado['tamano'],
            ];
            return [200, $respuesta];
        } catch (Exception $e) {
            RegistroAuditoria::error('Migraciones', 'Error al crear respaldo', [
                'error' => $e->getMessage(),
            ]);
            $respuesta['mensaje_error'] = $e->getMessage();
            $respuesta['codigo_error'] = 'error_respaldo';
            return [500, $respuesta];
        }
    }
}
