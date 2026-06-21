<?php

define('DIRECTORIO_RAIZ', __DIR__ . '/..');
define('URL_BASE', '');
define('APP_ENTORNO_TEST', true);

require_once DIRECTORIO_RAIZ . '/servidor/autoload.php';

require_once __DIR__ . '/TestBase.php';
require_once __DIR__ . '/Integracion/Modelo/TestCaseDb.php';

GestorEntorno::cargar();

ManejadorErrores::registrar();
