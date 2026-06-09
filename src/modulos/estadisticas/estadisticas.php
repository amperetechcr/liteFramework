<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

try {

$partial = $_GET['partial'] ?? '';
$tokenCSRF = SeguridadServidor::generarTokenAntiFalsificacion();

$estadisticas = [];
$totalEstadisticas = 0;
$paginaActual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$porPagina = 10;
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$paginador = null;

try {
    $resultado = Estadistica::listarConFiltros($busqueda, $paginaActual, $porPagina);
    $estadisticas = $resultado['estadisticas'];
    $totalEstadisticas = $resultado['total'];
    $totalPaginas = $resultado['total_paginas'] ?? 1;
    $paginaActual = $resultado['pagina'];
    $paginador = Paginador::crear($totalEstadisticas, $porPagina);
} catch (PDOException $e) {
    RegistroAuditoria::error('Estadisticas', 'Error al cargar listado', [
        'error' => $e->getMessage(),
    ]);
    $estadisticas = [];
    $totalPaginas = 1;
    $paginador = Paginador::crear(0, $porPagina);
}

if ($esAjax && !$partial) {
    echo '<div data-titulo-pagina="Estadisticas"></div>';
}

if ($partial === 'lista') {
    require __DIR__ . '/listadoEstadisticas.php';
    return;
}

if (!$esAjax) {
    $tituloPagina = 'Estadisticas';
    $moduloActivo = 'estadisticas';
    require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php';
}
?>

<section aria-label="Gestion de estadisticas">
    <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm margen-inferior-normal">
        <h1 class="margen-inferior-0 texto-2xl">Estadisticas</h1>
    </div>

    <div class="pestanas-ui" role="tablist">
        <button type="button" class="pestana activa" data-tab="dashboard" role="tab" aria-selected="true">📊 Dashboard</button>
        <button type="button" class="pestana" data-tab="lista" role="tab" aria-selected="false">📋 Estadisticas <span class="etiqueta etiqueta-marca" id="contador-estadisticas"><?= $totalEstadisticas ?></span></button>
        <button type="button" class="pestana" data-tab="nueva" role="tab" aria-selected="false">+ Nueva</button>
    </div>

    <section class="pestana-panel activo" data-panel="dashboard" aria-label="Dashboard">
        <?php require __DIR__ . '/dashboard.php'; ?>
    </section>

    <section class="pestana-panel" data-panel="lista" aria-label="Estadisticas" hidden>
        <div class="agrupador-flexible-filas flex-envolver brecha-normal flex-fin margen-inferior-normal">
            <div class="grupo-campo campo-agrupado flex-1 ancho-min-200">
                <label for="filtroBuscar">Buscar</label>
                <input type="search" id="filtroBuscar" placeholder="Buscar por titulo..." value="<?= h($busqueda) ?>">
            </div>
        </div>
        <div id="contenedor-lista-estadisticas">
            <?php require __DIR__ . '/listadoEstadisticas.php'; ?>
        </div>
    </section>

    <section class="pestana-panel" data-panel="nueva" aria-label="Nueva estadistica" hidden>
        <article class="ancho-max-700 margen-horizontal-auto">
            <h3 class="margen-inferior-normal" id="titulo-formulario">Nueva estadistica</h3>
            <form id="formularioEstadistica" class="agrupador-flexible-columnas" method="POST" novalidate>
                <input type="hidden" name="token_peticion" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="accion_crud" value="crud">
                <input type="hidden" name="entidad" value="estadistica">
                <input type="hidden" name="accion" value="crear">

                <div class="agrupador-flexible-filas brecha-normal">
                    <div class="grupo-campo campo-agrupado flex-1">
                        <label for="titulo">Titulo</label>
                        <input type="text" id="titulo" name="titulo" placeholder="Ej: Ventas mensuales por categoria" required>
                    </div>
                    <div class="grupo-campo campo-agrupado" style="max-width:200px">
                        <label for="tipoVisualizacion">Visualizacion</label>
                        <select id="tipoVisualizacion" name="tipo_visualizacion">
                            <option value="tarjetas">Tarjetas</option>
                            <option value="barras">Barras</option>
                            <option value="pastel">Pastel</option>
                            <option value="kpi">KPI</option>
                        </select>
                    </div>
                </div>

                <div class="grupo-campo campo-agrupado">
                    <label for="consultaSql">Consulta SQL</label>
                    <textarea id="consultaSql" name="consulta_sql" rows="4" placeholder="SELECT columna1, columna2 FROM tabla WHERE condicion ORDER BY columna1 DESC LIMIT 20" required></textarea>
                </div>

                <details class="detalle-servidor">
                    <summary class="texto-pequeno texto-suave">Opciones avanzadas</summary>
                    <div class="grupo-campo campo-agrupado">
                        <label for="descripcion">Descripcion (opcional)</label>
                        <input type="text" id="descripcion" name="descripcion" placeholder="Describe que muestra esta estadistica">
                    </div>
                    <div class="grupo-campo campo-agrupado">
                        <label for="columnasMostrar">Alias de columnas (JSON)</label>
                        <textarea id="columnasMostrar" name="columnas_mostrar" rows="2" placeholder='{"columna1": "Etiqueta 1", "columna2": "Etiqueta 2"}'></textarea>
                    </div>
                    <div class="grupo-campo campo-agrupado">
                        <label for="configuracionVisual">Colores (JSON)</label>
                        <textarea id="configuracionVisual" name="configuracion_visual" rows="2" placeholder='{"colores": ["#4f46e5","#059669","#d97706"]}'></textarea>
                    </div>
                    <div class="grupo-campo campo-agrupado">
                        <label for="cacheTtl">Cache (segundos)</label>
                        <input type="number" id="cacheTtl" name="cache_ttl" value="300" min="0" max="86400" placeholder="300 = 5 minutos">
                    </div>
                </details>

                <div class="agrupador-flexible-filas brecha-normal">
                    <button type="submit" class="ancho-completo-sm flex-1">Guardar estadistica</button>
                    <button type="button" class="accion-boton variante-texto ancho-completo-sm" id="botonCancelarEdicion" style="display:none">Cancelar edicion</button>
                </div>
            </form>
        </article>
    </section>
</section>

<div id="modal-vista-estadistica"
     class="modal-superposicion"
     role="dialog"
     aria-modal="true"
     aria-labelledby="titulo-modal-estadistica"
     hidden>
    <div class="modal-contenido ancho-max-900">
        <div class="modal-cabecera">
            <h2 id="titulo-modal-estadistica">Estadistica</h2>
            <div class="agrupador-flexible-filas brecha-normal">
                <button type="button" class="accion-boton variante-texto texto-xs" id="btn-exportar-json" title="Exportar JSON">JSON</button>
                <button type="button" class="accion-boton variante-texto texto-xs" id="btn-exportar-csv" title="Exportar CSV">CSV</button>
                <button type="button" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>
        </div>
        <div id="cuerpo-modal-estadistica" class="modal-cuerpo">
            <div class="alineacion-centrada"><span class="indicador-cargando">↻</span> Cargando...</div>
        </div>
    </div>
</div>

<script src="<?= URL_BASE ?>/src/js/lib/graficos.js"></script>
<script src="<?= URL_BASE ?>/src/js/modulos/estadisticas.js" data-token="<?= $tokenCSRF ?>" data-base-url="<?= URL_BASE ?>" data-operador-id="<?= $idOperador ?>"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif;

} catch (\Throwable $e) {
    \LiteFramework\Seguridad\RegistroAuditoria::error('Estadisticas', 'Error al cargar modulo', [
        'mensaje' => $e->getMessage(),
        'archivo' => $e->getFile(),
        'linea' => $e->getLine(),
    ]);
    if ($esAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage(), 'archivo' => $e->getFile(), 'linea' => $e->getLine()]);
    } else {
        http_response_code(500);
        echo '<h1>Error interno</h1><p>' . h($e->getMessage()) . '</p>';
    }
}
