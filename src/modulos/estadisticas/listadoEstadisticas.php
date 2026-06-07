<article>
    <h3 class="margen-inferior-normal">Estadisticas</h3>

    <?php if (empty($estadisticas)): ?>
    <p class="texto-pequeno texto-peligro">No hay estadisticas guardadas.</p>
    <?php else: ?>
    <div class="agrupador-flexible-columnas">
        <?php foreach ($estadisticas as $est):
            $fechaFormateada = Fecha::formatear($est['fecha_creacion'], 'd/m/Y H:i');
            $tipoEtiqueta = match($est['tipo_visualizacion']) {
                'barras' => ['Bar', 'etiqueta-info'],
                'pastel' => ['Pastel', 'etiqueta-advertencia'],
                'kpi' => ['KPI', 'etiqueta-exito'],
                default => ['Tarjetas', 'etiqueta-marca'],
            };
        ?>
        <div class="estadistica-tarjeta tarjeta" data-id="<?= $est['id_estadistica'] ?>" data-titulo="<?= h($est['titulo']) ?>" data-descripcion="<?= h($est['descripcion']) ?>" data-consulta="<?= h($est['consulta_sql']) ?>" data-tipo="<?= h($est['tipo_visualizacion']) ?>" data-columnas="<?= h($est['columnas_mostrar']) ?>" data-configuracion="<?= h($est['configuracion_visual']) ?>">
            <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm texto-centro-sm margen-inferior-pequeno">
                <div>
                    <p class="texto-negrita"><?= h($est['titulo']) ?></p>
                    <p class="texto-pequeno texto-suave"><?= $fechaFormateada ?></p>
                </div>
                <span class="etiqueta <?= $tipoEtiqueta[1] ?>"><?= $tipoEtiqueta[0] ?></span>
            </div>
            <div class="agrupador-flexible-filas flex-envolver brecha-pequena">
                <button type="button" class="accion-boton variante-borde boton-ver-estadistica" data-id="<?= $est['id_estadistica'] ?>">Ver</button>
                <button type="button" class="accion-boton variante-texto boton-editar-estadistica" data-id="<?= $est['id_estadistica'] ?>">Editar</button>
                <button type="button" class="accion-boton variante-texto color-peligro boton-eliminar-estadistica" data-id="<?= $est['id_estadistica'] ?>">Eliminar</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPaginas > 1): ?>
    <?= $paginador->render() ?>
    <?php endif; ?>
    <?php endif; ?>
</article>
<span id="total-estadisticas-partial" data-total="<?= $totalEstadisticas ?>" hidden></span>
