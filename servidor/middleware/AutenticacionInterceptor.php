<?php

declare(strict_types=1);

namespace LiteFramework\Middleware;

use LiteFramework\Nucleo\Interceptor;
use LiteFramework\Seguridad\SeguridadServidor;

class AutenticacionInterceptor implements Interceptor
{
    public function manejar(array $params, callable $siguiente): mixed
    {
        SeguridadServidor::iniciarSesionEstricta();

        if (!isset($_SESSION['operador_id'])) {
            header('Location: ' . URL_BASE . '/?error=privilegios_insuficientes');
            exit();
        }

        SeguridadServidor::validarHuellaCliente();

        return $siguiente($params);
    }
}
