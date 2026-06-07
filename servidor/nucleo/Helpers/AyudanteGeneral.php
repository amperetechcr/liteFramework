<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

/**
 * liteFramework - Ayudante General
 *
 * Utilidades generales que no encajan en otras categorías.
 *
 * Uso:
 *   General::tieneValor($variable)
 *   General::generarToken(32)
 */

class AyudanteGeneral extends Helper
{
    /**
     * Verifica si un valor tiene contenido (no vacío)
     */
    public static function tieneValor(mixed $valor): bool
    {
        if (is_null($valor)) {
            return false;
        }
        if (is_string($valor) && trim($valor) === '') {
            return false;
        }
        if (is_array($valor) && count($valor) === 0) {
            return false;
        }
        return true;
    }

    /**
     * Alias de tieneValor
     */
    public static function noEstaVacio(mixed $valor): bool
    {
        return self::tieneValor($valor);
    }

    /**
     * Alias inverso de tieneValor
     */
    public static function estaVacio(mixed $valor): bool
    {
        return !self::tieneValor($valor);
    }

    /**
     * Obtiene un valor de una fuente con fallback
     */
    public static function obtenerValor(array $fuente, string $clave, mixed $defecto = null): mixed
    {
        return $fuente[$clave] ?? $defecto;
    }

    /**
     * Alias para obtenerValor
     */
    public static function desde(array $fuente, string $clave, mixed $defecto = null): mixed
    {
        return self::obtenerValor($fuente, $clave, $defecto);
    }

    /**
     * Genera un token aleatorio
     */
    public static function generarToken(int $longitud = 32): string
    {
        if ($longitud <= 0) {
            return '';
        }

        $bytes = random_bytes(max(1, (int) ceil($longitud / 2)));
        $token = bin2hex($bytes);

        return substr($token, 0, $longitud);
    }

    /**
     * Clona un objeto de forma segura
     */
    public static function clonar(object $objeto): object
    {
        return clone $objeto;
    }

    /**
     * Verifica si un objeto tiene un método
     */
    public static function esMetodo(object $objeto, string $metodo): bool
    {
        return method_exists($objeto, $metodo);
    }

    /**
     * Verifica si un objeto tiene una propiedad
     */
    public static function tienePropiedad(object $objeto, string $propiedad): bool
    {
        return property_exists($objeto, $propiedad);
    }

    /**
     * Obtiene el nombre de clase de un objeto
     */
    public static function tipoDe(object $objeto): string
    {
        return get_class($objeto);
    }

    /**
     * Verifica si una variable es un objeto
     */
    public static function esObjeto(mixed $valor): bool
    {
        return is_object($valor);
    }

    /**
     * Verifica si una variable es un arreglo
     */
    public static function esArreglo(mixed $valor): bool
    {
        return is_array($valor);
    }

    /**
     * Verifica si una variable es string
     */
    public static function esString(mixed $valor): bool
    {
        return is_string($valor);
    }

    /**
     * Verifica si una variable es numérica
     */
    public static function esNumerico(mixed $valor): bool
    {
        return is_numeric($valor);
    }

    /**
     * Verifica si una variable es booleana
     */
    public static function esBooleano(mixed $valor): bool
    {
        return is_bool($valor);
    }

    /**
     * Convierte valor a booleano
     */
    public static function aBooleano(mixed $valor): bool
    {
        if (is_bool($valor)) {
            return $valor;
        }
        if (is_string($valor)) {
            return in_array(strtolower($valor), ['1', 'true', 'si', 'yes', 'on']);
        }
        return (bool) $valor;
    }

    /**
     * Convierte valor a entero
     */
    public static function aEntero(mixed $valor, int $defecto = 0): int
    {
        if (is_numeric($valor)) {
            return (int) $valor;
        }
        return $defecto;
    }

    /**
     * Convierte valor a flotante
     */
    public static function aFlotante(mixed $valor, float $defecto = 0.0): float
    {
        if (is_numeric($valor)) {
            return (float) $valor;
        }
        return $defecto;
    }

