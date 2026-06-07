<?php

declare(strict_types=1);

namespace LiteFramework\Seguridad;

use PDOException;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Nucleo\DialectoBaseDatos;
use PDO;

class RegistroAuditoria
{
    const NIVEL_INFO       = 'INFO';
    const NIVEL_ADVERTENCIA = 'ADVERTENCIA';
    const NIVEL_ERROR      = 'ERROR';
    const NIVEL_SEGURIDAD  = 'SEGURIDAD';
    const NIVEL_AUDITORIA  = 'AUDITORIA';

    private static bool $bitacoraHabilitada = true;
    private static ?string $archivoRuta = null;

    public static function deshabilitarBitacora(): void
    {
        self::$bitacoraHabilitada = false;
    }

    public static function habilitarArchivo(?string $ruta = null): void
    {
        if ($ruta === null) {
            $ruta = __DIR__ . '/../../storage/logs/trazabilidad.log';
        }
        $directorio = dirname($ruta);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        self::$archivoRuta = $ruta;
    }

    public static function info(string $modulo, string $accion, mixed $detalle = null): void
    {
        self::registrar(self::NIVEL_INFO, $modulo, $accion, $detalle);
    }

    public static function advertencia(string $modulo, string $accion, mixed $detalle = null): void
    {
        self::registrar(self::NIVEL_ADVERTENCIA, $modulo, $accion, $detalle);
    }

    public static function error(string $modulo, string $accion, mixed $detalle = null): void
    {
        self::registrar(self::NIVEL_ERROR, $modulo, $accion, $detalle);
    }

    public static function seguridad(string $accion, mixed $detalle = null): void
    {
        self::registrar(self::NIVEL_SEGURIDAD, 'Seguridad', $accion, $detalle);
    }

    public static function auditoria(string $modulo, string $accion, mixed $detalle = null): void
    {
        self::registrar(self::NIVEL_AUDITORIA, $modulo, $accion, $detalle);
    }

    private static function registrar(string $nivel, string $modulo, string $accion, mixed $detalle = null): void
    {
        if (!self::$bitacoraHabilitada) {
            return;
        }

        $idTraza = TrazadorPeticiones::obtenerId();

        $parametrosRequest = '';
        if (!empty($_REQUEST)) {
            $params = $_REQUEST;
            unset($params['clave'], $params['clave_acceso'], $params['clave_registro'], $params['clave_nueva'], $params['clave_actual'], $params['clave_confirmar']);
            $parametrosRequest = mb_substr(json_encode($params, JSON_UNESCAPED_UNICODE) ?: '', 0, 500);
        }

        $contexto = [
            'trace_id' => $idTraza,
            'nivel' => $nivel,
            'timestamp' => date('Y-m-d\TH:i:s.uP'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'desconocida',
            'agente_usuario' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'desconocido', 0, 255),
            'metodo' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'ruta' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'host' => $_SERVER['HTTP_HOST'] ?? gethostname(),
            'session_id' => session_status() === PHP_SESSION_ACTIVE ? hash('sha256', session_id() ?: '') : null,
            'http_referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'codigo_respuesta' => http_response_code() ?: 0,
            'tamano_bytes' => (int)($_SERVER['CONTENT_LENGTH'] ?? 0),
            'parametros_solicitud' => $parametrosRequest ?: null,
            'duracion_ms' => $idTraza !== 'N/A' ? TrazadorPeticiones::duracionMilisegundos() : null,
        ];

        $idOperador = null;
        if (isset($_SESSION['operador_id'])) {
            $contexto['operador_id'] = (int)$_SESSION['operador_id'];
            $contexto['operador_nombre'] = $_SESSION['operador_nombre'] ?? '';
            $idOperador = (int)$_SESSION['operador_id'];
        }

        if (!empty($_SESSION['_datos_cliente'])) {
            $contexto['cliente'] = $_SESSION['_datos_cliente'];
        }

        if ($detalle !== null) {
            $contexto['detalle'] = $detalle;
        }

        $jsonDetalles = json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';

        /** @phpstan-ignore-next-line $_SERVER puede no tener REMOTE_ADDR en CLI */
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
        self::escribirEnBitacoraBD($nivel, $modulo, $accion, $jsonDetalles, $idOperador, $ip);
        self::escribirEnArchivo($nivel, $modulo, $accion, $contexto);

        if ($nivel === self::NIVEL_ERROR || $nivel === self::NIVEL_SEGURIDAD) {
            try {
                SseGestor::emitirATodos('auditoria_alerta', [
                    'nivel' => $nivel,
                    'modulo' => $modulo,
                    'accion' => $accion,
                    'ip' => $ip,
                    'trace_id' => $idTraza,
                    'timestamp' => $contexto['timestamp'],
                ]);
            } catch (\Throwable $e) {
                error_log('[RegistroAuditoria] Error al emitir SSE: ' . $e->getMessage());
            }
        }
    }

