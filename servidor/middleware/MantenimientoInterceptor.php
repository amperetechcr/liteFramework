<?php

declare(strict_types=1);

namespace LiteFramework\Middleware;

use LiteFramework\Config\ConfiguracionSistema;

class MantenimientoInterceptor
{
    public function manejar(array $params, callable $siguiente): mixed
    {
        $mantenimiento = ConfiguracionSistema::obtener('MODO_MANTENIMIENTO', false);

        if ($mantenimiento) {
            $esAdmin = !empty($_SESSION['operador_es_admin']);

            if (!$esAdmin) {
                http_response_code(503);
                require DIRECTORIO_RAIZ . '/src/vistas/mantenimiento.php';
                exit();
            }
        }

        return $siguiente($params);
    }
}
