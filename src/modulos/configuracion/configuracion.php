<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

$codigoError = $_GET['error'] ?? '';
$codigoMensaje = $_GET['mensaje'] ?? '';

$tokenCSRF = SeguridadServidor::generarTokenAntiFalsificacion();

$operador = null;
$nombreRol = '—';

try {
    $operador = Operador::obtenerPerfil($idOperador);
    if ($operador) {
        $nombreRol = $operador['nombre_rol'];
    }
} catch (PDOException $e) {
    RegistroAuditoria::advertencia('Configuracion', 'Error al cargar perfil', [
        'error' => $e->getMessage(),
    ]);
    $operador = null;
}

$esSuperAdmin = ((int)($_SESSION['operador_rol'] ?? 0) === 1);

$configArchivos = [];
$detallesConfig = [];
$limitesPhp = GeneradorIniServidor::limitesActualesPHP();
$contenidoDirectivasPhp = GeneradorIniServidor::leerActual();

if ($esSuperAdmin) {
    $configArchivos = [
        'tamano_maximo_mb' => (int)ConfiguracionSistema::obtener('ARCHIVO_TAMANO_MAXIMO_MB', 40),
        'cuota_usuario_mb' => (int)ConfiguracionSistema::obtener('ARCHIVO_CUOTA_USUARIO_MB', 100),
        'tipos_mime_permitidos' => ConfiguracionSistema::obtener('ARCHIVO_TIPOS_MIME_PERMITIDOS', 'imagenes,documentos,codigo,datos'),
        'extensiones_permitidas' => ConfiguracionSistema::obtener('ARCHIVO_EXTENSIONES_PERMITIDAS', 'jpg,jpeg,png,gif,webp,bmp,pdf,doc,docx,xls,xlsx,txt,csv,php,js,css,sql,md,json,xml,log,ini,env,example,backup'),
        'memoria_php_mb' => (int)ConfiguracionSistema::obtener('ARCHIVO_MEMORIA_PHP_MB', 512),
        'tiempo_ejecucion_seg' => (int)ConfiguracionSistema::obtener('ARCHIVO_TIEMPO_EJECUCION_SEG', 300),
        'maximo_subidas_simultaneas' => (int)ConfiguracionSistema::obtener('ARCHIVO_MAXIMO_SUBIDAS_SIMULTANEAS', 20),
        'post_max_size_mb' => (int)ConfiguracionSistema::obtener('ARCHIVO_POST_MAX_SIZE_MB', 50),
    ];
    foreach (ConfiguracionSistema::obtenerTodas() as $clave => $fila) {
        if (strpos($clave, 'ARCHIVO_') === 0) {
            $detallesConfig[$clave] = [
                'valor' => ConfiguracionSistema::obtener($clave),
                'version' => (int)$fila['version'],
            ];
        }
    }
}

$valoresEfectivos = [
    'tamano_maximo_mb' => $limitesPhp['upload_max_filesize'],
    'memoria_php_mb' => $limitesPhp['memory_limit'],
    'tiempo_ejecucion_seg' => $limitesPhp['max_execution_time'] ?: 60,
    'maximo_subidas_simultaneas' => $limitesPhp['max_file_uploads'] ?: 20,
    'post_max_size_mb' => $limitesPhp['post_max_size'],
];
foreach ($valoresEfectivos as $clave => $valor) {
    $configArchivos[$clave] = $valor;
}

if ($esAjax) {
    echo '<div data-titulo-pagina="Configuración"></div>';
}
?>
<?php if (!$esAjax): $tituloPagina = 'Configuración'; $moduloActivo = 'configuracion'; require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php'; endif; ?>

<h1 class="margen-inferior-normal">Configuración</h1>

<nav class="pestanas-contenedor margen-inferior-normal" aria-label="Secciones de configuracion">
    <button type="button" class="pestana activa" data-tab="perfil">Perfil</button>
    <button type="button" class="pestana" data-tab="servidor">Servidor</button>
</nav>

<div class="pestanas-paneles">
    <section class="pestana-panel activo" data-panel="perfil" aria-label="Perfil">
        <?php require __DIR__ . '/perfil.php'; ?>
    </section>

    <section class="pestana-panel" data-panel="servidor" aria-label="Servidor" hidden>
        <?php if ($esSuperAdmin): ?>
            <?php require __DIR__ . '/configuracionArchivos.php'; ?>
        <?php else: ?>
            <?php require __DIR__ . '/limitesArchivos.php'; ?>
        <?php endif; ?>
    </section>
</div>

<script src="<?= URL_BASE ?>/src/js/modulos/configuracion.js"<?= $esAjax ? '' : ' defer' ?>></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; 