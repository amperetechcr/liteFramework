<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

$tokenCSRF = SeguridadServidor::generarTokenAntiFalsificacion();

$paletaActual    = configUI('paleta') ?? 'indigo';
$estiloActual    = configUI('estilo') ?? 'moderno';
$radioActual     = configUI('radio') ?? 'normal';
$animacionActual = configUI('animacion') ?? 'normal';
$fuenteActual    = configUI('fuente') ?? 'sistema';
$espaciadoActual = configUI('espaciado') ?? 'normal';
$tamanoActual    = configUI('tamano') ?? 'normal';
$grosorActual    = configUI('grosor') ?? 'normal';
$trazoActual     = configUI('trazo') ?? 'normal';
$sombraActual    = configUI('sombra') ?? 'normal';
$fondoActual     = configUI('fondo') ?? 'blanco';
$texturaActual    = configUI('textura') ?? 'ninguna';

if ($esAjax) {
    echo '<div data-titulo-pagina="Apariencia"></div>';
}
?>
<?php if (!$esAjax): $tituloPagina = 'Apariencia'; $moduloActivo = 'apariencia'; require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php'; endif; ?>

<div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm margen-inferior-normal">
    <h1 class="margen-inferior-0">Apariencia</h1>
    <div class="agrupador-flexible-filas brecha-pequena">
        <button type="button" id="btn-restablecer" class="accion-boton variante-borde" data-tamano="pequeno">Restablecer</button>
        <button type="button" id="btn-guardar-servidor" class="accion-boton variante-solida" data-tamano="pequeno">Guardar</button>
    </div>
</div>

<input type="hidden" name="token_peticion" value="<?= h($tokenCSRF) ?>">

