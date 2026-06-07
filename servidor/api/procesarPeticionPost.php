<?php

declare(strict_types=1);

namespace LiteFramework\Api;

use PDOException;
use LiteFramework\Seguridad\SeguridadServidor;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Seguridad\TrazadorPeticiones;
use LiteFramework\Config\GestorEntorno;
use LiteFramework\Api\Controladores\AutenticacionApiControlador;
use LiteFramework\Api\Controladores\OperadorApiControlador;
use LiteFramework\Api\Controladores\PersonalizacionApiControlador;
use LiteFramework\Api\Controladores\MigracionApiControlador;
use LiteFramework\Api\Controladores\ConfiguracionApiControlador;
use LiteFramework\Api\Controladores\CrudApiControlador;
use LiteFramework\Api\Controladores\GeneradorModuloApiControlador;
use LiteFramework\Api\Controladores\GeneradorProyectoApiControlador;
use Exception;

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/../autoload.php';
GestorEntorno::cargar();
if (!defined('URL_BASE')) {
    define('URL_BASE', rtrim(dirname($_SERVER['SCRIPT_NAME'], 3), '/\\'));
}
TrazadorPeticiones::iniciar();
RegistroAuditoria::habilitarArchivo();

function responder(array $datos, ?int $codigoHttp = null): void
{
    if ($codigoHttp !== null) {
        http_response_code($codigoHttp);
    }
    TrazadorPeticiones::finalizar();
    echo json_encode($datos);
    exit();
}

$respuestaServidor = [
    'estado_operacion' => false,
    'mensaje_error' => 'Error interno no controlado por el servidor.',
    'codigo_error' => 'error_interno',
    'nuevo_token' => ''
];

try {
    SeguridadServidor::iniciarSesionEstricta();

    $flujoEntrada = file_get_contents('php://input');
    $payload = json_decode($flujoEntrada, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($payload)) {
        if (!empty($_POST)) {
            $payload = $_POST;
        } else {
            RegistroAuditoria::advertencia('API', 'Payload invalido', [
                'error_json' => json_last_error_msg(),
                'longitud' => strlen($flujoEntrada),
            ]);
            $respuestaServidor['mensaje_error'] = 'Estructura de transmisión de datos inválida.';
            $respuestaServidor['codigo_error'] = 'datos_invalidos';
            responder($respuestaServidor, 400);
        }
    }

    $tokenPeticion = $payload['token_peticion'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (empty($tokenPeticion) || !SeguridadServidor::validarTokenAntiFalsificacion($tokenPeticion)) {
        RegistroAuditoria::seguridad('Token CSRF invalido', [
            'motivo' => empty($tokenPeticion) ? 'token_vacio' : 'token_invalido',
        ]);
        $respuestaServidor['mensaje_error'] = 'Token de seguridad CSRF no válido o expirado.';
        $respuestaServidor['codigo_error'] = 'token_invalido';
        responder($respuestaServidor, 403);
    }

    $nuevoTokenRotado = SeguridadServidor::generarTokenAntiFalsificacion();
    $respuestaServidor['nuevo_token'] = $nuevoTokenRotado;

    if (!empty($payload['_cliente']) && is_array($payload['_cliente'])) {
        $_SESSION['_datos_cliente'] = $payload['_cliente'];
    }

    $accionCrud = $payload['accion_crud'] ?? '';

    if (empty($accionCrud)) {
        $respuestaServidor['mensaje_error'] = 'Directiva de operación no especificada.';
        $respuestaServidor['codigo_error'] = 'datos_invalidos';
        responder($respuestaServidor, 400);
    }

    $ruta = [
        'iniciar_sesion'                  => [AutenticacionApiControlador::class, 'iniciarSesion'],
        'cerrar_sesion'                   => [AutenticacionApiControlador::class, 'cerrarSesion'],
        'registrar_operador'              => [OperadorApiControlador::class, 'registrar'],
        'actualizar_mi_perfil'            => [OperadorApiControlador::class, 'actualizarPerfil'],
        'guardar_personalizacion_ui'      => [PersonalizacionApiControlador::class, 'guardar'],
        'obtener_personalizacion_ui'      => [PersonalizacionApiControlador::class, 'obtener'],
        'migraciones_ejecutar'            => [MigracionApiControlador::class, 'ejecutar'],
        'migraciones_ejecutar_individual' => [MigracionApiControlador::class, 'ejecutarIndividual'],
        'migraciones_resetear'            => [MigracionApiControlador::class, 'resetear'],
        'migraciones_ver_sql'             => [MigracionApiControlador::class, 'verSql'],
        'migraciones_respaldo'            => [MigracionApiControlador::class, 'respaldo'],
        'actualizar_configuracion_archivos' => [ConfiguracionApiControlador::class, 'actualizarConfiguracionArchivos'],
        'generar_modulo'                   => [GeneradorModuloApiControlador::class, 'generarModulo'],
        'generar_proyecto'                 => [GeneradorProyectoApiControlador::class, 'generarProyecto'],
        'operador_suspender'               => [OperadorApiControlador::class, 'suspenderOperador'],
        'operador_activar'                 => [OperadorApiControlador::class, 'activarOperador'],
    ];

    if (isset($ruta[$accionCrud])) {
        [$controlador, $metodo] = $ruta[$accionCrud];
        $instancia = new $controlador();
        [$codigo, $datos] = $instancia->$metodo($payload);
        $datos['nuevo_token'] = $nuevoTokenRotado;
        responder($datos, $codigo);
    } else {
        $instancia = new CrudApiControlador();
        [$codigo, $datos] = $instancia->procesar($payload);
        $datos['nuevo_token'] = $nuevoTokenRotado;
        responder($datos, $codigo);
    }
} catch (PDOException $errorGeneral) {
    RegistroAuditoria::error('MotorCRUD', 'Error en base de datos', [
        'mensaje' => $errorGeneral->getMessage(),
        'trace' => GestorEntorno::esDepuracion() ? $errorGeneral->getTraceAsString() : null,
    ]);
    $respuestaServidor['mensaje_error'] = 'Error interno del servidor de datos.';
    $respuestaServidor['codigo_error'] = 'error_interno';
    responder($respuestaServidor, 500);
} catch (Exception $excepcionGeneral) {
    RegistroAuditoria::error('MotorCRUD', 'Excepcion no controlada', [
        'mensaje' => $excepcionGeneral->getMessage(),
        'trace' => GestorEntorno::esDepuracion() ? $excepcionGeneral->getTraceAsString() : null,
    ]);
    $respuestaServidor['mensaje_error'] = 'Error interno no controlado.';
    $respuestaServidor['codigo_error'] = 'error_interno';
    responder($respuestaServidor, 500);
}
