<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

$partial = $_GET['partial'] ?? '';
$tokenCSRF = SeguridadServidor::generarTokenAntiFalsificacion();

$rolesDisponibles = [];
$operadores = [];
$totalOperadores = 0;
$totalPaginasOperadores = 1;
$paginaActual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$operadoresPorPagina = 10;

$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$filtroRol = isset($_GET['rol']) ? (int)$_GET['rol'] : 0;
$filtroEstado = $_GET['estado'] ?? '';

try {
    $rolesDisponibles = array_map(fn($r) => $r->aArreglo(), Rol::todos());

    $resultado = Operador::listarConFiltros($busqueda, $filtroRol, $filtroEstado, $paginaActual, $operadoresPorPagina);
    $operadores = $resultado['operadores'];
    $totalOperadores = $resultado['total'];
    $totalPaginasOperadores = $resultado['total_paginas'];
    $paginaActual = $resultado['pagina'];

    $paginador = Paginador::crear($totalOperadores, $operadoresPorPagina);

} catch (PDOException $e) {
    RegistroAuditoria::error('Operadores', 'Error al cargar listado', [
        'error' => $e->getMessage(),
    ]);
    $rolesDisponibles = [['id_rol' => 1, 'nombre_rol' => 'Super Administrador']];
    $operadores = [];
    $paginador = Paginador::crear(0, $operadoresPorPagina);
}
$totalActivos = 0;
$totalSuspendidos = 0;
try {
    $totalActivos = Operador::contarActivos();
    $totalSuspendidos = Operador::contarSuspendidos();
} catch (Exception $e) {}

if ($esAjax && !$partial) {
    echo '<div data-titulo-pagina="Operadores"></div>';
}

