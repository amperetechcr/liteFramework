<?php

define('DIRECTORIO_RAIZ', __DIR__);
define('URL_BASE', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

require_once DIRECTORIO_RAIZ . '/servidor/autoload.php';
GestorEntorno::cargar();

ManejadorErrores::registrar();

$configUI = require DIRECTORIO_RAIZ . '/servidor/config/ui.php';

if (!empty($_GET['paleta']) && in_array($_GET['paleta'], $configUI['paletas_validas'], true)) {
    $configUI['paleta'] = $_GET['paleta'];
}
if (!empty($_GET['estilo']) && in_array($_GET['estilo'], $configUI['estilos_validos'], true)) {
    $configUI['estilo'] = $_GET['estilo'];
}
if (!empty($_GET['fondo']) && in_array($_GET['fondo'], $configUI['fondos_validos'], true)) {
    $configUI['fondo'] = $_GET['fondo'];
}
if (!empty($_GET['fuente']) && in_array($_GET['fuente'], $configUI['fuentes_validas'], true)) {
    $configUI['fuente'] = $_GET['fuente'];
}
if (!empty($_GET['espaciado']) && in_array($_GET['espaciado'], $configUI['espaciados_validos'], true)) {
    $configUI['espaciado'] = $_GET['espaciado'];
}
if (!empty($_GET['tamano']) && in_array($_GET['tamano'], $configUI['tamanos_validos'], true)) {
    $configUI['tamano'] = $_GET['tamano'];
}

if (!empty($_SESSION['personalizacion_ui'])) {
    $configUI = array_merge($configUI, $_SESSION['personalizacion_ui']);
}

$GLOBALS['configUI'] = $configUI;

function configUI(?string $clave = null): mixed
{
    $config = $GLOBALS['configUI'];
    if ($clave !== null) {
        return $config[$clave] ?? null;
    }
    return $config;
}

function claseFondoHTML(): string
{
    $fondo = configUI('fondo') ?? 'blanco';
    return 'fondo-' . $fondo;
}

function claseTexturaHTML(): string
{
    $textura = configUI('textura') ?? 'ninguna';
    if ($textura === 'ninguna') {
        return '';
    }
    return 'textura-' . $textura;
}

function claseFuenteHTML(): string
{
    $fuente = configUI('fuente') ?? 'sistema';
    if ($fuente === 'sistema') {
        return '';
    }
    return 'fuente-' . $fuente;
}

function claseEspaciadoHTML(): string
{
    $espaciado = configUI('espaciado') ?? 'normal';
    if ($espaciado === 'normal') {
        return '';
    }
    return 'espaciado-' . $espaciado;
}

function claseTamanoHTML(): string
{
    $tamano = configUI('tamano') ?? 'normal';
    if ($tamano === 'normal') {
        return '';
    }
    return 'tamano-' . $tamano;
}

function h(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

SeguridadServidor::establecerCabecerasSeguras();

$uri = $_SERVER['REQUEST_URI'];
$base = dirname($_SERVER['SCRIPT_NAME']);
$ruta = substr(parse_url($uri, PHP_URL_PATH), strlen($base));
if ($ruta === false || $ruta === '') {
    $ruta = '/';
}
$ruta = preg_replace('#/index\.php$#', '/', $ruta);
if ($ruta === '//') {
    $ruta = '/';
}

$enrutador = require DIRECTORIO_RAIZ . '/rutas/web.php';
$resultado = $enrutador->despachar($_SERVER['REQUEST_METHOD'], $ruta);

if ($resultado === false) {
    http_response_code(404);
    RegistroAuditoria::advertencia('Enrutador', 'Ruta no encontrada', [
        'metodo' => $_SERVER['REQUEST_METHOD'],
        'ruta' => $ruta,
    ]);
    require DIRECTORIO_RAIZ . '/src/error.php';
}
