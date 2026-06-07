<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

if ($esAjax) {
    echo '<div data-titulo-pagina="Documentacion"></div>';
}

$secciones = [];
foreach (glob(__DIR__ . '/secciones/*.php') as $archivo) {
    $secciones[] = require $archivo;
}

$porPaginaDoc = 12;
$totalSecciones = count($secciones);
$paginadorDoc = Paginador::crear($totalSecciones, $porPaginaDoc);
$inicioDoc = $paginadorDoc->offset();

if (!$esAjax) {
    $tituloPagina = 'Documentacion';
    $moduloActivo = 'documentacion';
    require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php';
}
?>

<section aria-label="Documentacion del framework" style="min-height:50vh">
    <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm texto-centro-sm margen-inferior-normal">
        <h1 class="margen-inferior-0 texto-2xl">Documentacion</h1>
        <div class="grupo-campo campo-agrupado margen-inferior-0" style="max-width:320px">
            <input type="search" id="buscadorDocumentacion" placeholder="Buscar secciones..." autocomplete="off">
        </div>
    </div>

    <div class="rejilla-automatica" id="rejillaDocumentacion">
        <?php foreach ($secciones as $i => $sec):
            $enPaginaActual = $i >= $inicioDoc && $i < $inicioDoc + $porPaginaDoc;
        ?>
        <article class="tarjeta-seccion-doc" data-seccion-id="<?= $sec['id'] ?>" data-etiquetas="<?= h($sec['etiquetas']) ?>" data-titulo="<?= h($sec['titulo']) ?>" data-contenido="<?= h(strip_tags($sec['contenido'])) ?>" data-descripcion="<?= h($sec['descripcion']) ?>" data-pagina="<?= (int)($i / $porPaginaDoc) + 1 ?>" style="<?= $enPaginaActual ? '' : 'display:none' ?>" role="button" tabindex="0" aria-label="Abrir documentacion de <?= h($sec['titulo']) ?>">
            <div class="envoltura-icono-modulo">
                <span class="icono-documentacion"><?= $sec['icono'] ?></span>
            </div>
            <div>
                <h3 class="texto-negrita margen-inferior-pequeno"><?= h($sec['titulo']) ?></h3>
                <p class="texto-pequeno texto-suave"><?= h($sec['descripcion']) ?></p>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <p class="texto-centro texto-pequeno texto-suave" id="sinResultados" style="display:none">No se encontraron secciones que coincidan con la busqueda.</p>
</section>

<?php foreach ($secciones as $sec): ?>
<div id="modal-<?= $sec['id'] ?>" class="modal-superposicion modal-documentacion" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-<?= $sec['id'] ?>" hidden>
    <div class="modal-contenido modal-documentacion-contenido">
        <div class="modal-cabecera">
            <h2 id="titulo-modal-<?= $sec['id'] ?>" class="margen-inferior-0"><?= h($sec['titulo']) ?></h2>
            <button type="button" class="modal-cerrar" aria-label="Cerrar modal">&times;</button>
        </div>
        <div class="modal-cuerpo modal-documentacion-cuerpo">
            <?= $sec['contenido'] ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="<?= URL_BASE ?>/src/js/modulos/documentacion.js"></script>

<?php if ($totalSecciones > $porPaginaDoc): ?>
<div class="alineacion-centrada" id="paginacion-documentacion"><?= $paginadorDoc->render() ?></div>
<?php endif; ?>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; 