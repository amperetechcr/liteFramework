<?php

declare(strict_types=1);

namespace LiteFramework\Config;

class GeneradorIniServidor
{
    private const RUTA_USER_INI = DIRECTORIO_RAIZ . '/.user.ini';
    private const RUTA_BACKUP = DIRECTORIO_RAIZ . '/.user.ini.backup';
    private const RUTA_HTACCESS = DIRECTORIO_RAIZ . '/.htaccess';
    private const TAMANO_MAXIMO_MB = 2048;

    private const MARCA_INICIO = '# === LIMITES_PHP_INICIO ===';
    private const MARCA_FIN = '# === LIMITES_PHP_FIN ===';

    public static function regenerar(array $valores): array
    {
        $errores = self::validarDirectivas($valores);
        if (!empty($errores)) {
            return ['estado' => 'error', 'errores' => $errores];
        }

        if (!is_writable(DIRECTORIO_RAIZ)) {
            return [
                'estado' => 'error',
                'errores' => ['El directorio raiz no tiene permisos de escritura.'],
            ];
        }

        $erroresAgregados = [];

        $resultadoUserIni = self::escribirUserIni($valores);
        if ($resultadoUserIni !== null) {
            $erroresAgregados = array_merge($erroresAgregados, $resultadoUserIni);
        }

        $resultadoHtaccess = self::escribirHtaccess($valores);
        if ($resultadoHtaccess !== null) {
            $erroresAgregados = array_merge($erroresAgregados, $resultadoHtaccess);
        }

        if (!empty($erroresAgregados)) {
            return ['estado' => 'error', 'errores' => $erroresAgregados];
        }

        return ['estado' => 'ok'];
    }

    public static function leerActual(): string
    {
        return self::leerBloqueHtaccess();
    }

    private static function escribirUserIni(array $valores): ?array
    {
        $contenido = self::generarContenidoUserIni($valores);
        $rutaTemporal = self::RUTA_USER_INI . '.tmp.' . bin2hex(random_bytes(4));

        $bytesEscritos = @file_put_contents($rutaTemporal, $contenido);
        if ($bytesEscritos === false) {
            return ['No se pudo escribir el archivo temporal para .user.ini.'];
        }

        if (!@rename($rutaTemporal, self::RUTA_USER_INI)) {
            @unlink($rutaTemporal);
            return ['No se pudo reemplazar .user.ini.'];
        }

        return null;
    }

    private static function escribirHtaccess(array $valores): ?array
    {
        $rutaHtaccess = self::RUTA_HTACCESS;

        $contenidoActual = file_exists($rutaHtaccess) ? (file_get_contents($rutaHtaccess) ?: '') : '';

        $bloqueNuevo = self::generarBloqueHtaccess($valores);

        $posInicio = strpos($contenidoActual, self::MARCA_INICIO);
        $posFin = strpos($contenidoActual, self::MARCA_FIN);

        $rtrimmed = rtrim($contenidoActual);

        if ($posInicio !== false && $posFin !== false) {
            $antes = rtrim(substr($contenidoActual, 0, $posInicio));
            $despues = ltrim(substr($contenidoActual, $posFin + strlen(self::MARCA_FIN)));
            $contenidoNuevo = $antes . "\n" . $bloqueNuevo . "\n" . $despues;
        } else {
            $contenidoNuevo = $rtrimmed . "\n\n" . $bloqueNuevo . "\n";
        }

        $rutaTemporal = $rutaHtaccess . '.tmp.' . bin2hex(random_bytes(4));
        $bytesEscritos = @file_put_contents($rutaTemporal, $contenidoNuevo);
        if ($bytesEscritos === false) {
            return ['No se pudo escribir el archivo temporal para .htaccess.'];
        }

        if (!@rename($rutaTemporal, $rutaHtaccess)) {
            @unlink($rutaTemporal);
            return ['No se pudo reemplazar .htaccess.'];
        }

        return null;
    }

    private static function leerBloqueHtaccess(): string
    {
        $rutaHtaccess = self::RUTA_HTACCESS;
        if (!file_exists($rutaHtaccess)) {
            return '';
        }

        $contenido = file_get_contents($rutaHtaccess) ?: '';
        $posInicio = strpos($contenido, self::MARCA_INICIO);
        $posFin = strpos($contenido, self::MARCA_FIN);

        if ($posInicio === false || $posFin === false) {
            return '';
        }

        $longitudInicio = strlen(self::MARCA_INICIO);
        $bloque = substr($contenido, $posInicio + $longitudInicio, $posFin - $posInicio - $longitudInicio);
        return trim($bloque) . "\n";
    }

