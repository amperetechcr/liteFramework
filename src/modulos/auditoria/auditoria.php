<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

$partial = $_GET['partial'] ?? '';
$exportar = $_GET['exportar'] ?? '';

$tienePermiso = SeguridadServidor::tienePermiso('bitacora_sistema.leer');
$tokenCSRF = SeguridadServidor::generarTokenAntiFalsificacion();

$moduloFiltro = !empty($_GET['modulo']) ? $_GET['modulo'] : null;
$nivelFiltro = !empty($_GET['nivel']) ? $_GET['nivel'] : null;
$ipFiltro = !empty($_GET['ip']) ? $_GET['ip'] : null;
$fechaDesde = !empty($_GET['desde']) ? $_GET['desde'] : null;
$fechaHasta = !empty($_GET['hasta']) ? $_GET['hasta'] : null;
$busqueda = !empty($_GET['busqueda']) ? $_GET['busqueda'] : null;
$paginaActual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$porPagina = 20;

$resumen = ['total' => 0, 'ultima_semana' => 0, 'hoy' => 0, 'por_modulo' => []];
$modulosDisponibles = [];
$totalEventos = 0;
$totalPaginas = 1;
$eventos = [];

if ($exportar && $tienePermiso) {
    $formato = $exportar === 'json' ? 'json' : 'csv';
    $contenido = RegistroAuditoria::exportarEventos($formato, null, $moduloFiltro, $fechaDesde, $fechaHasta, $nivelFiltro, $ipFiltro, $busqueda);
    $ext = $formato;
    header('Content-Type: ' . ($formato === 'json' ? 'application/json' : 'text/csv; charset=utf-8'));
    header('Content-Disposition: attachment; filename="auditoria_export_' . date('Ymd_His') . '.' . $ext . '"');
    header('Content-Length: ' . strlen($contenido));
    echo $contenido;
    exit;
}

if ($tienePermiso) {
    $resumen = RegistroAuditoria::obtenerResumen();
    $modulosDisponibles = RegistroAuditoria::obtenerModulos();
    $totalEventos = RegistroAuditoria::contarEventos(null, $moduloFiltro, $fechaDesde, $fechaHasta, $nivelFiltro, $ipFiltro, $busqueda);
    $totalPaginas = max(1, (int)ceil($totalEventos / $porPagina));
    if ($paginaActual > $totalPaginas) $paginaActual = $totalPaginas;
    $inicio = ($paginaActual - 1) * $porPagina;
    $eventos = RegistroAuditoria::consultarEventos(null, $moduloFiltro, $porPagina, $inicio, $fechaDesde, $fechaHasta, $nivelFiltro, $ipFiltro, $busqueda);
    $paginadorAuditoria = Paginador::crear($totalEventos, $porPagina);
} else {
    $paginadorAuditoria = Paginador::crear(0, $porPagina);
}

// Partial: solo la lista (usado por AJAX para actualizar sin recargar página)
if ($partial === 'lista') {
?>
<article>
    <h2 class="margen-inferior-normal">Registro de eventos <span class="etiqueta etiqueta-marca" id="total-eventos"><?= $totalEventos ?></span></h2>

    <?php if (empty($eventos)): ?>
    <p class="texto-pequeno texto-peligro">No se encontraron eventos con los filtros seleccionados.</p>
    <?php else: ?>
    <div class="agrupador-flexible-columnas">
        <?php foreach ($eventos as $ev):
            $nivel = 'INFO';
            $detalle = [];
            if (!empty($ev['detalles_json'])) {
                $detalle = json_decode($ev['detalles_json'], true);
                $nivel = $detalle['nivel'] ?? 'INFO';
            }
            $claseSeveridad = match ($nivel) {
                'ERROR', 'SEGURIDAD' => 'etiqueta-peligro',
                'ADVERTENCIA' => 'etiqueta-advertencia',
                default => 'etiqueta-exito',
            };
            $operadorNombre = $ev['nombre_completo'] ? h($ev['nombre_completo']) : '<span class="texto-suave">Sistema</span>';
            $tzCliente = $_SESSION['_datos_cliente']['timezone'] ?? null;
            $fechaFormateada = Fecha::formatear($ev['fecha_registro'], 'd/m/Y H:i:s', $tzCliente);
        ?>
        <article class="evento-auditoria evento-auditoria-con-padding">
            <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm margen-inferior-pequeno">
                <div class="agrupador-flexible-filas brecha-pequena">
                    <span class="etiqueta etiqueta-marca"><?= h($ev['modulo']) ?></span>
                    <span class="etiqueta <?= $claseSeveridad ?>"><?= $nivel ?></span>
                </div>
                <span class="texto-pequeno texto-suave"><?= $fechaFormateada ?></span>
            </div>
            <p class="texto-negrita"><?= h($ev['modulo']) ?></p>
            <div class="agrupador-flexible-filas distribucion-espaciada">
                <span class="texto-pequeno"><?= $operadorNombre ?></span>
                <?php if (!empty($detalle['ip'])): ?>
                    <span class="texto-pequeno texto-suave">IP: <?= h($detalle['ip']) ?></span>
                <?php endif; ?>
            </div>
            <button type="button" class="accion-boton variante-texto texto-pequeno margen-superior-pequeno btn-detalle-evento" data-detalle='<?= h(json_encode($detalle)) ?>'>Detalle</button>
        </article>
        <?php endforeach; ?>
    </div>

    <div id="paginacion-auditoria"><?= $paginadorAuditoria->render() ?></div>
    <?php endif; ?>
</article>
<span id="total-eventos-partial" data-total="<?= $totalEventos ?>" hidden></span>
<?php
    return;
}