    private static function escribirEnBitacoraBD(string $nivel, string $modulo, string $accion, string $jsonDetalles, ?int $idOperador, string $ip): void
    {
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $stmt = $conexion->prepare("
                INSERT INTO bitacora_sistema (id_operador, modulo, accion_realizada, nivel, ip_direccion, detalles_json)
                VALUES (:id_operador, :modulo, :accion, :nivel, :ip, :detalles)
            ");
            \assert($stmt !== false);
            $stmt->execute([
                ':id_operador' => $idOperador,
                ':modulo' => mb_substr($modulo, 0, 50),
                ':accion' => mb_substr($accion, 0, 100),
                ':nivel' => mb_substr($nivel, 0, 20),
                ':ip' => $ip,
                ':detalles' => $jsonDetalles,
            ]);
        } catch (PDOException $e) {
            error_log("[RegistroAuditoria] Error al escribir en bitacora_sistema: " . $e->getMessage());
        }
    }

    private static function escribirEnArchivo(string $nivel, string $modulo, string $accion, array $contexto): void
    {
        if (self::$archivoRuta === null) {
            return;
        }

        $detalle = $contexto['detalle'] ?? '';
        if (is_array($detalle)) {
            $detalle = json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $linea = sprintf(
            "[%s] [%s] [%s] %s — %s | trace=%s | ip=%s\n",
            date('Y-m-d H:i:s'),
            $nivel,
            $modulo,
            $accion,
            $detalle,
            $contexto['trace_id'],
            $contexto['ip']
        );

        @file_put_contents(self::$archivoRuta, $linea, FILE_APPEND | LOCK_EX);
    }

    public static function limpiarEventosAntiguos(int $dias = 90): int
    {
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $limite = DialectoBaseDatos::fechaRestar($conexion, 'DAY', $dias);
            $stmt = $conexion->prepare("DELETE FROM bitacora_sistema WHERE fecha_registro < {$limite}");
            \assert($stmt !== false);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("[RegistroAuditoria] Error al limpiar eventos antiguos: " . $e->getMessage());
            return 0;
        }
    }

    public static function limpiarArchivo(): void
    {
        if (self::$archivoRuta && file_exists(self::$archivoRuta)) {
            @unlink(self::$archivoRuta);
        }
    }

    public static function obtenerNiveles(): array
    {
        return [self::NIVEL_INFO, self::NIVEL_ADVERTENCIA, self::NIVEL_ERROR, self::NIVEL_SEGURIDAD, self::NIVEL_AUDITORIA];
    }

