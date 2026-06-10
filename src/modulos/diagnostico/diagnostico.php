<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

use LiteFramework\Servicios\DiagnosticoError;
use LiteFramework\Servicios\ContextoError;
use LiteFramework\Servicios\RemediadorError;
use LiteFramework\Seguridad\RegistroAuditoria;

$tokenCSRF = SeguridadServidor::generarTokenAntiFalsificacion();
$resultadoDiagnostico = null;
$resultadoReparacion = null;
$errorMensaje = null;
$infoSistema = null;
$ultimosErrores = [];
$resumenAuditoria = null;

// Auto diagnostico al cargar
$ctx = ContextoError::capturar('diagnostico_auto', 'Diagnóstico automático del sistema', __FILE__, __LINE__);
$resultadoDiagnostico = DiagnosticoError::diagnosticar($ctx);
$infoSistema = $ctx->diagnosticoSistema;

// Historial de errores
try {
    $ultimosErrores = RegistroAuditoria::consultarEventos(null, null, 10, 0, null, null, 'ERROR');
    $resumenAuditoria = RegistroAuditoria::obtenerResumen();
} catch (\Throwable $e) {
    $ultimosErrores = [];
}

// Reparacion manual via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reparar']) && isset($_POST['tipo'])) {
    try {
        $tipo = htmlspecialchars($_POST['tipo'], ENT_QUOTES, 'UTF-8');
        $resultadoReparacion = RemediadorError::ejecutarReparacion($tipo, $_POST);
    } catch (\Throwable $e) {
        $errorMensaje = 'Error al ejecutar reparacion: ' . $e->getMessage();
    }
}

if ($esAjax) {
    echo '<div data-titulo-pagina="Diagnostico"></div>';
}
?>
<?php if (!$esAjax): $tituloPagina = 'Diagnostico'; $moduloActivo = 'diagnostico'; require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php'; endif; ?>

<link rel="stylesheet" href="<?= URL_BASE ?>/src/css/diagnostico.css">

<div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm margen-inferior-normal">
    <h1 class="margen-inferior-0">Diagnostico del Sistema</h1>
    <div class="agrupador-flexible-filas brecha-pequena">
        <button type="button" id="btn-exportar" class="accion-boton variante-borde" data-tamano="pequeno">Exportar JSON</button>
        <a href="?refrescar=1" class="accion-boton variante-solida" data-tamano="pequeno">Refrescar</a>
    </div>
</div>

<input type="hidden" name="token_peticion" value="<?= h($tokenCSRF) ?>">

<?php if ($errorMensaje): ?>
<div class="alerta alerta-peligro margen-inferior-normal"><?= h($errorMensaje) ?></div>
<?php endif; ?>

<?php if (!empty($resultadoDiagnostico['reparaciones'])): ?>
<div class="alerta" style="background:rgb(from var(--color-exito) r g b / 0.1);border-color:var(--color-exito);color:var(--color-exito);margin-bottom:var(--espacio-normal);">
    <strong>Reparaciones automaticas aplicadas:</strong>
    <ul style="margin:0.5rem 0 0 1rem;">
    <?php foreach ($resultadoDiagnostico['reparaciones'] as $r): ?>
        <li><?= h($r['mensaje'] ?? '') ?> (<?= h($r['verificador'] ?? '') ?>)</li>
    <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($resultadoReparacion): ?>
<div class="tarjeta margen-inferior-normal">
    <h3 class="margen-inferior-normal">Resultado de Reparacion</h3>
    <?php if (!empty($resultadoReparacion['exito'])): ?>
    <div class="alerta" style="background:rgb(from var(--color-exito) r g b / 0.1);border-color:var(--color-exito);color:var(--color-exito);">
        <?= h($resultadoReparacion['mensaje'] ?? 'Reparacion ejecutada correctamente.') ?>
    </div>
    <?php else: ?>
    <div class="alerta alerta-peligro">
        <?= h($resultadoReparacion['mensaje'] ?? 'No se pudo ejecutar la reparacion.') ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- SALUD DEL SISTEMA -->
