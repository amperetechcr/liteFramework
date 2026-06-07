<?php

declare(strict_types=1);

namespace LiteFramework\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

class Logger implements LoggerInterface
{
    private string $rutaDirectorio;
    private string $nivelMinimo;
    private ?string $archivoActual = null;
    private $recurso = null;
    private static array $niveles = [
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7,
    ];

    public function __construct(?string $directorio = null, string $nivelMinimo = 'debug')
    {
        $this->rutaDirectorio = $directorio ?? dirname(__DIR__, 2) . '/storage/logs';
        $this->nivelMinimo = $nivelMinimo;
    }

    public function __destruct()
    {
        if ($this->recurso) {
            fclose($this->recurso);
        }
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->registrar(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->registrar(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->registrar(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->registrar(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->registrar(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->registrar(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->registrar(LogLevel::INFO, $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->registrar(LogLevel::DEBUG, $message, $context);
    }

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->registrar($level, $message, $context);
    }

    private function registrar(mixed $nivel, string|Stringable $mensaje, array $context = []): void
    {
        $nivelTexto = is_string($nivel) ? $nivel : (static::$niveles[$nivel] ?? 'error');

        if (!isset(static::$niveles[$nivelTexto])) {
            $nivelTexto = 'error';
        }

        if (static::$niveles[$nivelTexto] > static::$niveles[$this->nivelMinimo]) {
            return;
        }

        $fecha = date('Y-m-d\TH:i:s');
        $contextoJson = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $linea = "[{$fecha}] framework.{$nivelTexto}: {$mensaje}{$contextoJson}" . PHP_EOL;

        $archivo = $this->rutaDirectorio . '/framework-' . date('Y-m-d') . '.log';
        if ($archivo !== $this->archivoActual) {
            if ($this->recurso) {
                fclose($this->recurso);
                $this->recurso = null;
            }
            $this->archivoActual = $archivo;
        }

        if (!$this->recurso) {
            $directorio = dirname($archivo);
            if (!is_dir($directorio)) {
                @mkdir($directorio, 0755, true);
            }
            $this->recurso = @fopen($archivo, 'a');
        }

        if ($this->recurso) {
            @fwrite($this->recurso, $linea);
        }
    }
}
