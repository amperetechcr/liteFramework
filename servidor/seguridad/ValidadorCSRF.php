<?php

declare(strict_types=1);

namespace LiteFramework\Seguridad;

use LiteFramework\Nucleo\Validador;

/**
 * Validador de Tokens Anti-Falsificación (CSRF) - Lite Framework
 * Ubicación: servidor/seguridad/ValidadorCSRF.php
 * Propósito: Generar y validar tokens CSRF con rotación automática y período de gracia.
 */

class ValidadorCSRF
{
    /**
     * Genera el token CSRF activo.
     */
    public static function generarToken(): string
    {
        if (!empty($_SESSION['token_seguridad_peticion'])) {
            $_SESSION['token_seguridad_previo'] = $_SESSION['token_seguridad_peticion'];
            $_SESSION['tiempo_token_previo'] = time();
        }
        $_SESSION['token_seguridad_peticion'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $_SESSION['token_seguridad_peticion'];
        return $_SESSION['token_seguridad_peticion'];
    }

    /**
     * Valida el token y lo rota automáticamente. Soporta peticiones asíncronas
     * concurrentes otorgando un período de gracia de 60 segundos al token anterior.
     * @param string $tokenRecibido
     * @return bool
     */
    public static function validarToken(string $tokenRecibido): bool
    {
        if (empty($tokenRecibido)) {
            return false;
        }

        $tokenActual = $_SESSION['token_seguridad_peticion'] ?? '';
        $tokenAnterior = $_SESSION['token_seguridad_previo'] ?? '';
        $tiempoAnterior = $_SESSION['tiempo_token_previo'] ?? 0;

        $esValidoActual = hash_equals($tokenActual, $tokenRecibido);

        $esValidoAnterior = false;
        if ($tokenAnterior !== '' && (time() - $tiempoAnterior) <= 60) {
            $esValidoAnterior = hash_equals($tokenAnterior, $tokenRecibido);
        }

        if ($esValidoActual || $esValidoAnterior) {
            if ($esValidoActual) {
                $_SESSION['token_seguridad_previo'] = $tokenActual;
                $_SESSION['tiempo_token_previo'] = time();
                $_SESSION['token_seguridad_peticion'] = bin2hex(random_bytes(32));
                $_SESSION['csrf_token'] = $_SESSION['token_seguridad_peticion'];
            }
            return true;
        }

        RegistroAuditoria::seguridad('Token CSRF invalido', [
            'motivo' => 'no_coincide_con_actual_ni_anterior',
            'longitud_recibido' => strlen($tokenRecibido),
        ]);
        return false;
    }
}
