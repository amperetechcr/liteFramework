<?php

declare(strict_types=1);

namespace LiteFramework\Seguridad;

/**
 * PoliticaContrasena — Valida que una contraseña cumpla los
 * requisitos mínimos de seguridad del framework.
 *
 * Uso:
 *   $resultado = PoliticaContrasena::validar($clavePlana);
 *   if ($resultado !== true) { ... mostrar error ... }
 */
class PoliticaContrasena
{
    /**
     * Valida una contraseña contra la política del sistema.
     *
     * @param string $clave Contraseña en texto plano
     * @return true|string true si es válida, o mensaje de error
     */
    public static function validar(string $clave)
    {
        if (strlen($clave) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $clave)) {
            return 'La contraseña debe contener al menos una mayúscula.';
        }
        if (!preg_match('/\d/', $clave)) {
            return 'La contraseña debe contener al menos un número.';
        }
        if (!preg_match('/[@$!%*?&]/', $clave)) {
            return 'La contraseña debe contener al menos un símbolo (@$!%*?&).';
        }
        return true;
    }
}
