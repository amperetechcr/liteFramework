<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$metodo = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json; charset=utf-8');
header('X-Lite-Test: 1');

switch (true) {
    case $uri === '/get':
        http_response_code(200);
        echo json_encode([
            'args' => $_GET,
            'headers' => getallheaders(),
            'url' => $_SERVER['REQUEST_URI'],
            'origin' => 'test-server',
            'fuente' => 'liteframework-test-local',
        ]);
        break;

    case $uri === '/post':
        http_response_code(200);
        $cuerpo = json_decode(file_get_contents('php://input'), true) ?? [];
        $form = $_POST ?: [];
        echo json_encode([
            'args' => $_GET,
            'data' => $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [],
            'form' => $form,
            'json' => $cuerpo,
            'headers' => getallheaders(),
            'origin' => 'test-server',
            'fuente' => 'liteframework-test-local',
        ]);
        break;

    case $uri === '/headers':
        http_response_code(200);
        echo json_encode([
            'headers' => getallheaders(),
            'origin' => 'test-server',
        ]);
        break;

    case preg_match('#^/status/(\d+)$#', $uri, $m) === 1:
        http_response_code((int)$m[1]);
        echo json_encode([
            'codigo' => (int)$m[1],
            'error' => match ((int)$m[1]) {
                404 => 'No encontrado',
                500 => 'Error interno del servidor',
                default => 'Error',
            },
        ]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada']);
}
