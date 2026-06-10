<article>
    <?php if (empty($estadisticas)): ?>
    <p class="texto-pequeno texto-peligro">No hay estadisticas guardadas.</p>
    <?php else: ?>
    <div class="agrupador-flexible-columnas brecha-normal">
        <?php foreach ($estadisticas as $est):
            $fechaFormateada = Fecha::formatear($est['fecha_creacion'], 'd/m/Y H:i');
            $tipoEtiqueta = match($est['tipo_visualizacion']) {
                'barras' => ['Barras', 'etiqueta-info'],
                'pastel' => ['Pastel', 'etiqueta-advertencia'],
                'kpi' => ['KPI', 'etiqueta-exito'],
                default => ['Tarjetas', 'etiqueta-marca'],
            };
            $nombreOperador = $est['nombre_completo'] ?? null;
            $enDashboard = !empty($est['en_dashboard']);
            $ultimaEjec = !empty($est['ultima_ejecucion']) ? Fecha::formatear($est['ultima_ejecucion'], 'd/m/Y H:i') : null;
        ?>
        <div class="estadistica-tarjeta tarjeta" data-id="<?= $est['id_estadistica'] ?>"
             data-titulo="<?= h($est['titulo']) ?>"
             data-descripcion="<?= h($est['descripcion'] ?? '') ?>"
             data-consulta="<?= h($est['consulta_sql']) ?>"
             data-tipo="<?= h($est['tipo_visualizacion']) ?>"
             data-columnas="<?= h($est['columnas_mostrar'] ?? '') ?>"
             data-configuracion="<?= h(is_array($est['configuracion_visual']) ? json_encode($est['configuracion_visual']) : ($est['configuracion_visual'] ?? '')) ?>">
            <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm margen-inferior-pequeno">
                <div class="flex-1">
                    <div class="agrupador-flexible-filas brecha-pequena">
                        <p class="texto-negrita"><?= h($est['titulo']) ?></p>
                        <span class="etiqueta <?= $tipoEtiqueta[1] ?>"><?= $tipoEtiqueta[0] ?></span>
                        <?php if ($enDashboard): ?>
                        <span class="etiqueta etiqueta-exito" title="En el dashboard">📌</span>
                        <?php endif; ?>
                    </div>
                    <p class="texto-xs texto-suave margen-superior-minimo">
                        <?= $fechaFormateada ?>
                        <?php if ($nombreOperador): ?> &middot; <?= h($nombreOperador) ?><?php endif; ?>
                        <?php if ($ultimaEjec): ?> &middot; Ejec: <?= $ultimaEjec ?><?php endif; ?>
                    </p>
                </div>
            </div>
            <?php if (!empty($est['descripcion'])): ?>
            <p class="texto-xs texto-suave margen-inferior-pequeno"><?= h($est['descripcion']) ?></p>
            <?php endif; ?>
            <details class="detalle-sql">
                <summary class="texto-xs texto-suave" title="Ver consulta SQL">SQL</summary>
                <pre class="texto-xs codigo-sql"><?= h($est['consulta_sql']) ?></pre>
            </details>
            <div class="agrupador-flexible-filas flex-envolver brecha-pequena margen-superior-pequeno">
                <button type="button" class="accion-boton variante-borde boton-ver-estadistica" data-id="<?= $est['id_estadistica'] ?>">Ver</button>
                <button type="button" class="accion-boton variante-texto boton-pinear-estadistica <?= $enDashboard ? 'boton-pineado' : '' ?>" data-id="<?= $est['id_estadistica'] ?>"><?= $enDashboard ? '📌 Quitar' : '📍 Pinear' ?></button>
                <button type="button" class="accion-boton variante-texto boton-editar-estadistica" data-id="<?= $est['id_estadistica'] ?>">Editar</button>
                <button type="button" class="accion-boton variante-texto boton-clonar-estadistica" data-id="<?= $est['id_estadistica'] ?>">Clonar</button>
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
