<?php

define('DIRECTORIO_RAIZ', __DIR__);
define('URL_BASE', '');
define('DB_ANFITRION', 'localhost');
define('DB_NOMBRE', 'test');
define('DB_USUARIO', 'root');
define('DB_CLAVE', '');
define('SENTRY_DSN', '');

function h(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function configUI(?string $clave = null): mixed
{
    $config = [
        'paleta' => 'indigo',
        'estilo' => 'moderno',
        'fondo' => 'blanco',
        'textura' => 'ninguna',
        'fuente' => 'sistema',
        'espaciado' => 'normal',
        'tamano' => 'normal',
    ];
    if ($clave !== null) {
        return $config[$clave] ?? null;
    }
    return $config;
}

function claseFondoHTML(): string
{
    return 'fondo-blanco';
}
function claseTexturaHTML(): string
{
    return '';
}
function claseFuenteHTML(): string
{
    return '';
}
function claseEspaciadoHTML(): string
{
    return '';
}
function claseTamanoHTML(): string
{
    return '';
}
