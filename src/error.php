<?php

$codigosValidos = [400, 401, 403, 404, 500, 503];

if (isset($excepcion)) {
    $codigo = 500;
    $mensajes = [500 => 'Error interno del servidor'];
    $descripciones = [500 => 'Ocurrió un error interno en el servidor.'];
    $detalleExcepcion = [
        'mensaje' => $excepcion->getMessage(),
        'archivo' => $excepcion->getFile(),
        'linea' => $excepcion->getLine(),
        'traza' => GestorEntorno::esDepuracion() ? $excepcion->getTraceAsString() : null,
    ];
} else {
    $codigo = (int)($_GET['code'] ?? 500);
    if (!in_array($codigo, $codigosValidos, true)) {
        $codigo = 500;
    }
    $detalleExcepcion = null;
}

$mensajes = $mensajes ?? [
    400 => 'Solicitud incorrecta',
    401 => 'No autenticado',
    403 => 'Acceso denegado',
    404 => 'No encontrado',
    500 => 'Error interno del servidor',
    503 => 'Servicio no disponible'
];

$descripciones = $descripciones ?? [
    400 => 'La solicitud no puede ser procesada debido a un formato incorrecto.',
    401 => 'Debes iniciar sesión para acceder a este recurso.',
    403 => 'No tienes permisos suficientes para acceder a esta página.',
    404 => 'La página que buscas no existe o fue movida a otra ubicación.',
    500 => 'Ocurrió un error interno en el servidor. Estamos trabajando para solucionarlo.',
    503 => 'El servicio no está disponible temporalmente. Por favor intenta más tarde.'
];

$mensaje = $mensajes[$codigo] ?? 'Error desconocido';
$descripcion = $descripciones[$codigo] ?? 'Ha ocurrido un error inesperado.';
$traceId = null;
if (class_exists('TrazadorPeticiones')) {
    try {
        $traceId = TrazadorPeticiones::obtenerId();
    } catch (Exception $e) {
        error_log('[error.php] Error al obtener id de traza: ' . $e->getMessage());
    }
}

http_response_code($codigo);

$urlBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= $codigo ?> - liteFramework</title>
    <link rel="stylesheet" href="<?= $urlBase ?>/src/css/errores.css">
</head>
<body>
    <svg class="error-icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <?php if ($codigo === 403) : ?>
        <circle cx="12" cy="12" r="10"/>
        <path d="M4.93 4.93l14.14 14.14"/>
        <?php elseif ($codigo === 404) : ?>
        <circle cx="12" cy="12" r="10"/>
        <path d="M16 16s-1.5-2-4-2-4 2-4 2"/>
        <line x1="9" y1="9" x2="15" y2="15"/>
        <?php elseif ($codigo === 500) : ?>
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
        <?php else : ?>
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <circle cx="12" cy="16" r="0.5" fill="currentColor"/>
        <?php endif; ?>
    </svg>

    <div class="error-codigo"><?= $codigo ?></div>

    <h1 class="error-titulo"><?= htmlspecialchars($mensaje) ?></h1>
    <p class="error-mensaje"><?= htmlspecialchars($descripcion) ?></p>

    <?php if ($detalleExcepcion && GestorEntorno::esDepuracion()) : ?>
    <div class="error-detalle">
        <p><strong>Mensaje:</strong> <?= htmlspecialchars($detalleExcepcion['mensaje']) ?></p>
        <p><strong>Archivo:</strong> <?= htmlspecialchars($detalleExcepcion['archivo']) ?> (línea <?= $detalleExcepcion['linea'] ?>)</p>
        <?php if ($detalleExcepcion['traza']) : ?>
        <pre><?= htmlspecialchars($detalleExcepcion['traza']) ?></pre>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($traceId) : ?>
    <div class="error-trace">ID de seguimiento: <?= htmlspecialchars($traceId) ?></div>
    <?php endif; ?>

    <?php if (!empty($diagnosticoHtml['sugerencias'])) : ?>
    <div class="error-diagnostico">
        <h2>Acciones sugeridas</h2>
        <ul>
            <?php foreach ($diagnosticoHtml['sugerencias'] as $sug) : ?>
            <li><?= nl2br(htmlspecialchars($sug)) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php if (!empty($diagnosticoHtml['accion']['tipo']) && $diagnosticoHtml['accion']['tipo'] === 'reparar') : ?>
        <button type="button" class="boton boton-reparar" onclick="window.location.reload()">Reintentar</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($diagnosticoHtml['diagnosticos'])) : ?>
    <div class="error-diagnostico error-diagnostico-detalle">
        <h2>Diagnóstico del sistema</h2>
        <?php foreach ($diagnosticoHtml['diagnosticos'] as $diag) : ?>
        <div class="diagnostico-item">
            <span class="diagnostico-tipo"><?= htmlspecialchars($diag['tipo'] ?? '') ?></span>
            <p><?= htmlspecialchars($diag['detalle'] ?? '') ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($diagnosticoHtml['tieneRemedio'])) : ?>
    <div class="error-diagnostico error-remedio">
        <p>El sistema intentará resolver este problema automáticamente.</p>
    </div>
    <?php endif; ?>

    <div class="enlaces">
        <a href="<?= $urlBase ?>/" class="boton">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            </svg>
            Volver al inicio
        </a>
        <a href="#" data-accion="volver-atras" class="boton boton-secundario">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Página anterior
        </a>
    </div>

    <footer>
        liteFramework v1.1.0
    </footer>

    <script src="<?= $urlBase ?>/src/js/error.js"></script>
</body>
</html>