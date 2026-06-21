<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$metodo = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true) ?? [];

header('Content-Type: application/json; charset=utf-8');
header('X-Lite-Test: 1');

switch (true) {
    // === Endpoints existentes ===

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
        echo json_encode([
            'args' => $_GET,
            'data' => $_POST ?: [],
            'json' => $body,
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

    // === Sentry mock ===

    case preg_match('#^/sentry/api/(\d+)/store/$#', $uri, $m) === 1 && $metodo === 'POST':
        if (empty($body)) {
            http_response_code(400);
            echo json_encode(['error' => 'empty body']);
            break;
        }
        http_response_code(200);
        echo json_encode([
            'id' => bin2hex(random_bytes(16)),
            'event_id' => $body['event_id'] ?? null,
        ]);
        break;

    // === OAuth Google mock ===

    case $uri === '/oauth/google/token' && $metodo === 'POST':
        if (!isset($body['code']) || !isset($body['client_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_request']);
            break;
        }
        http_response_code(200);
        echo json_encode([
            'access_token' => 'ya29.mock-access-token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ]);
        break;

    case $uri === '/oauth/google/userinfo' && $metodo === 'GET':
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_contains($auth, 'ya29.')) {
            http_response_code(401);
            echo json_encode(['error' => 'invalid_token']);
            break;
        }
        http_response_code(200);
        echo json_encode([
            'id' => '123456789',
            'email' => 'usuario@gmail.com',
            'verified_email' => true,
            'name' => 'Usuario Test',
        ]);
        break;

    // === OAuth GitHub mock ===

    case $uri === '/oauth/github/token' && $metodo === 'POST':
        if (!isset($body['code'])) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_request']);
            break;
        }
        http_response_code(200);
        echo json_encode([
            'access_token' => 'gho_mock-access-token',
            'token_type' => 'bearer',
            'scope' => 'user:email',
        ]);
        break;

    case $uri === '/oauth/github/user' && $metodo === 'GET':
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_contains($auth, 'gho_')) {
            http_response_code(401);
            echo json_encode(['error' => 'bad_credentials']);
            break;
        }
        http_response_code(200);
        echo json_encode([
            'id' => 987654321,
            'login' => 'testuser',
            'email' => 'testuser@github.com',
        ]);
        break;

    // === API framework mock ===

    case preg_match('#^/api/([a-zA-Z0-9_]+)$#', $uri, $m) === 1 && $metodo === 'POST':
        $accion = $m[1];
        $response = match ($accion) {
            'ok' => ['exito' => true, 'data' => $body],
            'error' => ['exito' => false, 'error' => 'error_simulado'],
            'sin_csrf' => ['exito' => false, 'error' => 'token_csrf_invalido'],
            default => null,
        };
        if ($response === null) {
            http_response_code(404);
            echo json_encode(['exito' => false, 'error' => "accion_no_reconocida: {$accion}"]);
            break;
        }
        http_response_code(200);
        echo json_encode($response);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada']);
}
