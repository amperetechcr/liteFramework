<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

$codigoError = $_GET['error'] ?? '';
$codigoMensaje = $_GET['mensaje'] ?? '';

$partial = $_GET['partial'] ?? '';
$tokenCSRF = SeguridadServidor::generarTokenAntiFalsificacion();
$esAdmin = ($idRol === 1);

$migraciones = [];
$total = 0;
$aplicadas = 0;
$pendientes = 0;
$error = '';
$sqlPendientes = [];
$respaldos = [];

if ($esAdmin) {
    try {
        $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $gestor = new GestorMigraciones($conexion);
        $migraciones = $gestor->listarTodas();
        $total = count($migraciones);
        $aplicadas = count(array_filter($migraciones, function ($m) {
            return $m['estado'] === 'aplicada';
        }));
        $pendientes = $total - $aplicadas;
        $sqlPendientes = $gestor->obtenerSqlPendientes();
        $respaldos = $gestor->listarRespaldos();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if ($esAjax && !$partial) {
    echo '<div data-titulo-pagina="Migraciones"></div>';
}

if ($partial === 'lista') {
?>
<div id="resultado-ejecucion" class="oculto margen-inferior-normal"></div>

<?php if ($error): ?>
<p class="texto-peligro"><?= htmlspecialchars($error) ?></p>
<?php elseif (empty($migraciones)): ?>
<p class="texto-suave">No hay migraciones disponibles.</p>
<?php else: ?>
<div class="agrupador-flexible-columnas">
    <?php foreach ($migraciones as $m):
        $esAplicada = $m['estado'] === 'aplicada';
        $clase = $esAplicada ? 'etiqueta-exito' : 'etiqueta-advertencia';
        $texto = $esAplicada ? 'Aplicada' : 'Pendiente';
    ?>
    <div class="evento-auditoria evento-auditoria-con-padding">
        <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm">
            <div class="agrupador-flexible-filas brecha-pequena">
                <span class="etiqueta <?= $clase ?>"><?= $texto ?></span>
                <span class="texto-negrita"><?= htmlspecialchars($m['archivo']) ?></span>
            </div>
            <div class="agrupador-flexible-filas brecha-pequena">
                <span class="texto-pequeno texto-suave"><?= $esAplicada ? htmlspecialchars($m['fecha']) : '&mdash;' ?></span>
                <button type="button" class="accion-boton variante-borde btn-ver-sql" data-archivo="<?= htmlspecialchars($m['archivo']) ?>" title="Ver SQL">SQL</button>
                <?php if (!$esAplicada): ?>
                <button type="button" class="accion-boton variante-primaria btn-ejecutar-individual" data-archivo="<?= htmlspecialchars($m['archivo']) ?>" title="Ejecutar esta migracion">Ejecutar</button>
                <?php else: ?>
                <button type="button" class="accion-boton variante-borde btn-resetear" data-archivo="<?= htmlspecialchars($m['archivo']) ?>" title="Resetear para re-aplicar">Resetear</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<span id="resumen-migraciones" data-total="<?= $total ?>" data-aplicadas="<?= $aplicadas ?>" data-pendientes="<?= $pendientes ?>" hidden></span>
<?php
    return;
}

if (!$esAjax) {
    $tituloPagina = 'Migraciones';
    $moduloActivo = 'migraciones';
    require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php';
}
?>

<?php if (!$esAdmin): ?>
<article>
    <p class="texto-peligro texto-negrita">Solo los administradores pueden gestionar migraciones.</p>
</article>
<?php else: ?>

<input type="hidden" name="token_peticion" value="<?= $tokenCSRF ?>">

<header class="agrupador-flexible-filas distribucion-espaciada margen-inferior-normal">
    <h1 class="margen-inferior-0">Migraciones <span class="etiqueta etiqueta-marca" id="contador-migraciones"><?= $total ?></span></h1>
    <div class="agrupador-flexible-filas brecha-pequena">
        <button type="button" id="boton-respaldo" class="accion-boton variante-borde">Respaldo</button>
        <button type="button" id="boton-ejecutar" class="accion-boton variante-primaria" <?= $pendientes === 0 ? 'disabled' : '' ?>>Ejecutar pendientes</button>
    </div>
</header>

<p class="margen-inferior-normal">
    <span id="resumen-aplicadas" class="texto-negrita"><?= $aplicadas ?></span> de <span id="resumen-total" class="texto-negrita"><?= $total ?></span> aplicadas
    <?php if ($pendientes > 0): ?>
    &middot; <span id="resumen-pendientes" class="texto-negrita"><?= $pendientes ?></span> pendiente(s)
    <?php endif; ?>
</p>

<div id="resultado-ejecucion" class="oculto margen-inferior-normal"></div>

<?php if ($sqlPendientes): ?>
<details class="margen-inferior-normal">
    <summary class="texto-negrita sumario-migraciones">
        Vista previa SQL: <?= count($sqlPendientes) ?> pendiente(s)
    </summary>
    <div id="preview-pendientes" class="margen-superior-normal">
        <?php foreach ($sqlPendientes as $sp): ?>
        <div class="preview-sql">
            <p class="texto-negrita margen-inferior-pequeno"><?= htmlspecialchars($sp['archivo']) ?></p>
            <pre><code><?= htmlspecialchars($sp['sql']) ?></code></pre>
        </div>
        <?php endforeach; ?>
    </div>
</details>
<?php endif; ?>

<div id="contenedor-lista-migraciones">
    <?php if ($error): ?>
    <p class="texto-peligro"><?= htmlspecialchars($error) ?></p>
    <?php elseif (empty($migraciones)): ?>
    <p class="texto-suave">No hay migraciones disponibles.</p>
    <?php else: ?>
    <div class="agrupador-flexible-columnas">
        <?php foreach ($migraciones as $m):
            $esAplicada = $m['estado'] === 'aplicada';
            $clase = $esAplicada ? 'etiqueta-exito' : 'etiqueta-advertencia';
            $texto = $esAplicada ? 'Aplicada' : 'Pendiente';
        ?>
        <div class="evento-auditoria evento-auditoria-con-padding">
            <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm">
                <div class="agrupador-flexible-filas brecha-pequena">
                    <span class="etiqueta <?= $clase ?>"><?= $texto ?></span>
                    <span class="texto-negrita"><?= htmlspecialchars($m['archivo']) ?></span>
                </div>
                <div class="agrupador-flexible-filas brecha-pequena">
                    <span class="texto-pequeno texto-suave"><?= $esAplicada ? htmlspecialchars($m['fecha']) : '&mdash;' ?></span>
                    <button type="button" class="accion-boton variante-borde btn-ver-sql" data-archivo="<?= htmlspecialchars($m['archivo']) ?>" title="Ver SQL">SQL</button>
                    <?php if (!$esAplicada): ?>
                    <button type="button" class="accion-boton variante-primaria btn-ejecutar-individual" data-archivo="<?= htmlspecialchars($m['archivo']) ?>" title="Ejecutar esta migracion">Ejecutar</button>
                    <?php else: ?>
                    <button type="button" class="accion-boton variante-borde btn-resetear" data-archivo="<?= htmlspecialchars($m['archivo']) ?>" title="Resetear para re-aplicar">Resetear</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <span id="resumen-migraciones" data-total="<?= $total ?>" data-aplicadas="<?= $aplicadas ?>" data-pendientes="<?= $pendientes ?>" hidden></span>
</div>

<section class="margen-superior-normal">
    <details open>
        <summary class="texto-negrita sumario-migraciones">
            Respaldos: <?= count($respaldos) ?> archivo(s)
        </summary>
        <div id="contenedor-respaldos" class="margen-superior-normal">
            <?php if (empty($respaldos)): ?>
            <p class="texto-suave">No hay respaldos disponibles.</p>
            <?php else: ?>
            <div class="agrupador-flexible-columnas">
                <?php foreach ($respaldos as $r): ?>
                <div class="evento-auditoria evento-auditoria-con-padding">
                    <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm">
                        <div>
                            <span class="texto-negrita"><?= htmlspecialchars($r['archivo']) ?></span>
                        </div>
                        <div class="agrupador-flexible-filas brecha-pequena">
                            <span class="texto-pequeno texto-suave"><?= htmlspecialchars($r['fecha']) ?></span>
                            <span class="texto-pequeno texto-suave"><?= htmlspecialchars($r['tamano_formato']) ?></span>
                            <button type="button" class="accion-boton variante-borde btn-descargar-respaldo" data-archivo="<?= htmlspecialchars($r['archivo']) ?>" title="Descargar respaldo">Descargar</button>
                            <button type="button" class="accion-boton variante-primaria btn-restaurar-respaldo" data-archivo="<?= htmlspecialchars($r['archivo']) ?>" title="Restaurar este respaldo">Restaurar</button>
                            <button type="button" class="accion-boton variante-peligro btn-eliminar-respaldo" data-archivo="<?= htmlspecialchars($r['archivo']) ?>" title="Eliminar respaldo">Eliminar</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </details>
</section>

<div id="modal-restaurar-respaldo" class="modal-superposicion" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-restaurar" hidden>
    <div class="ventana-confirmacion">
        <div class="modal-cabecera">
            <h2 id="titulo-modal-restaurar">Confirmar restauraci&oacute;n</h2>
            <button type="button" class="modal-cerrar-respaldo" aria-label="Cerrar">&times;</button>
        </div>
        <p id="mensaje-modal-restaurar" class="margen-inferior-normal"></p>
        <p class="texto-peligro texto-negrita texto-pequeno margen-inferior-normal">Esta acci&oacute;n reemplazar&aacute; TODA la base de datos actual. Se crear&aacute; un respaldo de seguridad autom&aacute;tico antes de restaurar.</p>
        <div class="modal-acciones">
            <button type="button" id="cancelar-restaurar" class="accion-boton variante-borde modal-cerrar-respaldo">Cancelar</button>
            <button type="button" id="confirmar-restaurar" class="accion-boton variante-peligro">Restaurar ahora</button>
        </div>
    </div>
</div>

<div id="modal-confirmar" class="modal-superposicion" role="dialog" aria-modal="true" aria-labelledby="titulo-modal" hidden>
    <div class="ventana-confirmacion">
        <div class="modal-cabecera">
            <h2 id="titulo-modal">Confirmar ejecuci&oacute;n</h2>
            <button type="button" class="modal-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <p id="mensaje-modal" class="margen-inferior-normal"></p>
        <p class="texto-peligro texto-pequeno margen-inferior-normal">Se crear&aacute; un respaldo de seguridad antes de ejecutar.</p>
        <div class="modal-acciones">
            <button type="button" id="cancelar-ejecutar" class="accion-boton variante-borde modal-cerrar">Cancelar</button>
            <button type="button" id="confirmar-ejecutar" class="accion-boton variante-peligro">Ejecutar ahora</button>
        </div>
    </div>
</div>

<div id="modal-sql" class="modal-superposicion" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-sql" hidden>
    <div class="ventana-confirmacion ancho-max-800">
        <div class="modal-cabecera">
            <h2 id="titulo-modal-sql">Contenido SQL</h2>
            <button type="button" class="modal-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-sql-codigo">
            <pre><code id="codigo-sql"></code></pre>
        </div>
        <div class="modal-acciones margen-superior-normal">
            <button type="button" class="accion-boton variante-borde modal-cerrar">Cerrar</button>
        </div>
    </div>
</div>

<script src="<?= URL_BASE ?>/src/js/modulos/migraciones.js"></script>

<?php endif; ?>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; 