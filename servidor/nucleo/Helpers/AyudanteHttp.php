<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo\Helpers;

use LiteFramework\Nucleo\Excepciones\ErrorHttp;
use LiteFramework\Nucleo\Excepciones\ErrorRed;

class AyudanteHttp extends Helper
{
    private const MAPA_CODIGOS = [
        200 => 'OK', 201 => 'Creado', 204 => 'Sin contenido',
        301 => 'Movido permanentemente', 302 => 'Encontrado', 304 => 'No modificado',
        400 => 'Solicitud incorrecta', 401 => 'No autorizado', 403 => 'Prohibido',
        404 => 'No encontrado', 405 => 'Metodo no permitido', 409 => 'Conflicto',
        422 => 'Entidad no procesable', 429 => 'Demasiadas solicitudes',
        500 => 'Error interno del servidor', 502 => 'Puerta de enlace incorrecta',
        503 => 'Servicio no disponible', 504 => 'Tiempo de espera agotado',
    ];

    public static function obtener(string $url, array $cabeceras = [], int $timeout = 15): array
    {
        return self::enviar('GET', $url, ['cabeceras' => $cabeceras, 'timeout' => $timeout]);
    }

    public static function post(string $url, array|string $datos, array $cabeceras = [], int $timeout = 15): array
    {
        return self::enviar('POST', $url, [
            'cuerpo' => $datos,
            'cabeceras' => $cabeceras,
            'timeout' => $timeout,
        ]);
    }

    public static function postJson(string $url, array $datos, array $cabeceras = [], int $timeout = 15): array
    {
        $cabeceras = array_merge($cabeceras, ['Content-Type: application/json']);
        return self::enviar('POST', $url, [
            'cuerpo' => json_encode($datos, JSON_UNESCAPED_UNICODE),
            'cabeceras' => $cabeceras,
            'timeout' => $timeout,
        ]);
    }

    public static function enviar(string $metodo, string $url, array $opciones = []): array
    {
        $inicio = microtime(true);

        $ch = curl_init($url);
        if ($ch === false) {
            throw new ErrorRed('No se pudo inicializar curl');
        }

        $cabeceras = $opciones['cabeceras'] ?? [];
        $timeout = $opciones['timeout'] ?? 15;
        $cuerpo = $opciones['cuerpo'] ?? null;

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min($timeout, 10),
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => $opciones['seguirRedirecciones'] ?? true,
            CURLOPT_MAXREDIRS => $opciones['maxRedirecciones'] ?? 5,
        ];

        if (isset($opciones['userAgent'])) {
            $opts[CURLOPT_USERAGENT] = $opciones['userAgent'];
        } else {
            $opts[CURLOPT_USERAGENT] = 'liteFramework-HttpCliente/1.0';
        }

        $metodo = strtoupper($metodo);
        $opts[CURLOPT_CUSTOMREQUEST] = $metodo;

        if ($metodo === 'HEAD') {
            $opts[CURLOPT_NOBODY] = true;
        }

        if ($cuerpo !== null && in_array($metodo, ['POST', 'PUT', 'PATCH'], true)) {
            if (is_array($cuerpo)) {
                $opts[CURLOPT_POSTFIELDS] = http_build_query($cuerpo);
            } else {
                $opts[CURLOPT_POSTFIELDS] = $cuerpo;
            }
        }

        $opts[CURLOPT_HTTPHEADER] = $cabeceras;

        curl_setopt_array($ch, $opts);

        $respuesta = curl_exec($ch);
        $tiempo = round((microtime(true) - $inicio) * 1000, 2);

