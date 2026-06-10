<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

if ($esAjax) {
    echo '<div data-titulo-pagina="Rendimiento"></div>';
}

if (!$esAjax) {
    $tituloPagina = 'Rendimiento';
    $moduloActivo = 'rendimiento';
    require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php';
}
?>

<section aria-label="Rendimiento del sistema" id="seccion-rendimiento">
    <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm margen-inferior-normal">
        <div>
            <h1 class="margen-inferior-0 texto-2xl">Rendimiento</h1>
            <p class="texto-xs texto-suave">Monitoreo de tiempos de respuesta y memoria del framework.
                <span class="indicador-cargando" id="rend-cargando" style="display:none">↻</span>
            </p>
        </div>
        <div class="agrupador-flexible-filas brecha-pequena flex-envolver flex-fin">
            <span class="rend-estado" id="rend-estado"></span>
            <span class="texto-xs texto-suave" id="rend-actualizado"></span>
        </div>
    </div>

    <div class="rejilla-rendimiento" id="rejilla-rendimiento">
        <div class="tarjeta-rendimiento" data-metrica="total">
            <span class="rend-valor" id="rend-total">—</span>
            <span class="rend-etiqueta">Solicitudes registradas</span>
        </div>
        <div class="tarjeta-rendimiento" data-metrica="promedio">
            <span class="rend-valor" id="rend-promedio">—</span>
            <span class="rend-etiqueta">Tiempo promedio</span>
        </div>
        <div class="tarjeta-rendimiento" data-metrica="mediana">
            <span class="rend-valor" id="rend-mediana">—</span>
            <span class="rend-etiqueta">Mediana</span>
        </div>
        <div class="tarjeta-rendimiento" data-metrica="p95">
            <span class="rend-valor" id="rend-p95">—</span>
            <span class="rend-etiqueta">Percentil 95</span>
            <span class="rend-sub" title="95% de solicitudes son mas rapidas que esto">95% mas rapidas</span>
        </div>
        <div class="tarjeta-rendimiento" data-metrica="p99">
            <span class="rend-valor" id="rend-p99">—</span>
            <span class="rend-etiqueta">Percentil 99</span>
            <span class="rend-sub" title="99% de solicitudes son mas rapidas que esto">99% mas rapidas</span>
        </div>
        <div class="tarjeta-rendimiento" data-metrica="maximo">
            <span class="rend-valor" id="rend-maximo">—</span>
            <span class="rend-etiqueta">Maximo</span>
        </div>
        <div class="tarjeta-rendimiento" data-metrica="lentos">
            <span class="rend-valor" id="rend-lentos">—</span>
            <span class="rend-etiqueta">Lentos (&gt;500ms)</span>
            <span class="rend-sub" id="rend-porcentaje-lentos"></span>
        </div>
        <div class="tarjeta-rendimiento" data-metrica="memoria">
            <span class="rend-valor" id="rend-memoria">—</span>
            <span class="rend-etiqueta">Memoria promedio</span>
        </div>
    </div>

    <div class="panel-columnas margen-superior-normal">
        <div class="panel-columna-principal">
            <div class="rend-barras" id="rend-distribucion">
                <h3 class="margen-inferior-normal texto-sm texto-negrita">Distribucion de tiempos de respuesta</h3>
                <div class="rend-barra-etiquetas">
                    <span>0-50ms</span>
                    <span>50-100ms</span>
                    <span>100-200ms</span>
                    <span>200-500ms</span>
                    <span>500ms+</span>
                </div>
                <div class="rend-barra-contenedor">
                    <div class="rend-barra" data-rango="0-50" style="width:0%"></div>
                    <div class="rend-barra" data-rango="50-100" style="width:0%"></div>
                    <div class="rend-barra" data-rango="100-200" style="width:0%"></div>
                    <div class="rend-barra" data-rango="200-500" style="width:0%"></div>
                    <div class="rend-barra rend-barra-lenta" data-rango="500+" style="width:0%"></div>
                </div>
                <div class="rend-barra-leyenda" id="rend-leyenda"></div>
            </div>
        </div>

        <div class="panel-columna-lateral">
            <section aria-label="Ultimas solicitudes">
                <h3 class="margen-inferior-normal texto-sm texto-negrita">Ultimas solicitudes</h3>
                <div id="rend-ultimos">
                    <p class="texto-xs texto-suave">Esperando datos...</p>
                </div>
            </section>
        </div>
    </div>
</section>

<script src="<?= URL_BASE ?>/src/js/modulos/rendimiento.js"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif;
