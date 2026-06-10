<article>
    <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm margen-inferior-normal">
        <div>
            <h2>Limites del servidor</h2>
            <p class="texto-suave texto-pequeno margen-superior-pequeno">Solo el Super Administrador puede modificar los limites del servidor.</p>
        </div>
        <div class="agrupador-flexible-filas brecha-pequena">
            <span class="etiqueta etiqueta-info">Subida max: <?= $limitesPhp['upload_max_filesize'] ?>M</span>
            <span class="etiqueta etiqueta-info">POST max: <?= $limitesPhp['post_max_size'] ?>M</span>
            <span class="etiqueta etiqueta-info">Memoria: <?= $limitesPhp['memory_limit'] ?>M</span>
        </div>
    </div>
</article>