    /**
     * Convierte valor a string
     */
    public static function aString(mixed $valor): string
    {
        if (is_null($valor)) {
            return '';
        }
        if (is_string($valor)) {
            return $valor;
        }
        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }
        if (is_numeric($valor)) {
            return (string) $valor;
        }
        if (is_array($valor)) {
            return json_encode($valor) ?: '';
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
     * Serializa valor a JSON
     */
    public static function aJson(mixed $valor, bool $pretty = false): string
    {
        if ($pretty) {
            return json_encode($valor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '';
        }
        return json_encode($valor) ?: '';
    }

    /**
     * Desserializa JSON a valor
     */
    public static function desdeJson(string $json, mixed $defecto = null): mixed
    {
        $resultado = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $defecto;
        }
        return $resultado;
    }

    /**
     * Verifica si es JSON válido
     */
    public static function esJson(string $texto): bool
    {
        json_decode($texto);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Formatea un número como moneda
     */
    public static function moneda(float|int $monto, string $simbolo = '$', string $separadorMiles = ',', string $separadorDecimal = '.'): string
    {
        $parteEntera = floor(abs($monto));
        $parteDecimal = round(abs($monto) - $parteEntera, 2) * 100;

        $signo = $monto < 0 ? '-' : '';

        $parteEnteraFormateada = number_format($parteEntera, 0, '', $separadorMiles);

        return $signo . $simbolo . $parteEnteraFormateada . $separadorDecimal . str_pad((string)$parteDecimal, 2, '0');
    }

    /**
     * Formatea un número con separadores de miles
     */
    public static function numero(int|float $numero, string $separadorMiles = ',', string $separadorDecimal = '.'): string
    {
        $partes = explode('.', (string) $numero);
        $parteEntera = number_format((int) $partes[0], 0, '', $separadorMiles);
        return isset($partes[1]) ? $parteEntera . $separadorDecimal . $partes[1] : $parteEntera;
    }

    /**
     * Trunca un número
     */
    public static function truncarNumero(float $numero, int $decimales = 0): float
    {
        $multiplo = pow(10, $decimales);
        return floor($numero * $multiplo) / $multiplo;
    }

    /**
     * Redondea un número
     */
    public static function redondear(float $numero, int $decimales = 0, string $modo = 'normal'): float
    {
        $multiplo = pow(10, $decimales);

        switch ($modo) {
            case 'arriba':
                return ceil($numero * $multiplo) / $multiplo;
            case 'abajo':
                return floor($numero * $multiplo) / $multiplo;
            default:
                return round($numero, $decimales);
        }
    }

    /**
     * Convierte bytes a formato legible
     */
    public static function bytesLegibles(int $bytes): string
    {
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $potencia = floor(($bytes ? log($bytes) : 0) / log(1024));
        $potencia = min($potencia, count($unidades) - 1);
        $bytes /= pow(1024, $potencia);

        return round($bytes, 2) . ' ' . $unidades[$potencia];
    }

    /**
     * Pausa la ejecución por segundos
     */
    public static function dormir(int $segundos): void
    {
        sleep($segundos);
    }

    /**
     * Pausa la ejecución por milisegundos
     */
    public static function dormirMilisegundos(int $milisegundos): void
    {
        usleep($milisegundos * 1000);
    }

    /**
     * Ejecuta una función solo una vez
     */
    private static array $ejecutado = [];

    public static function unaVez(string $clave, callable $funcion): mixed
    {
        if (isset(self::$ejecutado[$clave])) {
            return self::$ejecutado[$clave];
        }

        self::$ejecutado[$clave] = $funcion();
        return self::$ejecutado[$clave];
    }

    /**
     * Resetea el tracker de unaVez
     */
    public static function resetUnaVez(string $clave = ''): void
    {
        if ($clave === '') {
            self::$ejecutado = [];
        } else {
            unset(self::$ejecutado[$clave]);
        }
    }

    /**
     * Dumps and dies - para debugging
     */
    public static function dd(mixed ...$valores): void
    {
        echo '<pre class="debug-volcado">';
        foreach ($valores as $valor) {
            var_dump($valor);
        }
        echo '</pre>';
        die;
    }

    /**
     * Dumps sin morir
     */
    public static function dump(mixed ...$valores): void
    {
        echo '<pre class="debug-volcado">';
        foreach ($valores as $valor) {
            var_dump($valor);
        }
        echo '</pre>';
    }
}

/**
 * Alias corto para AyudanteGeneral
 */
class General extends AyudanteGeneral
{
}
