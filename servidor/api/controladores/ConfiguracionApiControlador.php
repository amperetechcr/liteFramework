<?php

declare(strict_types=1);

namespace LiteFramework\Api\Controladores;

use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Config\ConfiguracionSistema;
use LiteFramework\Config\GeneradorIniServidor;

class ConfiguracionApiControlador
{
    public function actualizarConfiguracionArchivos(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        if (empty($_SESSION['operador_id'])) {
            $respuesta['mensaje_error'] = 'Su sesion ha expirado. Recargue la pagina.';
            $respuesta['codigo_error'] = 'no_autenticado';
            return [401, $respuesta];
        }

        $idRol = (int)($_SESSION['operador_rol'] ?? 0);
        if ($idRol !== 1) {
            RegistroAuditoria::seguridad('Intento de modificar configuracion sin permisos', [
                'id_operador' => $_SESSION['operador_id'],
                'id_rol' => $idRol,
            ]);
            $respuesta['mensaje_error'] = 'Solo el Super Administrador puede modificar la configuracion del sistema.';
            $respuesta['codigo_error'] = 'sin_permiso';
            return [403, $respuesta];
        }

        $confirmacion = trim($payload['confirmacion'] ?? '');
        if (strtoupper($confirmacion) !== 'CONFIRMAR') {
            $respuesta['mensaje_error'] = 'Debe escribir CONFIRMAR en el campo de confirmacion para aplicar los cambios.';
            $respuesta['codigo_error'] = 'confirmacion_requerida';
            return [400, $respuesta];
        }

        $modoRepositorio = !empty($payload['modo_repositorio']);

        $campos = [
            'ARCHIVO_TAMANO_MAXIMO_MB' => (int)($payload['tamano_maximo_mb'] ?? 0),
            'ARCHIVO_TIPOS_MIME_PERMITIDOS' => $modoRepositorio ? '*' : trim($payload['tipos_mime_permitidos'] ?? ''),
            'ARCHIVO_CUOTA_USUARIO_MB' => (int)($payload['cuota_usuario_mb'] ?? 0),
            'ARCHIVO_EXTENSIONES_PERMITIDAS' => $modoRepositorio ? '*' : trim($payload['extensiones_permitidas'] ?? ''),
            'ARCHIVO_MEMORIA_PHP_MB' => (int)($payload['memoria_php_mb'] ?? 0),
            'ARCHIVO_TIEMPO_EJECUCION_SEG' => (int)($payload['tiempo_ejecucion_seg'] ?? 0),
            'ARCHIVO_MAXIMO_SUBIDAS_SIMULTANEAS' => (int)($payload['maximo_subidas_simultaneas'] ?? 0),
            'ARCHIVO_POST_MAX_SIZE_MB' => (int)($payload['post_max_size_mb'] ?? 0),
        ];

        foreach ($campos as $clave => $valor) {
            if (strpos($clave, 'TAMANO') !== false || strpos($clave, 'CUOTA') !== false || strpos($clave, 'MEMORIA') !== false || strpos($clave, 'TIEMPO') !== false || strpos($clave, 'SUBIDAS') !== false || strpos($clave, 'POST_MAX') !== false) {
                if ($valor < 1) {
                    $respuesta['mensaje_error'] = "El valor de $clave debe ser mayor a 0.";
                    $respuesta['codigo_error'] = 'valor_invalido';
                    return [400, $respuesta];
                }
            }
            if (empty($valor)) {
                $respuesta['mensaje_error'] = "El valor de $clave no puede estar vacio.";
                $respuesta['codigo_error'] = 'valor_invalido';
                return [400, $respuesta];
            }
        }

        $resultados = [];
        $huboConflictos = [];

        foreach ($campos as $clave => $valor) {
            $fila = ConfiguracionSistema::obtenerFila($clave);
            $versionActual = (int)($fila['version'] ?? 0);
            $valorAnterior = $fila['valor'] ?? null;

            $resultado = ConfiguracionSistema::establecer($clave, $valor, $versionActual);

            if ($resultado['estado'] === 'conflicto') {
                $forzado = ConfiguracionSistema::forzarEstablecer($clave, $valor);
                $huboConflictos[$clave] = [
                    'valor_anterior' => $valorAnterior,
                    'valor_intentado' => $valor,
                    'version_forzada' => $forzado,
                ];
                RegistroAuditoria::seguridad('Sobrescritura de configuracion por conflicto de version', [
                    'clave' => $clave,
                    'valor_anterior' => $valorAnterior,
                    'valor_nuevo' => $valor,
                ]);
            } elseif ($resultado['estado'] === 'error') {
                $respuesta['mensaje_error'] = "Error al guardar $clave: " . $resultado['mensaje'];
                $respuesta['codigo_error'] = 'error_interno';
                return [500, $respuesta];
            }

            $resultados[$clave] = $resultado;
        }

        $valoresParaIni = [
            'memory_limit' => $campos['ARCHIVO_MEMORIA_PHP_MB'],
            'post_max_size' => $campos['ARCHIVO_POST_MAX_SIZE_MB'],
            'upload_max_filesize' => $campos['ARCHIVO_TAMANO_MAXIMO_MB'],
            'max_file_uploads' => $campos['ARCHIVO_MAXIMO_SUBIDAS_SIMULTANEAS'],
            'max_execution_time' => $campos['ARCHIVO_TIEMPO_EJECUCION_SEG'],
        ];

        $iniResultado = GeneradorIniServidor::regenerar($valoresParaIni);
        $iniOk = ($iniResultado['estado'] === 'ok');
        $advertenciaIni = null;

        if (!$iniOk) {
            $advertenciaIni = 'La configuracion se guardo en BD pero no se pudo regenerar archivos de configuracion PHP (.user.ini/.htaccess): '
                . implode(' ', $iniResultado['errores']);
            RegistroAuditoria::advertencia('Configuracion', 'Fallo al regenerar .user.ini/.htaccess', [
                'errores' => $iniResultado['errores'],
            ]);
        }

        RegistroAuditoria::auditoria('Configuracion', 'Actualizacion de limites de archivos', [
            'id_operador' => $_SESSION['operador_id'],
            'valores' => $campos,
            'ini_regenerado' => $iniOk,
            'hubo_conflictos' => !empty($huboConflictos),
            'detalles_conflictos' => $huboConflictos,
        ]);

        $respuesta['estado_operacion'] = true;
        $respuesta['mensaje_error'] = null;
        $respuesta['codigo_error'] = null;
        $respuesta['datos'] = [
            'mensaje' => 'configuracion_actualizada',
            'ini_regenerado' => $iniOk,
            'advertencia_ini' => $advertenciaIni,
            'hubo_conflictos' => !empty($huboConflictos),
            'detalles_conflictos' => $huboConflictos,
            'limites_php' => GeneradorIniServidor::limitesActualesPHP(),
            'contenido_htaccess' => $iniOk ? GeneradorIniServidor::leerActual() : null,
        ];
        return [200, $respuesta];
    }
}
