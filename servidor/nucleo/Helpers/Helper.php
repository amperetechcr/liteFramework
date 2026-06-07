<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

use DateTime;

/**
 * liteFramework - Clase base para Helpers
 *
 * Proporciona métodos comunes a todos los ayudantes.
 */

abstract class Helper
{
    /**
     * Verifica si un valor está vacío
     */
    protected static function esVacio(mixed $valor): bool
    {
        return empty($valor) && $valor !== '0' && $valor !== 0;
    }

    /**
     * Obtiene un valor por defecto si el valor está vacío
     */
    protected static function conDefecto(mixed $valor, mixed $defecto): mixed
    {
        return self::esVacio($valor) ? $defecto : $valor;
    }

    /**
     * Convierte un valor a string de forma segura
     */
    protected static function aString(mixed $valor): string
    {
        if (is_null($valor)) {
            return '';
        }
        if (is_string($valor)) {
            return $valor;
        }
        if (is_numeric($valor)) {
            return (string) $valor;
        }
        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }
        if (is_array($valor)) {
            return json_encode($valor);
        }
        if (is_object($valor)) {
            if (method_exists($valor, '__toString')) {
                return (string) $valor;
            }
            return get_class($valor);
        }
        return '';
    }

    /**
     * Verifica si un valor es una fecha válida
     */
    protected static function esFechaValida(mixed $valor): bool
    {
        if (empty($valor)) {
            return false;
        }
        if ($valor instanceof DateTime) {
            return true;
        }
        return strtotime($valor) !== false;
    }
}
