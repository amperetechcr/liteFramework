<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

/**
 * liteFramework - Ayudante de Cadena
 *
 * Utilidades para el manejo y manipulación de texto.
 *
 * Uso:
 *   Cadena::limitar($texto, 100)
 *   Cadena::slug('Hola Mundo!')
 */

class AyudanteCadena extends Helper
{
    /**
     * Limita una cadena de texto agregando puntos suspensivos
     */
    public static function limitar(string|null $texto, int $limite, string $fin = '...'): string
    {
        if (empty($texto)) {
            return '';
        }
        if (mb_strlen($texto) <= $limite) {
            return $texto;
        }

        return mb_substr($texto, 0, $limite - mb_strlen($fin)) . $fin;
    }

    /**
     * Trunca una cadena sin cortar palabras completas
     */
    public static function truncar(string|null $texto, int $limite, string $fin = '...'): string
    {
        if (empty($texto)) {
            return '';
        }
        if (mb_strlen($texto) <= $limite) {
            return $texto;
        }

        $texto = mb_substr($texto, 0, $limite);

        $ultimoEspacio = mb_strrpos($texto, ' ');
        if ($ultimoEspacio !== false && $ultimoEspacio > $limite - 20) {
            $texto = mb_substr($texto, 0, $ultimoEspacio);
        }

        return trim($texto) . $fin;
    }

    /**
     * Convierte una cadena a slug URL friendly
     */
    public static function slug(string|null $texto): string
    {
        if (empty($texto)) {
            return '';
        }

        $texto = mb_strtolower($texto);

        $texto = preg_replace('/[^a-z0-9\s-]/u', '', $texto);

        $texto = preg_replace('/[\s-]+/', '-', $texto);

        $texto = trim($texto, '-');

        return $texto;
    }

    /**
     * Capitaliza la primera letra de una cadena
     */
    public static function capitalizar(string|null $texto): string
    {
        if (empty($texto)) {
            return '';
        }
        return mb_strtoupper(mb_substr($texto, 0, 1)) . mb_strtolower(mb_substr($texto, 1));
    }