        if ($respuesta === false) {
            $error = curl_error($ch);
            $codigoCurl = curl_errno($ch);
            curl_close($ch);
            if ($codigoCurl === CURLE_OPERATION_TIMEDOUT) {
                return [
                    'exito' => false,
                    'codigo' => 0,
                    'error' => 'Tiempo de espera agotado',
                    'errorDetalle' => $error,
                    'errorCodigo' => $codigoCurl,
                    'tiempo' => $tiempo,
                ];
            }
            return [
                'exito' => false,
                'codigo' => 0,
                'error' => 'Error de red: ' . $error,
                'errorDetalle' => $error,
                'errorCodigo' => $codigoCurl,
                'tiempo' => $tiempo,
            ];
        }

        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $respuestaCruda = $respuesta;
        \assert(is_string($respuestaCruda));
        $cabecerasCrudas = substr($respuestaCruda, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $cuerpoCrudo = substr($respuestaCruda, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        curl_close($ch);

        $cabecerasParseadas = self::parsearCabeceras($cabecerasCrudas);
        $contenido = self::decodificarCuerpo($cuerpoCrudo);

        $resultado = [
            'exito' => $codigoHttp >= 200 && $codigoHttp < 300,
            'codigo' => $codigoHttp,
            'codigoTexto' => self::codigoComoTexto($codigoHttp),
            'cuerpo' => $contenido,
            'cuerpoCrudo' => $cuerpoCrudo,
            'cabeceras' => $cabecerasParseadas,
            'tiempo' => $tiempo,
        ];

        if ($codigoHttp >= 400) {
            $resultado['error'] = self::codigoComoTexto($codigoHttp);
        }

        return $resultado;
    }

    public static function paralelo(array $peticiones, int $timeout = 15): array
    {
        if (!function_exists('curl_multi_init')) {
            throw new ErrorRed('La extensión curl_multi no está disponible');
        }

        $multi = curl_multi_init();
        $recursos = [];
        $resultados = [];
        $inicios = [];

        foreach ($peticiones as $clave => $pet) {
            $url = $pet['url'] ?? '';
            $metodo = strtoupper($pet['metodo'] ?? 'GET');
            $cabeceras = $pet['cabeceras'] ?? [];
            $cuerpo = $pet['cuerpo'] ?? null;
            $timeoutPet = $pet['timeout'] ?? $timeout;

            $ch = curl_init($url);
            if ($ch === false) {
                continue;
            }

            $opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_TIMEOUT => $timeoutPet,
                CURLOPT_CONNECTTIMEOUT => min($timeoutPet, 10),
                CURLOPT_HEADER => true,
                CURLOPT_FOLLOWLOCATION => $pet['seguirRedirecciones'] ?? true,
                CURLOPT_MAXREDIRS => $pet['maxRedirecciones'] ?? 5,
                CURLOPT_CUSTOMREQUEST => $metodo,
                CURLOPT_USERAGENT => 'liteFramework-HttpCliente/1.0',
                CURLOPT_HTTPHEADER => $cabeceras,
            ];

            if ($cuerpo !== null && in_array($metodo, ['POST', 'PUT', 'PATCH'], true)) {
                $opts[CURLOPT_POSTFIELDS] = is_array($cuerpo) ? http_build_query($cuerpo) : $cuerpo;
            }

            curl_setopt_array($ch, $opts);
            curl_multi_add_handle($multi, $ch);
            $recursos[$clave] = $ch;
            $inicios[$clave] = microtime(true);
        }

        $activo = null;
        do {
            $estado = curl_multi_exec($multi, $activo);
        } while ($estado === CURLM_CALL_MULTI_PERFORM);

        while ($activo > 0 && $estado === CURLM_OK) {
            if (curl_multi_select($multi, 0.5) === -1) {
                usleep(100000);
            }
            do {
                $estado = curl_multi_exec($multi, $activo);
            } while ($estado === CURLM_CALL_MULTI_PERFORM);
        }

        foreach ($recursos as $clave => $ch) {
            $tiempo = round((microtime(true) - $inicios[$clave]) * 1000, 2);

            $respuesta = curl_multi_getcontent($ch);
            $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($respuesta === null) {
                $resultados[$clave] = [
                    'exito' => false,
                    'codigo' => 0,
                    'error' => 'Error de red: ' . curl_error($ch),
                    'errorCodigo' => curl_errno($ch),
                    'tiempo' => $tiempo,
                ];
            } else {
                $cabecerasCrudas = substr($respuesta, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
                $cuerpoCrudo = substr($respuesta, curl_getinfo($ch, CURLINFO_HEADER_SIZE));

                $resultados[$clave] = [
                    'exito' => $codigoHttp >= 200 && $codigoHttp < 300,
                    'codigo' => $codigoHttp,
                    'codigoTexto' => self::codigoComoTexto($codigoHttp),
                    'cuerpo' => self::decodificarCuerpo($cuerpoCrudo),
                    'cuerpoCrudo' => $cuerpoCrudo,
                    'cabeceras' => self::parsearCabeceras($cabecerasCrudas),
                    'tiempo' => $tiempo,
                ];

                if ($codigoHttp >= 400) {
                    $resultados[$clave]['error'] = self::codigoComoTexto($codigoHttp);
                }
            }

            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi);

        return $resultados;
    }

    public static function codigoComoTexto(int $codigo): string
    {
        return self::MAPA_CODIGOS[$codigo] ?? 'Codigo desconocido';
    }

    public static function verificarDisponible(): bool
    {
        return function_exists('curl_init') && function_exists('curl_multi_init');
    }

    private static function parsearCabeceras(string $crudas): array
    {
        $lineas = explode("\r\n", trim($crudas));
        $cabeceras = [];

        foreach ($lineas as $linea) {
            $partes = explode(':', $linea, 2);
            if (count($partes) === 2) {
                $clave = trim($partes[0]);
                $valor = trim($partes[1]);
                $claveLower = strtolower($clave);

                if (!isset($cabeceras[$claveLower])) {
                    $cabeceras[$claveLower] = $valor;
                } elseif (is_string($cabeceras[$claveLower])) {
                    $cabeceras[$claveLower] = [$cabeceras[$claveLower], $valor];
                } else {
                    $cabeceras[$claveLower][] = $valor;
                }
            }
        }

        return $cabeceras;
    }

    private static function decodificarCuerpo(string $cuerpo): mixed
    {
        if (empty($cuerpo)) {
            return null;
        }

        $decodificado = json_decode($cuerpo, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decodificado;
        }

        return $cuerpo;
    }
}