    public static function consultarEventos(
        ?int $idOperador = null,
        ?string $modulo = null,
        int $limite = 50,
        int $inicio = 0,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        ?string $nivel = null,
        ?string $ip = null,
        ?string $busqueda = null
    ): array {
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $condiciones = [];
            $parametros = [];

            if (!empty($idOperador)) {
                $condiciones[] = 'b.id_operador = :id_operador';
                $parametros[':id_operador'] = $idOperador;
            }
            if (!empty($modulo)) {
                $condiciones[] = 'b.modulo = :modulo';
                $parametros[':modulo'] = $modulo;
            }
            if (!empty($fechaDesde)) {
                $condiciones[] = 'b.fecha_registro >= :fecha_desde';
                $parametros[':fecha_desde'] = $fechaDesde;
            }
            if (!empty($fechaHasta)) {
                $condiciones[] = 'b.fecha_registro <= :fecha_hasta';
                $parametros[':fecha_hasta'] = $fechaHasta . ' 23:59:59';
            }
            if (!empty($nivel)) {
                $condiciones[] = 'b.nivel = :nivel';
                $parametros[':nivel'] = $nivel;
            }
            if (!empty($ip)) {
                $condiciones[] = 'b.ip_direccion LIKE :ip';
                $parametros[':ip'] = '%' . trim($ip) . '%';
            }
            if (!empty($busqueda)) {
                $condiciones[] = 'b.accion_realizada LIKE :busqueda';
                $parametros[':busqueda'] = '%' . trim($busqueda) . '%';
            }

            $where = '';
            if (!empty($condiciones)) {
                $where = 'WHERE ' . implode(' AND ', $condiciones);
            }

            $stmt = $conexion->prepare("
                SELECT b.*, o.nombre_completo
                FROM bitacora_sistema b
                LEFT JOIN operador o ON b.id_operador = o.id_operador
                {$where}
                ORDER BY b.fecha_registro DESC
                LIMIT :limite OFFSET :inicio
            ");
            \assert($stmt !== false);

            foreach ($parametros as $clave => $valor) {
                $stmt->bindValue($clave, $valor);
            }
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("[RegistroAuditoria] Error al consultar eventos: " . $e->getMessage());
            return [];
        }
    }

