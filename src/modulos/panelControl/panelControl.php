<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

$codigoError = $_GET['error'] ?? '';
$codigoMensaje = $_GET['mensaje'] ?? '';

$stats = [
    'total_usuarios' => 0,
    'usuarios_activos' => 0,
    'total_roles' => 0,
    'total_archivos' => 0,
    'espacio_usado_mb' => 0,
    'eventos_hoy' => 0,
    'ultima_migracion' => '—',
];
$actividadReciente = [];

try {
    $stats['total_usuarios'] = Operador::contar();
    $stats['usuarios_activos'] = Operador::contarActivos();
    $stats['total_roles'] = Rol::contar();
} catch (Exception $e) {
    error_log('[panelControl] Error al cargar stats basicos: ' . $e->getMessage());
}

try {
    $actividadReciente = RegistroAuditoria::consultarEventos($idOperador, null, 6, 0);
    $resumen = RegistroAuditoria::obtenerResumen();
    $stats['eventos_hoy'] = $resumen['hoy'];
} catch (Exception $e) {
    error_log('[panelControl] Error al cargar actividad: ' . $e->getMessage());
}

try {
    $gestor = new GestorMigraciones(ConexionBaseDatos::obtenerInstancia()->obtenerConector());
    $ultima = $gestor->obtenerUltimaMigracion();
    if ($ultima) $stats['ultima_migracion'] = $ultima;
} catch (Exception $e) {
    error_log('[panelControl] Error al obtener ultima migracion: ' . $e->getMessage());
}

$totalPermisos = count($permisos);

$nombreRol = $_SESSION['operador_rol_nombre'] ?? '—';

try {
    if (class_exists('Archivo')) {
        $stats['total_archivos'] = Archivo::contar();
        $stats['espacio_usado_mb'] = round(Archivo::sumaTamanoBytes() / 1048576, 1);
    }
} catch (Exception $e) {
    error_log('[panelControl] Error al obtener stats de archivos: ' . $e->getMessage());
}

$seguridadActiva = [
    'csrf' => true,
    'huella' => !empty($_SESSION['huella_seguridad_cliente']),
    'https' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'rbac' => ($totalPermisos > 0)
];
$puntajeSeguridad = (int)((array_sum($seguridadActiva) / count($seguridadActiva)) * 100);

$modulos = [
    'inicio' => ['ruta' => '/inicio', 'titulo' => 'Inicio', 'icono' => '🏠', 'desc' => 'Dashboard principal con resumen del sistema y actividad reciente.', 'stats' => 'Vista general'],
    'archivos' => ['ruta' => '/archivos', 'titulo' => 'Archivos', 'icono' => '📁', 'desc' => 'Subida y almacenamiento seguro con explorador, cuota y descargas.', 'stats' => $stats['total_archivos'] . ' archivos · ' . $stats['espacio_usado_mb'] . ' MB'],
    'operadores' => ['ruta' => '/operadores', 'titulo' => 'Operadores', 'icono' => '👥', 'desc' => 'Gestion de cuentas, roles RBAC, permisos y estados de cuenta.', 'stats' => $stats['usuarios_activos'] . '/' . $stats['total_usuarios'] . ' activos'],
    'auditoria' => ['ruta' => '/auditoria', 'titulo' => 'Auditoria', 'icono' => '📋', 'desc' => 'Bitacora de eventos con filtros y busqueda. Trazabilidad completa.', 'stats' => $stats['eventos_hoy'] . ' eventos hoy'],
    'migraciones' => ['ruta' => '/migraciones', 'titulo' => 'Migraciones', 'icono' => '🗄', 'desc' => 'Ejecucion de SQL pendientes, respaldos automaticos y restauracion.', 'stats' => 'Ultima: ' . $stats['ultima_migracion']],
    'apariencia' => ['ruta' => '/apariencia', 'titulo' => 'Apariencia', 'icono' => '🎨', 'desc' => 'Personaliza paletas de color, estilos, fuentes, espaciado y presets visuales.', 'stats' => 'Cambios en vivo'],
    'configuracion' => ['ruta' => '/configuracion', 'titulo' => 'Configuracion', 'icono' => '⚙', 'desc' => 'Perfil de usuario, limites de subida de archivos y directivas PHP del servidor.', 'stats' => 'Perfil · Limites · PHP'],
    'documentacion' => ['ruta' => '/documentacion', 'titulo' => 'Documentacion', 'icono' => '📖', 'desc' => 'Casos de uso, snippets y ejemplos practicos para cada modulo.', 'stats' => '20 secciones'],
    'generadorModulo' => ['ruta' => '/generador-modulo', 'titulo' => 'Generador CRUD', 'icono' => '🧩', 'desc' => 'Genera un modulo completo con modelo, controlador, vistas y rutas.', 'stats' => 'PHP + JS + SQL'],
    'generadorProyecto' => ['ruta' => '/generador-proyecto', 'titulo' => 'Generar Proyecto', 'icono' => '🚀', 'desc' => 'Crea un proyecto completo desde cero con wizard guiado.', 'stats' => 'Full stack'],
    'generadorPdf' => ['ruta' => '/generador-pdf', 'titulo' => 'PDF', 'icono' => '📄', 'desc' => 'Genera documentos PDF desde HTML con estilos y diseno profesional.', 'stats' => 'Documentos'],
    'estadisticas' => ['ruta' => '/estadisticas', 'titulo' => 'Estadisticas', 'icono' => '📊', 'desc' => 'Visualiza graficos y reportes generados desde consultas SQL.', 'stats' => 'Graficos'],
];

