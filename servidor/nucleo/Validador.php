<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo;

use LiteFramework\Config\ConexionBaseDatos;
use Exception;

class Validador
{
    private array $datos;
    private array $reglas;
    private array $errores = [];
    private array $mensajesPersonalizados = [];

    private array $mensajes = [
        'requerido' => 'El campo %s es obligatorio.',
        'correo' => 'El campo %s debe ser un correo electronico valido.',
        'minimo' => 'El campo %s debe tener al menos %d caracteres.',
        'maximo' => 'El campo %s no debe exceder %d caracteres.',
        'unico' => 'El valor del campo %s ya esta registrado en el sistema.',
        'regex' => 'El campo %s no tiene un formato valido.',
        'confirmado' => 'La confirmacion del campo %s no coincide.',
        'numero' => 'El campo %s debe ser un numero.',
        'entero' => 'El campo %s debe ser un numero entero.',
        'archivo' => 'El campo %s debe ser un archivo.',
        'imagen' => 'El campo %s debe ser una imagen (JPEG, PNG, GIF, WebP).',
        'max_tamano' => 'El campo %s no debe exceder %d MB.',
        'diferente' => 'El campo %s debe ser diferente al campo %s.',
        'en' => 'El campo %s debe ser uno de los valores permitidos.',
    ];

    public function __construct(array $datos, array $reglas)
    {
        $this->datos = $datos;
        $this->reglas = $reglas;
        $this->validar();
    }

    public function mensajesPersonalizados(array $mensajes): static
    {
        $this->mensajesPersonalizados = $mensajes;
        return $this;
    }

    private function validar(): void
    {
        foreach ($this->reglas as $campo => $reglasStr) {
            if (is_string($reglasStr)) {
                $listaReglas = array_filter(array_map('trim', explode('|', $reglasStr)));
                foreach ($listaReglas as $reglaRaw) {
                    if (strpos($reglaRaw, ',') !== false && strpos($reglaRaw, ':') === false) {
                        $subReglas = array_filter(array_map('trim', explode(',', $reglaRaw)));
                        foreach ($subReglas as $regla) {
                            $this->procesarRegla($campo, $regla, []);
                        }
                    } else {
                        $parametros = [];
                        $regla = $reglaRaw;
                        if (strpos($regla, ':') !== false) {
                            $partes = explode(':', $regla, 2);
                            $regla = $partes[0];
                            if (strpos($partes[1], ',') !== false) {
                                $parametros = explode(',', $partes[1]);
                            } else {
                                $parametros = [$partes[1]];
                            }
                        }
                        $this->procesarRegla($campo, $regla, $parametros);
                    }
                }
            } else {
                foreach ($reglasStr as $regla) {
                    $this->procesarRegla($campo, $regla, []);
                }
            }
        }
    }

    private function procesarRegla(string $campo, string $regla, array $parametros): void
    {
        $metodo = 'regla' . ucfirst($regla);
        if (method_exists($this, $metodo)) {
            $this->$metodo($campo, $parametros);
        }
    }

    private function reglaRequerido(string $campo, array $params): void
    {
        $valor = $this->datos[$campo] ?? '';
        if (is_string($valor) && trim($valor) === '') {
            $this->agregarError($campo, 'requerido');
        } elseif ($valor === null || $valor === '') {
            $this->agregarError($campo, 'requerido');
        }
    }

