<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

use LiteFramework\Servicios\AdministradorArchivos;

class AyudanteArchivo extends Helper
{
    private static array $iconosExtension = [
        'pdf' => '📄', 'doc' => '📝', 'docx' => '📝', 'xls' => '📊', 'xlsx' => '📊',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️', 'webp' => '🖼️', 'svg' => '🖼️',
        'mp4' => '🎬', 'avi' => '🎬', 'mov' => '🎬',
        'mp3' => '🎵', 'wav' => '🎵', 'ogg' => '🎵',
        'zip' => '📦', 'rar' => '📦', 'tar' => '📦', 'gz' => '📦',
        'txt' => '📄', 'csv' => '📊', 'json' => '📋', 'xml' => '📋',
        'php' => '⚙️', 'html' => '🌐', 'css' => '🎨', 'js' => '⚡',
    ];

    public static function tamanoLegible(int $bytes): string
    {
        return AdministradorArchivos::tamanoFormateado($bytes);
    }

    public static function esImagen(string $mime): bool
    {
        return AdministradorArchivos::esImagen($mime);
    }

    public static function esDocumento(string $mime): bool
    {
        return AdministradorArchivos::esDocumento($mime);
    }

    public static function esVideo(string $mime): bool
    {
        return AdministradorArchivos::esVideo($mime);
    }

    public static function esAudio(string $mime): bool
    {
        return AdministradorArchivos::esAudio($mime);
    }

    public static function esComprimido(string $mime): bool
    {
        return AdministradorArchivos::esComprimido($mime);
    }

    public static function categoriaMime(string $mime): string
    {
        return AdministradorArchivos::categoriaMime($mime);
    }

    public static function iconoExtension(string $extension): string
    {
        $ext = strtolower($extension);
        return self::$iconosExtension[$ext] ?? '📄';
    }

    public static function extensionSegura(string $nombre): string
    {
        return AdministradorArchivos::extensionSegura($nombre);
    }

    public static function sanitizarNombre(string $nombre): string
    {
        return AdministradorArchivos::sanitizarNombre($nombre);
    }

    public static function esNombreSeguro(string $nombre): bool
    {
        return AdministradorArchivos::esNombreSeguro($nombre);
    }
}

class ArchivoH extends AyudanteArchivo
{
}
