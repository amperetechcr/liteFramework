<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

use LiteFramework\Modelos\Archivo;
use LiteFramework\Nucleo\SubidaArchivos;
use LiteFramework\Config\ConfiguracionSistema;

class AdministradorArchivos
{
    private const MAPA_MIME = [
        'imagenes' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp', 'image/tiff',
        ],
        'documentos' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'text/plain', 'text/csv', 'text/markdown',
        ],
        'videos' => [
            'video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm', 'video/x-msvideo', 'video/x-matroska',
        ],
        'audio' => [
            'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/webm', 'audio/x-wav', 'audio/flac',
        ],
        'comprimidos' => [
            'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
            'application/x-tar', 'application/gzip',
        ],
        'ejecutables' => [
            'application/x-dosexec',
            'application/x-msdownload',
            'application/x-msdos-program',
            'application/x-msi',
            'application/x-executable',
            'application/x-mach-binary',
        ],
        'codigo' => [
            'text/javascript', 'application/javascript',
            'application/json',
            'text/css',
            'text/xml', 'application/xml',
            'text/x-php', 'application/x-httpd-php',
            'text/x-html', 'text/html',
            'application/sql',
            'application/x-sh', 'text/x-shellscript',
            'text/x-yaml', 'text/x-ini',
        ],
        'datos' => [
            'application/octet-stream',
            'text/x-log',
            'text/x-env',
            'application/x-sqlite3',
        ],
    ];

    public function __construct(
        private readonly string $storagePath,
        private readonly string $baseUrl,
    ) {
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    // ─── Operaciones principales ───

    public function subir(array $archivoFila, int $idOperador, array $opciones = []): array
    {
        $tamanoMaximo = (int)(ConfiguracionSistema::obtener('ARCHIVO_TAMANO_MAXIMO_MB', 40)) * 1024 * 1024;
        $cuotaUsuario = (int)(ConfiguracionSistema::obtener('ARCHIVO_CUOTA_USUARIO_MB', 100)) * 1024 * 1024;

        $moduloOrigen = $opciones['modulo_origen'] ?? 'general';
        $etiquetas = $opciones['etiquetas'] ?? '';
        $descripcion = $opciones['descripcion'] ?? '';
        $rutaRelativa = $opciones['ruta_relativa'] ?? '';

        $campoFormulario = $opciones['campo_formulario'] ?? 'archivo';

        if ($cuotaUsuario > 0) {
            $usoActual = $this->calcularUsoUsuario($idOperador);
            $tamanoArchivo = (int)$archivoFila['size'];
            if (($usoActual + $tamanoArchivo) > $cuotaUsuario) {
                return [
                    'estado_operacion' => false,
                    'codigo_error' => 'cuota_excedida',
                    'mensaje_error' => 'Cuota de almacenamiento excedida.',
                    'usado_bytes' => $usoActual,
                    'cuota_bytes' => $cuotaUsuario,
                ];
            }
        }

        $_FILES[$campoFormulario] = $archivoFila;

        $subida = new SubidaArchivos($campoFormulario);
        $tiposMime = $this->obtenerTiposMimePermitidos();
        $extensiones = $this->obtenerExtensionesPermitidas();
        $subida->establecerExtensionesPermitidas($extensiones);
        $subida->validar($tiposMime, $tamanoMaximo);

        if ($subida->tieneError()) {
            return [
                'estado_operacion' => false,
                'codigo_error' => 'tipo_no_permitido',
                'mensaje_error' => $subida->error(),
            ];
        }

        $rutaDestino = $this->storagePath;
        if ($rutaRelativa !== '') {
            $directorioRel = str_replace('\\', '/', dirname($rutaRelativa));
            if ($directorioRel !== '.') {
                $rutaDestino = $this->storagePath . '/' . $directorioRel;
            }
        }

        $rutaGuardado = $subida->guardar($rutaDestino, true);
        if (!$rutaGuardado) {
            return [
                'estado_operacion' => false,
                'codigo_error' => 'error_guardado',
                'mensaje_error' => $subida->error(),
            ];
        }

        $archivo = Archivo::crear([
            'nombre_original' => $subida->nombreOriginal(),
            'nombre_generado' => basename($rutaGuardado),
            'ruta_archivo' => $rutaGuardado,
            'tipo_mime' => $subida->tipoMime(),
            'tamano_bytes' => $subida->tamano(),
            'id_operador' => $idOperador,
            'modulo_origen' => $moduloOrigen,
            'etiquetas' => $etiquetas,
            'descripcion' => $descripcion,
        ]);

        return [
            'estado_operacion' => true,
            'datos' => $archivo->aArreglo(),
            'archivo_id' => $archivo->id_archivo,
        ];
    }

    public function eliminar(int $idArchivo): array
    {
        $archivo = Archivo::buscar($idArchivo);
        if (!$archivo) {
            return [
                'estado_operacion' => false,
                'codigo_error' => 'no_encontrado',
                'mensaje_error' => 'Archivo no encontrado.',
            ];
        }

        if (file_exists($archivo->ruta_archivo)) {
            unlink($archivo->ruta_archivo);
        }

        $archivo->eliminar();

        return [
            'estado_operacion' => true,
            'datos' => ['id' => $idArchivo],
        ];
    }

    public function eliminarCarpeta(string $rutaRelativa): array
    {
        $rutaRelativa = trim(str_replace('\\', '/', $rutaRelativa), '/');
        if ($rutaRelativa === '' || str_contains($rutaRelativa, '..')) {
            return [
                'estado_operacion' => false,
                'codigo_error' => 'ruta_invalida',
                'mensaje_error' => 'Ruta de carpeta invalida.',
            ];
        }

        $rutaFisica = rtrim($this->storagePath, '/\\') . '/' . $rutaRelativa;
        if (!is_dir($rutaFisica)) {
            return [
                'estado_operacion' => false,
                'codigo_error' => 'carpeta_no_existe',
                'mensaje_error' => 'La carpeta no existe en el servidor.',
            ];
        }

        $eliminados = 0;
        $todos = Archivo::todos();
        $prefijoBusqueda = rtrim($this->storagePath, '/\\') . '/' . $rutaRelativa;

        foreach ($todos as $archivo) {
            $rutaArchivo = str_replace('\\', '/', $archivo->ruta_archivo ?? '');
            if (str_starts_with($rutaArchivo, $prefijoBusqueda . '/')) {
                if (file_exists($archivo->ruta_archivo)) {
                    unlink($archivo->ruta_archivo);
                }
                $archivo->eliminar();
                $eliminados++;
            }
        }

        $this->limpiarDirectorioVacio($rutaFisica);

        return [
            'estado_operacion' => true,
            'datos' => ['eliminados' => $eliminados],
        ];
    }

    private function limpiarDirectorioVacio(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $archivo) {
            if ($archivo->isDir()) {
                @rmdir($archivo->getRealPath());
            } else {
                @unlink($archivo->getRealPath());
            }
        }
        @rmdir($dir);
    }

    public function descargar(int $idArchivo): array
    {
        $archivo = Archivo::buscar($idArchivo);
        if (!$archivo) {
            return [
                'estado_operacion' => false,
                'codigo_error' => 'no_encontrado',
                'mensaje_error' => 'Archivo no encontrado.',
            ];
        }

        $rutaFisica = $archivo->ruta_archivo;
        if (!file_exists($rutaFisica)) {
            return [
                'estado_operacion' => false,
                'codigo_error' => 'archivo_fisico_no_encontrado',
                'mensaje_error' => 'El archivo fisico no existe en el servidor.',
            ];
        }

        return [
            'estado_operacion' => true,
            'datos' => [
                'ruta' => $rutaFisica,
                'nombre_original' => $archivo->nombre_original,
                'tipo_mime' => $archivo->tipo_mime ?: 'application/octet-stream',
                'tamano_bytes' => filesize($rutaFisica),
            ],
        ];
    }

    public function descargarCarpeta(string $rutaRelativa): array
    {
        $rutaRelativa = trim(str_replace('\\', '/', $rutaRelativa), '/');
        if ($rutaRelativa === '' || str_contains($rutaRelativa, '..')) {
            return [
                'estado_operacion' => false,
                'codigo_error' => 'ruta_invalida',
                'mensaje_error' => 'Ruta de carpeta invalida.',
            ];
        }

        $rutaFisica = rtrim($this->storagePath, '/\\') . '/' . $rutaRelativa;
        if (!is_dir($rutaFisica)) {
            return [
                'estado_operacion' => false,
                'codigo_error' => 'carpeta_no_existe',
                'mensaje_error' => 'La carpeta no existe en el servidor.',
            ];
        }

        $nombreZip = basename($rutaRelativa) . '.zip';
        $rutaZip = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8)) . '.zip';

        try {
            $zip = new \PharData($rutaZip, 0, null, \Phar::ZIP);

            $archivos = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($rutaFisica, \RecursiveDirectoryIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            $zip->buildFromIterator($archivos, str_replace('\\', '/', rtrim($this->storagePath, '/\\')) . '/');
        } catch (\Exception $e) {
            @unlink($rutaZip);
            return [
                'estado_operacion' => false,
                'codigo_error' => 'error_zip',
                'mensaje_error' => 'No se pudo crear el archivo ZIP.',
            ];
        }

        if (!file_exists($rutaZip) || filesize($rutaZip) === 0) {
            @unlink($rutaZip);
            return [
                'estado_operacion' => false,
                'codigo_error' => 'zip_vacio',
                'mensaje_error' => 'La carpeta esta vacia.',
            ];
        }

        return [
            'estado_operacion' => true,
            'datos' => [
                'ruta' => $rutaZip,
                'nombre_original' => $nombreZip,
                'tipo_mime' => 'application/zip',
                'tamano_bytes' => filesize($rutaZip),
                'temp' => true,
            ],
        ];
    }

    public function listar(?array $filtros = []): array
    {
        $todos = Archivo::todos();
        $resultado = [];

        foreach ($todos as $archivo) {
            if (!file_exists($archivo->ruta_archivo)) {
                continue;
            }

            $datos = $archivo->aArreglo();
            $datos['ruta_mostrar'] = self::rutaMostrar($archivo->ruta_archivo, $this->storagePath);

            if (!empty($filtros['id_operador']) && (int)$archivo->id_operador !== (int)$filtros['id_operador']) {
                continue;
            }
            if (!empty($filtros['modulo_origen']) && $archivo->modulo_origen !== $filtros['modulo_origen']) {
                continue;
            }

            $resultado[] = $datos;
        }

        return $resultado;
    }

    public function obtener(int $idArchivo): ?array
    {
        $archivo = Archivo::buscar($idArchivo);
        if (!$archivo) {
            return null;
        }
        $datos = $archivo->aArreglo();
        $datos['ruta_mostrar'] = self::rutaMostrar($archivo->ruta_archivo, $this->storagePath);
        return $datos;
    }

    // ─── Cuota y uso ───

    public function calcularUsoUsuario(int $idOperador): int
    {
        return Archivo::sumaTamanoPorOperador($idOperador);
    }

    public function obtenerConfiguracion(): array
    {
        $config = [];
        foreach (ConfiguracionSistema::obtenerTodas() as $clave => $fila) {
            if (strpos($clave, 'ARCHIVO_') === 0) {
                $config[$clave] = [
                    'valor' => ConfiguracionSistema::obtener($clave),
                    'version' => (int)$fila['version'],
                ];
            }
        }
        return $config;
    }

    // ─── Configuracion MIME ───

    public function obtenerTiposMimePermitidos(): array
    {
        $categorias = ConfiguracionSistema::obtener('ARCHIVO_TIPOS_MIME_PERMITIDOS', 'imagenes,documentos,codigo,datos');
        $categorias = array_map('trim', explode(',', $categorias));
        if (in_array('*', $categorias, true)) {
            return [];
        }
        $tipos = [];
        foreach ($categorias as $categoria) {
            if (isset(self::MAPA_MIME[$categoria])) {
                $tipos = array_merge($tipos, self::MAPA_MIME[$categoria]);
            }
        }
        return $tipos;
    }

    public function obtenerExtensionesPermitidas(): array
    {
        $extensiones = ConfiguracionSistema::obtener('ARCHIVO_EXTENSIONES_PERMITIDAS', 'jpg,jpeg,png,gif,webp,bmp,pdf,doc,docx,xls,xlsx,txt,csv,php,js,css,sql,md,json,xml,log,ini,env,example,backup');
        $lista = array_map('trim', explode(',', $extensiones));
        if (in_array('*', $lista, true)) {
            return [];
        }
        return $lista;
    }

    // ─── Metodos estaticos de utilidad ───

    public static function esImagen(string $mime): bool
    {
        return in_array($mime, self::MAPA_MIME['imagenes'], true);
    }

    public static function esDocumento(string $mime): bool
    {
        return in_array($mime, self::MAPA_MIME['documentos'], true);
    }

    public static function esVideo(string $mime): bool
    {
        return in_array($mime, self::MAPA_MIME['videos'], true);
    }

    public static function esAudio(string $mime): bool
    {
        return in_array($mime, self::MAPA_MIME['audio'], true);
    }

    public static function esComprimido(string $mime): bool
    {
        return in_array($mime, self::MAPA_MIME['comprimidos'], true);
    }

    public static function categoriaMime(string $mime): string
    {
        foreach (self::MAPA_MIME as $categoria => $mimes) {
            if (in_array($mime, $mimes, true)) {
                return $categoria;
            }
        }
        return 'otro';
    }

    public static function tamanoFormateado(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = min(floor(log($bytes) / log(1024)), count($unidades) - 1);
        return round($bytes / pow(1024, $i), 2) . ' ' . $unidades[$i];
    }

    public static function rutaMostrar(string $rutaArchivo, string $storagePath): string
    {
        $base = rtrim($storagePath, '/\\') . '/';
        if (str_starts_with($rutaArchivo, $base)) {
            $relativa = substr($rutaArchivo, strlen($base));
            $dir = dirname($relativa);
            return $dir === '.' ? '' : $relativa;
        }
        return $rutaArchivo;
    }

    public static function enlaceDescarga(int $idArchivo, string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . '/archivos/descargar/' . $idArchivo;
    }

    public static function iconoExtension(string $extension): string
    {
        $iconos = [
            'pdf' => "\u{1F4C4}", 'doc' => "\u{1F4DD}", 'docx' => "\u{1F4DD}",
            'xls' => "\u{1F4CA}", 'xlsx' => "\u{1F4CA}",
            'jpg' => "\u{1F5BC}", 'jpeg' => "\u{1F5BC}", 'png' => "\u{1F5BC}",
            'gif' => "\u{1F5BC}", 'webp' => "\u{1F5BC}", 'svg' => "\u{1F5BC}",
            'mp4' => "\u{1F3AC}", 'avi' => "\u{1F3AC}", 'mov' => "\u{1F3AC}",
            'mp3' => "\u{1F3B5}", 'wav' => "\u{1F3B5}", 'ogg' => "\u{1F3B5}",
            'zip' => "\u{1F4E6}", 'rar' => "\u{1F4E6}", 'tar' => "\u{1F4E6}", 'gz' => "\u{1F4E6}",
            'txt' => "\u{1F4C4}", 'csv' => "\u{1F4CA}", 'json' => "\u{1F4CB}", 'xml' => "\u{1F4CB}",
            'php' => "\u{2699}", 'html' => "\u{1F310}", 'css' => "\u{1F3A8}", 'js' => "\u{26A1}",
        ];
        return $iconos[strtolower($extension)] ?? "\u{1F4C4}";
    }

    public static function extensionSegura(string $nombre): string
    {
        $partes = explode('.', $nombre);
        if (count($partes) < 2) {
            return '';
        }
        return strtolower(end($partes));
    }

    public static function sanitizarNombre(string $nombre): string
    {
        $nombre = preg_replace('/[^\w\.\- ]/u', '_', $nombre);
        $nombre = preg_replace('/_{2,}/', '_', $nombre);
        $nombre = trim($nombre, '._-');
        return empty($nombre) ? 'archivo' : $nombre;
    }

    public static function esNombreSeguro(string $nombre): bool
    {
        return preg_match('/^[\w\.\- ]+$/', $nombre) === 1;
    }
}
