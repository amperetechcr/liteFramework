<?php

define('DIRECTORIO_RAIZ', __DIR__ . '/..');
define('URL_BASE', '');
define('APP_ENTORNO_TEST', true);

require_once DIRECTORIO_RAIZ . '/servidor/autoload.php';

GestorEntorno::cargar();

ManejadorErrores::registrar();