    public static function revertir(): array
    {
        if (!file_exists(self::RUTA_BACKUP)) {
            return ['estado' => 'error', 'mensaje' => 'No hay backup disponible para revertir.'];
        }
        $contenidoBackup = file_get_contents(self::RUTA_BACKUP) ?: '';
        $rutaTemporal = self::RUTA_USER_INI . '.tmp.' . bin2hex(random_bytes(4));
        if (@file_put_contents($rutaTemporal, $contenidoBackup) === false) {
            return ['estado' => 'error', 'mensaje' => 'No se pudo escribir el archivo temporal.'];
        }
        if (!@rename($rutaTemporal, self::RUTA_USER_INI)) {
            @unlink($rutaTemporal);
            return ['estado' => 'error', 'mensaje' => 'No se pudo restaurar el backup.'];
        }
        return ['estado' => 'ok'];
    }

    public static function limitesActualesPHP(): array
    {
        return [
            'upload_max_filesize' => self::iniToMB(ini_get('upload_max_filesize') ?: ''),
            'post_max_size' => self::iniToMB(ini_get('post_max_size') ?: ''),
            'memory_limit' => self::iniToMB(ini_get('memory_limit') ?: ''),
            'max_execution_time' => (int)(ini_get('max_execution_time') ?: '0'),
            'max_file_uploads' => (int)ini_get('max_file_uploads'),
            'file_uploads' => ini_get('file_uploads') ? 'habilitado' : 'deshabilitado',
        ];
    }

    private static function generarContenidoUserIni(array $valores): string
    {
        $contenido = "; liteFramework - Limites de subida\n";
        $contenido .= "; Generado automaticamente desde /configuracion\n";
        $contenido .= "; NO editar a mano. Use la UI de Configuracion.\n";
        $contenido .= "; Regenerado: " . date('Y-m-d H:i:s') . "\n\n";
        $contenido .= "memory_limit = " . (int)$valores['memory_limit'] . "M\n";
        $contenido .= "post_max_size = " . (int)$valores['post_max_size'] . "M\n";
        $contenido .= "upload_max_filesize = " . (int)$valores['upload_max_filesize'] . "M\n";
        $contenido .= "max_file_uploads = " . (int)$valores['max_file_uploads'] . "\n";
        $contenido .= "max_execution_time = " . (int)$valores['max_execution_time'] . "\n";
        return $contenido;
    }

    private static function generarBloqueHtaccess(array $valores): string
    {
        $bloque = self::MARCA_INICIO . "\n";
        $bloque .= "# Generado automaticamente desde /configuracion - " . date('Y-m-d H:i:s') . "\n";
        $bloque .= "# NO editar a mano. Use la UI de Configuracion.\n";
        $bloque .= "php_value memory_limit " . (int)$valores['memory_limit'] . "M\n";
        $bloque .= "php_value post_max_size " . (int)$valores['post_max_size'] . "M\n";
        $bloque .= "php_value upload_max_filesize " . (int)$valores['upload_max_filesize'] . "M\n";
        $bloque .= "php_value max_file_uploads " . (int)$valores['max_file_uploads'] . "\n";
        $bloque .= "php_value max_execution_time " . (int)$valores['max_execution_time'] . "\n";
        $bloque .= self::MARCA_FIN;
        return $bloque;
    }

    private static function validarDirectivas(array $valores): array
    {
        $errores = [];
        $requeridas = ['upload_max_filesize', 'post_max_size', 'memory_limit', 'max_execution_time', 'max_file_uploads'];

        foreach ($requeridas as $key) {
            if (!isset($valores[$key]) || !is_numeric($valores[$key]) || (int)$valores[$key] <= 0) {
                $errores[] = "El valor '$key' debe ser un numero positivo.";
            }
        }

        if ((int)($valores['upload_max_filesize'] ?? 0) > self::TAMANO_MAXIMO_MB) {
            $errores[] = 'El tamano maximo por archivo no puede exceder ' . self::TAMANO_MAXIMO_MB . ' MB.';
        }

        if ((int)($valores['post_max_size'] ?? 0) < (int)($valores['upload_max_filesize'] ?? 0)) {
            $errores[] = 'post_max_size debe ser mayor o igual a upload_max_filesize.';
        }

        if ((int)($valores['memory_limit'] ?? 0) < (int)($valores['post_max_size'] ?? 0)) {
            $errores[] = 'memory_limit debe ser mayor o igual a post_max_size.';
        }

        if ((int)($valores['max_execution_time'] ?? 0) > 3600) {
            $errores[] = 'max_execution_time no puede exceder 3600 segundos (1 hora).';
        }

        if ((int)($valores['max_file_uploads'] ?? 0) > 1000) {
            $errores[] = 'max_file_uploads no puede exceder 1000.';
        }

        return $errores;
    }

    private static function iniToMB(string $valor): int
    {
        $valor = trim($valor);
        if ($valor === '' || $valor === '-1') {
            return 0;
        }
        $unidad = strtoupper(substr($valor, -1));
        $numero = (int)$valor;
        return match ($unidad) {
            'G' => $numero * 1024,
            'M' => $numero,
            'K' => (int)round($numero / 1024),
            default => (int)round($numero / 1024 / 1024),
        };
    }
}
