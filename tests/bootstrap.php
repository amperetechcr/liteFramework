<?php

define('DIRECTORIO_RAIZ', __DIR__ . '/..');
define('URL_BASE', '');

require_once DIRECTORIO_RAIZ . '/servidor/autoload.php';

GestorEntorno::cargar();

ManejadorErrores::registrar();
