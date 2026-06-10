<?php

declare(strict_types=1);

namespace LiteFramework\Api;

use LiteFramework\Seguridad\SeguridadServidor;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Seguridad\TrazadorPeticiones;
use LiteFramework\Config\GestorEntorno;

require_once __DIR__ . '/../autoload.php';
GestorEntorno::cargar();
if (!defined('URL_BASE')) {
    define('URL_BASE', rtrim(dirname($_SERVER['SCRIPT_NAME'], 3), '/\\'));
}
TrazadorPeticiones::iniciar();
RegistroAuditoria::habilitarArchivo();

$esPeticionJson = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
    || ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';

if ($esPeticionJson) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
}

SeguridadServidor::iniciarSesionEstricta();

if (!isset($_SESSION['operador_id'])) {
    if ($esPeticionJson) {
        echo json_encode(['estado_operacion' => false, 'mensaje_error' => 'No habia sesion activa.']);
        exit();
    }
    header('Location: ' . URL_BASE . '/?error=sesion_invalida');
    exit();
}

$inicioSesion = $_SESSION['_inicio_sesion'] ?? 0;
$duracion = $inicioSesion > 0 ? time() - $inicioSesion : 0;
RegistroAuditoria::auditoria('Acceso', 'Cierre de sesion manual', [
    'nombre' => $_SESSION['operador_nombre'] ?? '',
    'duracion_sesion_seg' => $duracion,
    'duracion_sesion_min' => round($duracion / 60, 1),
]);

SeguridadServidor::destruirSesionCompletamente();

if ($esPeticionJson) {
    echo json_encode([
        'estado_operacion' => true,
        'mensaje_error' => null,
        'codigo_error' => null,
        'redireccion' => URL_BASE . '/?mensaje=sesion_finalizada',
    ]);
    exit();
}

header('Location: ' . URL_BASE . '/?mensaje=sesion_finalizada');
exit();
