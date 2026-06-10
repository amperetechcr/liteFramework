<form id="form-subir-archivo" method="post" enctype="multipart/form-data" class="archivos-formulario tarjeta margen-inferior-normal">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">

    <div id="zona-subida">
        <input type="file" id="archivo" name="archivo"
            data-max-size="<?= (int)$tamanoMaximoMB * 1024 * 1024 ?>"
            multiple>
        <input type="file" id="archivo-carpeta" name="archivo_carpeta"
            data-max-size="<?= (int)$tamanoMaximoMB * 1024 * 1024 ?>"
            multiple webkitdirectory directory style="display:none">
        <div class="texto-xs texto-negrita margen-inferior-pequeno">Max. <?= (int)$tamanoMaximoMB ?>MB por archivo</div>
        <div class="agrupador-flexible-filas brecha-pequena">
            <button type="button" class="accion-boton variante-borde tamano-pequeno" id="archivos-subir-archivos">📄 Seleccionar archivos</button>
            <button type="button" class="accion-boton variante-borde tamano-pequeno" id="archivos-subir-carpeta">📁 Seleccionar carpeta</button>
        </div>
        <div class="archivos-seleccion-resumen" id="archivos-seleccion-resumen">
            <span id="archivos-seleccion-texto"></span>
            <span id="archivos-seleccion-detalle" class="texto-pequeno texto-suave oculto margen-superior-pequeno"></span>
            <button type="button" id="archivos-limpiar-seleccion">Limpiar seleccion</button>
        </div>
    </div>

    <div class="agrupador-flexible-filas brecha-normal margen-superior-pequeno">
        <div class="grupo-campo campo-agrupado flex-1">
            <label for="modulo_origen">Modulo</label>
            <input type="text" id="modulo_origen" name="modulo_origen" placeholder="ej: productos" maxlength="100">
        </div>
        <div class="grupo-campo campo-agrupado flex-1">
            <label for="etiquetas">Etiquetas</label>
            <input type="text" id="etiquetas" name="etiquetas" placeholder="ej: foto, perfil" maxlength="500">
        </div>
        <div class="grupo-campo campo-agrupado flex-1">
            <label for="descripcion">Descripcion</label>
            <input type="text" id="descripcion" name="descripcion" placeholder="Descripcion opcional..." maxlength="500">
        </div>
    </div>

    <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm">
        <button type="reset" data-variante="borde" id="archivos-limpiar-formulario" class="tamano-pequeno">Limpiar</button>
        <button type="submit" class="accion-boton variante-solida" id="archivos-boton-subir">
            Subir <span id="archivos-contador-boton"></span>
        </button>
    </div>
</form>

<div id="contenedor-progreso" class="archivos-contenedor-progreso" hidden></div>
<div id="mensaje-subida" class="archivos-mensaje" role="status" aria-live="polite"></div>
