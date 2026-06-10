<article>
    <h3 class="margen-inferior-normal">Plantillas</h3>

    <?php if (empty($documentos)): ?>
    <p class="texto-pequeno texto-peligro">No hay plantillas guardadas.</p>
    <?php else: ?>
    <div class="agrupador-flexible-columnas">
        <?php foreach ($documentos as $doc):
            $fechaFormateada = Fecha::formatear($doc['fecha_creacion'], 'd/m/Y H:i');
        ?>
        <div class="documento-tarjeta tarjeta" data-id="<?= $doc['id_documento'] ?>" data-titulo="<?= h($doc['titulo']) ?>">
            <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm texto-centro-sm margen-inferior-pequeno">
                <div>
                    <p class="texto-negrita"><?= h($doc['titulo']) ?></p>
                    <p class="texto-pequeno texto-suave"><?= $fechaFormateada ?></p>
                </div>
                <span class="etiqueta etiqueta-marca">PDF</span>
            </div>
            <div class="agrupador-flexible-filas flex-envolver brecha-pequena">
                <button type="button" class="accion-boton variante-borde boton-imprimir-documento" data-id="<?= $doc['id_documento'] ?>" data-titulo="<?= h($doc['titulo']) ?>">Vista previa</button>
                <button type="button" class="accion-boton variante-texto boton-editar-documento" data-id="<?= $doc['id_documento'] ?>" data-titulo="<?= h($doc['titulo']) ?>" data-contenido="<?= h($doc['contenido_html']) ?>">Editar</button>
                <button type="button" class="accion-boton variante-texto color-peligro boton-eliminar-documento" data-id="<?= $doc['id_documento'] ?>">Eliminar</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($paginador) && $paginador->totalPaginas > 1): ?>
    <?= $paginador->render() ?>
    <?php endif; ?>
    <?php endif; ?>
</article>
<span id="total-documentos-partial" data-total="<?= $totalDocumentos ?>" hidden></span>
