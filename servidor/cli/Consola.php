<?php

declare(strict_types=1);

namespace LiteFramework\Cli;

class Consola
{
    private array $comandos = [];
    private string $nombreScript;

    public function __construct(string $nombreScript = 'consola')
    {
        $this->nombreScript = $nombreScript;
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
            $this->imprimir("Ejecuta '{$this->nombreScript} list' para ver los comandos disponibles.");
            exit(1);
        }

        $argsComando = array_slice($argv, 2);
        $cmd = $this->comandos[$comando];

        try {
            $codigoSalida = (int)($cmd['ejecutor'])($argsComando);
            exit($codigoSalida);
        } catch (\Throwable $e) {
            $this->imprimirError("Error: {$e->getMessage()}");
            exit(1);
        }
    }

    private function mostrarLista(): void
    {
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
    }

    private function mostrarAyuda(string $comando): void
    {
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

    private function imprimir(string $texto): void
    {
        echo $texto . PHP_EOL;
    }

    private function imprimirError(string $texto): void
    {
        fwrite(STDERR, "ERROR: {$texto}" . PHP_EOL);
    }
}
