<?php

declare(strict_types=1);

namespace LiteFramework\Controladores;

use LiteFramework\Seguridad\SeguridadServidor;

class ControladorBase
{
    protected function verificarAutenticacion(): void
    {
        SeguridadServidor::iniciarSesionEstricta();

        if (!isset($_SESSION['operador_id'])) {
            header('Location: ' . URL_BASE . '/?error=privilegios_insuficientes');
            exit();
        }
    }

    protected function requerirAutenticacion(): void
    {
        $this->verificarAutenticacion();
    }

    protected function obtenerIdOperador(): int
    {
        return (int)($_SESSION['operador_id'] ?? 0);
    }

    protected function obtenerNombreOperador(): string
    {
        return $_SESSION['operador_nombre'] ?? '';
    }

    protected function obtenerIdRol(): int
    {
        return (int)($_SESSION['operador_rol'] ?? 0);
    }

    protected function obtenerPermisos(): array
    {
        return $_SESSION['matriz_permisos'] ?? [];
    }
}
