<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo;

use LiteFramework\Seguridad\SeguridadServidor;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Config\GestorEntorno;
use Exception;
use LiteFramework\Seguridad\TrazadorPeticiones;
use LiteFramework\Nucleo\Excepciones\ErrorSeguridad;
use LiteFramework\Nucleo\Excepciones\ErrorAutenticacion;
use LiteFramework\Servicios\ContextoError;
use LiteFramework\Servicios\DiagnosticoError;
use ErrorException;

class ManejadorErrores
{
    private static bool $registrados = false;
    private static ?array $ultimoDiagnostico = null;
    private static bool $diagnosticando = false;

    public static function registrar(): void
    {
        if (self::$registrados) {
            return;
        }
        self::$registrados = true;

        set_error_handler([self::class, 'manejarError']);
        set_exception_handler([self::class, 'manejarExcepcion']);
        register_shutdown_function([self::class, 'manejarFatal']);
    }

    public static function manejarError(int $nivel, string $mensaje, string $archivo, int $linea): bool
    {
        if (!(error_reporting() & $nivel)) {
            return false;
        }

        $excepcion = new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
        self::loggear($excepcion, 'ERROR_PHP');

        if (GestorEntorno::esDepuracion()) {
            throw $excepcion;
        }

        return true;
    }

    public static function manejarExcepcion(\Throwable $excepcion): void
    {
        if ($excepcion instanceof ErrorException) {
            self::loggear($excepcion, 'ERROR_PHP');
        } else {
            self::loggear($excepcion, 'EXCEPCION');
        }

        if (self::esPeticionApi()) {
            self::responderJsonApi($excepcion);
            return;
        }

        self::responderHtml($excepcion);
    }

    public static function manejarFatal(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }
        if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        $excepcion = new ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        );

        self::loggear($excepcion, 'FATAL');
        http_response_code(500);

        if (self::esPeticionApi()) {
            self::responderJsonApi($excepcion);
            return;
        }

        if (headers_sent()) {
            echo '<h1>Error interno del servidor</h1>';
            if (GestorEntorno::esDepuracion()) {
                echo '<pre>' . htmlspecialchars($excepcion->getMessage()) . '</pre>';
            }
            exit;
        }

        self::responderHtml($excepcion);
    }

    private static function loggear(\Throwable $excepcion, string $tipo): void
    {
        $idTraza = 'N/A';
        if (class_exists('TrazadorPeticiones')) {
            try {
                $idTraza = TrazadorPeticiones::obtenerId();
            } catch (Exception $e) {
                error_log('[ManejadorErrores] Error al obtener id de traza: ' . $e->getMessage());
            }
        }

        $contexto = [
            'tipo' => $tipo,
            'mensaje' => $excepcion->getMessage(),
            'archivo' => $excepcion->getFile(),
            'linea' => $excepcion->getLine(),
            'trace_id' => $idTraza,
        ];

        if (GestorEntorno::esDepuracion()) {
            $contexto['traza'] = $excepcion->getTraceAsString();
        }

        $logMsg = "[{$tipo}] {$excepcion->getMessage()} en {$excepcion->getFile()}:{$excepcion->getLine()} | trace={$idTraza}";

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $logMsg . "\n");
        } else {
            error_log($logMsg);
        }

        if (class_exists('RegistroAuditoria')) {
            try {
                RegistroAuditoria::habilitarArchivo();
                if (!self::$diagnosticando) {
                    self::$diagnosticando = true;
                    try {
                        $contexto['_diagnostico'] = self::diagnosticarError($excepcion, $tipo, $idTraza);
                    } finally {
                        self::$diagnosticando = false;
                    }
                }
                RegistroAuditoria::error('Sistema', "Error {$tipo}", $contexto);
            } catch (Exception $e) {
                error_log("[ManejadorErrores] No se pudo loggear en auditoria: " . $e->getMessage());
            }
        } elseif (!self::$diagnosticando) {
            self::$diagnosticando = true;
            try {
                self::diagnosticarError($excepcion, $tipo, $idTraza);
            } finally {
                self::$diagnosticando = false;
            }
        }
    }

    private static function diagnosticarError(\Throwable $excepcion, string $tipo, string $idTraza): array
    {
        try {
            $ctx = ContextoError::capturar('error_interno', $excepcion->getMessage(), $excepcion->getFile(), $excepcion->getLine(), [
                'tipo' => $tipo,
                'trace_id' => $idTraza,
            ]);
            $diagnostico = DiagnosticoError::diagnosticar($ctx);
            self::$ultimoDiagnostico = $diagnostico;
            return $diagnostico;
        } catch (\Throwable $e) {
            error_log('[ManejadorErrores] Error al diagnosticar: ' . $e->getMessage());
            return [];
        }
    }

    private static function esPeticionApi(): bool
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        if (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            return true;
        }
        return false;
    }

    private static function responderJsonApi(\Throwable $excepcion): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }

        $respuesta = [
            'estado_operacion' => false,
            'mensaje_error' => 'Error interno del servidor.',
            'codigo_error' => 'error_interno',
            'nuevo_token' => '',
            'datos' => null,
        ];

        if (class_exists('SeguridadServidor')) {
            try {
                $respuesta['nuevo_token'] = SeguridadServidor::generarTokenAntiFalsificacion();
            } catch (Exception $e) {
                error_log('[ManejadorErrores] Error al generar token CSRF: ' . $e->getMessage());
            }
        }

        if (GestorEntorno::esDepuracion()) {
            $respuesta['depuracion'] = [
                'mensaje' => $excepcion->getMessage(),
                'archivo' => $excepcion->getFile(),
                'linea' => $excepcion->getLine(),
            ];
        }

        if (self::$ultimoDiagnostico !== null) {
            if (!empty(self::$ultimoDiagnostico['accion'])) {
                $respuesta['accion'] = self::$ultimoDiagnostico['accion'];
            }
            if (!empty(self::$ultimoDiagnostico['sugerencias'])) {
                $respuesta['sugerencias'] = self::$ultimoDiagnostico['sugerencias'];
            }
            if (!empty(self::$ultimoDiagnostico['diagnosticos'])) {
                $respuesta['diagnostico'] = self::$ultimoDiagnostico['diagnosticos'];
            }
        }

        echo json_encode($respuesta);
        exit;
    }

    private static function responderHtml(\Throwable $excepcion): void
    {
        if (headers_sent()) {
            echo '<h1>Error interno del servidor</h1>';
            if (GestorEntorno::esDepuracion()) {
                echo '<pre>' . htmlspecialchars($excepcion->__toString()) . '</pre>';
            }
            exit;
        }

        $codigo = ($excepcion instanceof ErrorSeguridad || $excepcion instanceof ErrorAutenticacion)
            ? $excepcion->getCode()
            : 500;
        http_response_code($codigo);

        $excepcionParaVista = GestorEntorno::esDepuracion() ? $excepcion : null;
        $diagnosticoHtml = self::$ultimoDiagnostico;

        if (file_exists(DIRECTORIO_RAIZ . '/src/error.php')) {
            require DIRECTORIO_RAIZ . '/src/error.php';
        } else {
            echo '<h1>Error interno del servidor</h1>';
        }
        exit;
    }
}