    public static function contarEventos(
        ?int $idOperador = null,
        ?string $modulo = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        ?string $nivel = null,
        ?string $ip = null,
        ?string $busqueda = null
    ): int {
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $condiciones = [];
            $parametros = [];

            if (!empty($idOperador)) {
                $condiciones[] = 'id_operador = :id_operador';
                $parametros[':id_operador'] = $idOperador;
            }
            if (!empty($modulo)) {
                $condiciones[] = 'modulo = :modulo';
                $parametros[':modulo'] = $modulo;
            }
            if (!empty($fechaDesde)) {
                $condiciones[] = 'fecha_registro >= :fecha_desde';
                $parametros[':fecha_desde'] = $fechaDesde;
            }
            if (!empty($fechaHasta)) {
                $condiciones[] = 'fecha_registro <= :fecha_hasta';
                $parametros[':fecha_hasta'] = $fechaHasta . ' 23:59:59';
            }
            if (!empty($nivel)) {
                $condiciones[] = 'nivel = :nivel';
                $parametros[':nivel'] = $nivel;
            }
            if (!empty($ip)) {
                $condiciones[] = 'ip_direccion LIKE :ip';
                $parametros[':ip'] = '%' . trim($ip) . '%';
            }
            if (!empty($busqueda)) {
                $condiciones[] = 'accion_realizada LIKE :busqueda';
                $parametros[':busqueda'] = '%' . trim($busqueda) . '%';
            }

            $where = '';
            if (!empty($condiciones)) {
                $where = 'WHERE ' . implode(' AND ', $condiciones);
            }

            $stmt = $conexion->prepare("SELECT COUNT(*) FROM bitacora_sistema {$where}");
            \assert($stmt !== false);
            foreach ($parametros as $clave => $valor) {
                $stmt->bindValue($clave, $valor);
            }
            $stmt->execute();

            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("[RegistroAuditoria] Error al contar eventos: " . $e->getMessage());
            return 0;
        }
    }

    public static function exportarEventos(
        string $formato,
        ?int $idOperador = null,
        ?string $modulo = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        ?string $nivel = null,
        ?string $ip = null,
        ?string $busqueda = null,
        int $limite = 10000
    ): string {
        $eventos = self::consultarEventos($idOperador, $modulo, $limite, 0, $fechaDesde, $fechaHasta, $nivel, $ip, $busqueda);

        if ($formato === 'json') {
            $salida = [];
            foreach ($eventos as $ev) {
                $detalle = !empty($ev['detalles_json']) ? json_decode($ev['detalles_json'], true) : [];
                $salida[] = [
                    'id_bitacora' => $ev['id_bitacora'] ?? $ev['id_registro'] ?? null,
                    'id_operador' => $ev['id_operador'],
                    'operador_nombre' => $ev['nombre_completo'],
                    'modulo' => $ev['modulo'],
                    'accion' => $ev['accion_realizada'],
                    'nivel' => $ev['nivel'] ?? $detalle['nivel'] ?? 'INFO',
                    'ip' => $ev['ip_direccion'] ?? $detalle['ip'] ?? null,
                    'fecha_registro' => $ev['fecha_registro'],
                    'detalles' => $detalle,
                ];
            }
            return json_encode(['eventos' => $salida, 'total' => count($salida), 'exportado' => date('Y-m-d\TH:i:sP')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '';
        }

        $csv = fopen('php://memory', 'r+');
        \assert($csv !== false);
        $cabeceras = ['ID', 'Operador', 'Modulo', 'Accion', 'Nivel', 'IP', 'Fecha', 'Trace ID', 'Host', 'Duracion ms', 'Codigo Respuesta'];
        fputcsv($csv, $cabeceras, ';');
        foreach ($eventos as $ev) {
            $detalle = !empty($ev['detalles_json']) ? json_decode($ev['detalles_json'], true) : [];
            $fila = [
                $ev['id_bitacora'] ?? $ev['id_registro'] ?? '',
                $ev['nombre_completo'] ?? 'Sistema',
                $ev['modulo'],
                $ev['accion_realizada'],
                $ev['nivel'] ?? $detalle['nivel'] ?? 'INFO',
                $ev['ip_direccion'] ?? $detalle['ip'] ?? '',
                $ev['fecha_registro'],
                $detalle['trace_id'] ?? '',
                $detalle['host'] ?? '',
                $detalle['duracion_ms'] ?? '',
                $detalle['codigo_respuesta'] ?? '',
            ];
            fputcsv($csv, $fila, ';');
        }
        rewind($csv);
        $contenido = stream_get_contents($csv) ?: '';
        fclose($csv);
        return $contenido;
    }

    public static function obtenerModulos(): array
    {
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $stmt = $conexion->query("SELECT DISTINCT modulo FROM bitacora_sistema ORDER BY modulo");
            \assert($stmt !== false);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("[RegistroAuditoria] Error al obtener modulos: " . $e->getMessage());
            return [];
        }
    }

    public static function obtenerResumen(): array
    {
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $stmtTotal = $conexion->query("SELECT COUNT(*) FROM bitacora_sistema");
            \assert($stmtTotal !== false);
            $total = (int)$stmtTotal->fetchColumn();

            $stmtModulos = $conexion->query("
                SELECT modulo, COUNT(*) as total
                FROM bitacora_sistema
                GROUP BY modulo
                ORDER BY total DESC
                LIMIT 10
            ");
            \assert($stmtModulos !== false);
            $porModulo = $stmtModulos->fetchAll(PDO::FETCH_ASSOC);

            $sqlSemana = "SELECT COUNT(*) FROM bitacora_sistema WHERE fecha_registro >= " . DialectoBaseDatos::fechaRestar($conexion, 'DAY', 7);
            $stmtSemana = $conexion->query($sqlSemana);
            \assert($stmtSemana !== false);
            $ultimaSemana = (int)$stmtSemana->fetchColumn();

            $sqlHoy = "SELECT COUNT(*) FROM bitacora_sistema WHERE " . DialectoBaseDatos::extraerFecha($conexion, 'fecha_registro') . " = " . DialectoBaseDatos::fechaHoy($conexion);
            $stmtHoy = $conexion->query($sqlHoy);
            \assert($stmtHoy !== false);
            $hoy = (int)$stmtHoy->fetchColumn();

            return [
                'total' => $total,
                'ultima_semana' => $ultimaSemana,
                'hoy' => $hoy,
                'por_modulo' => $porModulo,
            ];
        } catch (PDOException $e) {
            error_log("[RegistroAuditoria] Error al obtener resumen: " . $e->getMessage());
            return ['total' => 0, 'ultima_semana' => 0, 'hoy' => 0, 'por_modulo' => []];
        }
    }
}
