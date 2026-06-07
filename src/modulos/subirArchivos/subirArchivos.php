<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

$partial = $_GET['partial'] ?? '';
$rutaCarpeta = $_GET['ruta'] ?? '';

$tokenCSRF = SeguridadServidor::generarTokenAntiFalsificacion();

$tamanoMaximoMB = (int)ConfiguracionSistema::obtener('ARCHIVO_TAMANO_MAXIMO_MB', 40);
$cuotaUsuarioMB = (int)ConfiguracionSistema::obtener('ARCHIVO_CUOTA_USUARIO_MB', 100);
$extensionesTexto = ConfiguracionSistema::obtener('ARCHIVO_EXTENSIONES_PERMITIDAS', 'jpg,jpeg,png,gif,webp,bmp,pdf,doc,docx,xls,xlsx,txt,csv,php,js,css,sql,md,json,xml,log,ini,env,example,backup');
$categoriasTexto = ConfiguracionSistema::obtener('ARCHIVO_TIPOS_MIME_PERMITIDOS', 'imagenes,documentos,codigo,datos');

$extensionesLista = array_map('trim', explode(',', $extensionesTexto));
$categoriasLista = array_map('trim', explode(',', $categoriasTexto));
$modoRepositorio = in_array('*', $extensionesLista, true) || in_array('*', $categoriasLista, true);

$categoriasActivas = array_map('trim', explode(',', $categoriasTexto));

$etiquetasCategorias = [
    'imagenes' => 'Imagenes',
    'documentos' => 'Documentos',
    'videos' => 'Videos',
    'audio' => 'Audio',
    'comprimidos' => 'Comprimidos',
    'ejecutables' => 'Ejecutables',
    'codigo' => 'Codigo',
    'datos' => 'Datos',
];
$categoriasDescripcion = [];
foreach ($categoriasActivas as $cat) {
    if (isset($etiquetasCategorias[$cat])) {
        $categoriasDescripcion[] = $etiquetasCategorias[$cat];
    }
}
$categoriasDescripcionTexto = implode(', ', $categoriasDescripcion);

$usoUsuarioMB = 0;
try {
    if (class_exists('Archivo')) {
        $bytes = Archivo::sumaTamanoPorOperador((int)$_SESSION['operador_id']);
        $usoUsuarioMB = round($bytes / 1048576, 2);
    }
} catch (Exception $e) {
    RegistroAuditoria::advertencia('Archivos', 'Error al obtener uso de cuota', [
        'error' => $e->getMessage(),
    ]);
}

$porcentajeCuota = $cuotaUsuarioMB > 0 ? min(100, ($usoUsuarioMB / $cuotaUsuarioMB) * 100) : 0;
$cuotaAdvertencia = $porcentajeCuota > 80;

$archivos = [];
$totalArchivos = 0;
$archivosPaginados = [];

try {
    $todos = Archivo::todos();
    $archivosValidos = [];
    foreach ($todos as $obj) {
        if (file_exists($obj->ruta_archivo)) {
            $archivosValidos[] = $obj;
        } else {
            $obj->eliminar();
        }
    }

    $totalArchivos = count($archivosValidos);
    $archivosPaginados = $archivosValidos;
} catch (Exception $e) {
    RegistroAuditoria::error('Archivos', 'Error al cargar listado de archivos', [
        'error' => $e->getMessage(),
    ]);
    $totalArchivos = 0;
}

if ($esAjax && !$partial) {
    echo '<div data-titulo-pagina="Subir Archivos"></div>';
}

require __DIR__ . '/funcionesExplorador.php';

if ($partial === 'lista') {
    require __DIR__ . '/listadoArchivos.php';
    return;
}

if (!$esAjax) {
    $tituloPagina = 'Subir Archivos';
    $moduloActivo = 'archivos';
    require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php';
}
?>

<section aria-label="Gestion de archivos">
    <div class="cabecera-principal">
        <div class="agrupador-flexible-filas brecha-pequena">
            <h1 class="margen-0 texto-2xl">Archivos</h1>
            <span class="etiqueta etiqueta-marca" id="contador-archivos"><?= (int)$totalArchivos ?></span>
        </div>
        <div class="agrupador-flexible-filas brecha-pequena">
            <span class="etiqueta etiqueta-info">⬆ <?= (int)$tamanoMaximoMB ?> MB</span>
            <?php if ($modoRepositorio): ?>
            <span class="etiqueta etiqueta-exito">★ Modo repositorio</span>
            <?php elseif (!empty($categoriasDescripcionTexto)): ?>
            <span class="etiqueta etiqueta-marca"><?= htmlspecialchars($categoriasDescripcionTexto, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($cuotaUsuarioMB > 0): ?>
    <div class="flex-columna brecha-pequena margen-inferior-normal">
        <div class="flex-entre">
            <span class="texto-xs texto-seminegrita">Cuota de almacenamiento</span>
            <span class="texto-xs texto-negrita <?= $cuotaAdvertencia ? 'color-peligro' : 'color-marca' ?>"><?= $usoUsuarioMB ?>MB / <?= (int)$cuotaUsuarioMB ?>MB</span>
        </div>
        <progress value="<?= $porcentajeCuota ?>" max="100" aria-label="Cuota usada" class="archivos-barra-cuota <?= $cuotaAdvertencia ? 'barra-cuota-advertencia' : 'barra-cuota-normal' ?>"></progress>
    </div>
    <?php endif; ?>

    <?php require __DIR__ . '/formularioSubida.php'; ?>

    <section aria-label="Listado de archivos">
        <div id="contenedor-lista-archivos" class="margen-superior-normal">
            <?php require __DIR__ . '/listadoArchivos.php'; ?>
        </div>
    </section>
</section>

<script src="<?= URL_BASE ?>/src/js/modulos/subirArchivos.js"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; ?>
