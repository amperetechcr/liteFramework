<?php

declare(strict_types=1);

namespace LiteFramework\Migraciones;

use PDOException;
use PDO;
use Exception;

class GestorMigraciones
{
    private PDO $conexion;
    private string $directorio;

    public function __construct(PDO $conexion, ?string $directorio = null)
    {
        $this->conexion = $conexion;
        if ($directorio === null) {
            $directorio = __DIR__;
        }
        $this->directorio = rtrim($directorio, '/\\');
        $this->asegurarTablaMigraciones();
    }

    private function asegurarTablaMigraciones(): void
    {
        $this->conexion->exec("
            CREATE TABLE IF NOT EXISTS `_migraciones` (
                `id_migracion` INT AUTO_INCREMENT PRIMARY KEY,
                `archivo` VARCHAR(255) NOT NULL UNIQUE,
                `hash_contenido` VARCHAR(64) NOT NULL,
                `fecha_aplicacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function obtenerPendientes(): array
    {
        $stmtAplicadas = $this->conexion->query("SELECT archivo, hash_contenido FROM _migraciones");
        \assert($stmtAplicadas !== false);
        $aplicadas = $stmtAplicadas->fetchAll(PDO::FETCH_KEY_PAIR);

        $archivos = glob($this->directorio . '/*.sql') ?: [];
        sort($archivos);

        $pendientes = [];
        $advertencias = [];
        foreach ($archivos as $ruta) {
            $archivo = basename($ruta);
            $contenido = file_get_contents($ruta) ?: '';
            $hash = hash('sha256', $contenido);

            if (!isset($aplicadas[$archivo])) {
                $pendientes[] = ['archivo' => $archivo, 'ruta' => $ruta, 'hash' => $hash];
            } elseif ($aplicadas[$archivo] !== $hash) {
                $advertencias[] = "La migracion '{$archivo}' ha sido modificada desde que se aplico.";
            }
        }

        return ['pendientes' => $pendientes, 'advertencias' => $advertencias];
    }

    public function obtenerSqlPendientes(): array
    {
        $info = $this->obtenerPendientes();
        $sqls = [];
        foreach ($info['pendientes'] as $item) {
            $sqls[] = [
                'archivo' => $item['archivo'],
                'sql' => file_get_contents($item['ruta']),
            ];
        }
        return $sqls;
    }

    public function ejecutarPendientes(): array
    {
        $info = $this->obtenerPendientes();
        $resultados = [];

        $this->limpiarEntradasHuerfanas();

        foreach ($info['pendientes'] as $item) {
            $sql = file_get_contents($item['ruta']) ?: '';
            if (empty(trim($sql))) {
                $resultados[] = ['archivo' => $item['archivo'], 'estado' => 'saltada', 'mensaje' => 'Archivo vacio'];
                continue;
            }

            $sentencias = $this->separarSentencias($sql);
            $errores = [];

            $this->conexion->beginTransaction();

            foreach ($sentencias as $sentencia) {
                $sentencia = trim($sentencia);
                if (empty($sentencia)) {
                    continue;
                }

                try {
                    $this->conexion->exec($sentencia);
                } catch (PDOException $e) {
                    $codigo = $e->getCode();
                    $mensaje = $e->getMessage();

                    if ($codigo === '42000' && strpos($mensaje, 'Duplicate key name') !== false) {
                        continue;
                    }

                    $errores[] = $mensaje;
                    break;
                }
            }

            if (empty($errores)) {
                if ($this->conexion->inTransaction()) {
                    $this->conexion->commit();
                }

                $stmt = $this->conexion->prepare("
                    INSERT INTO _migraciones (archivo, hash_contenido)
                    VALUES (:archivo, :hash)
                ");
                \assert($stmt !== false);
                $stmt->execute([
                    ':archivo' => $item['archivo'],
                    ':hash' => $item['hash'],
                ]);

                $resultados[] = ['archivo' => $item['archivo'], 'estado' => 'aplicada'];
            } else {
                if ($this->conexion->inTransaction()) {
                    $this->conexion->rollBack();
                }

                $resultados[] = [
                    'archivo' => $item['archivo'],
                    'estado' => 'error',
                    'mensaje' => implode('; ', array_unique($errores)),
                ];
            }
        }

        return $resultados;
    }

    private function limpiarEntradasHuerfanas(): void
    {
        $stmtAplicadas = $this->conexion->query("SELECT archivo FROM _migraciones");
        \assert($stmtAplicadas !== false);
        $aplicadas = $stmtAplicadas->fetchAll(PDO::FETCH_COLUMN);

        foreach ($aplicadas as $archivo) {
            $ruta = $this->directorio . '/' . $archivo;
            if (!file_exists($ruta)) {
                $stmt = $this->conexion->prepare("DELETE FROM _migraciones WHERE archivo = :archivo");
                \assert($stmt !== false);
                $stmt->execute([':archivo' => $archivo]);
            }
        }
    }

    private function separarSentencias(string $sql): array
    {
        $sql = preg_replace('/^-- .*$/m', '', $sql);
        $sql = preg_replace('/^#.*$/m', '', $sql);
        $sentencias = explode(';', $sql);
        return array_filter(array_map('trim', $sentencias));
    }

    public function listarTodas(): array
    {
        $stmtAplicadas = $this->conexion->query("
            SELECT archivo, hash_contenido, fecha_aplicacion FROM _migraciones ORDER BY archivo
        ");
        \assert($stmtAplicadas !== false);
        $aplicadas = $stmtAplicadas->fetchAll(PDO::FETCH_ASSOC);

        $mapaAplicadas = [];
        foreach ($aplicadas as $a) {
            $mapaAplicadas[$a['archivo']] = $a;
        }

        $archivos = glob($this->directorio . '/*.sql') ?: [];
        sort($archivos);

        $todas = [];
        foreach ($archivos as $ruta) {
            $archivo = basename($ruta);
            if (isset($mapaAplicadas[$archivo])) {
                $todas[] = [
                    'archivo' => $archivo,
                    'estado' => 'aplicada',
                    'fecha' => $mapaAplicadas[$archivo]['fecha_aplicacion'],
                ];
            } else {
                $todas[] = [
                    'archivo' => $archivo,
                    'estado' => 'pendiente',
                    'fecha' => null,
                ];
            }
        }

        return $todas;
    }

    public function ejecutarIndividual(string $archivo): array
    {
        $ruta = $this->directorio . '/' . $archivo;
        if (!file_exists($ruta)) {
            return ['archivo' => $archivo, 'estado' => 'error', 'mensaje' => 'Archivo no encontrado'];
        }

        $stmtAplicadas = $this->conexion->query("SELECT archivo FROM _migraciones");
        \assert($stmtAplicadas !== false);
        $aplicadas = $stmtAplicadas->fetchAll(PDO::FETCH_COLUMN);
        if (in_array($archivo, $aplicadas, true)) {
            return ['archivo' => $archivo, 'estado' => 'error', 'mensaje' => 'Ya esta aplicada'];
        }

        $sql = file_get_contents($ruta) ?: '';
        if (empty(trim($sql))) {
            return ['archivo' => $archivo, 'estado' => 'saltada', 'mensaje' => 'Archivo vacio'];
        }

        $sentencias = $this->separarSentencias($sql);
        $errores = [];

        $this->conexion->beginTransaction();

        foreach ($sentencias as $sentencia) {
            $sentencia = trim($sentencia);
            if (empty($sentencia)) {
                continue;
            }

            try {
                $this->conexion->exec($sentencia);
            } catch (PDOException $e) {
                $codigo = $e->getCode();
                $mensaje = $e->getMessage();

                if ($codigo === '42000' && strpos($mensaje, 'Duplicate key name') !== false) {
                    continue;
                }

                $errores[] = $mensaje;
                break;
            }
        }

        if (empty($errores)) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->commit();
            }

            $hash = hash('sha256', $sql);
            $stmt = $this->conexion->prepare("
                INSERT INTO _migraciones (archivo, hash_contenido)
                VALUES (:archivo, :hash)
            ");
            \assert($stmt !== false);
            $stmt->execute([':archivo' => $archivo, ':hash' => $hash]);

            return ['archivo' => $archivo, 'estado' => 'aplicada'];
        } else {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            return ['archivo' => $archivo, 'estado' => 'error', 'mensaje' => implode('; ', array_unique($errores))];
        }
    }

    public function resetear(string $archivo): array
    {
        $stmtAplicadas = $this->conexion->query("SELECT archivo FROM _migraciones");
        \assert($stmtAplicadas !== false);
        $aplicadas = $stmtAplicadas->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array($archivo, $aplicadas, true)) {
            return ['archivo' => $archivo, 'estado' => 'error', 'mensaje' => 'No esta aplicada'];
        }

        $stmt = $this->conexion->prepare("DELETE FROM _migraciones WHERE archivo = :archivo");
        \assert($stmt !== false);
        $stmt->execute([':archivo' => $archivo]);

        return ['archivo' => $archivo, 'estado' => 'reseteada'];
    }

    public function obtenerSql(string $archivo): ?string
    {
        $ruta = $this->directorio . '/' . $archivo;
        if (!file_exists($ruta)) {
            return null;
        }
        return file_get_contents($ruta) ?: '';
    }

    public function crearRespaldo(): array
    {
        $dirBackups = dirname($this->directorio, 2) . '/storage/backups';
        if (!is_dir($dirBackups)) {
            mkdir($dirBackups, 0755, true);
        }

        $nombreArchivo = 'respaldo_' . date('Ymd_His') . '.sql';
        $rutaCompleta = $dirBackups . '/' . $nombreArchivo;

        $stmtTablas = $this->conexion->query("SHOW TABLES");
        \assert($stmtTablas !== false);
        $tablas = $stmtTablas->fetchAll(PDO::FETCH_COLUMN);
        $contenido = '';

        foreach ($tablas as $tabla) {
            if ($tabla === '_migraciones') {
                continue;
            }

            $stmtEstructura = $this->conexion->query("SHOW CREATE TABLE `$tabla`");
            \assert($stmtEstructura !== false);
            $estructura = $stmtEstructura->fetch(PDO::FETCH_ASSOC);
            $contenido .= "DROP TABLE IF EXISTS `$tabla`;\n";
            $contenido .= $estructura['Create Table'] . ";\n\n";

            $stmtFilas = $this->conexion->query("SELECT * FROM `$tabla`");
            \assert($stmtFilas !== false);
            $filas = $stmtFilas->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($filas)) {
                foreach ($filas as $fila) {
                    $valores = array_map(function ($v) {
                        return $v === null ? 'NULL' : $this->conexion->quote($v);
                    }, $fila);
                    $columnas = implode('`, `', array_keys($fila));
                    $contenido .= "INSERT INTO `$tabla` (`$columnas`) VALUES (" . implode(', ', $valores) . ");\n";
                }
                $contenido .= "\n";
            }
        }

        file_put_contents($rutaCompleta, $contenido);
        return ['archivo' => $nombreArchivo, 'tamano' => strlen($contenido)];
    }

    public function listarRespaldos(): array
    {
        $dirBackups = dirname($this->directorio, 2) . '/storage/backups';
        if (!is_dir($dirBackups)) {
            return [];
        }
        $archivos = glob($dirBackups . '/respaldo_*.sql') ?: [];
        rsort($archivos);
        $respaldos = [];
        foreach ($archivos as $ruta) {
            $archivo = basename($ruta);
            $tamano = (int)filesize($ruta);
            $respaldos[] = [
                'archivo' => $archivo,
                'tamano' => $tamano,
                'tamano_formato' => $this->formatoTamano($tamano),
                'fecha' => date('Y-m-d H:i:s', (int)filemtime($ruta)),
            ];
        }
        return $respaldos;
    }

    public function restaurarRespaldo(string $archivo): array
    {
        $dirBackups = dirname($this->directorio, 2) . '/storage/backups';
        $rutaBackup = $dirBackups . '/' . basename($archivo);
        if (!file_exists($rutaBackup)) {
            return ['estado' => 'error', 'mensaje' => 'Archivo de respaldo no encontrado.'];
        }

        $this->crearRespaldo();

        $this->conexion->exec("SET FOREIGN_KEY_CHECKS = 0");
        $stmtTablas = $this->conexion->query("SHOW TABLES");
        \assert($stmtTablas !== false);
        $tablas = $stmtTablas->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tablas as $tabla) {
            if ($tabla === '_migraciones') {
                continue;
            }
            $this->conexion->exec("DROP TABLE IF EXISTS `{$tabla}`");
        }

        $sql = file_get_contents($rutaBackup) ?: '';
        $sentencias = $this->separarSentencias($sql);
        $errores = [];
        foreach ($sentencias as $sentencia) {
            $sentencia = trim($sentencia);
            if (empty($sentencia)) {
                continue;
            }
            try {
                $this->conexion->exec($sentencia);
            } catch (PDOException $e) {
                $errores[] = $e->getMessage();
            }
        }
        $this->conexion->exec("SET FOREIGN_KEY_CHECKS = 1");

        if (!empty($errores)) {
            return ['estado' => 'error', 'mensaje' => implode('; ', array_unique($errores))];
        }
        return ['estado' => 'restaurado', 'archivo' => $archivo];
    }

    public function obtenerUltimaMigracion(): ?string
    {
        try {
            $stmtUltimo = $this->conexion->query("SELECT archivo FROM _migraciones ORDER BY id_migracion DESC LIMIT 1");
            \assert($stmtUltimo !== false);
            $archivo = $stmtUltimo->fetchColumn();
            return $archivo !== false && $archivo !== null ? (string)$archivo : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function eliminarRespaldo(string $archivo): array
    {
        $dirBackups = dirname($this->directorio, 2) . '/storage/backups';
        $ruta = $dirBackups . '/' . basename($archivo);
        if (!file_exists($ruta)) {
            return ['eliminado' => false, 'mensaje' => 'Archivo no encontrado.'];
        }
        if (!unlink($ruta)) {
            return ['eliminado' => false, 'mensaje' => 'No se pudo eliminar el archivo.'];
        }
        return ['eliminado' => true, 'archivo' => $archivo];
    }

    private function formatoTamano(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
