<?php
if (!defined('DIRECTORIO_RAIZ')) {
    require_once __DIR__ . '/../../servidor/autoload.php';
    GestorEntorno::cargar();
}
if (!defined('URL_BASE')) {
    define('URL_BASE', rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\'));
}

SeguridadServidor::iniciarSesionEstricta();

if (!isset($_SESSION['operador_id'])) {
    header('Location: ' . URL_BASE . '/?error=privilegios_insuficientes');
    exit();
}

$idEstadistica = isset($datosVista) ? (int)$datosVista : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($idEstadistica <= 0) {
    http_response_code(404);
    echo '<h1>Estadistica no encontrada</h1>';
    exit;
}

$est = new GeneradorEstadisticas();
$est->desdePlantilla($idEstadistica);

if ($est->tieneError() && empty($est->obtenerResultados())) {
    http_response_code(500);
    echo '<h1>Error</h1><p>' . h($est->obtenerError()) . '</p>';
    exit;
}

$est->renderizar();
