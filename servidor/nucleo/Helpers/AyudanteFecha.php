<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

use DateTime;

class AyudanteFecha extends Helper
{
    public const FECHA_COMPLETA = 'd/m/Y H:i:s';
    public const FECHA_SOLA = 'd/m/Y';
    public const HORA_SOLA = 'H:i:s';
    public const FECHA_ISO = 'Y-m-d H:i:s';
    public const FECHA_CORTA = 'd/m/y';
    public const MES_NOMBRE = 'd de F de Y';
    public const HORA_12 = 'h:i A';

    public static function hoy(string $formato = 'Y-m-d'): string
    {
        return date($formato);
    }

    public static function ahora(string $formato = 'Y-m-d H:i:s'): string
    {
        return date($formato);
    }

    public static function formatear(string|DateTime|null $fecha, string $formato = self::FECHA_COMPLETA, ?int $timezoneOffset = null): string
    {
        if (empty($fecha)) {
            return '';
        }
        if ($fecha instanceof DateTime) {
            return $fecha->format($formato);
        }
        $timestamp = strtotime($fecha);
        if ($timestamp === false) {
            return '';
        }
        if ($timezoneOffset !== null) {
            $tz = new DateTimeZone(sprintf('%+03d:%02d', -intdiv($timezoneOffset, 60), abs($timezoneOffset % 60)));
            return (new DateTime('@' . $timestamp))->setTimezone($tz)->format($formato);
        }
        return date($formato, $timestamp);
    }

    public static function diferencia(string|DateTime $fecha1, string|DateTime $fecha2, string $unidad = 'dias'): int
    {
        $ts1 = $fecha1 instanceof DateTime ? $fecha1->getTimestamp() : strtotime($fecha1);
        $ts2 = $fecha2 instanceof DateTime ? $fecha2->getTimestamp() : strtotime($fecha2);
        $diff = abs($ts1 - $ts2);
        return match ($unidad) {
            'segundos' => $diff,
            'minutos' => (int)($diff / 60),
            'horas' => (int)($diff / 3600),
            'semanas' => (int)($diff / 604800),
            'meses' => (int)($diff / 2592000),
            'anos' => (int)($diff / 31536000),
            default => (int)($diff / 86400),
        };
    }

    public static function relativo(string|DateTime|null $fecha): string
    {
        if (empty($fecha)) {
            return '';
        }
        $timestamp = $fecha instanceof DateTime ? $fecha->getTimestamp() : strtotime($fecha);
        if ($timestamp === false) {
            return '';
        }
        $diferencia = time() - $timestamp;
        $negativo = $diferencia < 0;
        $diferencia = abs($diferencia);
        if ($diferencia < 60) {
            return $negativo ? 'en un momento' : 'hace un momento';
        }
        if ($diferencia < 3600) {
            $m = (int)($diferencia / 60);
            return ($negativo ? 'en ' : 'hace ') . $m . ' minuto' . ($m > 1 ? 's' : '');
        }
        if ($diferencia < 86400) {
            $h = (int)($diferencia / 3600);
            return ($negativo ? 'en ' : 'hace ') . $h . ' hora' . ($h > 1 ? 's' : '');
        }
        if ($diferencia < 604800) {
            $d = (int)($diferencia / 86400);
            return ($negativo ? 'en ' : 'hace ') . $d . ' dia' . ($d > 1 ? 's' : '');
        }
        if ($diferencia < 2592000) {
            $s = (int)($diferencia / 604800);
            return ($negativo ? 'en ' : 'hace ') . $s . ' semana' . ($s > 1 ? 's' : '');
        }
        if ($diferencia < 31536000) {
            $m = (int)($diferencia / 2592000);
            return ($negativo ? 'en ' : 'hace ') . $m . ' mes' . ($m > 1 ? 'es' : '');
        }
        $a = (int)($diferencia / 31536000);
        return ($negativo ? 'en ' : 'hace ') . $a . ' ano' . ($a > 1 ? 's' : '');
    }

    public static function edad(string|DateTime $fechaNacimiento): int
    {
        $nacimiento = $fechaNacimiento instanceof DateTime ? $fechaNacimiento : new DateTime($fechaNacimiento);
        return (int)(new DateTime())->diff($nacimiento)->y;
    }

    public static function esHoy(string|DateTime $fecha): bool
    {
        $fechaObj = $fecha instanceof DateTime ? $fecha : new DateTime($fecha);
        return $fechaObj->format('Y-m-d') === (new DateTime())->format('Y-m-d');
    }

    public static function esPasado(string|DateTime $fecha): bool
    {
        $fechaObj = $fecha instanceof DateTime ? $fecha : new DateTime($fecha);
        $hoy = new DateTime();
        $hoy->setTime(0, 0, 0);
        return $fechaObj < $hoy;
    }

    public static function esFuturo(string|DateTime $fecha): bool
    {
        $fechaObj = $fecha instanceof DateTime ? $fecha : new DateTime($fecha);
        $hoy = new DateTime();
        $hoy->setTime(0, 0, 0);
        return $fechaObj > $hoy;
    }

    public static function sumarDias(string|DateTime $fecha, int $dias): DateTime
    {
        $fechaObj = $fecha instanceof DateTime ? clone $fecha : new DateTime($fecha);
        return $fechaObj->modify("+$dias days");
    }

    public static function restarDias(string|DateTime $fecha, int $dias): DateTime
    {
        $fechaObj = $fecha instanceof DateTime ? clone $fecha : new DateTime($fecha);
        return $fechaObj->modify("-$dias days");
    }

    public static function primerDiaMes(string|DateTime|null $fecha = null): DateTime
    {
        $fechaObj = $fecha instanceof DateTime ? clone $fecha : new DateTime($fecha ?? 'now');
        return $fechaObj->modify('first day of this month')->setTime(0, 0, 0);
    }

    public static function ultimoDiaMes(string|DateTime|null $fecha = null): DateTime
    {
        $fechaObj = $fecha instanceof DateTime ? clone $fecha : new DateTime($fecha ?? 'now');
        return $fechaObj->modify('last day of this month')->setTime(23, 59, 59);
    }

    public static function comparar(string|DateTime $fecha1, string|DateTime $fecha2): int
    {
        $ts1 = $fecha1 instanceof DateTime ? $fecha1->getTimestamp() : strtotime($fecha1);
        $ts2 = $fecha2 instanceof DateTime ? $fecha2->getTimestamp() : strtotime($fecha2);
        return $ts1 <=> $ts2;
    }

    public static function estaEntre(string|DateTime $fecha, string|DateTime $inicio, string|DateTime $fin): bool
    {
        $fechaTs = $fecha instanceof DateTime ? $fecha->getTimestamp() : strtotime($fecha);
        $inicioTs = $inicio instanceof DateTime ? $inicio->getTimestamp() : strtotime($inicio);
        $finTs = $fin instanceof DateTime ? $fin->getTimestamp() : strtotime($fin);
        return $fechaTs >= $inicioTs && $fechaTs <= $finTs;
    }

    public static function aTimestamp(string|DateTime $fecha): int
    {
        return $fecha instanceof DateTime ? $fecha->getTimestamp() : (int)strtotime($fecha);
    }

    public static function crear(string|DateTime|null $fecha = null): DateTime
    {
        if ($fecha === null) {
            return new DateTime();
        }
        return $fecha instanceof DateTime ? clone $fecha : new DateTime($fecha);
    }

    public static function aMySQL(string|DateTime $fecha): string
    {
        return self::formatear($fecha, 'Y-m-d H:i:s');
    }
}

class Fecha extends AyudanteFecha
{
}
