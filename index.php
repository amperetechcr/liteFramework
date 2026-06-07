<?php

// ═══════════════════════════════════════════════════════════════
// liteFramework — AI-First PHP MVC Framework
// Pipeline de una peticion HTTP:
// 1 CONSTANTES → 2 HELPERS → 3 BOOTSTRAP → 4 UI CONFIG →
// 5 SEGURIDAD → 6 RUTEO → 7 DISPATCH
// ═══════════════════════════════════════════════════════════════

// ─── 1. CONSTANTES GLOBALES ──────────────────────────────────
// Raiz del proyecto y base URL. Disponibles en todo el sistema.

define('DIRECTORIO_RAIZ', __DIR__);
define('URL_BASE', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

// ─── 2. HELPERS DE VISTA ─────────────────────────────────────
// Funciones globales de UI: sanitizacion, clases CSS de personalizacion.

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

// ─── 3. BOOTSTRAP ────────────────────────────────────────────
// Orden estricto: autoloader → .env → Sentry → ManejadorErrores.
// Sentry se inicia antes del handler: si algo falla en bootstrap se captura.

require_once DIRECTORIO_RAIZ . '/servidor/autoload.php';
\LiteFramework\Config\GestorEntorno::cargar();

\LiteFramework\Servicios\ReporteroSentry::iniciar(
    \LiteFramework\Config\GestorEntorno::obtener('SENTRY_DSN', '')
);

\LiteFramework\Nucleo\ManejadorErrores::registrar();

// ─── 4. CONFIGURACION UI (3 capas) ───────────────────────────
// 1. defaults del archivo ui.php
// 2. override via GET params (previsualizacion sin guardar)
// 3. override via sesion (preferencias del operador)

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

// ─── 5. SEGURIDAD ────────────────────────────────────────────
// Cabeceras CSP, HSTS, X-Frame-Options antes de cualquier salida.

\LiteFramework\Seguridad\SeguridadServidor::establecerCabecerasSeguras();

// ─── 6. PARSEO DE RUTA ───────────────────────────────────────
// URI → path relativo → chequeo modo mantenimiento.

$uri = $_SERVER['REQUEST_URI'];
$base = dirname($_SERVER['SCRIPT_NAME']);
$path = parse_url($uri, PHP_URL_PATH) ?: '';
$ruta = substr($path, strlen($base));
if ($ruta === '') {
    $ruta = '/';
}
$ruta = preg_replace('#/index\.php$#', '/', $ruta);
if ($ruta === '//') {
    $ruta = '/';
}

$enrutador = require DIRECTORIO_RAIZ . '/rutas/web.php';

$mantenimiento = \LiteFramework\Config\ConfiguracionSistema::obtener('MODO_MANTENIMIENTO', false);
if ($mantenimiento) {
    $rutaRelativa = $ruta;
    $esLogin = in_array($rutaRelativa, ['/', '/ingreso', '/salir'], true);
    $esRecurso = (bool)preg_match('/\.(css|js|png|jpg|svg|ico|woff2?)$/', $rutaRelativa);
    $esAdmin = !empty($_SESSION['operador_id']);

    if (!$esLogin && !$esRecurso && !$esAdmin) {
        (new \LiteFramework\Middleware\MantenimientoInterceptor())->manejar([], function (): void {
        });
    }
}

// ─── 7. DISPATCH ─────────────────────────────────────────────
// Enrutador ejecuta interceptors → controlador → vista/JSON.
// Si no hay ruta: 404 con auditoria.

$resultado = $enrutador->despachar($_SERVER['REQUEST_METHOD'], $ruta);

if ($resultado === false) {
    http_response_code(404);
    \LiteFramework\Seguridad\RegistroAuditoria::advertencia('Enrutador', 'Ruta no encontrada', [
        'metodo' => $_SERVER['REQUEST_METHOD'],
        'ruta' => $ruta,
    ]);
    require DIRECTORIO_RAIZ . '/src/error.php';
}