$indicadores = [
    ['label' => 'Operadores', 'valor' => $stats['usuarios_activos'] . '/' . $stats['total_usuarios'], 'icono' => '👥', 'color' => 'marca'],
    ['label' => 'Archivos', 'valor' => $stats['total_archivos'], 'icono' => '📁', 'color' => 'marca'],
    ['label' => 'Eventos hoy', 'valor' => $stats['eventos_hoy'], 'icono' => '📋', 'color' => 'marca'],
    ['label' => 'Seguridad', 'valor' => $puntajeSeguridad . '%', 'icono' => '🛡', 'color' => $puntajeSeguridad >= 75 ? 'exito' : 'peligro'],
];

$checksSeguridad = [
    'csrf' => ['Proteccion CSRF', true],
    'huella' => ['Huella de sesion', !empty($_SESSION['huella_seguridad_cliente'])],
    'rbac' => ['Control RBAC', $totalPermisos > 0],
    'https' => ['Conexion HTTPS', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')],
];

if ($esAjax) {
    echo '<div data-titulo-pagina="Panel"></div>';
}
?>
<?php if (!$esAjax): $tituloPagina = 'Panel'; $moduloActivo = 'panelControl'; require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php'; endif; ?>

<div class="cabecera-panel margen-inferior-normal">
    <div>
        <h1 class="margen-inferior-pequeno"><?= h($nombreOperador) ?>, bienvenido</h1>
        <p class="texto-pequeno texto-suave"><?= date('l, d \de F \de Y') ?> · Rol: <?= h($nombreRol) ?> · <?= $totalPermisos ?> permisos asignados</p>
    </div>
    <div class="agrupador-flexible-filas brecha-pequena">
        <span class="etiqueta etiqueta-<?= $puntajeSeguridad >= 75 ? 'exito' : 'peligro' ?>"><?= $puntajeSeguridad >= 75 ? 'Sistema seguro' : 'Revisar seguridad' ?></span>
    </div>
</div>

<section aria-label="Indicadores del sistema" class="margen-inferior-normal">
    <p class="texto-xs texto-suave margen-inferior-pequeno">Resumen en tiempo real del estado del framework.</p>
    <div class="rejilla-automatica">
        <?php foreach ($indicadores as $ind): ?>
        <article class="alineacion-centrada">
            <p class="texto-2xl texto-negrita color-<?= $ind['color'] ?>"><?= $ind['icono'] ?> <?= $ind['valor'] ?></p>
            <p class="texto-pequeno texto-seminegrita texto-suave"><?= $ind['label'] ?></p>
        </article>
        <?php endforeach; ?>
    </div>
</section>

<div class="panel-columnas">

    <div class="panel-columna-principal">
        <section aria-label="Modulos del sistema" class="margen-inferior-normal">
            <h2 class="margen-inferior-normal">Modulos</h2>
            <p class="texto-xs texto-suave margen-inferior-pequeno">Accede a cada seccion del panel de control.</p>
            <div class="rejilla-automatica">
                <?php foreach ($modulos as $clave => $mod): ?>
                <a href="<?= URL_BASE . $mod['ruta']; ?>" class="tarjeta-modulo tarjeta-seleccion-modulo" data-modulo="<?= $clave; ?>">
                    <div class="envoltura-icono-modulo">
                        <span class="icono-documentacion"><?= $mod['icono'] ?></span>
                    </div>
                    <div class="flex-1">
                        <h3 class="texto-negrita margen-inferior-minimo"><?= $mod['titulo'] ?></h3>
                        <p class="texto-pequeno texto-suave margen-inferior-pequeno"><?= $mod['desc'] ?></p>
                        <span class="etiqueta etiqueta-marca"><?= $mod['stats'] ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if (!empty($actividadReciente)): ?>
        <section aria-label="Actividad reciente">
            <article>
                <h3 class="margen-inferior-normal">Actividad reciente</h3>
                <p class="texto-xs texto-suave margen-inferior-pequeno">Tus ultimas acciones registradas en la bitacora.</p>
                <div class="agrupador-flexible-columnas">
                    <?php foreach ($actividadReciente as $act): ?>
                    <div class="agrupador-flexible-filas distribucion-espaciada">
                        <div>
                            <p class="texto-pequeno texto-negrita"><?= h($act['accion_realizada']) ?></p>
                            <p class="texto-xs texto-suave"><?= h($act['modulo']) ?></p>
                        </div>
                        <span class="texto-xs texto-suave"><?= Fecha::formatear($act['fecha_registro'], 'd/m H:i') ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>
        <?php endif; ?>
    </div>

    <div class="panel-columna-lateral">
        <section aria-label="Estado del sistema" class="margen-inferior-normal">
            <article>
                <h3 class="margen-inferior-normal">Estado del sistema</h3>
                <p class="texto-xs texto-suave margen-inferior-pequeno">Controles de seguridad activos en el framework.</p>
                <div class="agrupador-flexible-columnas">
                    <?php foreach ($checksSeguridad as $clave => $info): ?>
                    <div class="agrupador-flexible-filas distribucion-espaciada">
                        <span class="texto-pequeno"><?= $info[0] ?></span>
                        <span class="etiqueta <?= $info[1] ? 'etiqueta-exito' : 'etiqueta-peligro'; ?>"><?= $info[1] ? 'Activo' : 'Inactivo' ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section aria-label="Informacion del sistema" class="margen-inferior-normal">
            <article>
                <h3 class="margen-inferior-normal">Servidor</h3>
                <p class="texto-xs texto-suave margen-inferior-pequeno">Entorno de ejecucion del framework.</p>
                <div class="agrupador-flexible-columnas">
                    <div class="agrupador-flexible-filas distribucion-espaciada">
                        <span class="texto-pequeno">PHP</span>
                        <span class="texto-xs texto-suave"><?= phpversion() ?></span>
                    </div>
                    <div class="agrupador-flexible-filas distribucion-espaciada">
                        <span class="texto-pequeno">Motor BD</span>
                        <span class="texto-xs texto-suave"><?= GestorEntorno::obtener('DB_TIPO', 'mysql') ?></span>
                    </div>
                    <div class="agrupador-flexible-filas distribucion-espaciada">
                        <span class="texto-pequeno">Sesion</span>
                        <span class="texto-xs texto-suave"><?= session_id() ? substr(session_id(), 0, 12) . '...' : '—' ?></span>
                    </div>
                    <div class="agrupador-flexible-filas distribucion-espaciada">
                        <span class="texto-pequeno">Roles</span>
                        <span class="texto-xs texto-suave"><?= $stats['total_roles'] ?> configurados</span>
                    </div>
                </div>
            </article>
        </section>

        <?php if ($totalPermisos > 0): ?>
        <section aria-label="Tus permisos">
            <article>
                <h3 class="margen-inferior-normal">Tus permisos <span class="etiqueta etiqueta-marca"><?= $totalPermisos ?></span></h3>
                <p class="texto-xs texto-suave margen-inferior-pequeno">Permisos asignados a tu rol actual.</p>
                <div class="contenedor-permisos">
                    <?php foreach ($permisos as $permiso): ?>
                    <span class="permiso-etiqueta"><?= h($permiso) ?></span>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>
        <?php endif; ?>
    </div>

</div>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; 