<?php

return [
        'id' => 'rutas',
        'titulo' => 'Rutas',
        'icono' => '↗',
        'etiquetas' => 'rutas enrutador get post parametros interceptor',
        'descripcion' => 'Enrutador fluido con soporte para parametros dinamicos, interceptores, nombres de ruta e inyeccion de dependencias.',
        'contenido' => '
            <p>El sistema de rutas es estilo Laravel: fluido, con soporte para parametros, interceptores y nombres. Se define en <code>rutas/web.php</code> y se despacha desde <code>index.php</code>.</p>

            <h3 class="margen-inferior-pequeno">Rutas basicas</h3>
            <pre><code>$enrutador->get(\'/productos\', function() {
    (new ModuloControlador())->indice(\'productos\');
})->interceptor(AutenticacionInterceptor::class)->nombre(\'productos\');

$enrutador->post(\'/productos/guardar\', function() {
    require DIRECTORIO_RAIZ . \'/servidor/api/procesarPeticionPost.php\';
})->interceptor(AutenticacionInterceptor::class)->nombre(\'productos.guardar\');</code></pre>

            <h3 class="margen-inferior-pequeno">Rutas con parametros</h3>
            <pre><code>$enrutador->get(\'/productos/editar/{id}\', function($id) {
    $producto = Producto::buscar((int)$id);
    require DIRECTORIO_RAIZ . \'/src/modulos/productos/editar.php\';
})->interceptor(AutenticacionInterceptor::class)->nombre(\'productos.editar\');

$enrutador->post(\'/archivos/eliminar\', function() {
    $id = (int)($_POST[\'id\'] ?? 0);
    (new SubirArchivosControlador())->eliminar($id);
})->interceptor(AutenticacionInterceptor::class)->nombre(\'archivos.eliminar\');</code></pre>

            <h3 class="margen-inferior-pequeno">Rutas de API</h3>
            <pre><code>$enrutador->get(\'/api/productos\', function() {
    header(\'Content-Type: application/json\');
    $productos = Producto::todos();
    echo json_encode(array_map(fn($p) => $p->aArreglo(), $productos));
})->interceptor(ApiAuthInterceptor::class)->nombre(\'api.productos\');</code></pre>

            <h3 class="margen-inferior-pequeno">Convenciones</h3>
            <ul>
                <li>URLs en <strong>kebab-case</strong>: <code>/mi-modulo</code></li>
                <li>Nombres de ruta en <strong>camelCase</strong>: <code>miModulo</code></li>
                <li>Rutas publicas sin interceptor</li>
                <li>Rutas del panel con <code>->interceptor(AutenticacionInterceptor::class)</code></li>
                <li>Rutas API con <code>->interceptor(ApiAuthInterceptor::class)</code></li>
                <li>Prefijo <code>/api/</code> para endpoints JSON</li>
                <li>Usar <code>ModuloControlador</code> generico para modulos del panel</li>
            </ul>
        ',
    ];