<section class="margen-inferior-normal">
    <h2 class="margen-inferior-normal">Salud del Sistema</h2>
    <div class="diagnostico-grid">
        <div class="diagnostico-card">
            <div class="diagnostico-card-header">PHP</div>
            <div class="diagnostico-card-body">
                <div class="diagnostico-item"><span class="diag-label">Version</span><span class="diag-val"><?= h($infoSistema['php_version'] ?? '?') ?></span></div>
                <div class="diagnostico-item">
                    <span class="diag-label">Memoria</span>
                    <span class="diag-val"><?= h($infoSistema['memoria_usada_mb'] ?? '0') ?> MB / <?= h($infoSistema['memoria_limite'] ?? '?') ?></span>
                </div>
                <div class="diag-bar"><div class="diag-bar-fill" style="width:<?= min(100, round(($infoSistema['memoria_usada_mb'] ?? 0) / 128 * 100)) ?>%"></div></div>
                <div class="diagnostico-item"><span class="diag-label">Ejecucion</span><span class="diag-val"><?= h($infoSistema['tiempo_ejecucion'] ?? '?') ?>s</span></div>
                <div class="diagnostico-item"><span class="diag-label">Upload max</span><span class="diag-val"><?= h($infoSistema['upload_max'] ?? '?') ?></span></div>
            </div>
        </div>

        <div class="diagnostico-card">
            <div class="diagnostico-card-header">Disco</div>
            <div class="diagnostico-card-body">
                <div class="diagnostico-item"><span class="diag-label">Libre</span><span class="diag-val"><?= h($infoSistema['disco_libre_mb'] ?? '0') ?> MB</span></div>
                <div class="diagnostico-item"><span class="diag-label">Total</span><span class="diag-val"><?= h($infoSistema['disco_total_mb'] ?? '0') ?> MB</span></div>
                <?php $pctDisco = ($infoSistema['disco_total_mb'] ?? 1) > 0 ? round(($infoSistema['disco_libre_mb'] ?? 0) / ($infoSistema['disco_total_mb'] ?? 1) * 100) : 0; ?>
                <div class="diag-bar"><div class="diag-bar-fill diag-bar-verde" style="width:<?= $pctDisco ?>%"></div></div>
                <div class="diagnostico-item"><span class="diag-label">Tmp dir</span><span class="diag-val <?= !empty($infoSistema['tmp_dir_escribible']) ? 'diag-val-ok' : 'diag-val-error' ?>"><?= !empty($infoSistema['tmp_dir_escribible']) ? 'Accesible' : 'Problema' ?></span></div>
            </div>
        </div>

        <div class="diagnostico-card">
            <div class="diagnostico-card-header">Base de Datos</div>
            <div class="diagnostico-card-body">
                <?php $estadoBD = $ctx->estadoMySQL ?? 'desconocido'; ?>
                <div class="diagnostico-item">
                    <span class="diag-label">Estado</span>
                    <span class="diag-val <?= $estadoBD === 'ok' ? 'diag-val-ok' : 'diag-val-error' ?>">
                        <?= $estadoBD === 'ok' ? 'Conectado' : h($estadoBD) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="diagnostico-card">
            <div class="diagnostico-card-header">Extensiones</div>
            <div class="diagnostico-card-body">
                <?php foreach (($infoSistema['extensiones'] ?? []) as $ext => $ok): ?>
                <div class="diagnostico-item">
                    <span class="diag-label"><?= h($ext) ?></span>
                    <span class="diag-val <?= $ok ? 'diag-val-ok' : 'diag-val-error' ?>"><?= $ok ? 'OK' : 'Falta' ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="diagnostico-card">
            <div class="diagnostico-card-header">Auditoria</div>
            <div class="diagnostico-card-body">
                <?php $totalErrores = $resumenAuditoria['total'] ?? ($ultimosErrores ? count($ultimosErrores) : 0); ?>
                <div class="diagnostico-item"><span class="diag-label">Errores (24h)</span><span class="diag-val"><?= (int)$totalErrores ?></span></div>
                <div class="diagnostico-item"><span class="diag-label">Niveles</span><span class="diag-val"><?= implode(', ', RegistroAuditoria::obtenerNiveles()) ?></span></div>
            </div>
        </div>
    </div>
</section>