    private function reglaCorreo(string $campo, array $params): void
    {
        $valor = $this->datos[$campo] ?? '';
        if ($valor === '') {
            return;
        }
        if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            $this->agregarError($campo, 'correo');
        }
    }

    private function reglaMinimo(string $campo, array $params): void
    {
        $valor = $this->datos[$campo] ?? '';
        if ($valor === '') {
            return;
        }
        $min = (int)($params[0] ?? 0);
        if (mb_strlen($valor) < $min) {
            $this->agregarError($campo, 'minimo', [$min]);
        }
    }

    private function reglaMaximo(string $campo, array $params): void
    {
        $valor = $this->datos[$campo] ?? '';
        if ($valor === '') {
            return;
        }
        $max = (int)($params[0] ?? 0);
        if (mb_strlen($valor) > $max) {
            $this->agregarError($campo, 'maximo', [$max]);
        }
    }

    private function reglaUnico(string $campo, array $params): void
    {
        $valor = $this->datos[$campo] ?? '';
        if ($valor === '') {
            return;
        }
        $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', $params[0] ?? '');
        $columna = preg_replace('/[^a-zA-Z0-9_]/', '', $params[1] ?? $campo);
        $excluirId = $params[2] ?? null;

        if (empty($tabla) || empty($columna)) {
            return;
        }

        try {
            $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $sql = "SELECT COUNT(*) FROM {$tabla} WHERE {$columna} = :valor";
            $parametros = [':valor' => $valor];

            if ($excluirId !== null) {
                $idCol = preg_replace('/[^a-zA-Z0-9_]/', '', $params[3] ?? 'id');
                if (empty($idCol)) {
                    $idCol = 'id';
                }
                $sql .= " AND {$idCol} != :excluir";
                $parametros[':excluir'] = $excluirId;
            }

            $consulta = $bd->prepare($sql);
            $consulta->execute($parametros);
            if ((int)$consulta->fetchColumn() > 0) {
                $this->agregarError($campo, 'unico');
            }
        } catch (Exception $e) {
            error_log('[Validador] Error en regla unico: ' . $e->getMessage());
        }
    }

    private function reglaRegex(string $campo, array $params): void
    {
        $valor = $this->datos[$campo] ?? '';
        if ($valor === '') {
            return;
        }
        $patron = $params[0] ?? '';
        if (!preg_match($patron, $valor)) {
            $this->agregarError($campo, 'regex');
        }
    }

    private function reglaConfirmado(string $campo, array $params): void
    {
        $valor = $this->datos[$campo] ?? '';
        $confirmacion = $this->datos[$campo . '_confirmation']
            ?? $this->datos[$campo . '_confirmacion']
            ?? $this->datos[$campo . '_confirmacion_alt']
            ?? $this->datos[$campo . '_repetir']
            ?? '';
        if ($valor !== '' && $valor !== $confirmacion) {
            $this->agregarError($campo, 'confirmado');
        }
    }

    private function reglaNumero(string $campo, array $params): void
    {
        $valor = $this->datos[$campo] ?? '';
        if ($valor === '') {
            return;
        }
        if (!is_numeric($valor)) {
            $this->agregarError($campo, 'numero');
        }
    }

    private function reglaEntero(string $campo, array $params): void
    {
        $valor = $this->datos[$campo] ?? '';
        if ($valor === '') {
            return;
        }
        if (filter_var($valor, FILTER_VALIDATE_INT) === false) {
            $this->agregarError($campo, 'entero');
        }
    }

    private function reglaArchivo(string $campo, array $params): void
    {
        $archivo = $_FILES[$campo] ?? null;
        if (!$archivo || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $this->agregarError($campo, 'archivo');
        }
    }

    private function reglaImagen(string $campo, array $params): void
    {
        $archivo = $_FILES[$campo] ?? null;
        if (!$archivo || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $tipo = mime_content_type($archivo['tmp_name']);
        if (!in_array($tipo, $tiposPermitidos)) {
            $this->agregarError($campo, 'imagen');
        }
    }

    private function reglaMaxTamano(string $campo, array $params): void
    {
        $archivo = $_FILES[$campo] ?? null;
        if (!$archivo || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }
        $maxMB = (float)($params[0] ?? 2);
        $maxBytes = $maxMB * 1024 * 1024;
        if ($archivo['size'] > $maxBytes) {
            $this->agregarError($campo, 'max_tamano', [$maxMB]);
        }
    }

    private function reglaDiferente(string $campo, array $params): void
    {
        $valor = $this->datos[$campo] ?? '';
        $otroCampo = $params[0] ?? '';
        $otroValor = $this->datos[$otroCampo] ?? '';
        if ($valor !== '' && $valor === $otroValor) {
            $this->agregarError($campo, 'diferente', [$otroCampo]);
        }
    }

    private function reglaEn(string $campo, array $params): void
    {
        $valor = $this->datos[$campo] ?? '';
        if ($valor === '') {
            return;
        }
        if (!in_array($valor, $params)) {
            $this->agregarError($campo, 'en');
        }
    }

    private function agregarError(string $campo, string $regla, array $parametrosExtra = []): void
    {
        $mensajePersonalizado = $this->mensajesPersonalizados[$campo] ?? null;
        if (is_array($mensajePersonalizado) && isset($mensajePersonalizado[$regla])) {
            $mensaje = $mensajePersonalizado[$regla];
        } elseif (is_string($mensajePersonalizado)) {
            $mensaje = $mensajePersonalizado;
        } else {
            $mensaje = $this->mensajesPersonalizados[$campo . '.' . $regla]
                ?? $this->mensajes[$regla]
                ?? 'El campo %s no paso la validacion ' . $regla . '.';
        }

        $etiqueta = $this->mensajesPersonalizados['etiquetas.' . $campo] ?? $campo;
        $args = array_merge([$etiqueta], $parametrosExtra);
        $this->errores[$campo][] = vsprintf($mensaje, $args);
    }

    public function falla(): bool
    {
        return !$this->pasa();
    }

    public function pasa(): bool
    {
        return empty($this->errores);
    }

    public function obtenerErrores(): array
    {
        return $this->errores();
    }

    public function errores(): array
    {
        return $this->errores;
    }

    public function primerError(?string $campo = null): ?string
    {
        if ($campo !== null) {
            return $this->errores[$campo][0] ?? null;
        }
        foreach ($this->errores as $campo => $erroresCampo) {
            return $erroresCampo[0];
        }
        return null;
    }

    public function erroresComoString(string $separador = '<br>'): string
    {
        $partes = [];
        foreach ($this->errores as $campo => $erroresCampo) {
            foreach ($erroresCampo as $error) {
                $partes[] = $error;
            }
        }
        return implode($separador, $partes);
    }
}
