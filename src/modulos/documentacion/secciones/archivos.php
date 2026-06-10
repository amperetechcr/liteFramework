<?php

return [
        'id' => 'archivos',
        'titulo' => 'Archivos',
        'icono' => '📁',
        'etiquetas' => 'archivos upload subida mime cuota almacenamiento descarga',
        'descripcion' => 'Sistema de subida segura con validacion MIME, cuota de almacenamiento por usuario, extensiones configurables y descarga via endpoint protegido.',
        'contenido' => '
            <p>El framework incluye un sistema completo de gestion de archivos: subida con validacion, almacenamiento en <code>/storage/archivos/</code> (bloqueado por .htaccess), y descarga via endpoint autenticado. Los limites son configurables desde el panel (Configuracion → Limites de subida).</p>

            <h3 class="margen-inferior-pequeno">Subir un archivo</h3>
            <pre><code>$subida = new SubidaArchivos(\'archivo\');
$subida->validar([\'image/jpeg\', \'image/png\', \'application/pdf\'], 5 * 1024 * 1024);

if ($subida->tieneError()) {
    echo json_encode([\'error\' => $subida->error()]);
    return;
}

$ruta = $subida->guardar(DIRECTORIO_RAIZ . \'/storage/archivos\');

$archivo = Archivo::crear([
    \'nombre_original\' => $subida->nombreOriginal(),
    \'nombre_generado\' => basename($ruta),
    \'ruta_archivo\' => $ruta,
    \'tipo_mime\' => $subida->tipoMime(),
    \'tamano_bytes\' => $subida->tamano(),
    \'id_operador\' => $idOperador,
    \'modulo_origen\' => \'productos\',
    \'etiquetas\' => \'foto,producto\',
]);</code></pre>

            <h3 class="margen-inferior-pequeno">Descargar un archivo</h3>
            <pre><code>$archivo = Archivo::buscar($id);
if (!$archivo || !file_exists($archivo->ruta_archivo)) {
    http_response_code(404);
    return;
}
header(\'Content-Type: \' . $archivo->tipo_mime);
header(\'Content-Disposition: attachment; filename="\' . $archivo->nombre_original . \'"\');
header(\'Content-Length: \' . filesize($archivo->ruta_archivo));
readfile($archivo->ruta_archivo);
exit;</code></pre>

            <h3 class="margen-inferior-pequeno">Configuracion desde BD</h3>
            <p>Los limites se consultan con <code>ConfiguracionSistema</code> y se pueden modificar desde el panel de control.</p>
            <pre><code>$tamanoMax = (int)ConfiguracionSistema::obtener(\'ARCHIVO_TAMANO_MAXIMO_MB\', 40);
$cuota = (int)ConfiguracionSistema::obtener(\'ARCHIVO_CUOTA_USUARIO_MB\', 100);
$extensiones = ConfiguracionSistema::obtener(\'ARCHIVO_EXTENSIONES_PERMITIDAS\', \'jpg,png,pdf\');

$usoMB = round($controlador->obtenerUsoUsuarioMB($idOperador), 2);</code></pre>
        ',
    ];
