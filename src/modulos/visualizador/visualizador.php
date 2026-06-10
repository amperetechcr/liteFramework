<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

$agentes = [
    ["id" => 1,  "nombre" => "Valeria",  "iniciales" => "VL", "rol" => "Tech Lead",      "color" => "#6366f1"],
    ["id" => 2,  "nombre" => "Santiago", "iniciales" => "SG", "rol" => "Backend",         "color" => "#22c55e"],
    ["id" => 3,  "nombre" => "Camila",   "iniciales" => "CM", "rol" => "Frontend",        "color" => "#f59e0b"],
    ["id" => 4,  "nombre" => "Andres",   "iniciales" => "AN", "rol" => "DevOps",          "color" => "#ef4444"],
    ["id" => 5,  "nombre" => "Mariana",  "iniciales" => "MA", "rol" => "QA",              "color" => "#8b5cf6"],
    ["id" => 6,  "nombre" => "Felipe",   "iniciales" => "FE", "rol" => "Data",            "color" => "#06b6d4"],
    ["id" => 7,  "nombre" => "Gabriela", "iniciales" => "GA", "rol" => "UX",              "color" => "#ec4899"],
    ["id" => 8,  "nombre" => "Tomas",    "iniciales" => "TO", "rol" => "Backend",         "color" => "#3b82f6"],
    ["id" => 9,  "nombre" => "Isabella", "iniciales" => "IS", "rol" => "Frontend",        "color" => "#14b8a6"],
    ["id" => 10, "nombre" => "Diego",    "iniciales" => "DI", "rol" => "DevOps",          "color" => "#f97316"],
    ["id" => 11, "nombre" => "Sofia",    "iniciales" => "SO", "rol" => "Seguridad",       "color" => "#dc2626"],
    ["id" => 12, "nombre" => "Mateo",    "iniciales" => "MT", "rol" => "QA",              "color" => "#a855f7"],
    ["id" => 13, "nombre" => "Lucia",    "iniciales" => "LU", "rol" => "Data",            "color" => "#0ea5e9"],
    ["id" => 14, "nombre" => "Javier",   "iniciales" => "JA", "rol" => "Backend",         "color" => "#84cc16"],
    ["id" => 15, "nombre" => "Daniela",  "iniciales" => "DA", "rol" => "Frontend",        "color" => "#e11d48"],
    ["id" => 16, "nombre" => "Pablo",    "iniciales" => "PA", "rol" => "DevOps",          "color" => "#2563eb"],
    ["id" => 17, "nombre" => "Laura",    "iniciales" => "LA", "rol" => "Tech Lead",       "color" => "#7c3aed"],
    ["id" => 18, "nombre" => "Cristobal","iniciales" => "CR", "rol" => "Backend",         "color" => "#059669"],
    ["id" => 19, "nombre" => "Renata",   "iniciales" => "RE", "rol" => "UX",              "color" => "#db2777"],
    ["id" => 20, "nombre" => "Emilio",   "iniciales" => "EM", "rol" => "QA",              "color" => "#d97706"],
    ["id" => 21, "nombre" => "Valentina","iniciales" => "VA", "rol" => "Data",            "color" => "#0891b2"],
    ["id" => 22, "nombre" => "Benjamin", "iniciales" => "BE", "rol" => "Frontend",        "color" => "#4f46e5"],
    ["id" => 23, "nombre" => "Martina",  "iniciales" => "MA", "rol" => "Seguridad",       "color" => "#b91c1c"],
    ["id" => 24, "nombre" => "Nicolas",  "iniciales" => "NI", "rol" => "Backend",         "color" => "#65a30d"],
    ["id" => 25, "nombre" => "Florencia","iniciales" => "FL", "rol" => "DevOps",          "color" => "#0f766e"],
    ["id" => 26, "nombre" => "Agustin",  "iniciales" => "AG", "rol" => "QA",              "color" => "#9333ea"],
    ["id" => 27, "nombre" => "Victoria", "iniciales" => "VI", "rol" => "Data",            "color" => "#0284c7"],
    ["id" => 28, "nombre" => "Sebastian","iniciales" => "SE", "rol" => "Frontend",        "color" => "#ca8a04"],
    ["id" => 29, "nombre" => "Antonella","iniciales" => "AT", "rol" => "Backend",         "color" => "#1d4ed8"],
];
$total = count($agentes);
?>
<?php if (!$esAjax): $tituloPagina = "Oficina"; $moduloActivo = "visualizador"; require DIRECTORIO_RAIZ . "/src/plantillas/encabezado.php"; endif; ?>

<style>
.visualizador-oficina {
    display: flex;
    gap: var(--espacio-mediano);
    align-items: flex-start;
}
.canvas-contenedor {
    border: 1px solid var(--trazo-suave);
    border-radius: var(--radio-redondeado);
    overflow: hidden;
    background: var(--fondo-elemento);
    box-shadow: var(--sombra-pequena);
}
#canvas-oficina { display: block; }
.panel-lateral-oficina {
    min-width: 220px;
    max-width: 260px;
    padding: var(--espacio-normal);
    background: var(--fondo-elemento);
    border: 1px solid var(--trazo-suave);
    border-radius: var(--radio-redondeado);
    font-size: var(--tamano-sm);
}
.panel-lateral-oficina h3 {
    font-size: var(--tamano-base);
    margin-bottom: var(--espacio-normal);
    padding-bottom: var(--espacio-pequeno);
    border-bottom: 1px solid var(--trazo-suave);
}
.panel-agente {
    display: flex;
    align-items: center;
    gap: var(--espacio-pequeno);
    padding: var(--espacio-minimo) 0;
}
.panel-agente .iniciales {
    width: 28px; height: 28px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: #fff;
    flex-shrink: 0;
}
.panel-agente .nombre { flex: 1; }
.panel-agente .accion { font-size: 16px; }
.panel-contadores {
    display: flex; gap: var(--espacio-pequeno);
    margin: var(--espacio-normal) 0;
    flex-wrap: wrap;
}
.contador {
    flex: 1; min-width: 60px;
    text-align: center;
    padding: var(--espacio-minimo) var(--espacio-pequeno);
    background: var(--fondo-superficie);
    border-radius: var(--radio-pequeno);
}
.contador .numero { font-size: var(--tamano-xl); font-weight: 700; display: block; }
.contador .etiqueta { font-size: var(--tamano-xs); color: var(--texto-suave); }
.log-actividad {
    margin-top: var(--espacio-normal);
    padding-top: var(--espacio-pequeno);
    border-top: 1px solid var(--trazo-suave);
}
.log-item {
    font-size: var(--tamano-xs);
    color: var(--texto-suave);
    padding: 2px 0;
}
.log-item strong { color: var(--texto-principal); }
</style>

<div class="visualizador-oficina">
    <div class="canvas-contenedor">
        <canvas id="canvas-oficina" width="720" height="500"></canvas>
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
<script src="<?= URL_BASE ?>/src/js/modulos/visualizador.js"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . "/src/plantillas/pie.php"; endif; ?>
