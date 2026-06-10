<?php

declare(strict_types=1);

namespace LiteFramework\Servicios\Verificadores;

use LiteFramework\Servicios\ContextoError;

class VerificadorSeguridad implements VerificadorError
{
    public function tipo(): string
    {
        return 'seguridad';
    }

    public function diagnosticar(ContextoError $ctx): ?array
    {
        $codigo = $ctx->codigo;
        $msg = $ctx->mensaje;

        if ($codigo === 'token_invalido' || str_contains($msg, 'CSRF')) {
            $sesionActiva = session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION);
            return [
                'tipo' => 'csrf_expirado',
                'detalle' => 'El token de seguridad CSRF no es válido o ha expirado.',
                'sesion_activa' => $sesionActiva,
            ];
        }

        if ($codigo === 'bloqueo_temporal' || str_contains($msg, 'bloqueado') || str_contains($msg, 'Demasiados intentos')) {
            preg_match('/(\d+)\s*minuto/', $msg, $m);
            $tiempo = (int)($m[1] ?? 15);
            return [
                'tipo' => 'rate_limit',
                'detalle' => "Demasiados intentos. Espere {$tiempo} minutos.",
                'minutos_restantes' => $tiempo,
            ];
        }

        if ($codigo === 'acceso_denegado' || $codigo === 'sin_permiso' || str_contains($msg, 'permiso') || str_contains($msg, 'Permiso')) {
            preg_match("/'([^']+)'/", $msg, $m);
            $permiso = $m[1] ?? null;
            return [
                'tipo' => 'permiso_faltante',
                'detalle' => $permiso ? "No tiene el permiso '{$permiso}'." : 'No tiene permisos para realizar esta acción.',
                'permiso' => $permiso,
            ];
        }

        if ($codigo === 'no_autenticado' || $codigo === 'sesion_expirada' || str_contains($msg, 'sesi')) {
            return [
                'tipo' => 'sesion_expirada',
                'detalle' => 'Su sesión ha expirado. Debe iniciar sesión nuevamente.',
            ];
        }

        if ($codigo === 'sesion_invalida_o_secuestrada' || str_contains($msg, 'secuestro') || str_contains($msg, 'huella')) {
            return [
                'tipo' => 'posible_hijacking',
                'detalle' => 'Su sesión fue invalidada por razones de seguridad.',
            ];
        }

        if ($codigo === 'cuenta_suspendida' || str_contains($msg, 'deshabilitada') || str_contains($msg, 'suspendida')) {
            return [
                'tipo' => 'cuenta_suspendida',
                'detalle' => 'Su cuenta ha sido deshabilitada temporalmente.',
            ];
        }

        return null;
    }

    public function tieneRemedioAutomatico(): bool
    {
        return true;
    }

    public function ejecutarRemedio(array $diagnostico): array
    {
        $tipo = $diagnostico['tipo'] ?? '';

        if ($tipo === 'csrf_expirado' && !empty($diagnostico['sesion_activa'])) {
            return ['exito' => true, 'mensaje' => 'Regenerando token CSRF...', 'reintentar' => true, 'accion_frontend' => 'regenerar_token'];
        }

        if ($tipo === 'sesion_expirada' || $tipo === 'posible_hijacking') {
            return ['exito' => true, 'mensaje' => 'Redirigiendo al inicio de sesión...', 'reintentar' => false, 'accion_frontend' => 'redirigir_login'];
        }

        return ['exito' => false, 'mensaje' => 'Este problema requiere intervención manual.', 'reintentar' => false];
    }

    public function obtenerSugerencias(array $diagnostico): array
    {
        $tipo = $diagnostico['tipo'] ?? '';
        $sugs = [];

        switch ($tipo) {
            case 'csrf_expirado':
                if (!empty($diagnostico['sesion_activa'])) {
                    $sugs[] = 'El token de seguridad expiró. Recargue la página para obtener uno nuevo.';
                } else {
                    $sugs[] = 'Su sesión expiró. Inicie sesión nuevamente.';
                }
                break;
            case 'rate_limit':
                $minutos = $diagnostico['minutos_restantes'] ?? 15;
                $sugs[] = "Demasiados intentos fallidos. Espere {$minutos} minutos antes de intentar de nuevo.";
                $sugs[] = '¿Olvidó su contraseña? Use la opción "Recuperar acceso" en la página de inicio de sesión.';
                break;
            case 'permiso_faltante':
                $permiso = $diagnostico['permiso'] ?? '';
                $sugs[] = $permiso
                    ? "No tiene el permiso '{$permiso}' para realizar esta acción."
                    : 'No tiene permisos suficientes.';
                $sugs[] = 'Contacte al administrador del sistema para solicitar acceso.';
                break;
            case 'sesion_expirada':
                $sugs[] = 'Su sesión expiró por inactividad.';
                $sugs[] = 'Vuelva a iniciar sesión para continuar.';
                break;
            case 'posible_hijacking':
                $sugs[] = 'Su sesión fue invalidada por razones de seguridad.';
                $sugs[] = 'Esto puede ocurrir si alguien más intentó usar su sesión.';
                $sugs[] = 'Vuelva a iniciar sesión. Si el problema persiste, contacte al administrador.';
                break;
            case 'cuenta_suspendida':
                $sugs[] = 'Su cuenta ha sido deshabilitada temporalmente.';
                $sugs[] = 'Contacte al administrador del sistema para reactivarla.';
                break;
        }
        return $sugs;
    }
}
