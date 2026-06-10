<?php

declare(strict_types=1);

define('DIRECTORIO_RAIZ', dirname(__DIR__));
define('URL_BASE', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    exit;
}

require_once DIRECTORIO_RAIZ . '/servidor/autoload.php';
GestorEntorno::cargar();

ManejadorErrores::registrar();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no');

if (ob_get_level()) {
    ob_end_clean();
}

SeguridadServidor::iniciarSesionEstricta();

$idOperador = (int)($_SESSION['operador_id'] ?? 0);
if ($idOperador === 0) {
    echo "event: sse.error\n";
    echo "data: {\"error\":\"no_autenticado\"}\n\n";
    ob_flush();
    flush();
    exit;
}

session_write_close();

$ultimoId = (int)($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0);
SseGestor::conectar($idOperador, $ultimoId);
