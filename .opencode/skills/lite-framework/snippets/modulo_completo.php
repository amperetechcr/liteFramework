<?php
require_once __DIR__ . '/../plantillas/modulo_cabecera.php';

$partial = $_GET['partial'] ?? '';
$tokenCSRF = SeguridadServidor::generarTokenAntiFalsificacion();

$categorias = [];
$totalCategorias = 0;
$paginaActual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$porPagina = 20;
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

try {
    $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();

    $condiciones = [];
    $parametros = [];

    if ($busqueda !== '') {
        $condiciones[] = "nombre LIKE :buscar";
        $parametros[':buscar'] = '%' . $busqueda . '%';
    }

    $clausulaWhere = !empty($condiciones) ? 'WHERE ' . implode(' AND ', $condiciones) : '';

    $sqlTotal = "SELECT COUNT(*) FROM categorias {$clausulaWhere}";
    $stmtTotal = $conexion->prepare($sqlTotal);
    foreach ($parametros as $clave => $valor) {
        $stmtTotal->bindValue($clave, $valor);
    }
    $stmtTotal->execute();
    $totalCategorias = (int)$stmtTotal->fetchColumn();

    $totalPaginas = max(1, (int)ceil($totalCategorias / $porPagina));
    if ($paginaActual > $totalPaginas) $paginaActual = $totalPaginas;
    $inicio = ($paginaActual - 1) * $porPagina;

    $sql = "SELECT * FROM categorias {$clausulaWhere} ORDER BY id DESC LIMIT :limite OFFSET :inicio";
    $consulta = $conexion->prepare($sql);
    foreach ($parametros as $clave => $valor) {
        $consulta->bindValue($clave, $valor);
    }
    $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $consulta->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $consulta->execute();
    $categorias = $consulta->fetchAll(PDO::FETCH_ASSOC);

    $paginador = Paginador::crear($totalCategorias, $porPagina);

} catch (PDOException $e) {
    $categorias = [];
    $paginador = Paginador::crear(0, $porPagina);
}

if ($esAjax && !$partial) {
    echo '<div data-titulo-pagina="Categorías"></div>';
}

if ($partial === 'lista') {
?>
<article>
    <h3 class="margen-inferior-normal">Listado</h3>
    <?php if (empty($categorias)): ?>
    <p class="texto-pequeno texto-peligro">No hay categorías registradas.</p>
    <?php else: ?>
    <div class="rejilla-automatica">
        <?php foreach ($categorias as $cat): ?>
        <div class="tarjeta">
            <p class="texto-negrita"><?= htmlspecialchars($cat['nombre']) ?></p>
            <p class="texto-pequeno texto-suave">ID: <?= htmlspecialchars($cat['id']) ?></p>
            <button type="button" class="accion-boton variante-texto">Editar</button>
        </div>
        <?php endforeach; ?>
    </div>
    <?= $paginador->render() ?>
    <?php endif; ?>
</article>
<?php
    return;
}

if (!$esAjax) {
    $tituloPagina = 'Categorías';
    $moduloActivo = 'categorias';
    require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php';
}
?>

<section aria-label="Gestión de categorías">
    <h2 class="margen-inferior-normal">Categorías</h2>

    <article class="margen-inferior-normal">
        <form id="formularioCategoria" method="POST">
            <input type="hidden" name="token_peticion" value="<?= $tokenCSRF ?>">
            <input type="hidden" name="accion_crud" value="registrar">
            <input type="hidden" name="tabla_destino" value="categorias">

            <div class="grupo-campo campo-agrupado">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>

            <button type="submit" class="ancho-completo-sm">Guardar</button>
        </form>
    </article>

    <div id="contenedor-lista-categorias">
        <article>
            <h3 class="margen-inferior-normal">Listado</h3>
            <?php if (empty($categorias)): ?>
            <p class="texto-pequeno texto-peligro">No hay categorías registradas.</p>
            <?php else: ?>
            <div class="rejilla-automatica">
                <?php foreach ($categorias as $cat): ?>
                <div class="tarjeta">
                    <p class="texto-negrita"><?= htmlspecialchars($cat['nombre']) ?></p>
                    <p class="texto-pequeno texto-suave">ID: <?= htmlspecialchars($cat['id']) ?></p>
                    <button type="button" class="accion-boton variante-texto">Editar</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?= $paginador->render() ?>
            <?php endif; ?>
        </article>
    </div>
</section>

<script src="<?= URL_BASE ?>/src/js/modulos/categorias.js"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; ?>
