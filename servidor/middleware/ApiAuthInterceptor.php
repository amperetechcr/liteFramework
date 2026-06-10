<?php

declare(strict_types=1);

namespace LiteFramework\Middleware;

use LiteFramework\Nucleo\Interceptor;
use LiteFramework\Seguridad\SeguridadServidor;

/**
 * ApiAuthInterceptor — Interceptor que protege endpoints de API.
 *
 * Establece cabeceras JSON, inicia sesión estricta y verifica
 * que el operador esté autenticado antes de pasar al siguiente
 * manejador en la cadena.
 *
 * Uso en rutas/web.php:
 *   $enrutador->get('/api/ejemplo', fn() => ...)
 *       ->interceptor(ApiAuthInterceptor::class);
 */
class ApiAuthInterceptor implements Interceptor
{
    public function manejar(array $params, callable $siguiente): mixed
    {
        header('Content-Type: application/json; charset=utf-8');
        SeguridadServidor::iniciarSesionEstricta();

        if (!isset($_SESSION['operador_id'])) {
            http_response_code(401);
            echo json_encode(['estado_operacion' => false, 'mensaje_error' => 'No autenticado', 'codigo_error' => 'sesion_invalida']);
            return null;
        }

        return $siguiente($params);
    }
}
