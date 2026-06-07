<?php
if (!defined('DIRECTORIO_RAIZ')) {
    require_once __DIR__ . '/../../servidor/autoload.php';
    GestorEntorno::cargar();
}
if (!defined('URL_BASE')) {
    define('URL_BASE', rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\'));
}

$esAjax = !empty($_GET['ajax']);

SeguridadServidor::iniciarSesionEstricta();

if (!isset($_SESSION['operador_id'])) {
    header("Location: " . URL_BASE . "/?error=privilegios_insuficientes");
    exit();
}

$idOperador = (int)$_SESSION['operador_id'];
$nombreOperador = $_SESSION['operador_nombre'];
$idRol = (int)$_SESSION['operador_rol'];
$permisos = $_SESSION['matriz_permisos'] ?? [];