    /**
     * Capitaliza cada palabra
     */
    public static function titulo(string|null $texto): string
    {
        if (empty($texto)) {
            return '';
        }
        return mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convierte a minúsculas
     */
    public static function minusculas(string|null $texto): string
    {
        if (empty($texto)) {
            return '';
        }
        return mb_strtolower($texto);
    }

    /**
     * Convierte a mayúsculas
     */
    public static function mayusculas(string|null $texto): string
    {
        if (empty($texto)) {
            return '';
        }
        return mb_strtoupper($texto);
    }

    /**
     * Verifica si una cadena contiene otra
     */
    public static function contiene(string|null $cadena, string $buscar, bool $sensitivo = false): bool
    {
        if (empty($cadena)) {
            return false;
        }

        if ($sensitivo) {
            return strpos($cadena, $buscar) !== false;
        }

        return mb_stripos($cadena, $buscar) !== false;
    }

    /**
     * Obtiene los primeros N caracteres
     */
    public static function iniciar(string|null $texto, int $longitud): string
    {
        if (empty($texto)) {
            return '';
        }
        return mb_substr($texto, 0, $longitud);
    }

    /**
     * Obtiene los últimos N caracteres
     */
    public static function terminar(string|null $texto, int $longitud): string
    {
        if (empty($texto)) {
            return '';
        }
        return mb_substr($texto, -$longitud);
    }

    /**
     * Genera una cadena aleatoria
     */
    public static function aleatorio(int $longitud = 16): string
    {
        $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $resultado = '';
        $max = mb_strlen($caracteres) - 1;

        for ($i = 0; $i < $longitud; $i++) {
            $resultado .= $caracteres[random_int(0, $max)];
        }

        return $resultado;
    }

    /**
     * Elimina espacios en blanco excesivos
     */
    public static function espaciar(string|null $texto): string
    {
        if (empty($texto)) {
            return '';
        }
        return preg_replace('/\s+/', ' ', trim($texto));
    }

    /**
     * Envuelve texto entre etiquetas
     */
    public static function envolver(string|null $texto, string $etiquetaApertura, ?string $etiquetaCierre = null): string
    {
        if (empty($texto)) {
            return '';
        }
        if ($etiquetaCierre === null) {
            $etiquetaCierre = str_replace('<', '</', $etiquetaApertura);
            if (strpos($etiquetaCierre, ' ') !== false) {
                $etiquetaCierre = explode(' ', $etiquetaCierre)[0] . '>';
            }
        }
        return $etiquetaApertura . $texto . $etiquetaCierre;
    }

    /**
     * Reemplaza texto entre etiquetas
     */
    public static function reemplazarEntre(string|null $texto, string $etiqueta, string $reemplazo): string
    {
        if (empty($texto)) {
            return '';
        }

        $patron = '/' . preg_quote($etiqueta, '/') . '(.+?)' . preg_quote(str_replace('<', '</', $etiqueta), '/') . '/s';
        return preg_replace($patron, $reemplazo, $texto);
    }

    /**
     * Extrae texto entre dos marcadores
     */
    public static function extraer(string|null $texto, string $inicio, string $fin): string
    {
        if (empty($texto)) {
            return '';
        }

        $posInicio = strpos($texto, $inicio);
        if ($posInicio === false) {
            return '';
        }

        $posInicio += strlen($inicio);
        $posFin = strpos($texto, $fin, $posInicio);

        if ($posFin === false) {
            return '';
        }

        return substr($texto, $posInicio, $posFin - $posInicio);
    }

    /**
     * Cuenta palabras en un texto
     */
    public static function palabras(string|null $texto): int
    {
        if (empty(trim($texto))) {
            return 0;
        }
        return count(preg_split('/\s+/', trim($texto)) ?: []);
    }

    /**
     * Cuenta caracteres (sin espacios)
     */
    public static function contarCaracteres(string|null $texto): int
    {
        if (empty($texto)) {
            return 0;
        }
        return mb_strlen(preg_replace('/\s/', '', $texto));
    }

    /**
     * Invierte una cadena
     */
    public static function invertir(string|null $texto): string
    {
        if (empty($texto)) {
            return '';
        }
        return strrev($texto);
    }

    /**
     * Genera un hash corto único
     */
    public static function hash(string|null $texto): string
    {
        if (empty($texto)) {
            return '';
        }
        return substr(hash('sha256', $texto), 0, 8);
    }

    /**
     * Quita etiquetas HTML de una cadena
     */
    public static function stripTags(string|null $texto, ?string $etiquetasPermitidas = null): string
    {
        if (empty($texto)) {
            return '';
        }

        if ($etiquetasPermitidas === null) {
            return strip_tags($texto);
        }

        return strip_tags($texto, $etiquetasPermitidas);
    }

    /**
     * Escapa caracteres especiales para HTML
     */
    public static function escapar(string|null $texto): string
    {
        if (empty($texto)) {
            return '';
        }
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Desescapa caracteres HTML
     */
    public static function desescapar(string|null $texto): string
    {
        if (empty($texto)) {
            return '';
        }
        return html_entity_decode($texto, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Verifica si una cadena es un email válido
     */
    public static function esEmail(string|null $texto): bool
    {
        if (empty($texto)) {
            return false;
        }
        return filter_var($texto, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Verifica si una cadena es una URL válida
     */
    public static function esUrl(string|null $texto): bool
    {
        if (empty($texto)) {
            return false;
        }
        return filter_var($texto, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Parcha una cadena con máscara (ej: 1234****5678)
     */
    public static function enmascarar(string|null $texto, string $patron = '*', int $mostrarInicio = 4, int $mostrarFin = 4): string
    {
        if (empty($texto)) {
            return '';
        }
        $longitud = mb_strlen($texto);

        if ($longitud <= $mostrarInicio + $mostrarFin) {
            return str_repeat($patron, $longitud);
        }

        $inicio = mb_substr($texto, 0, $mostrarInicio);
        $fin = mb_substr($texto, -$mostrarFin);
        $medio = str_repeat($patron, max(0, $longitud - $mostrarInicio - $mostrarFin));

        return $inicio . $medio . $fin;
    }

    /**
     * Normaliza texto (quitar acentos)
     */
    public static function normalizar(string|null $texto): string
    {
        if (empty($texto)) {
            return '';
        }

        $acentos = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U'
        ];

        return strtr($texto, $acentos);
    }
}

/**
 * Alias corto para AyudanteCadena
 */
class Cadena extends AyudanteCadena
{
}
