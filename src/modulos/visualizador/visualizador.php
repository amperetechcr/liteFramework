<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

$configFile = __DIR__ . '/../../../servidor/crewai/visualizador_config.json';
$config = json_decode(@file_get_contents($configFile) ?: '{}', true) ?: [];
$agentes = $config['agentes'] ?? [];
$total = count($agentes);
?>
<?php if (!$esAjax): $tituloPagina = "Oficina"; $moduloActivo = "visualizador"; require DIRECTORIO_RAIZ . "/src/plantillas/encabezado.php"; endif; ?>

<link rel="stylesheet" href="<?= URL_BASE ?>/src/css/visualizador.css?v=<?= filemtime(__DIR__ . '/../../css/visualizador.css') ?>">

<div class="visualizador-oficina">
    <div class="canvas-contenedor">
        <canvas id="canvas-oficina"></canvas>
        <div id="oficina-svg-container"><?php
            $svgFile = __DIR__ . '/../../../servidor/crewai/oficina.svg';
            if (file_exists($svgFile)) {
                echo file_get_contents($svgFile);
            }
        ?></div>
    </div>
    <aside class="panel-lateral-oficina" id="panel-lateral-oficina">
        <h3>&#127961; PixelAmpCrew</h3>
        <div id="panel-agentes"></div>
        <div class="panel-contadores" id="panel-contadores"></div>
        <div class="log-actividad" id="log-actividad">
            <p class="texto-xs texto-seminegrita margen-inferior-pequeno">&#128203; Actividad reciente</p>
            <div id="log-lista"></div>
        </div>
    </aside>
</div>

<script>
window.PIXELAMP_DATA = <?= json_encode([
    "agentes" => $agentes,
    "total" => $total,
    "URL_BASE" => URL_BASE,
]) ?>;
</script>
<script src="<?= URL_BASE ?>/src/js/modulos/visualizador.js?v=<?= filemtime(__DIR__ . '/../../../src/js/modulos/visualizador.js') ?>"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . "/src/plantillas/pie.php"; endif; ?>


