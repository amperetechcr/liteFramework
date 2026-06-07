<?php

declare(strict_types=1);

namespace LiteFramework\Servicios\Verificadores;

use LiteFramework\Servicios\ContextoError;

class VerificadorArchivos implements VerificadorError
{
    public function tipo(): string
    {
        return 'archivos';
    }

    public function diagnosticar(ContextoError $ctx): ?array
    {
        $msg = $ctx->mensaje;

        if (str_contains($msg, 'UPLOAD_ERR_INI_SIZE') || str_contains($msg, 'upload_max_filesize')) {
            $limite = ini_get('upload_max_filesize');
            return [
                'tipo' => 'archivo_demasiado_grande',
                'detalle' => "El archivo excede el límite de subida ({$limite}).",
                'limite_actual' => $limite,
            ];
        }

        if (str_contains($msg, 'UPLOAD_ERR_FORM_SIZE')) {
            return [
                'tipo' => 'archivo_demasiado_grande',
                'detalle' => 'El archivo excede el tamaño máximo permitido en el formulario.',
            ];
        }

        if (str_contains($msg, 'UPLOAD_ERR_NO_TMP_DIR')) {
            $tmp = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
            return [
                'tipo' => 'tmp_dir_faltante',
                'detalle' => "El directorio temporal ({$tmp}) no existe o no es accesible.",
                'tmp_dir' => $tmp,
            ];
        }

        if (str_contains($msg, 'UPLOAD_ERR_CANT_WRITE')) {
            $espacio = function_exists('disk_free_space') ? round(disk_free_space(__DIR__) / 1048576, 1) : 0;
            return [
                'tipo' => 'disco_sin_espacio',
                'detalle' => "No se pudo escribir el archivo en el disco. Espacio libre: {$espacio} MB.",
                'espacio_libre_mb' => $espacio,
            ];
        }

        if (str_contains($msg, 'mkdir()') && str_contains($msg, 'Permission denied')) {
            preg_match("/mkdir\(([^)]+)\)/", $msg, $m);
            $ruta = $m[1] ?? 'desconocida';
            return [
                'tipo' => 'directorio_no_creable',
                'detalle' => "No se puede crear el directorio: {$ruta}.",
                'ruta' => $ruta,
            ];
        }

        if (str_contains($msg, 'move_uploaded_file') || str_contains($msg, 'Permission denied')) {
            return [
                'tipo' => 'permiso_escritura',
                'detalle' => 'Error de permisos al escribir archivos en el servidor.',
            ];
        }

        if (str_contains($msg, 'file_put_contents') && str_contains($msg, 'failed to open stream')) {
            preg_match("/file_put_contents\(([^)]+)\)/", $msg, $m);
            $ruta = $m[1] ?? 'desconocida';
            return [
                'tipo' => 'archivo_no_escribible',
                'detalle' => "No se puede escribir en: {$ruta}. Verifique permisos del directorio.",
                'ruta' => $ruta,
            ];
        }

        return null;
    }

    public function tieneRemedioAutomatico(): bool
    {
        return true;
    }

    public function ejecutarRemedio(array $diagnostico): array
    {
        $tipo = $diagnostico['tipo'] ?? '';

        if ($tipo === 'directorio_no_creable') {
            $ruta = $diagnostico['ruta'] ?? '';
            if ($ruta) {
                $rutaLimpia = trim($ruta, "'\"");
                try {
                    if (!is_dir($rutaLimpia)) {
                        mkdir($rutaLimpia, 0755, true);
                        if (is_dir($rutaLimpia)) {
                            return ['exito' => true, 'mensaje' => "Directorio '{$rutaLimpia}' creado correctamente.", 'reintentar' => true];
                        }
                    }
                } catch (\Throwable $e) {
                    return ['exito' => false, 'mensaje' => "No se pudo crear el directorio: " . $e->getMessage(), 'reintentar' => false];
                }
            }
        }

        if ($tipo === 'tmp_dir_faltante') {
            $tmp = $diagnostico['tmp_dir'] ?? '';
            if ($tmp) {
                try {
                    if (!is_dir($tmp)) {
                        mkdir($tmp, 0777, true);
                        if (is_dir($tmp)) {
                            return ['exito' => true, 'mensaje' => "Directorio temporal '{$tmp}' creado.", 'reintentar' => true];
                        }
                    }
                } catch (\Throwable $e) {
                    return ['exito' => false, 'mensaje' => "No se pudo crear el directorio temporal.", 'reintentar' => false];
                }
            }
        }

        return ['exito' => false, 'mensaje' => 'Este problema requiere intervención manual.', 'reintentar' => false];
    }

    public function obtenerSugerencias(array $diagnostico): array
    {
        $tipo = $diagnostico['tipo'] ?? '';
        $sugs = [];

        switch ($tipo) {
            case 'archivo_demasiado_grande':
                $limite = $diagnostico['limite_actual'] ?? 'desconocido';
                $sugs[] = "El archivo excede el límite de subida ({$limite}).";
                $sugs[] = 'Puede: comprimir el archivo, o aumentar el límite en php.ini:';
                $sugs[] = "  upload_max_filesize = 100M";
                $sugs[] = "  post_max_size = 100M";
                $sugs[] = 'También puede ajustar estos valores desde Administración → Configuración del servidor.';
                break;
            case 'tmp_dir_faltante':
                $tmp = $diagnostico['tmp_dir'] ?? '';
                $sugs[] = "El directorio temporal ({$tmp}) no existe.";
                $sugs[] = "Cree el directorio manualmente: mkdir -p {$tmp}";
                $sugs[] = 'O configure upload_tmp_dir en php.ini apuntando a un directorio que exista.';
                break;
            case 'disco_sin_espacio':
                $libre = $diagnostico['espacio_libre_mb'] ?? 0;
                $sugs[] = "Espacio libre en disco: {$libre} MB.";
                $sugs[] = 'Libere espacio eliminando archivos temporales o respaldos antiguos.';
                break;
            case 'directorio_no_creable':
                $ruta = $diagnostico['ruta'] ?? '';
                $sugs[] = "No se puede crear el directorio: {$ruta}";
                $sugs[] = "Ejecute: mkdir -p {$ruta}";
                $sugs[] = "Luego: chmod 775 {$ruta}";
                break;
            case 'permiso_escritura':
            case 'archivo_no_escribible':
                $ruta = $diagnostico['ruta'] ?? 'directorio de destino';
                $sugs[] = "Error de permisos de escritura en: {$ruta}";
                $sugs[] = "Ejecute: chmod 775 {$ruta}";
                $sugs[] = 'En Windows, verifique que la carpeta no sea de solo lectura.';
                break;
        }
        return $sugs;
    }
}
