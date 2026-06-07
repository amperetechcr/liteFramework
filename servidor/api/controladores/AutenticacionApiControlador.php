<?php

declare(strict_types=1);

namespace LiteFramework\Api\Controladores;

use PDO;
use LiteFramework\Seguridad\SeguridadServidor;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Config\GestorEntorno;

class AutenticacionApiControlador
{
    public function iniciarSesion(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        $correo = SeguridadServidor::procesarCorreoElectronico($payload['correo'] ?? '');
        $clave = $payload['clave'] ?? '';
        $correoOriginal = $payload['correo'] ?? '';

        if (!$correo || empty($clave)) {
            RegistroAuditoria::seguridad('Intento de inicio de sesion con datos invalidos', [
                'correo_proporcionado' => $correoOriginal,
                'motivo' => 'formato_invalido',
            ]);
            $respuesta['mensaje_error'] = 'Correo o contraseña no válidos.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [400, $respuesta];
        }

        $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();

        if (SeguridadServidor::verificarBloqueoAcceso($conexion, $correo)) {
            $minutos = (int)GestorEntorno::obtener('APP_BLOQUEO_MINUTOS', 15);
            $maxIntentos = (int)GestorEntorno::obtener('APP_MAX_INTENTOS_ACCESO', 5);
            RegistroAuditoria::seguridad('Inicio de sesion bloqueado por tasa', [
                'correo_intentado' => $correo,
                'max_intentos' => $maxIntentos,
                'ventana_minutos' => $minutos,
            ]);
            $respuesta['mensaje_error'] = "Demasiados intentos. Espere {$minutos} minutos e intente de nuevo.";
            $respuesta['codigo_error'] = 'bloqueo_temporal';
            return [429, $respuesta];
        }

        $consulta = $conexion->prepare("
            SELECT o.id_operador, o.id_rol, r.nombre_rol, o.nombre_completo, o.clave_acceso, o.estado_cuenta
            FROM operador o
            JOIN rbac_rol r ON r.id_rol = o.id_rol
            WHERE o.correo_electronico = :correo
            LIMIT 1
        ");
        $consulta->execute([':correo' => $correo]);
        $operador = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$operador || !SeguridadServidor::verificarClaveOperador($clave, $operador['clave_acceso'])) {
            SeguridadServidor::registrarIntentoAccesoFallido($conexion, $correo);
            $maxIntentos = (int)GestorEntorno::obtener('APP_MAX_INTENTOS_ACCESO', 5);
            $intentosActuales = SeguridadServidor::contarIntentosAcceso($conexion, $correo);
            RegistroAuditoria::seguridad('Inicio de sesion fallido', [
                'correo_intentado' => $correo,
                'motivo' => !$operador ? 'usuario_no_encontrado' : 'clave_incorrecta',
                'intentos_actuales' => $intentosActuales,
                'intentos_restantes' => max(0, $maxIntentos - $intentosActuales),
                'huella' => hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? '')),
            ]);
            $respuesta['mensaje_error'] = 'Credenciales incorrectas.';
            $respuesta['codigo_error'] = 'acceso_denegado';
            return [401, $respuesta];
        }

        if ((int)$operador['estado_cuenta'] !== 1) {
            RegistroAuditoria::seguridad('Inicio de sesion bloqueado por cuenta deshabilitada', [
                'id_operador' => $operador['id_operador'],
                'correo' => $correo,
            ]);
            $respuesta['mensaje_error'] = 'Cuenta deshabilitada temporalmente.';
            $respuesta['codigo_error'] = 'cuenta_suspendida';
            return [403, $respuesta];
        }

        SeguridadServidor::regenerarSesionSegura();
        SeguridadServidor::vincularHuellaCliente();

        $_SESSION['operador_id'] = $operador['id_operador'];
        $_SESSION['operador_nombre'] = $operador['nombre_completo'];
        $_SESSION['operador_rol'] = $operador['id_rol'];
        $_SESSION['operador_rol_nombre'] = $operador['nombre_rol'];
        $_SESSION['operador_es_admin'] = (int)$operador['id_rol'] === 1;
        $_SESSION['_inicio_sesion'] = time();

        SeguridadServidor::cargarPermisosEnMemoria($conexion, $operador['id_rol']);

        SeguridadServidor::limpiarIntentosAcceso($conexion, $correo);

        RegistroAuditoria::auditoria('Acceso', 'Inicio de sesion exitoso', [
            'id_operador' => $operador['id_operador'],
            'nombre' => $operador['nombre_completo'],
            'rol_id' => $operador['id_rol'],
        ]);

        $respuesta['estado_operacion'] = true;
        $respuesta['mensaje_error'] = null;
        $respuesta['codigo_error'] = null;
        $respuesta['redireccion'] = URL_BASE . '/inicio';
        $respuesta['datos'] = ['operador_nombre' => $operador['nombre_completo']];
        return [200, $respuesta];
    }

    public function cerrarSesion(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        if (!isset($_SESSION['operador_id'])) {
            $respuesta['mensaje_error'] = 'No habia sesion activa.';
            $respuesta['codigo_error'] = 'no_autenticado';
            return [200, $respuesta];
        }

        $inicioSesion = $_SESSION['_inicio_sesion'] ?? 0;
        $duracion = $inicioSesion > 0 ? time() - $inicioSesion : 0;
        RegistroAuditoria::auditoria('Acceso', 'Cierre de sesion manual', [
            'nombre' => $_SESSION['operador_nombre'] ?? '',
            'duracion_sesion_seg' => $duracion,
            'duracion_sesion_min' => round($duracion / 60, 1),
        ]);

        SeguridadServidor::destruirSesionCompletamente();

        $respuesta['estado_operacion'] = true;
        $respuesta['mensaje_error'] = null;
        $respuesta['codigo_error'] = null;
        $respuesta['redireccion'] = URL_BASE . '/?mensaje=sesion_finalizada';
        return [200, $respuesta];
    }
}
