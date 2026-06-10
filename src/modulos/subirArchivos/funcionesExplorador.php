<?php
function tarjetaArchivo(Archivo $a): string {
    $tipo = 'base';
    $mime = strtolower($a->tipo_mime);
    if ($a->esImagen()) $tipo = 'imagen';
    elseif (strpos($mime, 'video') !== false) $tipo = 'video';
    elseif (strpos($mime, 'audio') !== false) $tipo = 'audio';
    elseif (strpos($mime, 'pdf') !== false || strpos($mime, 'document') !== false || strpos($mime, 'sheet') !== false || strpos($mime, 'text') !== false) $tipo = 'documento';
    elseif (strpos($mime, 'zip') !== false || strpos($mime, 'rar') !== false || strpos($mime, 'tar') !== false || strpos($mime, 'gzip') !== false || strpos($mime, '7z') !== false) $tipo = 'comprimido';
    elseif (strpos($mime, 'dosexec') !== false || strpos($mime, 'msdownload') !== false || strpos($mime, 'executable') !== false || strpos($mime, 'x-msi') !== false) $tipo = 'ejecutable';

    if ($a->esImagen()) $icono = '<img src="' . htmlspecialchars($a->enlaceDescarga()) . '" alt="' . htmlspecialchars($a->nombre_original) . '" style="width:100%;height:100%;object-fit:contain" loading="lazy">';
    else $icono = '<span>' . ([
        'video' => '🎬', 'audio' => '🎵', 'comprimido' => '📦', 'documento' => '📝',
        'ejecutable' => '⚙', 'imagen' => '🖼', 'base' => '📄'
    ][$tipo] ?? '📄') . '</span>';

    $html = '<article class="tarjeta-archivo tarjeta relleno-normal" data-id="' . (int)$a->id_archivo . '">';
    $html .= '<span class="etiqueta-tipo-archivo" data-tipo="' . $tipo . '">' . ucfirst($tipo) . '</span>';
    $html .= '<div class="flex flex-entre brecha-pequena">';

    $html .= '<div class="flex flex-1 brecha-pequena ancho-completo margen-superior-pequeno">';
    $html .= '<div class="flex" style="flex-shrink:0;width:2rem;height:2rem;align-items:center;justify-content:center;font-size:var(--tamano-lg)">' . $icono . '</div>';
    $html .= '<div class="flex-columna flex-1" style="min-width:0">';
    $html .= '<p class="texto-xs texto-negrita margen-0" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="' . htmlspecialchars($a->nombre_original) . '">' . htmlspecialchars($a->nombre_original) . '</p>';
    $html .= '<p class="texto-xs texto-suave margen-0" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' . htmlspecialchars($a->tamanoFormateado()) . ' · ' . htmlspecialchars(Fecha::formatear($a->fecha_subida, 'd/m/Y')) . '</p>';
    if (!empty($a->etiquetas)) {
        $html .= '<div class="flex brecha-pequena margen-superior-pequeno flex-envolver">';
        foreach (explode(',', $a->etiquetas) as $et) $html .= '<span class="etiqueta" style="font-size:var(--tamano-2xs);padding:0.0625rem 0.375rem">' . htmlspecialchars(trim($et)) . '</span>';
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="flex brecha-pequena" style="flex-shrink:0">';
    if ($a->esImagen()) $html .= '<a href="' . htmlspecialchars($a->enlaceDescarga()) . '" target="_blank" class="accion-boton variante-texto tamano-pequeno" style="padding:0.125rem 0.375rem">Ver</a>';
    $html .= '<a href="' . htmlspecialchars($a->enlaceDescarga()) . '" download="' . htmlspecialchars($a->nombre_original) . '" class="accion-boton variante-texto tamano-pequeno" style="padding:0.125rem 0.375rem">Descargar</a>';
    $html .= '<button type="button" class="accion-boton variante-peligro tamano-pequeno btn-eliminar-archivo" data-id="' . (int)$a->id_archivo . '" style="padding:0.125rem 0.375rem">Eliminar</button>';
    $html .= '</div>';

    $html .= '</div>';
    $html .= '</article>';
    return $html;
}

function filtrarArchivosPorCarpeta(array $archivos, string $rutaCarpeta): array {
    $archivosDirectos = [];
    $subcarpetas = [];
    $prefijo = $rutaCarpeta ? $rutaCarpeta . '/' : '';

    foreach ($archivos as $a) {
        $ruta = $a->rutaMostrar();

        if ($rutaCarpeta === '') {
            if ($ruta === '') {
                $archivosDirectos[] = $a;
            } else {
                $subcarpetas[explode('/', $ruta)[0]] = true;
            }
        } else {
            if ($ruta === $rutaCarpeta) {
                $archivosDirectos[] = $a;
            } elseif (str_starts_with($ruta, $prefijo)) {
                $resto = substr($ruta, strlen($prefijo));
                if (str_contains($resto, '/')) {
                    $subcarpetas[explode('/', $resto)[0]] = true;
                } else {
                    $archivosDirectos[] = $a;
                }
            }
        }
    }

    ksort($subcarpetas);
    return [$archivosDirectos, array_keys($subcarpetas)];
}

function renderizarSubcarpeta(string $nombre, string $rutaCompleta): string {
    return '<article class="tarjeta-archivo tarjeta-carpeta tarjeta relleno-normal" data-ruta="' . htmlspecialchars($rutaCompleta) . '">'
        . '<div class="flex flex-entre">'
        . '<div class="flex brecha-pequena flex-1">'
        . '<div class="flex" style="flex-shrink:0;width:2rem;height:2rem;align-items:center;justify-content:center;font-size:var(--tamano-xl)">📁</div>'
        . '<div class="flex-columna">'
        . '<p class="texto-sm texto-negrita margen-0">' . htmlspecialchars($nombre) . '</p>'
        . '<p class="texto-xs texto-suave margen-0">Carpeta</p>'
        . '</div>'
        . '</div>'
        . '<div class="flex brecha-pequena" style="flex-shrink:0">'
        . '<a href="' . htmlspecialchars(URL_BASE . '/archivos/descargar-carpeta?ruta=' . urlencode($rutaCompleta)) . '" class="accion-boton variante-texto tamano-pequeno" style="padding:0.125rem 0.375rem">Descargar</a>'
        . '<button type="button" class="accion-boton variante-peligro tamano-pequeno btn-eliminar-carpeta" data-ruta="' . htmlspecialchars($rutaCompleta) . '" data-nombre="' . htmlspecialchars($nombre) . '" style="padding:0.125rem 0.375rem">Eliminar</button>'
        . '</div>'
        . '</div>'
        . '</article>';
}

function renderizarVistaCarpeta(array $archivos, string $rutaCarpeta): string {
    list($archivosDirectos, $subcarpetas) = filtrarArchivosPorCarpeta($archivos, $rutaCarpeta);

    $vacia = empty($archivosDirectos) && empty($subcarpetas);
    if ($vacia) {
        $mensaje = $rutaCarpeta ? 'Esta carpeta está vacía.' : 'No hay archivos registrados. Sube tu primer archivo arrastrándolo o usando los botones de arriba.';
        return '<div class="texto-centro relleno-normal archivos-aviso-vacio"><p class="texto-lg margen-0" style="font-size:var(--tamano-3xl)">📂</p><p class="texto-suave texto-sm">' . $mensaje . '</p></div>';
    }

    $html = '<div class="explorador-archivos"><div class="rejilla-automatica">';

    foreach ($subcarpetas as $sub) {
        $rutaSub = $rutaCarpeta ? $rutaCarpeta . '/' . $sub : $sub;
        $html .= renderizarSubcarpeta($sub, $rutaSub);
    }

    foreach ($archivosDirectos as $a) {
        $html .= tarjetaArchivo($a);
    }

    $html .= '</div></div>';
    return $html;
}
