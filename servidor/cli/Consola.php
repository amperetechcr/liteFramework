<?php

declare(strict_types=1);

namespace LiteFramework\Cli;

use LiteFramework\Seguridad\ControlAccesoRBAC;
use LiteFramework\Nucleo\Excepciones\ErrorSeguridad;
use LiteFramework\Nucleo\ManejadorErrores;

class Consola
{
    private array $comandos = [];
    private string $nombreScript;
    private bool $modoJson = false;
    private bool $modoIA = false;
    private bool $tokenValido = false;
    private ?string $errorToken = null;

    public function __construct(string $nombreScript = 'consola')
    {
        $this->nombreScript = $nombreScript;
    }

    public function estaEnModoJson(): bool
    {
        return $this->modoJson;
    }

    public function estaEnModoIA(): bool
    {
        return $this->modoIA;
    }

    public function tokenEsValido(): bool
    {
        return $this->tokenValido;
    }

    public function registrar(string $nombre, string $descripcion, callable $ejecutor, array $argumentos = []): static
    {
        $this->comandos[$nombre] = [
            'descripcion' => $descripcion,
            'ejecutor' => $ejecutor,
            'argumentos' => $argumentos,
        ];
        return $this;
    }

    public function ejecutar(array $argv): never
    {
        $this->parsearFlagsGlobales($argv);

        if ($this->errorToken !== null) {
            $this->jsonError('Token invalido: ' . $this->errorToken, 'ERR_AUTH', 403);
        }

        $comando = $argv[1] ?? null;

        if ($comando === null || $comando === 'list') {
            $this->mostrarLista();
            exit(0);
        }

        if ($comando === 'help' && isset($argv[2])) {
            $this->mostrarAyuda($argv[2]);
            exit(0);
        }

        if (!isset($this->comandos[$comando])) {
            $this->imprimirError("Comando desconocido: {$comando}");
            if (!$this->modoJson) {
                $this->imprimir("Ejecuta '{$this->nombreScript} list' para ver los comandos disponibles.");
            }
            exit(1);
        }

        $argsComando = array_slice($argv, 2);
        $cmd = $this->comandos[$comando];

        try {
            $codigoSalida = (int)($cmd['ejecutor'])($argsComando);
            exit($codigoSalida);
        } catch (ErrorSeguridad $e) {
            $this->jsonError($e->getMessage(), 'ERR_SEGURIDAD', (int)$e->getCode() ?: 403);
        } catch (\Throwable $e) {
            $this->imprimirError("Error: {$e->getMessage()}");
            exit(1);
        }
    }

    private function parsearFlagsGlobales(array &$argv): void
    {
        $restantes = [];
        $tokenRecibido = null;

        foreach ($argv as $i => $arg) {
            if ($arg === '--json' || $arg === '--ai') {
                $this->modoJson = true;
                if ($arg === '--ai') {
                    $this->modoIA = true;
                }
                continue;
            }
            if (str_starts_with($arg, '--token=')) {
                $tokenRecibido = substr($arg, 8);
                continue;
            }
            $restantes[] = $arg;
        }

        $argv = $restantes;

        if ($tokenRecibido !== null) {
            if (!ControlAccesoRBAC::autenticarTokenIA($tokenRecibido)) {
                $this->errorToken = 'hash no coincide con AI_CREW_TOKEN_HASH';
                return;
            }
            $this->tokenValido = true;
            $this->modoIA = true;
            $this->modoJson = true;
        }

        if ($this->modoJson) {
            ManejadorErrores::activarModoJson();
            self::$instance = $this;
        }
    }

    public function jsonOut(mixed $data, ?string $comando = null, int $codigo = 0): void
    {
        $payload = [
            'ok' => true,
            'codigo' => $codigo,
        ];
        if ($comando !== null) {
            $payload['comando'] = $comando;
        }
        $payload['data'] = $data;

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
    }

