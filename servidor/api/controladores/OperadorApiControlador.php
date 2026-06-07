<?php

declare(strict_types=1);

namespace LiteFramework\Api\Controladores;

use LiteFramework\Modelos\Operador;
use LiteFramework\Seguridad\SeguridadServidor;
use LiteFramework\Seguridad\PoliticaContrasena;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Seguridad\LimitadorPeticiones;
use LiteFramework\Seguridad\SseGestor;

class OperadorApiControlador
{
    public function registrar(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        $nombreCompleto = SeguridadServidor::sanitizarTextoPlano($payload['nombre_completo'] ?? '');
        $correoElectronico = SeguridadServidor::procesarCorreoElectronico($payload['correo_electronico'] ?? '');
        $clavePlana = $payload['clave_registro'] ?? '';
        $idRolAsignado = (int)($payload['id_rol'] ?? 1);

        if (empty($nombreCompleto) || !$correoElectronico || empty($clavePlana)) {
            $respuesta['mensaje_error'] = 'Datos de registro incompletos o inválidos.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [400, $respuesta];
        }

        $validacionClave = PoliticaContrasena::validar($clavePlana);
        if ($validacionClave !== true) {
            $respuesta['mensaje_error'] = $validacionClave;
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [400, $respuesta];
        }

        $limitador = new LimitadorPeticiones();
        $claveRate = 'registro:' . $_SERVER['REMOTE_ADDR'] ?? '';

        if ($limitador->haExcedido($claveRate, 3, 900)) {
            RegistroAuditoria::seguridad('Registro bloqueado por tasa', [
                'correo_intentado' => $correoElectronico,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
            $respuesta['mensaje_error'] = 'Demasiados intentos. Espere unos minutos e intente de nuevo.';
            $respuesta['codigo_error'] = 'error_interno';
            return [429, $respuesta];
        }

        if (Operador::donde('correo_electronico', $correoElectronico)->primero()) {
            $respuesta['mensaje_error'] = 'El correo ya está registrado.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [409, $respuesta];
        }

        $claveHash = SeguridadServidor::encriptarClaveOperador($clavePlana);

        $nuevoOperador = Operador::crear([
            'id_rol' => $idRolAsignado,
            'nombre_completo' => $nombreCompleto,
            'correo_electronico' => $correoElectronico,
            'clave_acceso' => $claveHash,
            'estado_cuenta' => 1,
        ]);

        $limitador->reiniciar($claveRate);

        RegistroAuditoria::auditoria('Operadores', 'Registro de operador', [
            'id_nuevo_operador' => $nuevoOperador->id_operador,
            'nombre' => $nombreCompleto,
            'correo' => $correoElectronico,
            'rol_asignado' => $idRolAsignado,
            'registrado_por' => $_SESSION['operador_id'] ?? null,
        ]);

        SseGestor::emitirATodos('operador.registrado', [
            'id' => $nuevoOperador->id_operador,
            'nombre' => $nombreCompleto,
        ]);

        $respuesta['estado_operacion'] = true;
        $respuesta['mensaje_error'] = null;
        $respuesta['codigo_error'] = null;
        $respuesta['datos'] = ['mensaje' => 'registro_exitoso'];
        return [200, $respuesta];
    }

    public function actualizarPerfil(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        $idEntidad = (int)($payload['id_entidad'] ?? 0);
        $idSesion = (int)($_SESSION['operador_id'] ?? 0);

        if ($idEntidad !== $idSesion || $idEntidad === 0) {
            RegistroAuditoria::seguridad('Intento de actualizar perfil de otro operador', [
                'id_intentado' => $idEntidad,
                'id_sesion' => $idSesion,
            ]);
            $respuesta['mensaje_error'] = 'No puedes modificar el perfil de otro operador.';
            $respuesta['codigo_error'] = 'acceso_denegado';
            return [403, $respuesta];
        }

        $nombreCompleto = SeguridadServidor::sanitizarTextoPlano($payload['nombre_completo'] ?? '');
        $correoElectronico = SeguridadServidor::procesarCorreoElectronico($payload['correo_electronico'] ?? '');
        $clavePlana = $payload['clave_acceso'] ?? '';
        $claveConfirmar = $payload['clave_acceso_confirmar'] ?? '';

        if (empty($nombreCompleto) || !$correoElectronico) {
            $respuesta['mensaje_error'] = 'El nombre y el correo electrónico son obligatorios.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [400, $respuesta];
        }

        $existeCorreo = Operador::donde('correo_electronico', $correoElectronico)
            ->donde('id_operador', '!=', $idEntidad)
            ->primero();
        if ($existeCorreo) {
            $respuesta['mensaje_error'] = 'El correo electrónico ya está en uso por otro operador.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [409, $respuesta];
        }

        $operador = Operador::buscar($idEntidad);
        if (!$operador) {
            $respuesta['mensaje_error'] = 'Operador no encontrado.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [404, $respuesta];
        }

        $datosActualizar = [
            'nombre_completo' => $nombreCompleto,
            'correo_electronico' => $correoElectronico,
        ];

        if (!empty($clavePlana) || !empty($claveConfirmar)) {
            if ($clavePlana !== $claveConfirmar) {
                $respuesta['mensaje_error'] = 'Las contraseñas no coinciden.';
                $respuesta['codigo_error'] = 'datos_invalidos';
                return [400, $respuesta];
            }

            $validacionClave = PoliticaContrasena::validar($clavePlana);
            if ($validacionClave !== true) {
                $respuesta['mensaje_error'] = $validacionClave;
                $respuesta['codigo_error'] = 'datos_invalidos';
                return [400, $respuesta];
            }

            $datosActualizar['clave_acceso'] = SeguridadServidor::encriptarClaveOperador($clavePlana);
            RegistroAuditoria::seguridad('Cambio de contrasena', [
                'id_operador' => $idEntidad,
            ]);
        }

        $operador->llenar($datosActualizar);
        $operador->guardar();

        $_SESSION['operador_nombre'] = $nombreCompleto;

        RegistroAuditoria::auditoria('Configuracion', 'Actualización de perfil propio', [
            'id_operador' => $idEntidad,
            'nombre' => $nombreCompleto,
            'correo' => $correoElectronico,
            'cambio_clave' => !empty($clavePlana),
        ]);

        $respuesta['estado_operacion'] = true;
        $respuesta['mensaje_error'] = null;
        $respuesta['codigo_error'] = null;
        $respuesta['datos'] = ['mensaje' => 'perfil_actualizado'];
        $respuesta['nuevo_nombre'] = $nombreCompleto;
        return [200, $respuesta];
    }

    public function suspenderOperador(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        if ((int)($_SESSION['operador_rol'] ?? 0) !== 1) {
            $respuesta['mensaje_error'] = 'Solo el administrador puede suspender operadores.';
            $respuesta['codigo_error'] = 'acceso_denegado';
            return [403, $respuesta];
        }

        $idOperador = (int)($payload['id_entidad'] ?? 0);
        if ($idOperador === 0) {
            $respuesta['mensaje_error'] = 'Identificador de operador inválido.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [400, $respuesta];
        }

        $operador = Operador::buscar($idOperador);
        if (!$operador) {
            $respuesta['mensaje_error'] = 'Operador no encontrado.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [404, $respuesta];
        }

        $operador->suspender();
        RegistroAuditoria::auditoria('Operadores', 'Operador suspendido', [
            'id_operador' => $idOperador,
            'suspendido_por' => $_SESSION['operador_id'] ?? null,
        ]);
        SseGestor::emitirATodos('operador.actualizado', ['id' => $idOperador, 'estado' => 0]);

        $respuesta['estado_operacion'] = true;
        return [200, $respuesta];
    }

    public function activarOperador(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        if ((int)($_SESSION['operador_rol'] ?? 0) !== 1) {
            $respuesta['mensaje_error'] = 'Solo el administrador puede activar operadores.';
            $respuesta['codigo_error'] = 'acceso_denegado';
            return [403, $respuesta];
        }

        $idOperador = (int)($payload['id_entidad'] ?? 0);
        if ($idOperador === 0) {
            $respuesta['mensaje_error'] = 'Identificador de operador inválido.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [400, $respuesta];
        }

        $operador = Operador::buscar($idOperador);
        if (!$operador) {
            $respuesta['mensaje_error'] = 'Operador no encontrado.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [404, $respuesta];
        }

        $operador->activar();
        RegistroAuditoria::auditoria('Operadores', 'Operador activado', [
            'id_operador' => $idOperador,
            'activado_por' => $_SESSION['operador_id'] ?? null,
        ]);
        SseGestor::emitirATodos('operador.actualizado', ['id' => $idOperador, 'estado' => 1]);

        $respuesta['estado_operacion'] = true;
        return [200, $respuesta];
    }
}