if (!$esAjax) {
    $tituloPagina = 'Auditoria';
    $moduloActivo = 'auditoria';
    require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php';
}
?>

<?php if (!$tienePermiso): ?>
<article>
    <p class="texto-peligro texto-negrita">No tienes permiso para consultar la bitacora del sistema.</p>
</article>
<?php else: ?>

<h1 class="margen-inferior-normal">Auditoría</h1>

<!-- Summary -->
<section class="margen-inferior-normal" aria-label="Resumen de auditoria">
    <div class="rejilla-automatica">
        <article class="alineacion-centrada">
            <p class="texto-gigante texto-color-marca" id="resumen-total"><?= $resumen['total'] ?></p>
            <p class="texto-pequeno texto-negrita">Eventos totales</p>
        </article>
        <article class="alineacion-centrada">
            <p class="texto-gigante texto-color-marca" id="resumen-hoy"><?= $resumen['hoy'] ?></p>
            <p class="texto-pequeno texto-negrita">Eventos hoy</p>
        </article>
        <article class="alineacion-centrada">
            <p class="texto-gigante texto-color-marca" id="resumen-semana"><?= $resumen['ultima_semana'] ?></p>
            <p class="texto-pequeno texto-negrita">Ultimos 7 dias</p>
        </article>
        <article class="alineacion-centrada">
            <p class="texto-gigante texto-color-marca" id="resumen-modulos"><?= count($modulosDisponibles) ?></p>
            <p class="texto-pequeno texto-negrita">Modulos</p>
        </article>
    </div>
</section>

<!-- Filters -->
<article class="margen-inferior-normal" id="filtros-auditoria">
    <div class="agrupador-flexible-filas flex-envolver brecha-normal flex-fin">
        <div class="grupo-campo campo-agrupado ancho-min-180 flex-1">
            <label for="filtro-modulo">Modulo</label>
            <select id="filtro-modulo" name="modulo">
                <option value="">Todos los modulos</option>
                <?php foreach ($modulosDisponibles as $mod): ?>
                <option value="<?= h($mod) ?>" <?= $mod === $moduloFiltro ? 'selected' : '' ?>>
                    <?= h($mod) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grupo-campo campo-agrupado ancho-min-140">
            <label for="filtro-desde">Desde</label>
            <input type="date" id="filtro-desde" name="desde" value="<?= h($fechaDesde ?? '') ?>">
        </div>
        <div class="grupo-campo campo-agrupado ancho-min-140">
            <label for="filtro-hasta">Hasta</label>
            <input type="date" id="filtro-hasta" name="hasta" value="<?= h($fechaHasta ?? '') ?>">
        </div>
        <div class="grupo-campo campo-agrupado ancho-min-140">
            <label for="filtro-nivel">Severidad</label>
            <select id="filtro-nivel" name="nivel">
                <option value="">Todas</option>
                <option value="ERROR" <?= $nivelFiltro === 'ERROR' ? 'selected' : '' ?>>ERROR</option>
                <option value="SEGURIDAD" <?= $nivelFiltro === 'SEGURIDAD' ? 'selected' : '' ?>>SEGURIDAD</option>
                <option value="ADVERTENCIA" <?= $nivelFiltro === 'ADVERTENCIA' ? 'selected' : '' ?>>ADVERTENCIA</option>
                <option value="INFO" <?= $nivelFiltro === 'INFO' ? 'selected' : '' ?>>INFO</option>
                <option value="AUDITORIA" <?= $nivelFiltro === 'AUDITORIA' ? 'selected' : '' ?>>AUDITORIA</option>
            </select>
        </div>
        <div class="grupo-campo campo-agrupado ancho-min-160">
            <label for="filtro-ip">Direccion IP</label>
            <input type="text" id="filtro-ip" name="ip" placeholder="Ej: 192.168.1" value="<?= h($ipFiltro ?? '') ?>">
        </div>
        <div class="grupo-campo campo-agrupado ancho-min-200 flex-1">
            <label for="filtro-busqueda">Buscar accion</label>
            <input type="search" id="filtro-busqueda" name="busqueda" placeholder="Ej: inicio de sesion..." value="<?= h($busqueda ?? '') ?>">
        </div>
    </div>