<!-- DIAGNOSTICOS ENCONTRADOS -->
<section class="margen-inferior-normal">
    <h2 class="margen-inferior-normal">Diagnosticos Encontrados (<?= count($resultadoDiagnostico['diagnosticos'] ?? []) ?>)</h2>
    
    <?php if (empty($resultadoDiagnostico['diagnosticos'])): ?>
    <div class="alerta" style="background:rgb(from var(--color-exito) r g b / 0.1);border-color:var(--color-exito);color:var(--color-exito);">
        No se encontraron problemas en el sistema.
    </div>
    <?php else: ?>
    <div class="diagnostico-grid">
        <?php foreach ($resultadoDiagnostico['diagnosticos'] as $diag): ?>
        <div class="diagnostico-card diagnostico-card-problema">
            <div class="diagnostico-card-header">
                <span class="etiqueta etiqueta-marca"><?= h($diag['tipo'] ?? '') ?></span>
                <span class="texto-pequeno texto-suave"><?= h($diag['verificador'] ?? '') ?></span>
            </div>
            <div class="diagnostico-card-body">
                <p><?= h($diag['detalle'] ?? '') ?></p>
                <?php if (!empty($diag['recomendacion'])): ?>
                <p class="diag-recomendacion"><?= h($diag['recomendacion']) ?></p>
                <?php endif; ?>
                <?php if ($resultadoDiagnostico['tieneRemedio'] ?? false): ?>
                <form method="post" class="margen-superior-pequeno">
                    <input type="hidden" name="token_peticion" value="<?= h($tokenCSRF) ?>">
                    <input type="hidden" name="reparar" value="1">
                    <input type="hidden" name="tipo" value="<?= h($diag['verificador'] ?? '') ?>">
                    <button type="submit" class="accion-boton variante-borde" data-tamano="pequeno">Reparar</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- SUGERENCIAS -->
<?php if (!empty($resultadoDiagnostico['sugerencias'])): ?>
<section class="margen-inferior-normal">
    <h2 class="margen-inferior-normal">Sugerencias</h2>
    <div class="diagnostico-grid">
        <?php foreach ($resultadoDiagnostico['sugerencias'] as $sug): ?>
        <div class="diagnostico-card">
            <div class="diagnostico-card-body">
                <p class="texto-pequeno"><?= nl2br(h($sug)) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- PRUEBAS DE ERROR -->
<section class="margen-inferior-normal">
    <h2 class="margen-inferior-normal">Pruebas de Error</h2>
    <div class="agrupador-flexible-filas brecha-normal">
        <a href="<?= URL_BASE ?>/probar-error.php?tipo=csrf" target="_blank" class="accion-boton variante-borde" data-tamano="pequeno">CSRF</a>
        <a href="<?= URL_BASE ?>/probar-error.php?tipo=sesion" target="_blank" class="accion-boton variante-borde" data-tamano="pequeno">Sesion</a>
        <a href="<?= URL_BASE ?>/probar-error.php?tipo=archivos" target="_blank" class="accion-boton variante-borde" data-tamano="pequeno">Archivos</a>
        <a href="<?= URL_BASE ?>/probar-error.php?tipo=deadlock" target="_blank" class="accion-boton variante-borde" data-tamano="pequeno">Deadlock</a>
        <a href="<?= URL_BASE ?>/probar-error.php?tipo=exception" target="_blank" class="accion-boton variante-borde" data-tamano="pequeno">Excepcion</a>
    </div>
</section>

<!-- HISTORIAL DE ERRORES -->
<?php if (!empty($ultimosErrores)): ?>
<section class="margen-inferior-normal">
    <h2 class="margen-inferior-normal">Ultimos Errores</h2>
    <div class="tarjeta" style="overflow-x:auto;">
        <table class="tabla diagnostico-tabla">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Nivel</th>
                    <th>Modulo</th>
                    <th>Accion</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimosErrores as $ev): ?>
                <tr>
                    <td class="texto-pequeno"><?= h($ev['fecha'] ?? '') ?></td>
                    <td><span class="etiqueta etiqueta-peligro"><?= h($ev['nivel'] ?? '') ?></span></td>
                    <td><?= h($ev['modulo'] ?? '') ?></td>
                    <td><?= h($ev['accion'] ?? '') ?></td>
                    <td class="texto-pequeno texto-suave"><?= h(mb_substr($ev['detalle'] ?? '', 0, 100)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<div id="export-data" style="display:none;"><?= h(json_encode([
    'sistema' => $infoSistema,
    'mysql' => $ctx->estadoMySQL ?? 'desconocido',
    'diagnosticos' => $resultadoDiagnostico['diagnosticos'] ?? [],
    'reparaciones' => $resultadoDiagnostico['reparaciones'] ?? [],
    'sugerencias' => $resultadoDiagnostico['sugerencias'] ?? [],
    'errores_24h' => $ultimosErrores,
    'fecha' => date('Y-m-d H:i:s'),
])) ?></div>

<script src="<?= URL_BASE ?>/src/js/modulos/diagnostico.js"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; ?>
