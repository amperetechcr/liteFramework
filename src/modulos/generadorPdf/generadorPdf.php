<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

$partial = $_GET['partial'] ?? '';
$tokenCSRF = SeguridadServidor::generarTokenAntiFalsificacion();

$documentos = [];
$totalDocumentos = 0;
$paginaActual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$porPagina = 10;
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$paginador = null;

try {
    $resultado = DocumentoPdf::listarConFiltros($busqueda, $paginaActual, $porPagina);
    $documentos = $resultado['documentos'];
    $totalDocumentos = $resultado['total'];
    $paginaActual = $resultado['pagina'];

    $paginador = Paginador::crear($totalDocumentos, $porPagina);

} catch (PDOException $e) {
    RegistroAuditoria::error('DocumentosPDF', 'Error al cargar listado', [
        'error' => $e->getMessage(),
    ]);
    $documentos = [];
    $paginador = Paginador::crear(0, $porPagina);
}

if ($esAjax && !$partial) {
    echo '<div data-titulo-pagina="Plantillas PDF"></div>';
}

if ($partial === 'lista') {
    require __DIR__ . '/listadoDocumentos.php';
    return;
}

if (!$esAjax) {
    $tituloPagina = 'Plantillas PDF';
    $moduloActivo = 'generadorPdf';
    require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php';
}
?>

<section aria-label="Gestion de plantillas PDF">
    <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm texto-centro-sm margen-inferior-normal">
        <h1 class="margen-inferior-0 texto-2xl">Plantillas PDF <span class="etiqueta etiqueta-marca" id="contador-documentos"><?= $totalDocumentos ?></span></h1>
    </div>

    <div class="rejilla-automatica">

        <section aria-label="Crear plantilla">
            <article class="margen-inferior-normal">
                <h3 class="margen-inferior-normal">Nueva plantilla</h3>
                <form id="formularioDocumento" class="agrupador-flexible-columnas" method="POST" novalidate>
                    <input type="hidden" name="token_peticion" value="<?= $tokenCSRF ?>">
                    <input type="hidden" name="accion_crud" value="crud">
                    <input type="hidden" name="entidad" value="documento_pdf">
                    <input type="hidden" name="accion" value="crear">

                    <div class="grupo-campo campo-agrupado">
                        <label for="titulo">Titulo del documento</label>
                        <input type="text" id="titulo" name="titulo" placeholder="Ej: Reporte mensual de ventas" required>
                    </div>

                    <div class="grupo-campo campo-agrupado">
                        <label for="contenidoHtml">Contenido HTML</label>
                        <textarea id="contenidoHtml" name="contenido_html" rows="12" placeholder="<h1>Titulo</h1>&#10;<p>Contenido del documento...</p>&#10;<table>...</table>" required></textarea>
                    </div>

                    <button type="submit" class="ancho-completo-sm">Guardar plantilla</button>
                </form>
            </article>
        </section>

        <section aria-label="Plantillas guardadas">
            <article class="relleno-normal margen-inferior-normal">
                <div class="agrupador-flexible-filas flex-envolver brecha-normal flex-fin">
                    <div class="grupo-campo campo-agrupado flex-1 ancho-min-200">
                        <label for="filtroBuscar">Buscar</label>
                        <input type="search" id="filtroBuscar" placeholder="Buscar por titulo..." value="<?= h($busqueda) ?>">
                    </div>
                </div>
            </article>

            <div id="contenedor-lista-documentos">
                <?php require __DIR__ . '/listadoDocumentos.php'; ?>
            </div>
        </section>

    </div>
</section>

<script src="<?= URL_BASE ?>/src/js/modulos/generadorPdf.js?ver=1" data-token="<?= $tokenCSRF ?>" data-base-url="<?= URL_BASE ?>" data-operador-id="<?= $idOperador ?>"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; 