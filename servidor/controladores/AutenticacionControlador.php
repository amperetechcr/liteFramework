<?php

declare(strict_types=1);

namespace LiteFramework\Controladores;

use LiteFramework\Seguridad\SeguridadServidor;
use LiteFramework\Config\ConexionBaseDatos;
use Exception;

class AutenticacionControlador extends ControladorBase
{
    public function mostrarInicioSesion(): void
    {
        SeguridadServidor::iniciarSesionEstricta();
        $codigoError = $_GET['error'] ?? '';
        $codigoMensaje = $_GET['mensaje'] ?? '';
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $totalUsuarios = (int)$conexion->query("SELECT COUNT(*) FROM operador")->fetchColumn();
            $totalRoles = (int)$conexion->query("SELECT COUNT(*) FROM rbac_rol")->fetchColumn();
        } catch (Exception $e) {
            $totalUsuarios = 0;
            $totalRoles = 0;
        }
        $tokenCSRF = SeguridadServidor::generarTokenAntiFalsificacion();
        require DIRECTORIO_RAIZ . '/src/vistas/inicio_sesion.php';
    }

    public function cerrarSesion(): void
    {
        SeguridadServidor::iniciarSesionEstricta();
        SeguridadServidor::destruirSesionCompletamente();
        header('Location: ' . URL_BASE . '/');
        exit();
    }
}
