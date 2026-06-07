<?php
$enrutador->get('/categorias', function() {
    (new ModuloControlador())->indice('categorias');
})->interceptor(AutenticacionInterceptor::class)->nombre('categorias');

$enrutador->get('/categorias/nuevo', function() {
    (new ModuloControlador())->indice('categorias_formulario');
})->interceptor(AutenticacionInterceptor::class)->nombre('categorias_nuevo');

$enrutador->get('/categorias/editar/{id}', function($id) {
    (new ModuloControlador())->mostrar('categorias_formulario', $id);
})->interceptor(AutenticacionInterceptor::class)->nombre('categorias_editar');

$enrutador->post('/categorias/guardar', function() {
    require DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
})->interceptor(AutenticacionInterceptor::class)->nombre('categorias_guardar');

$enrutador->get('/api/categorias', function() {
    header('Content-Type: application/json');
    $categorias = Categoria::todos();
    echo json_encode(array_map(fn($c) => $c->aArreglo(), $categorias));
})->interceptor(ApiAuthInterceptor::class)->nombre('api_categorias');
