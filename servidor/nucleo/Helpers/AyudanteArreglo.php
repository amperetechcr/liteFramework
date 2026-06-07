<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

use InvalidArgumentException;

/**
 * liteFramework - Ayudante de Arreglo
 *
 * Utilidades para el manejo de arreglos y colecciones.
 *
 * Uso:
 *   Arreglo::primero($items)
 *   Arreglo::pluck($usuarios, 'nombre')
 */

class AyudanteArreglo extends Helper
{
    /**
     * Obtiene el primer elemento de un arreglo
     */
    public static function primero(array $arreglo): mixed
    {
        if (empty($arreglo)) {
            return null;
        }
        return reset($arreglo);
    }

    /**
     * Obtiene el último elemento de un arreglo
     */
    public static function ultimo(array $arreglo): mixed
    {
        if (empty($arreglo)) {
            return null;
        }
        return end($arreglo);
    }

    /**
     * Obtiene un valor de un arreglo con valor por defecto
     */
    public static function obtener(array $arreglo, int|string $clave, mixed $defecto = null): mixed
    {
        return $arreglo[$clave] ?? $defecto;
    }

    /**
     * Toma los primeros N elementos
     */
    public static function tomar(array $arreglo, int $limite): array
    {
        if ($limite <= 0) {
            return [];
        }
        return array_slice($arreglo, 0, $limite);
    }

    /**
     * Ignora los primeros N elementos
     */
    public static function ignorar(array $arreglo, int $limite): array
    {
        if ($limite <= 0) {
            return $arreglo;
        }
        return array_slice($arreglo, $limite);
    }

    /**
     * Extrae una columna específica de un arreglo de arreglos
     */
    public static function pluck(array $arreglo, string $clave, ?string $indice = null): array
    {
        $resultado = [];

        foreach ($arreglo as $elemento) {
            if (is_array($elemento)) {
                $valor = $elemento[$clave] ?? null;
            } elseif (is_object($elemento)) {
                $valor = $elemento->$clave ?? null;
            } else {
                continue;
            }

            if ($indice !== null) {
                if (is_array($elemento)) {
                    $resultado[$elemento[$indice]] = $valor;
                } elseif (is_object($elemento)) {
                    $resultado[$elemento->$indice] = $valor;
                }
            } else {
                $resultado[] = $valor;
            }
        }

        return $resultado;
    }

    /**
     * Agrupa elementos por una clave
     */
    public static function agrupar(array $arreglo, string $clave): array
    {
        $resultado = [];

        foreach ($arreglo as $elemento) {
            $valorClave = null;

            if (is_array($elemento)) {
                $valorClave = $elemento[$clave] ?? null;
            } elseif (is_object($elemento)) {
                $valorClave = $elemento->$clave ?? null;
            }

            if ($valorClave !== null) {
                $resultado[$valorClave][] = $elemento;
            }
        }

        return $resultado;
    }

    /**
     * Filtra un arreglo usando una función callback
     */
    public static function filtrar(array $arreglo, callable $callback): array
    {
        return array_values(array_filter($arreglo, $callback));
    }

    /**
     * Ordena un arreglo por una clave
     */
    public static function ordenar(array $arreglo, string $clave, string $direccion = 'ASC'): array
    {
        usort($arreglo, function ($a, $b) use ($clave, $direccion) {
            $valorA = is_array($a) ? ($a[$clave] ?? null) : ($a->$clave ?? null);
            $valorB = is_array($b) ? ($b[$clave] ?? null) : ($b->$clave ?? null);

            if ($valorA === $valorB) {
                return 0;
            }

            $comparacion = $valorA <=> $valorB;
            return $direccion === 'DESC' ? -$comparacion : $comparacion;
        });

        return $arreglo;
    }

