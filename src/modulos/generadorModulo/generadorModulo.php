<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

$moduloTitulo = 'Generador de Módulos';
$moduloActivo = 'generadorModulo';

if ($esAjax) {
    echo '<div data-titulo-pagina="' . $moduloTitulo . '"></div>';
}
?>
<?php if (!$esAjax): $tituloPagina = $moduloTitulo; require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php'; endif; ?>

<div class="cabecera-panel margen-inferior-mediano">
    <h1 class="texto-xl"><?= h($moduloTitulo) ?></h1>
    <p class="texto-suave texto-sm margen-superior-pequeno">Genera módulos CRUD completos (modelo, migración, controlador API, vista y JS) con un solo clic.</p>
    <div class="alerta alinear-alerta color-exito flex brecha-pequena margen-superior-pequeno texto-sm">
        <span>⚡</span>
        <span>Se genera automáticamente: <strong>ID autoincremental</strong>, <strong>fecha_creacion</strong>, <strong>fecha_actualizacion</strong>, <strong>permisos RBAC</strong>, índices UNIQUE y <strong>foreign keys</strong> para campos <code>_id</code>.</span>
    </div>
</div>

<div class="rejilla-automatica columnas-2 separacion-16">
    <div class="tarjeta-simple">
        <form id="form-generador" class="formulario-generador">

            <!-- Paso 1: Configuracion basica -->
            <fieldset class="grupo-campos borde-0 relleno-0 margen-0">
                <legend class="flex flex-entre ancho-completo margen-inferior-normal">
                    <span class="flex brecha-pequena">
                        <span class="etiqueta etiqueta-marca tamano-pequeno">1</span>
                        <span class="texto-negrita">Configuración básica</span>
                    </span>
                </legend>

                <div class="grupo-campo">
                    <label for="clase_nombre">Nombre de la clase <span class="texto-peligro">*</span></label>
                    <input type="text" name="clase_nombre" id="clase_nombre" class="campo-entrada" placeholder="Ej: Producto, Cliente, Factura..." required autofocus>
                    <p class="ayuda-campo">Singular, PascalCase. Ej: <code>Producto</code>, <code>Cliente</code></p>
                </div>

                <div class="grupo-campo">
                    <label for="tabla">Nombre de la tabla <span class="texto-suave texto-xs">(opcional)</span></label>
                    <input type="text" name="tabla" id="tabla" class="campo-entrada" placeholder="Se infiere automáticamente">
                    <p class="ayuda-campo">Si se omite, se genera desde el nombre de la clase: <code id="tabla-inferida">—</code></p>
                </div>
            </fieldset>

            <hr class="borde-inferior margen-superior-normal margen-inferior-normal">

            <!-- Paso 2: Campos -->
            <fieldset class="grupo-campos borde-0 relleno-0 margen-0">
                <legend class="flex flex-entre ancho-completo margen-inferior-normal">
                    <span class="flex brecha-pequena">
                        <span class="etiqueta etiqueta-marca tamano-pequeno">2</span>
                        <span class="texto-negrita">Campos de la tabla</span>
                    </span>
                    <span id="contador-campos" class="campo-contador">0 campos</span>
                </legend>

                <div id="contenedor-campos"></div>

                <button type="button" id="btn-agregar-campo" class="accion-boton variante-borde ancho-completo margen-superior-pequeno flex brecha-pequena">
                    <span class="texto-lg texto-negrita">+</span> Agregar campo
                </button>

                <div class="margen-superior-normal flex flex-envolver brecha-pequena">
                    <span class="texto-xs texto-suave flex-1">Tipos:</span>
                    <code class="etiqueta etiqueta-info tamano-pequeno">string</code>
                    <code class="etiqueta etiqueta-info tamano-pequeno">text</code>
                    <code class="etiqueta etiqueta-info tamano-pequeno">int</code>
                    <code class="etiqueta etiqueta-info tamano-pequeno">decimal</code>
                    <code class="etiqueta etiqueta-info tamano-pequeno">bool</code>
                    <code class="etiqueta etiqueta-info tamano-pequeno">email</code>
                    <code class="etiqueta etiqueta-info tamano-pequeno">date</code>
                    <code class="etiqueta etiqueta-info tamano-pequeno">datetime</code>
                </div>
            </fieldset>

            <button type="submit" class="accion-boton variante-solida ancho-completo tamano-grande margen-superior-mediano" id="btn-generar">
                Generar Módulo
            </button>
        </form>
    </div>

    <div class="flex-columna brecha-mediana">
        <!-- Preview en vivo -->
        <div class="tarjeta-simple">
            <h2 class="texto-base texto-negrita margen-inferior-pequeno flex brecha-pequena">
                <span>Vista previa</span>
                <span class="etiqueta etiqueta-info tamano-pequeno">en vivo</span>
            </h2>
            <div id="preview-generador" class="tarjeta-preview">
                <div class="preview-vacio">Completa el formulario para ver la vista previa</div>
            </div>
        </div>

        <!-- Resultados -->
        <div class="tarjeta-simple" id="contenedor-resultados">
            <h2 class="texto-base texto-negrita margen-inferior-pequeno">Resultado</h2>
            <div id="resultado-generador" class="texto-sm">
                <p class="texto-suave">Completa los campos y haz clic en "Generar Módulo"</p>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= URL_BASE ?>/src/css/generadorModulo.css">
<script type="module" src="<?= URL_BASE ?>/src/js/modulos/generadorModulo.js"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; 