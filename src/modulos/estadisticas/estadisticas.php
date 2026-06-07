<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

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
    $paginaActual = $resultado['pagina'];

    $paginador = Paginador::crear($totalEstadisticas, $porPagina);

} catch (PDOException $e) {
    RegistroAuditoria::error('Estadisticas', 'Error al cargar listado', [
        'error' => $e->getMessage(),
    ]);
    $estadisticas = [];
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
    <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm texto-centro-sm margen-inferior-normal">
        <h1 class="margen-inferior-0 texto-2xl">Estadisticas <span class="etiqueta etiqueta-marca" id="contador-estadisticas"><?= $totalEstadisticas ?></span></h1>
    </div>

    <div class="rejilla-automatica">

        <section aria-label="Crear estadistica">
            <article class="margen-inferior-normal">
                <h3 class="margen-inferior-normal">Nueva estadistica</h3>
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
                    </details>

                    <button type="submit" class="ancho-completo-sm">Guardar estadistica</button>
                </form>
            </article>
        </section>

        <section aria-label="Estadisticas guardadas">
            <article class="relleno-normal margen-inferior-normal">
                <div class="agrupador-flexible-filas flex-envolver brecha-normal flex-fin">
                    <div class="grupo-campo campo-agrupado flex-1 ancho-min-200">
                        <label for="filtroBuscar">Buscar</label>
                        <input type="search" id="filtroBuscar" placeholder="Buscar por titulo..." value="<?= h($busqueda) ?>">
                    </div>
                </div>
            </article>

            <div id="contenedor-lista-estadisticas">
                <?php require __DIR__ . '/listadoEstadisticas.php'; ?>
            </div>
        </section>

    </div>
</section>

<script src="<?= URL_BASE ?>/src/js/modulos/estadisticas.js?ver=1" data-token="<?= $tokenCSRF ?>" data-base-url="<?= URL_BASE ?>" data-operador-id="<?= $idOperador ?>"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; 