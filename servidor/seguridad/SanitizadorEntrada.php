<?php

declare(strict_types=1);

namespace LiteFramework\Seguridad;

class SanitizadorEntrada
{
    public static function sanitizarTextoBase(?string $textoCrudo): string
    {
        if ($textoCrudo === null) {
            return '';
        }
        $textoLimpio = trim($textoCrudo);
        $textoLimpio = preg_replace('/^(?:javascript|vbscript|data):/i', 'bloqueado:', $textoLimpio);
        $textoLimpio = preg_replace('/(?:\b)on[a-zA-Z]+\s*=/i', 'x-evento-anulado=', $textoLimpio);
        return htmlspecialchars($textoLimpio, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function sanitizarArreglo(array $arregloEntrada): array
    {
        return self::sanitizarArregloGlobal($arregloEntrada);
    }

    public static function sanitizarArregloGlobal(array $arregloEntrada): array
    {
        $arregloLimpio = [];
        if (is_array($arregloEntrada)) {
            foreach ($arregloEntrada as $clave => $valor) {
                if (is_array($valor)) {
                    $arregloLimpio[$clave] = self::sanitizarArregloGlobal($valor);
                } elseif (is_string($valor) && (str_contains(strtolower($clave), 'email') || str_contains(strtolower($clave), 'correo'))) {
                    $procesado = self::procesarCorreoElectronico($valor);
                    $arregloLimpio[$clave] = $procesado !== false ? $procesado : self::sanitizarTextoBase($valor);
                } else {
                    $arregloLimpio[$clave] = self::sanitizarTextoBase($valor);
                }
            }
        }
        return $arregloLimpio;
    }

    public static function procesarCorreoElectronico(string $correoCrudo): string|false
    {
        $correoLimpio = filter_var(trim($correoCrudo), FILTER_SANITIZE_EMAIL);
        if (filter_var($correoLimpio, FILTER_VALIDATE_EMAIL)) {
            return strtolower($correoLimpio);
        }
        return false;
    }

    public static function encriptarClaveOperador(string $contrasenaPlana): string
    {
        return password_hash($contrasenaPlana, PASSWORD_DEFAULT);
    }

    public static function verificarClaveOperador(string $contrasenaIngresada, string $hashAlmacenado): bool
    {
        return password_verify($contrasenaIngresada, $hashAlmacenado);
    }

    public static function sanitizarTextoPlano(?string $textoCrudo): string
    {
        if ($textoCrudo === null) {
            return '';
        }
        $textoLimpio = trim($textoCrudo);
        $textoLimpio = preg_replace('/^(?:javascript|vbscript|data):/i', 'bloqueado:', $textoLimpio);
        $textoLimpio = preg_replace('/(?:\b)on[a-zA-Z]+\s*=/i', 'x-evento-anulado=', $textoLimpio);
        $textoLimpio = strip_tags($textoLimpio);
        $textoLimpio = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $textoLimpio);
        return $textoLimpio;
    }
}