    /**
     * Aplana un arreglo multidimensional
     */
    public static function aplanar(array $arreglo, int $profundidad = -1): array
    {
        $resultado = [];

        foreach ($arreglo as $elemento) {
            if (is_array($elemento)) {
                if ($profundidad === 0) {
                    $resultado[] = $elemento;
                } else {
                    $subArreglo = self::aplanar($elemento, $profundidad - 1);
                    $resultado = array_merge($resultado, $subArreglo);
                }
            } else {
                $resultado[] = $elemento;
            }
        }

        return $resultado;
    }

    /**
     * Obtiene valores únicos
     */
    public static function unico(array $arreglo): array
    {
        return array_values(array_unique($arreglo));
    }

    /**
     * Verifica si un arreglo contiene un valor
     */
    public static function contiene(array $arreglo, mixed $valor, bool $estricto = true): bool
    {
        return in_array($valor, $arreglo, $estricto);
    }

    /**
     * Busca el índice de un valor
     */
    public static function indiceDe(array $arreglo, mixed $valor, bool $estricto = true): int|string|null
    {
        $indice = array_search($valor, $arreglo, $estricto);
        return $indice === false ? null : $indice;
    }

    /**
     * Divide un arreglo en chunks
     */
    public static function chunks(array $arreglo, int $tamano): array
    {
        if ($tamano <= 0) {
            return [$arreglo];
        }
        return array_chunk($arreglo, $tamano);
    }

    /**
     * Combina dos arreglos (keys + values)
     */
    public static function combinar(array $claves, array $valores): array
    {
        if (count($claves) !== count($valores)) {
            throw new InvalidArgumentException('Los arreglos deben tener la misma longitud');
        }
        return array_combine($claves, $valores);
    }

    /**
     * Invierte un arreglo
     */
    public static function invertir(array $arreglo): array
    {
        return array_reverse($arreglo, true);
    }

    /**
     * Obtiene las claves de un arreglo
     */
    public static function claves(array $arreglo): array
    {
        return array_keys($arreglo);
    }

    /**
     * Obtiene los valores de un arreglo
     */
    public static function valores(array $arreglo): array
    {
        return array_values($arreglo);
    }

    /**
     * Cuenta occurrences de valores
     */
    public static function contarPor(array $arreglo, callable|string $callback): array
    {
        $resultado = [];

        foreach ($arreglo as $elemento) {
            $clave = is_callable($callback) ? $callback($elemento) : $elemento[$callback];
            $resultado[$clave] = ($resultado[$clave] ?? 0) + 1;
        }

        return $resultado;
    }

    /**
     * Reduce un arreglo a un solo valor
     */
    public static function reducir(array $arreglo, callable $callback, mixed $inicial = null): mixed
    {
        return array_reduce($arreglo, $callback, $inicial);
    }

    /**
     * Aplica una función a cada elemento
     */
    public static function cada(array $arreglo, callable $callback): array
    {
        foreach ($arreglo as $clave => $valor) {
            $arreglo[$clave] = $callback($valor, $clave);
        }
        return $arreglo;
    }

    /**
     * Verifica si el arreglo está vacío
     */
    public static function estaVacio(array $arreglo): bool
    {
        return empty($arreglo);
    }

    /**
     * Cuenta elementos
     */
    public static function conteo(array $arreglo): int
    {
        return count($arreglo);
    }

    /**
     * Obtiene elementos que coinciden con patrón
     */
    public static function buscar(array $arreglo, callable $callback): array
    {
        return array_filter($arreglo, $callback);
    }

    /**
     * Suma valores de una columna
     */
    public static function sumar(array $arreglo, string $clave): int|float
    {
        return array_sum(self::pluck($arreglo, $clave));
    }

    /**
     * Calcula promedio de una columna
     */
    public static function promedio(array $arreglo, string $clave): float
    {
        $valores = self::pluck($arreglo, $clave);
        if (empty($valores)) {
            return 0;
        }
        return array_sum($valores) / count($valores);
    }
}

/**
 * Alias corto para AyudanteArreglo
 */
class Arreglo extends AyudanteArreglo
{
}