</article>

<!-- Export -->
<div class="agrupador-flexible-filas brecha-normal margen-inferior-normal flex-fin">
    <a href="?exportar=csv<?= $moduloFiltro ? '&modulo=' . urlencode($moduloFiltro) : '' ?><?= $nivelFiltro ? '&nivel=' . urlencode($nivelFiltro) : '' ?><?= $ipFiltro ? '&ip=' . urlencode($ipFiltro) : '' ?><?= $fechaDesde ? '&desde=' . urlencode($fechaDesde) : '' ?><?= $fechaHasta ? '&hasta=' . urlencode($fechaHasta) : '' ?>" class="accion-boton variante-secundaria" download>CSV</a>
    <a href="?exportar=json<?= $moduloFiltro ? '&modulo=' . urlencode($moduloFiltro) : '' ?><?= $nivelFiltro ? '&nivel=' . urlencode($nivelFiltro) : '' ?><?= $ipFiltro ? '&ip=' . urlencode($ipFiltro) : '' ?><?= $fechaDesde ? '&desde=' . urlencode($fechaDesde) : '' ?><?= $fechaHasta ? '&hasta=' . urlencode($fechaHasta) : '' ?>" class="accion-boton variante-secundaria" download>JSON</a>
</div>

<!-- Events -->
<div id="resultados-auditoria">
    <article>
        <h2 class="margen-inferior-normal">Registro de eventos <span class="etiqueta etiqueta-marca" id="total-eventos"><?= $totalEventos ?></span></h2>

        <?php if (empty($eventos)): ?>
        <p class="texto-pequeno texto-peligro">No se encontraron eventos con los filtros seleccionados.</p>
        <?php else: ?>
        <div class="agrupador-flexible-columnas">
            <?php foreach ($eventos as $ev):
                $nivel = 'INFO';
                $detalle = [];
                if (!empty($ev['detalles_json'])) {
                    $detalle = json_decode($ev['detalles_json'], true);
                    $nivel = $detalle['nivel'] ?? 'INFO';
                }
                $claseSeveridad = match ($nivel) {
                    'ERROR', 'SEGURIDAD' => 'etiqueta-peligro',
                    'ADVERTENCIA' => 'etiqueta-advertencia',
                    default => 'etiqueta-exito',
                };
            $operadorNombre = $ev['nombre_completo'] ? h($ev['nombre_completo']) : '<span class="texto-suave">Sistema</span>';
                $tzCliente = $_SESSION['_datos_cliente']['timezone'] ?? null;
            $fechaFormateada = Fecha::formatear($ev['fecha_registro'], 'd/m/Y H:i:s', $tzCliente);
            ?>
            <article class="evento-auditoria evento-auditoria-con-padding">
                <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm margen-inferior-pequeno">
                    <div class="agrupador-flexible-filas brecha-pequena">
                        <span class="etiqueta etiqueta-marca"><?= h($ev['modulo']) ?></span>
                        <span class="etiqueta <?= $claseSeveridad ?>"><?= $nivel ?></span>
                    </div>
                    <span class="texto-pequeno texto-suave"><?= $fechaFormateada ?></span>
                </div>
                <p class="texto-negrita"><?= h($ev['modulo']) ?></p>
                <div class="agrupador-flexible-filas distribucion-espaciada">
                    <span class="texto-pequeno"><?= $operadorNombre ?></span>
                    <?php if (!empty($detalle['ip'])): ?>
                    <span class="texto-pequeno texto-suave">IP: <?= h($detalle['ip']) ?></span>
                    <?php endif; ?>
                </div>
                <button type="button" class="accion-boton variante-texto texto-pequeno margen-superior-pequeno btn-detalle-evento" data-detalle='<?= h(json_encode($detalle)) ?>'>Detalle</button>
            </article>
            <?php endforeach; ?>
        </div>

        <div id="paginacion-auditoria"><?= $paginadorAuditoria->render() ?></div>
        <?php endif; ?>
    </article>
    <span id="total-eventos-partial" data-total="<?= $totalEventos ?>" hidden></span>
</div>

<div id="modal-detalle-auditoria"
     class="modal-superposicion"
     role="dialog"
     aria-modal="true"
     aria-labelledby="titulo-modal-detalle"
     hidden>
    <div class="modal-contenido ancho-max-700">
        <div class="modal-cabecera">
            <h2 id="titulo-modal-detalle">Detalle de evento</h2>
            <button type="button" class="modal-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <div id="detalle-auditoria-cuerpo" class="modal-cuerpo"></div>
    </div>
</div>

<script src="<?= URL_BASE ?>/src/js/modulos/auditoria.js"></script>

<?php endif; ?>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; 