<?php
http_response_code(503);
header('Retry-After: 3600');
?><!DOCTYPE html>
<html lang="es-CR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento — liteFramework</title>
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/estilos.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/errores.css">
    <style>
        .mantenimiento-contenedor {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            padding: 2rem;
        }
        .mantenimiento-icono {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.6;
        }
        .mantenimiento-titulo {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .mantenimiento-descripcion {
            color: #666;
            max-width: 400px;
            margin-bottom: 2rem;
        }
        .mantenimiento-badge {
            display: inline-block;
            background: #ffc107;
            color: #333;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="mantenimiento-contenedor">
        <div class="mantenimiento-icono">🔧</div>
        <div class="mantenimiento-badge">503 Servicio no disponible</div>
        <h1 class="mantenimiento-titulo">Sitio en mantenimiento</h1>
        <p class="mantenimiento-descripcion">
            Estamos realizando tareas de mantenimiento. Volveremos en breve.
        </p>
    </div>
</body>
</html>
