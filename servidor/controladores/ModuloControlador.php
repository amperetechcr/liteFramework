<?php

declare(strict_types=1);

namespace LiteFramework\Controladores;

use LiteFramework\Middleware\AutenticacionInterceptor;

require_once __DIR__ . '/ControladorBase.php';

/**
 * ModuloControlador — Controlador genérico para cualquier módulo del panel.
 *
 * Reemplaza a: InicioControlador, OperadoresControlador,
 *              AuditoriaControlador, ConfiguracionControlador.
 *
 * Uso desde rutas/web.php:
 *   $enrutador->get('/inicio', function() {
 *       (new ModuloControlador())->indice('inicio');
 *   })->interceptor(AutenticacionInterceptor::class)->nombre('inicio');
 */
class ModuloControlador extends ControladorBase
{
    /**
     * Carga el módulo indicado verificando autenticación.
     *
     * @param string $modulo Nombre del archivo PHP en src/modulos/ (sin extensión)
     */
    public function indice(string $modulo): void
    {
        $this->verificarAutenticacion();
        $archivo = DIRECTORIO_RAIZ . '/src/modulos/' . $modulo . '/' . $modulo . '.php';

        if (!file_exists($archivo)) {
            http_response_code(404);
            require DIRECTORIO_RAIZ . '/src/error.php';
            return;
        }

        require $archivo;
    }
}