<div class="rejilla-automatica">

    <section aria-label="Modo">
        <article>
            <h3 class="margen-inferior-normal">Modo</h3>
            <div class="apariencia-tema-toggle">
                <button type="button" class="apariencia-tema-btn activo" data-tema="claro" id="btn-tema-claro">&#9788; Claro</button>
                <button type="button" class="apariencia-tema-btn" data-tema="oscuro" id="btn-tema-oscuro">&#9790; Oscuro</button>
            </div>
        </article>
    </section>

    <section aria-label="Color">
        <article>
            <h3 class="margen-inferior-normal">Color</h3>
            <div class="apariencia-colores" id="selector-paleta">
                <?php
                $colores = [
                    'indigo' => '#4f46e5', 'azul' => '#2563eb', 'esmeralda' => '#059669',
                    'rosa' => '#db2777', 'ambar' => '#d97706', 'violeta' => '#7c3aed',
                    'pizarra' => '#475569', 'cereza' => '#dc2626', 'cielo' => '#0891b2',
                    'teal' => '#0d9488', 'lima' => '#65a30d', 'naranja' => '#ea580c', 'fucsia' => '#c026d3',
                ];
                foreach ($colores as $id => $color):
                ?>
                <button type="button" class="apariencia-color <?= $id === $paletaActual ? 'activo' : '' ?>"
                    data-paleta="<?= $id ?>" style="background:<?= $color ?>">
                    <?= $id === $paletaActual ? '&#10003;' : '' ?>
                </button>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section aria-label="Disenos">
        <article>
            <h3 class="margen-inferior-normal">Dise&ntilde;os</h3>
            <div class="apariencia-pills" id="selector-estilo">
                <?php
                $estilos = ['moderno' => 'Moderno',
                    'glass' => 'Vidrio', 'neo' => 'Relieve', 'neon' => 'Neon',
                    'cyber' => 'Ciberpunk', 'brutal' => 'Brutalista', 'vapor' => 'Vaporwave',
                    'cosmic' => 'Cosmico', 'organic' => 'Organico', 'material' => 'Material',
                    'liquid' => 'Liquido', 'pixel' => 'Pixelado', 'mesh' => 'Degradado',
                    'clay' => 'Arcilla', 'academia' => 'Academia Oscura', 'minimal' => 'Ultra Minimal'];
                foreach ($estilos as $id => $nombre):
                ?>
                <button type="button" class="apariencia-pill <?= $id === $estiloActual ? 'activo' : '' ?>" data-estilo="<?= $id ?>"><?= $nombre ?></button>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section aria-label="Redondeado">
        <article>
            <h3 class="margen-inferior-normal">Redondeado</h3>
            <div class="apariencia-pills" id="selector-radio">
                <?php foreach (['ninguno' => 'Ninguno', 'sutil' => 'Sutil', 'normal' => 'Normal', 'redondeado' => 'Redondeado', 'circular' => 'Circular'] as $id => $nombre): ?>
                <button type="button" class="apariencia-pill <?= $id === $radioActual ? 'activo' : '' ?>" data-radio="<?= $id ?>"><?= $nombre ?></button>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section aria-label="Tipografia">
        <article>
            <h3 class="margen-inferior-normal">Tipografia</h3>
            <select id="selector-fuente" style="width:100%">
                <?php
                $fuentes = ['sistema' => 'Sistema', 'sans' => 'Moderna', 'serif' => 'Clasica',
                    'mono' => 'Monospace', 'escritura' => 'Manuscrita', 'humanista' => 'Humanista', 'decorativa' => 'Decorativa'];
                foreach ($fuentes as $id => $nombre):
                ?>
                <option value="<?= $id ?>" <?= $id === $fuenteActual ? 'selected' : '' ?>><?= $nombre ?></option>
                <?php endforeach; ?>
            </select>
            <p class="apariencia-font-preview" id="preview-fuente">Aa Bb Cc Dd 123 — El zorro marron salta sobre el perro perezoso.</p>
        </article>
    </section>

    <section aria-label="Tamano de letra">
        <article>
            <h3 class="margen-inferior-normal">Tamano de letra</h3>
            <div class="apariencia-pills" id="selector-tamano">
                <?php foreach (['muy-pequeno' => 'Muy pequena', 'pequeno' => 'Pequena', 'normal' => 'Normal', 'grande' => 'Grande', 'muy-grande' => 'Muy grande'] as $id => $nombre): ?>
                <button type="button" class="apariencia-pill <?= $id === $tamanoActual ? 'activo' : '' ?>" data-tamano="<?= $id ?>"><?= $nombre ?></button>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section aria-label="Espaciado">
        <article>
            <h3 class="margen-inferior-normal">Espaciado</h3>
            <div class="apariencia-pills" id="selector-espaciado">
                <?php foreach (['muy-estrecho' => 'Muy estrecho', 'estrecho' => 'Estrecho', 'normal' => 'Normal', 'amplio' => 'Amplio', 'muy-amplio' => 'Muy amplio'] as $id => $nombre): ?>
                <button type="button" class="apariencia-pill <?= $id === $espaciadoActual ? 'activo' : '' ?>" data-espaciado="<?= $id ?>"><?= $nombre ?></button>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section aria-label="Animaciones">
        <article>
            <h3 class="margen-inferior-normal">Animaciones</h3>
            <div class="apariencia-pills" id="selector-animacion">
                <?php foreach (['instantaneo' => 'Instantaneo', 'rapido' => 'Rapido', 'normal' => 'Normal', 'lento' => 'Lento'] as $id => $nombre): ?>
                <button type="button" class="apariencia-pill <?= $id === $animacionActual ? 'activo' : '' ?>" data-animacion="<?= $id ?>"><?= $nombre ?></button>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section aria-label="Grosor de fuente">
        <article>
            <h3 class="margen-inferior-normal">Grosor de fuente</h3>
            <div class="apariencia-pills" id="selector-grosor">
                <?php foreach (['delgado' => 'Delgado', 'ligero' => 'Ligero', 'normal' => 'Normal', 'seminegrita' => 'Seminegrita', 'negrita' => 'Negrita'] as $id => $nombre): ?>
                <button type="button" class="apariencia-pill <?= $id === $grosorActual ? 'activo' : '' ?>" data-grosor="<?= $id ?>"><?= $nombre ?></button>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section aria-label="Bordes">
        <article>
            <h3 class="margen-inferior-normal">Bordes</h3>
            <div class="apariencia-pills" id="selector-trazo">
                <?php foreach (['fino' => 'Fino', 'sutil' => 'Sutil', 'normal' => 'Normal', 'grueso' => 'Grueso', 'muy-grueso' => 'Muy grueso'] as $id => $nombre): ?>
                <button type="button" class="apariencia-pill <?= $id === $trazoActual ? 'activo' : '' ?>" data-trazo="<?= $id ?>"><?= $nombre ?></button>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section aria-label="Sombras">
        <article>
            <h3 class="margen-inferior-normal">Sombras</h3>
            <div class="apariencia-pills" id="selector-sombra">
                <?php foreach (['ninguna' => 'Ninguna', 'sutil' => 'Sutil', 'normal' => 'Normal', 'marcada' => 'Marcada', 'pronunciada' => 'Pronunciada'] as $id => $nombre): ?>
                <button type="button" class="apariencia-pill <?= $id === $sombraActual ? 'activo' : '' ?>" data-sombra="<?= $id ?>"><?= $nombre ?></button>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section aria-label="Color de fondo">
        <article>
            <h3 class="margen-inferior-normal">Color de fondo</h3>
            <div class="apariencia-colores" id="selector-fondo">
                <?php
                $fondos = ['blanco', 'lavanda', 'rosa', 'melon', 'cielo', 'menta', 'arena', 'lila', 'selva', 'medianoche', 'carmesi', 'bosque', 'marino', 'carbon', 'vino', 'azabache'];
                foreach ($fondos as $id):
                ?>
                <button type="button" class="apariencia-color <?= $id === $fondoActual ? 'activo' : '' ?>" data-fondo="<?= $id ?>">
                    <?= $id === $fondoActual ? '&#10003;' : '' ?>
                </button>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section aria-label="Efectos y texturas">
        <article>
            <h3 class="margen-inferior-normal">Efectos y texturas</h3>
            <div class="apariencia-pills" id="selector-textura">
                <?php foreach (['ninguna' => 'Ninguna', 'punto' => 'Punto', 'linea' => 'Linea', 'cuadricula' => 'Cuadricula', 'grain' => 'Grano'] as $id => $nombre): ?>
                <button type="button" class="apariencia-pill <?= $id === $texturaActual ? 'activo' : '' ?>" data-textura="<?= $id ?>"><?= $nombre ?></button>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

</div>

<script src="<?= URL_BASE ?>/src/js/modulos/apariencia.js"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; 