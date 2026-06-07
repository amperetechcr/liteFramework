<?php
if (!defined('DIRECTORIO_RAIZ')) {
    require_once __DIR__ . '/../../servidor/autoload.php';
    GestorEntorno::cargar();
}
if (!defined('URL_BASE')) {
    define('URL_BASE', rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\'));
}

if (empty($moduloActivo)) $moduloActivo = 'panelControl';
$nombreOperador = $_SESSION['operador_nombre'] ?? 'Operador';
$idRol = (int)($_SESSION['operador_rol'] ?? 0);

$nombreRol = $_SESSION['operador_rol_nombre'] ?? '—';

$paletaClase = 'paleta-' . (configUI('paleta') ?? 'indigo');
$estiloClase = 'estilo-' . (configUI('estilo') ?? 'moderno');
$fondoClase = claseFondoHTML();
$texturaClase = claseTexturaHTML();
$fuenteClase = claseFuenteHTML();
$espaciadoClase = claseEspaciadoHTML();
$tamanoClase = claseTamanoHTML();
$clasesHtml = trim($paletaClase . ' ' . $estiloClase . ' ' . $fondoClase . ' ' . $texturaClase . ' ' . $fuenteClase . ' ' . $espaciadoClase . ' ' . $tamanoClase);

$enlacesNav = [
    'panelControl' => ['ruta' => '/panelControl', 'etiqueta' => 'Panel', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>'],
    'archivos' => ['ruta' => '/archivos', 'etiqueta' => 'Archivos', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>'],
    'operadores' => ['ruta' => '/operadores', 'etiqueta' => 'Operadores', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>'],
    'auditoria' => ['ruta' => '/auditoria', 'etiqueta' => 'Auditoría', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>'],
    'configuracion' => ['ruta' => '/configuracion', 'etiqueta' => 'Configuración', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06-.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>'],
    'apariencia' => ['ruta' => '/apariencia', 'etiqueta' => 'Apariencia', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>'],
    'documentacion' => ['ruta' => '/documentacion', 'etiqueta' => 'Documentacion', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><polyline points="8 7 12 7"/><polyline points="8 11 16 11"/><polyline points="8 15 14 15"/></svg>'],
    'generadorModulo' => ['ruta' => '/generador-modulo', 'etiqueta' => 'Generador', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>'],
    'generadorProyecto' => ['ruta' => '/generador-proyecto', 'etiqueta' => 'Proyecto', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22 6 12 13 2 6"/></svg>'],
    'generadorPdf' => ['ruta' => '/generador-pdf', 'etiqueta' => 'PDF', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>'],
    'estadisticas' => ['ruta' => '/estadisticas', 'etiqueta' => 'Estadísticas', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>'],
    'inicio' => ['ruta' => '/inicio', 'etiqueta' => 'Inicio', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
    'migraciones' => ['ruta' => '/migraciones', 'etiqueta' => 'Migraciones', 'icono' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>'],
];

?><!DOCTYPE html>
<html lang="es-CR" class="<?= $clasesHtml ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?> &mdash; Lite Framework</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="<?= URL_BASE ?>/src/img/favicon.png">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/tema.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/paletas.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/maquetacion.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/componentes.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/modales.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/subirArchivos.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/generadorPdf.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/estadisticas.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/documentacion.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/apariencia.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/estilos.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/utilidades.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/personalizacion.css">

    <meta name="api-base" content="<?= URL_BASE ?>/api">
    <script type="module" src="<?= URL_BASE ?>/src/js/principal.js"></script>
    <script>
    window.VALORES_UI = <?= json_encode($GLOBALS['configUI']) ?>;
    </script>
</head>
<body
    data-error="<?= htmlspecialchars($codigoError ?? '', ENT_QUOTES, 'UTF-8') ?>"
    data-mensaje="<?= htmlspecialchars($codigoMensaje ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
    <a href="#contenido-principal" class="enlace-salto">Saltar al contenido principal</a>

    <div class="contenedor-principal envoltura-principal" id="envoltura-principal">

        <div class="capa-lateral" id="capa-lateral" aria-hidden="true"></div>

        <nav class="barra-lateral" id="barra-lateral" aria-label="Navegación principal">
            <div class="barra-lateral-cabecera">
                <h2 class="barra-lateral-titulo">Lite Framework</h2>
                <p class="texto-pequeno texto-suave">Panel de control</p>
            </div>

            <ul class="barra-lateral-menu">
                <?php foreach ($enlacesNav as $clave => $mod): ?>
                <?php $activo = ($clave === $moduloActivo); ?>
                <li>
                    <a href="<?= URL_BASE . $mod['ruta']; ?>"
                       class="barra-lateral-enlace<?= $activo ? ' enlace-activo' : '' ?>"
                       data-modulo="<?= $clave ?>"
                       <?= $activo ? 'aria-current="page"' : '' ?>>
                        <span class="barra-lateral-icono"><?= $mod['icono'] ?></span>
                        <span><?= $mod['etiqueta'] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="barra-lateral-pie">
                <div class="agrupador-flexible-filas brecha-pequena relleno-normal">
                    <button type="button" id="alternador-tema" class="alternador-tema" aria-label="Alternar tema claro/oscuro">&#x2600;</button>
                    <span class="texto-pequeno flex-1">
                        <span class="texto-color-marca texto-negrita"><?= htmlspecialchars($nombreOperador, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="etiqueta etiqueta-marca"><?= htmlspecialchars($nombreRol, ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </div>
                <a href="<?= URL_BASE ?>/salir" class="accion-boton variante-borde ancho-auto margen-superior-0 margen-inferior-normal margen-izquierdo-normal margen-derecho-normal" aria-label="Cerrar sesión">Salir</a>
            </div>
        </nav>

        <div class="area-contenido">
            <header class="cabecera-movil">
                <button type="button" id="boton-menu-lateral" class="boton-menu-lateral" aria-label="Abrir menú" aria-expanded="false">☰</button>
                <span class="cabecera-movil-titulo texto-negrita">Lite Framework</span>
                <span class="cabecera-movil-espacio"></span>
            </header>
            <main id="contenido-principal">
