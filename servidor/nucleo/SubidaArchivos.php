<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo;

class SubidaArchivos
{
    private string $campo;
    private ?string $nombreOriginal = null;
    private ?string $tipoMime = null;
    private ?int $tamanoBytes = null;
    private bool $huboError = false;
    private string $mensajeError = '';
    private bool $esMultiple = false;
    private array $extensionesPermitidas = [];


    public function establecerExtensionesPermitidas(array|string $extensiones): static
    {
        if (is_string($extensiones)) {
            $this->extensionesPermitidas = array_map('trim', explode(',', $extensiones));
        } else {
            $this->extensionesPermitidas = $extensiones;
        }
        return $this;
    }

    public function __construct(string $campo)
    {
        $this->campo = $campo;
        $this->analizarArchivo();
    }

    private function analizarArchivo(): void
    {
        if (!isset($_FILES[$this->campo])) {
            $this->huboError = true;
            $this->mensajeError = 'No se recibió ningún archivo.';
            return;
        }

        $archivo = $_FILES[$this->campo];

        if (is_array($archivo['name'])) {
            $this->esMultiple = true;
            $this->huboError = $archivo['error'][0] !== UPLOAD_ERR_OK;
            if ($this->huboError) {
                $this->mensajeError = $this->traducirErrorSubida($archivo['error'][0]);
            }
            return;
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $this->huboError = $archivo['error'] !== UPLOAD_ERR_NO_FILE;
            $this->mensajeError = $this->traducirErrorSubida($archivo['error']);
            return;
        }

        $this->nombreOriginal = $archivo['name'];
        $this->tipoMime = $archivo['type'];
        $this->tamanoBytes = $archivo['size'];
    }

    public function validar(array $tiposMimePermitidos, ?int $maxBytes = null): static
    {
        if ($this->huboError || $this->esMultiple) {
            return $this;
        }

        if (!empty($tiposMimePermitidos)) {
            if ($this->tipoMime && !in_array($this->tipoMime, $tiposMimePermitidos, true)) {
                $tipoReal = mime_content_type($_FILES[$this->campo]['tmp_name']);
                if (!in_array($tipoReal, $tiposMimePermitidos, true)) {
                    $this->huboError = true;
                    $this->mensajeError = 'Tipo de archivo no permitido.';
                    return $this;
                }
            }

            if (!empty($this->extensionesPermitidas)) {
                $extension = $this->nombreOriginal ? strtolower(pathinfo($this->nombreOriginal, PATHINFO_EXTENSION)) : '';
                if ($extension !== '' && !in_array($extension, $this->extensionesPermitidas, true)) {
                    $this->huboError = true;
                    $this->mensajeError = 'Extension de archivo no permitida.';
                    return $this;
                }
            }
        }

        if ($maxBytes !== null && $this->tamanoBytes && $this->tamanoBytes > $maxBytes) {
            $this->huboError = true;
            $this->mensajeError = 'El archivo excede el tamaño máximo permitido de ' . round($maxBytes / 1048576, 1) . ' MB.';
        }

        return $this;
    }

    public function guardar(string $directorio, bool $preservarNombre = false): string|false
    {
        if ($this->huboError || $this->esMultiple) {
            return false;
        }

        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $extension = $this->nombreOriginal ? strtolower(pathinfo($this->nombreOriginal, PATHINFO_EXTENSION)) : '';

        if ($preservarNombre && $this->nombreOriginal) {
            $nombreSeguro = preg_replace('/[^\w\.\-]/u', '_', $this->nombreOriginal);
            $nombreSeguro = preg_replace('/_{2,}/', '_', $nombreSeguro);
            $rutaDestino = $directorio . '/' . $nombreSeguro;

            if (file_exists($rutaDestino)) {
                $contador = 1;
                $info = pathinfo($nombreSeguro);
                while (file_exists($directorio . '/' . $info['filename'] . '_' . $contador . '.' . ($info['extension'] ?? ''))) {
                    $contador++;
                }
                $rutaDestino = $directorio . '/' . $info['filename'] . '_' . $contador . '.' . ($info['extension'] ?? '');
            }
        } else {
            $nombreUnico = bin2hex(random_bytes(16));
            $rutaDestino = $directorio . '/' . $nombreUnico . ($extension ? '.' . $extension : '');
        }

        if (move_uploaded_file($_FILES[$this->campo]['tmp_name'], $rutaDestino)) {
            return $rutaDestino;
        }

        $this->huboError = true;
        $this->mensajeError = 'Error al guardar el archivo en el servidor.';
        return false;
    }

    public function guardarMultiple(string $directorio, bool $preservarNombre = false): array
    {
        $resultados = [];
        if (!$this->esMultiple) {
            return $resultados;
        }

        $archivos = $_FILES[$this->campo];
        for ($i = 0; $i < count($archivos['name']); $i++) {
            if ($archivos['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $this->nombreOriginal = $archivos['name'][$i];
            $this->tipoMime = $archivos['type'][$i];
            $this->tamanoBytes = $archivos['size'][$i];
            $this->huboError = false;

            $archivoTmp = [
                'tmp_name' => $archivos['tmp_name'][$i],
                'name' => $archivos['name'][$i],
                'type' => $archivos['type'][$i],
                'size' => $archivos['size'][$i],
                'error' => $archivos['error'][$i],
            ];

            $_FILES[$this->campo] = $archivoTmp;
            $resultados[] = $this->guardar($directorio, $preservarNombre);
        }

        return $resultados;
    }

    public function nombreOriginal(): ?string
    {
        return $this->nombreOriginal;
    }
    public function tipoMime(): ?string
    {
        return $this->tipoMime;
    }
    public function tamano(): ?int
    {
        return $this->tamanoBytes;
    }

    public function tamanoFormateado(): string
    {
        if ($this->tamanoBytes === null) {
            return '0 B';
        }
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor($this->tamanoBytes > 0 ? log($this->tamanoBytes) / log(1024) : 0);
        $i = min($i, count($unidades) - 1);
        $valor = $this->tamanoBytes / pow(1024, $i);
        return round($valor, max(0, 2 - ($i > 0 ? 1 : 0))) . ' ' . $unidades[$i];
    }

    public function tieneError(): bool
    {
        return $this->huboError;
    }
    public function error(): string
    {
        return $this->mensajeError;
    }
    public function esMultiple(): bool
    {
        return $this->esMultiple;
    }

    private function traducirErrorSubida(int $codigo): string
    {
        return match ($codigo) {
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario.',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente.',
            UPLOAD_ERR_NO_FILE => '',
            UPLOAD_ERR_NO_TMP_DIR => 'No se encontró el directorio temporal del servidor.',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.',
            default => 'Error desconocido al subir el archivo.',
        };
    }
}