    public function jsonError(string $mensaje, string $codigo = 'ERR_GENERAL', int $httpCode = 400): never
    {
        $payload = [
            'ok' => false,
            'error' => $mensaje,
            'codigo' => $codigo,
        ];
        fwrite(STDERR, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        exit($httpCode >= 500 ? 1 : ($httpCode >= 400 ? 1 : 0));
    }

    public function jsonResultado(array $resultado): void
    {
        $payload = [
            'ok' => !empty($resultado['exito']),
            'data' => $resultado,
        ];
        if (empty($resultado['exito'])) {
            $payload['error'] = $resultado['error'] ?? ($resultado['errores'][0] ?? 'Error desconocido');
            fwrite(STDERR, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
            exit(1);
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
    }

    public function solicitar(string $mensaje, string $defecto = ''): string
    {
        if ($this->modoJson) {
            $this->jsonError("Dato requerido: {$mensaje}", 'ERR_DATO_REQUERIDO', 400);
        }
        echo $mensaje;
        if ($defecto !== '') {
            echo " [{$defecto}]";
        }
        echo ': ';
        $entrada = rtrim(fgets(STDIN) ?: '');
        return $entrada !== '' ? $entrada : $defecto;
    }

    public function solicitarRequerido(string $mensaje): string
    {
        if ($this->modoJson) {
            $this->jsonError("Dato requerido: {$mensaje}", 'ERR_DATO_REQUERIDO', 400);
        }
        while (true) {
            echo $mensaje . ': ';
            $entrada = rtrim(fgets(STDIN) ?: '');
            if ($entrada !== '') {
                return $entrada;
            }
            echo "  (Este campo es obligatorio)\n";
        }
    }

    private static ?Consola $instance = null;

    public static function instance(): ?Consola
    {
        return self::$instance;
    }

    private function mostrarLista(): void
    {
        if ($this->modoJson) {
            $comandos = [];
            foreach ($this->comandos as $nombre => $cmd) {
                $comandos[] = [
                    'nombre' => $nombre,
                    'descripcion' => $cmd['descripcion'],
                    'argumentos' => $cmd['argumentos'],
                ];
            }
            $this->jsonOut(['comandos' => $comandos], 'list');
            return;
        }

        $this->imprimir("{$this->nombreScript} – Comandos disponibles");
        $this->imprimir(str_repeat('─', 60));
        $this->imprimir("");

        $ancho = max(array_map(strlen(...), array_keys($this->comandos)) + [0 => 8]);

        foreach ($this->comandos as $nombre => $cmd) {
            $nombrePadded = str_pad($nombre, $ancho);
            $this->imprimir("  {$nombrePadded}  {$cmd['descripcion']}");
        }

        $this->imprimir("");
        $this->imprimir("Ayuda detallada: {$this->nombreScript} help <comando>");
        $this->imprimir("");
        $this->imprimir("Modo IA:  {$this->nombreScript} <comando> --ai --token=CLAVE");
        $this->imprimir("Salida JSON:  {$this->nombreScript} <comando> --json");
    }

    private function mostrarAyuda(string $comando): void
    {
        if ($this->modoJson) {
            if (!isset($this->comandos[$comando])) {
                $this->jsonError("Comando desconocido: {$comando}", 'ERR_COMANDO', 404);
            }
            $cmd = $this->comandos[$comando];
            $this->jsonOut([
                'nombre' => $comando,
                'descripcion' => $cmd['descripcion'],
                'argumentos' => $cmd['argumentos'],
            ], 'help');
            return;
        }

        if (!isset($this->comandos[$comando])) {
            $this->imprimirError("Comando desconocido: {$comando}");
            exit(1);
        }

        $cmd = $this->comandos[$comando];
        $this->imprimir("{$comando} – {$cmd['descripcion']}");
        $this->imprimir(str_repeat('─', 60));

        if (!empty($cmd['argumentos'])) {
            $this->imprimir("");
            $this->imprimir("Argumentos:");
            foreach ($cmd['argumentos'] as $arg) {
                $this->imprimir("  {$arg}");
            }
        }

        $this->imprimir("");
    }

    public function imprimir(string $texto): void
    {
        if ($this->modoJson) {
            return;
        }
        echo $texto . PHP_EOL;
    }

    public function imprimirError(string $texto): void
    {
        if ($this->modoJson) {
            $this->jsonError($texto, 'ERR_COMANDO', 1);
        }
        fwrite(STDERR, "ERROR: {$texto}" . PHP_EOL);
    }
}