// Partial: solo la lista (usado por AJAX para actualizar sin recargar página)
if ($partial === 'lista') {
?>
<article>
    <h3 class="margen-inferior-normal">Listado</h3>

    <?php if (empty($operadores)): ?>
    <p class="texto-pequeno texto-peligro">No hay operadores que coincidan con los filtros aplicados.</p>
    <?php else: ?>
    <div class="agrupador-flexible-columnas">
        <?php foreach ($operadores as $op):
            $estadoClase = (int)$op['estado_cuenta'] === 1 ? 'etiqueta-exito' : 'etiqueta-peligro';
            $estadoTexto = (int)$op['estado_cuenta'] === 1 ? 'activo' : 'suspendido';
        ?>
        <div class="operador-tarjeta operador-tarjeta-con-padding"
            data-id="<?= $op['id_operador'] ?>"
            data-nombre="<?= htmlspecialchars($op['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>"
            data-correo="<?= htmlspecialchars($op['correo_electronico'], ENT_QUOTES, 'UTF-8') ?>"
            data-rol-texto="<?= htmlspecialchars($op['nombre_rol'], ENT_QUOTES, 'UTF-8') ?>"
            data-rol-id="<?= $op['id_rol'] ?? '1' ?>"
            data-estado="<?= $estadoTexto ?>">
            <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm texto-centro-sm margen-inferior-pequeno">
                <div>
                    <p class="texto-negrita"><?= htmlspecialchars($op['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="texto-pequeno texto-suave"><?= htmlspecialchars($op['correo_electronico'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="agrupador-flexible-filas flex-envolver brecha-pequena">
                    <span class="etiqueta etiqueta-marca"><?= htmlspecialchars($op['nombre_rol'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="etiqueta <?= $estadoClase ?>"><?= $estadoTexto ?></span>
                </div>
            </div>
                        <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm texto-centro-sm">
                            <span class="texto-pequeno texto-suave">#<?= $op['id_operador'] ?> &middot; <?= Fecha::formatear($op['fecha_registro'], 'd/m/Y') ?></span>
                            <div class="agrupador-flexible-filas brecha-pequena">
                                <button type="button" class="accion-boton variante-borde tamano-pequeno boton-editar" data-id="<?= $op['id_operador'] ?>">Editar</button>
                                <?php if ((int)$op['estado_cuenta'] === 1): ?>
                                <button type="button" class="accion-boton variante-peligro tamano-pequeno boton-suspender" data-id="<?= $op['id_operador'] ?>" data-nombre="<?= htmlspecialchars($op['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>">Suspender</button>
                                <?php else: ?>
                                <button type="button" class="accion-boton variante-exito tamano-pequeno boton-activar" data-id="<?= $op['id_operador'] ?>" data-nombre="<?= htmlspecialchars($op['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>">Activar</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPaginasOperadores > 1): ?>
    <?= $paginador->render() ?>
    <?php endif; ?>
    <?php endif; ?>
</article>
<span id="total-operadores-partial" data-total="<?= $totalOperadores ?>" hidden></span>
<span id="total-activos-partial" data-total="<?= $totalActivos ?>" hidden></span>
<span id="total-suspendidos-partial" data-total="<?= $totalSuspendidos ?>" hidden></span>
<?php
    return;
}

if (!$esAjax) {
    $tituloPagina = 'Operadores';
    $moduloActivo = 'operadores';
    require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php';
}
?>

<section aria-label="Gestion de operadores">
    <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm texto-centro-sm margen-inferior-normal">
        <h1 class="margen-inferior-0 texto-2xl">Operadores <span class="etiqueta etiqueta-marca" id="contador-operadores"><?= $totalOperadores ?></span></h1>
        <button type="button" id="boton-nuevo-operador" class="accion-boton variante-primaria">+ Nuevo operador</button>
    </div>

    <section aria-label="Resumen" class="margen-inferior-normal">
        <div class="rejilla-automatica">
            <article class="alineacion-centrada relleno-normal">
                <p class="texto-xl texto-negrita color-exito" id="total-activos"><?= $totalActivos ?></p>
                <p class="texto-xs texto-suave">Activos</p>
            </article>
            <article class="alineacion-centrada relleno-normal">
                <p class="texto-xl texto-negrita color-peligro" id="total-suspendidos"><?= $totalSuspendidos ?></p>
                <p class="texto-xs texto-suave">Suspendidos</p>
            </article>
            <article class="alineacion-centrada relleno-normal">
                <p class="texto-xl texto-negrita" id="total-operadores-resumen"><?= $totalOperadores ?></p>
                <p class="texto-xs texto-suave">Total</p>
            </article>
        </div>
    </section>

    <section aria-label="Listado de operadores">
        <article class="relleno-normal margen-inferior-normal">
            <div class="agrupador-flexible-filas flex-envolver brecha-normal flex-fin">
                <div class="grupo-campo campo-agrupado flex-1 ancho-min-200">
                    <label for="filtro-buscar">Buscar</label>
                    <input type="search" id="filtro-buscar" placeholder="Nombre o correo..." value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="grupo-campo campo-agrupado ancho-min-150">
                    <label for="filtro-rol">Rol</label>
                    <select id="filtro-rol">
                        <option value="0">Todos</option>
                        <?php foreach ($rolesDisponibles as $rol): ?>
                        <option value="<?= $rol['id_rol'] ?>" <?= ($filtroRol === (int)$rol['id_rol']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rol['nombre_rol'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grupo-campo campo-agrupado ancho-min-150">
                    <label for="filtro-estado">Estado</label>
                    <select id="filtro-estado">
                        <option value="">Todos</option>
                        <option value="1" <?= ($filtroEstado === '1') ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= ($filtroEstado === '0') ? 'selected' : '' ?>>Suspendido</option>
                    </select>
                </div>
            </div>
        </article>

        <div id="contenedor-lista-operadores">
            <article>
                <h3 class="margen-inferior-normal">Listado</h3>

                <?php if (empty($operadores)): ?>
                <p class="texto-pequeno texto-peligro">No hay operadores registrados.</p>
                <?php else: ?>
                <div class="agrupador-flexible-columnas">
                    <?php foreach ($operadores as $op):
                        $estadoClase = (int)$op['estado_cuenta'] === 1 ? 'etiqueta-exito' : 'etiqueta-peligro';
                        $estadoTexto = (int)$op['estado_cuenta'] === 1 ? 'activo' : 'suspendido';
                        $esAdmin = (int)$op['id_rol'] === 1;
                    ?>
                    <div class="operador-tarjeta operador-tarjeta-con-padding"
                        data-id="<?= $op['id_operador'] ?>"
                        data-nombre="<?= htmlspecialchars($op['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>"
                        data-correo="<?= htmlspecialchars($op['correo_electronico'], ENT_QUOTES, 'UTF-8') ?>"
                        data-rol-texto="<?= htmlspecialchars($op['nombre_rol'], ENT_QUOTES, 'UTF-8') ?>"
                        data-rol-id="<?= $op['id_rol'] ?? '1' ?>"
                        data-estado="<?= $estadoTexto ?>">
                        <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm texto-centro-sm margen-inferior-pequeno">
                            <div>
                                <p class="texto-negrita"><?= htmlspecialchars($op['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="texto-pequeno texto-suave"><?= htmlspecialchars($op['correo_electronico'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="agrupador-flexible-filas flex-envolver brecha-pequena">
                                <span class="etiqueta etiqueta-marca"><?= htmlspecialchars($op['nombre_rol'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="etiqueta <?= $estadoClase ?>"><?= $estadoTexto ?></span>
                            </div>
                        </div>
                        <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm texto-centro-sm">
                            <span class="texto-pequeno texto-suave">#<?= $op['id_operador'] ?> &middot; <?= Fecha::formatear($op['fecha_registro'], 'd/m/Y') ?></span>
                            <div class="agrupador-flexible-filas brecha-pequena">
                                <button type="button" class="accion-boton variante-borde tamano-pequeno boton-editar" data-id="<?= $op['id_operador'] ?>">Editar</button>
                                <?php if ((int)$op['estado_cuenta'] === 1): ?>
                                <button type="button" class="accion-boton variante-peligro tamano-pequeno boton-suspender" data-id="<?= $op['id_operador'] ?>" data-nombre="<?= htmlspecialchars($op['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>">Suspender</button>
                                <?php else: ?>
                                <button type="button" class="accion-boton variante-exito tamano-pequeno boton-activar" data-id="<?= $op['id_operador'] ?>" data-nombre="<?= htmlspecialchars($op['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>">Activar</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPaginasOperadores > 1): ?>
                <?= $paginador->render() ?>
                <?php endif; ?>
                <?php endif; ?>
            </article>
            <span id="total-operadores-partial" data-total="<?= $totalOperadores ?>" hidden></span>
        </div>
    </section>
</section>

<!-- Modal nuevo operador -->
<div id="modalNuevoOperador" class="modal-superposicion" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-nuevo" hidden>
    <div class="modal-contenido">
        <div class="modal-cabecera">
            <h2 id="titulo-modal-nuevo" class="margen-inferior-normal">Nuevo operador</h2>
            <button type="button" class="modal-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <form id="formularioRegistroOperador" class="agrupador-flexible-columnas" method="POST" novalidate>
            <input type="hidden" name="token_peticion" value="<?= $tokenCSRF ?>">
            <input type="hidden" name="accion_crud" value="registrar_operador">

            <div class="grupo-campo campo-agrupado">
                <label for="modal-nuevo-nombre">Nombre completo</label>
                <input type="text" id="modal-nuevo-nombre" name="nombre_completo" placeholder="Nombre del operador" required autocomplete="name">
            </div>

            <div class="grupo-campo campo-agrupado">
                <label for="modal-nuevo-correo">Correo electronico</label>
                <input type="email" id="modal-nuevo-correo" name="correo_electronico" placeholder="operador@dominio.com" required autocomplete="email">
            </div>

            <div class="grupo-campo campo-agrupado">
                <label for="modal-nuevo-clave">Contraseña</label>
                <input type="password" id="modal-nuevo-clave" name="clave_registro" placeholder="Min. 8 carac., 1 mayuscula, 1 numero, 1 simbolo" required autocomplete="new-password">
            </div>

            <div class="grupo-campo campo-agrupado">
                <label for="modal-nuevo-rol">Rol asignado</label>
                <select id="modal-nuevo-rol" name="id_rol">
                    <?php foreach ($rolesDisponibles as $rol): ?>
                    <option value="<?= $rol['id_rol'] ?>" <?= ($rol['id_rol'] === 1) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rol['nombre_rol'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="agrupador-flexible-filas flex-columna-sm brecha-normal">
                <button type="submit" class="ancho-completo-sm">Guardar operador</button>
                <button type="button" class="accion-boton variante-borde modal-cerrar ancho-completo-sm">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal editar operador -->
<div id="modalEditarOperador" class="modal-superposicion" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-editar" hidden>
    <div class="modal-contenido">
        <div class="modal-cabecera">
            <h2 id="titulo-modal-editar" class="margen-inferior-normal">Editar operador</h2>
            <button type="button" class="modal-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <form id="formularioEditarOperador" class="agrupador-flexible-columnas" method="POST" novalidate>
            <input type="hidden" name="token_peticion" value="<?= $tokenCSRF ?>">
            <input type="hidden" name="id_entidad" id="campo-id-operador">
            <input type="hidden" name="accion_crud" value="actualizar">
            <input type="hidden" name="tabla_destino" value="operador">

            <div class="grupo-campo campo-agrupado">
                <label for="modal-nombre">Nombre completo</label>
                <input type="text" id="modal-nombre" name="nombre_completo" required>
            </div>

            <div class="grupo-campo campo-agrupado">
                <label for="modal-correo">Correo electronico</label>
                <input type="email" id="modal-correo" name="correo_electronico" required>
            </div>

            <div class="grupo-campo campo-agrupado">
                <label for="modal-rol">Rol asignado</label>
                <select id="modal-rol" name="id_rol">
                    <?php foreach ($rolesDisponibles as $rol): ?>
                    <option value="<?= $rol['id_rol'] ?>">
                        <?= htmlspecialchars($rol['nombre_rol'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grupo-campo campo-agrupado">
                <label for="modal-estado">Estado de cuenta</label>
                <select id="modal-estado" name="estado_cuenta">
                    <option value="1">Activo</option>
                    <option value="0">Suspendido</option>
                </select>
            </div>

            <div class="agrupador-flexible-filas flex-columna-sm brecha-normal">
                <button type="submit" class="ancho-completo-sm">Guardar cambios</button>
                <button type="button" class="accion-boton variante-borde modal-cerrar ancho-completo-sm">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal confirmar suspender/activar -->
<div id="modalConfirmarEstado" class="modal-superposicion" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-confirmar" hidden>
    <div class="ventana-confirmacion">
        <div class="modal-cabecera">
            <h2 id="titulo-modal-confirmar">Confirmar acción</h2>
            <button type="button" class="modal-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <p id="mensaje-confirmar-estado" class="margen-inferior-normal"></p>
        <div class="modal-acciones">
            <button type="button" class="accion-boton variante-borde modal-cerrar">Cancelar</button>
            <button type="button" id="confirmar-cambio-estado" class="accion-boton variante-peligro">Confirmar</button>
        </div>
    </div>
</div>

<script src="<?= URL_BASE ?>/src/js/modulos/operadores.js"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; 