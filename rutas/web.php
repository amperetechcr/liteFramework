<?php

use LiteFramework\Nucleo\Enrutador;
use LiteFramework\Controladores\AutenticacionControlador;
use LiteFramework\Controladores\ModuloControlador;
use LiteFramework\Controladores\SubirArchivosControlador;
use LiteFramework\Middleware\AutenticacionInterceptor;
use LiteFramework\Middleware\ApiAuthInterceptor;
use LiteFramework\Migraciones\GestorMigraciones;
use LiteFramework\Servicios\GeneradorPdf;
use LiteFramework\Servicios\GeneradorEstadisticas;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Servicios\RemediadorError;
use LiteFramework\Modelos\Operador;
use LiteFramework\Modelos\Rol;

$enrutador = new Enrutador();

// Pagina principal (ingreso)
$enrutador->get('/', [AutenticacionControlador::class, 'mostrarInicioSesion'])->nombre('ingreso');
$enrutador->get('/ingreso', [AutenticacionControlador::class, 'mostrarInicioSesion']);

// Cerrar sesion
$enrutador->get('/salir', [AutenticacionControlador::class, 'cerrarSesion'])->nombre('salir');

// OAuth — Google
$enrutador->get('/auth/google', function () {
    $oauth = new \LiteFramework\Servicios\AutenticacionOAuth();
    header('Location: ' . $oauth->urlGoogle());
    exit;
});

$enrutador->get('/auth/google/callback', function () {
    $oauth = new \LiteFramework\Servicios\AutenticacionOAuth();
    $resultado = $oauth->procesarGoogle($_GET['code'] ?? '', $_GET['state'] ?? '');
    if ($resultado['exito']) {
        header('Location: ' . URL_BASE . $resultado['redireccion']);
    } else {
        header('Location: ' . URL_BASE . '/?error=' . urlencode($resultado['mensaje']));
    }
    exit;
});

// OAuth — GitHub
$enrutador->get('/auth/github', function () {
    $oauth = new \LiteFramework\Servicios\AutenticacionOAuth();
    header('Location: ' . $oauth->urlGithub());
    exit;
});

$enrutador->get('/auth/github/callback', function () {
    $oauth = new \LiteFramework\Servicios\AutenticacionOAuth();
    $resultado = $oauth->procesarGithub($_GET['code'] ?? '', $_GET['state'] ?? '');
    if ($resultado['exito']) {
        header('Location: ' . URL_BASE . $resultado['redireccion']);
    } else {
        header('Location: ' . URL_BASE . '/?error=' . urlencode($resultado['mensaje']));
    }
    exit;
});

// API asincrona (mantiene el sistema existente)
$enrutador->post('/api', function () {
    require DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php';
});

// Modulos del panel (protegidos) — usan ModuloControlador genérico
$enrutador->get('/inicio', function () {
    (new ModuloControlador())->indice('inicio');
})->interceptor(AutenticacionInterceptor::class)->nombre('inicio');

$enrutador->get('/panelControl', function () {
    (new ModuloControlador())->indice('panelControl');
})->interceptor(AutenticacionInterceptor::class)->nombre('panelControl');

$enrutador->get('/operadores', function () {
    (new ModuloControlador())->indice('operadores');
})->interceptor(AutenticacionInterceptor::class)->nombre('operadores');

$enrutador->get('/auditoria', function () {
    (new ModuloControlador())->indice('auditoria');
})->interceptor(AutenticacionInterceptor::class)->nombre('auditoria');

$enrutador->get('/configuracion', function () {
    (new ModuloControlador())->indice('configuracion');
})->interceptor(AutenticacionInterceptor::class)->nombre('configuracion');

$enrutador->post('/configuracion', function () {
    if (!empty($_POST['accion_crud'])) {
        if (!\LiteFramework\Seguridad\SeguridadServidor::validarTokenAntiFalsificacion($_POST['token_peticion'] ?? '')) {
            header('Location: ' . URL_BASE . '/configuracion?error=token_invalido');
            exit;
        }
        $controlador = new \LiteFramework\Api\Controladores\ConfiguracionApiControlador();
        [$codigo, $respuesta] = $controlador->actualizarConfiguracionArchivos($_POST);
        $param = $respuesta['estado_operacion']
            ? 'mensaje=configuracion_actualizada'
            : 'error=' . urlencode($respuesta['mensaje_error']);
        http_response_code($codigo);
        header('Location: ' . URL_BASE . '/configuracion?' . $param);
        exit;
    }
    (new ModuloControlador())->indice('configuracion');
})->interceptor(AutenticacionInterceptor::class);

$enrutador->get('/apariencia', function () {
    (new ModuloControlador())->indice('apariencia');
})->interceptor(AutenticacionInterceptor::class)->nombre('apariencia');

$enrutador->get('/estadisticas', function () {
    (new ModuloControlador())->indice('estadisticas');
})->interceptor(AutenticacionInterceptor::class)->nombre('estadisticas');

$enrutador->get('/estadisticas/ver/{id}', function ($id) {
    $datosVista = (int)$id;
    require DIRECTORIO_RAIZ . '/src/modulos/estadisticas/vistaEstadistica.php';
})->interceptor(AutenticacionInterceptor::class)->nombre('estadisticas.ver');

$enrutador->get('/documentacion', function () {
    (new ModuloControlador())->indice('documentacion');
})->interceptor(AutenticacionInterceptor::class)->nombre('documentacion');

$enrutador->get('/generador-modulo', function () {
    (new ModuloControlador())->indice('generadorModulo');
})->interceptor(AutenticacionInterceptor::class)->nombre('generadorModulo');

$enrutador->get('/generador-proyecto', function () {
    (new ModuloControlador())->indice('generadorProyecto');
})->interceptor(AutenticacionInterceptor::class)->nombre('generadorProyecto');

$enrutador->get('/migraciones', function () {
    (new ModuloControlador())->indice('migraciones');
})->interceptor(AutenticacionInterceptor::class)->nombre('migraciones');

$enrutador->get('/migraciones/respaldos/descargar/{archivo}', function ($archivo) {
    $archivoSeguro = basename($archivo);
    if (!preg_match('/^respaldo_\d{8}_\d{6}\.sql$/', $archivoSeguro)) {
        http_response_code(404);
        require DIRECTORIO_RAIZ . '/src/error.php';
        return;
    }
    $ruta = DIRECTORIO_RAIZ . '/storage/backups/' . $archivoSeguro;
    if (!file_exists($ruta)) {
        http_response_code(404);
        require DIRECTORIO_RAIZ . '/src/error.php';
        return;
    }
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $archivoSeguro . '"');
    header('Content-Length: ' . filesize($ruta));
    readfile($ruta);
    exit;
})->interceptor(AutenticacionInterceptor::class)->nombre('migraciones.respaldos.descargar');

$enrutador->post('/migraciones/respaldos/eliminar', function () {
    $archivo = $_POST['archivo'] ?? '';
    if (empty($archivo)) {
        header('Location: ' . URL_BASE . '/migraciones?error=archivo_invalido');
        exit;
    }
    $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
    $gestor = new GestorMigraciones($conexion);
    $resultado = $gestor->eliminarRespaldo($archivo);
    RegistroAuditoria::info('Migraciones', 'eliminar_respaldo', [
        'archivo' => $archivo,
        'resultado' => $resultado['eliminado'] ? 'exito' : 'fallo',
    ]);
    $param = $resultado['eliminado'] ? 'mensaje=respaldo_eliminado' : 'error=' . urlencode($resultado['mensaje']);
    header('Location: ' . URL_BASE . '/migraciones?' . $param);
    exit;
})->interceptor(AutenticacionInterceptor::class)->nombre('migraciones.respaldos.eliminar');

$enrutador->post('/migraciones/respaldos/restaurar', function () {
    $archivo = $_POST['archivo'] ?? '';
    if (empty($archivo) || !preg_match('/^respaldo_\d{8}_\d{6}\.sql$/', $archivo)) {
        header('Location: ' . URL_BASE . '/migraciones?error=archivo_invalido');
        exit;
    }
    $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
    $gestor = new GestorMigraciones($conexion);
    $resultado = $gestor->restaurarRespaldo($archivo);
    RegistroAuditoria::info('Migraciones', 'restaurar_respaldo', [
        'archivo' => $archivo,
        'resultado' => $resultado['estado'],
    ]);
    $param = $resultado['estado'] === 'restaurado'
        ? 'mensaje=respaldo_restaurado'
        : 'error=' . urlencode($resultado['mensaje']);
    header('Location: ' . URL_BASE . '/migraciones?' . $param);
    exit;
})->interceptor(AutenticacionInterceptor::class)->nombre('migraciones.respaldos.restaurar');

$enrutador->get('/archivos', function () {
    (new ModuloControlador())->indice('subirArchivos');
})->interceptor(AutenticacionInterceptor::class)->nombre('archivos');

$enrutador->post('/archivos/subir', function () {
    (new SubirArchivosControlador())->subir();
})->interceptor(AutenticacionInterceptor::class)->nombre('archivos.subir');

$enrutador->post('/archivos/eliminar', function () {
    $id = (int)($_POST['id'] ?? 0);
    (new SubirArchivosControlador())->eliminar($id);
})->interceptor(AutenticacionInterceptor::class)->nombre('archivos.eliminar');

$enrutador->post('/archivos/eliminar-carpeta', function () {
    (new SubirArchivosControlador())->eliminarCarpeta();
})->interceptor(AutenticacionInterceptor::class)->nombre('archivos.eliminar_carpeta');

$enrutador->get('/archivos/descargar/{id}', function ($id) {
    (new SubirArchivosControlador())->descargar((int)$id);
})->interceptor(AutenticacionInterceptor::class)->nombre('archivos.descargar');

$enrutador->get('/archivos/descargar-carpeta', function () {
    (new SubirArchivosControlador())->descargarCarpeta();
})->interceptor(AutenticacionInterceptor::class)->nombre('archivos.descargar_carpeta');

$enrutador->get('/api/archivos', function () {
    (new SubirArchivosControlador())->listar();
})->interceptor(ApiAuthInterceptor::class)->nombre('api.archivos');

$enrutador->get('/api/archivos/configuracion', function () {
    (new SubirArchivosControlador())->configuracion();
})->interceptor(ApiAuthInterceptor::class)->nombre('api.archivos.configuracion');

// Ejemplo de PDF generado con el framework
$enrutador->get('/ejemploPdf', function () {
    $pdf = new GeneradorPdf('vertical', 'A4');
    $pdf->establecerTitulo('Reporte Mensual de Ventas — Junio 2026');
    $pdf->establecerEncabezado('<strong>Mi Empresa S.A.</strong> | RUC: 3-101-999999 | ' . date('d/m/Y'));
    $pdf->establecerPie('Documento generado con liteFramework — litePdf');
    $pdf->establecerMargen('normal');

    $pdf->agregarParrafo('A continuacion se presentan los resultados de ventas del mes de junio 2026, discriminados por categoria, producto y vendedor.');

    $pdf->agregarTitulo('Resumen ejecutivo', 2);
    $pdf->agregarHtml('<div class="rejilla-automatica margen-inferior-normal">' .
        '<article class="alineacion-centrada evitar-salto"><p class="texto-2xl texto-negrita color-marca">$128,450.00</p><p class="texto-pequeno texto-negrita">Total facturado</p></article>' .
        '<article class="alineacion-centrada evitar-salto"><p class="texto-2xl texto-negrita color-exito">847</p><p class="texto-pequeno texto-negrita">Ordenes completadas</p></article>' .
        '<article class="alineacion-centrada evitar-salto"><p class="texto-2xl texto-negrita color-advertencia">12</p><p class="texto-pequeno texto-negrita">Pendientes</p></article>' .
        '<article class="alineacion-centrada evitar-salto"><p class="texto-2xl texto-negrita texto-suave">94.3%</p><p class="texto-pequeno texto-negrita">Satisfaccion</p></article>' .
    '</div>');

    $ventasPorCategoria = [
        ['Electronica', '42', '$54,230.00', 'Maria Lopez'],
        ['Muebles', '28', '$32,100.00', 'Carlos Ruiz'],
        ['Ropa', '156', '$18,450.00', 'Ana Mendez'],
        ['Alimentos', '312', '$12,800.00', 'Pedro Solis'],
        ['Deportes', '89', '$6,340.00', 'Maria Lopez'],
        ['Libreria', '134', '$3,120.00', 'Carlos Ruiz'],
        ['Jugueteria', '76', '$1,320.00', 'Ana Mendez'],
        ['Ferreteria', '10', '$90.00', 'Pedro Solis'],
    ];

    $pdf->agregarTitulo('Ventas por categoria', 2);
    $pdf->agregarTabla($ventasPorCategoria, ['Categoria', 'Ordenes', 'Total', 'Vendedor'], [25, 15, 20, 40]);

    $pdf->agregarSaltoPagina();

    $pdf->agregarTitulo('Top 5 productos mas vendidos', 2);

    $topProductos = [
        ['Laptop Pro X15', 'Electronica', '24', '$28,800.00', 'Alta'],
        ['Silla Ejecutiva Ergo', 'Muebles', '18', '$5,940.00', 'Alta'],
        ['Camiseta Algodon Premium', 'Ropa', '89', '$2,670.00', 'Media'],
        ['Arroz Integral 5kg', 'Alimentos', '210', '$1,890.00', 'Media'],
        ['Balon Futbol Profesional', 'Deportes', '45', '$2,025.00', 'Media'],
    ];

    $pdf->agregarTabla($topProductos, ['Producto', 'Categoria', 'Unidades', 'Total', 'Demanda'], [30, 20, 15, 20, 15]);

    $pdf->agregarTitulo('Observaciones', 2);
    $pdf->agregarLista([
        'La categoria Electronica representa el 42.2% del total facturado.',
        'Se recomienda aumentar el inventario de Laptop Pro X15 por alta demanda.',
        'Los productos de Alimentos tienen margen bajo pero alto volumen de rotacion.',
        'Maria Lopez lidera en ventas totales con $60,570.00 facturados.',
        'Se detecto una disminucion del 15% en Ferreteria respecto al mes anterior.',
    ]);

    $pdf->agregarLineaSeparadora();
    $pdf->agregarParrafo('Este reporte fue generado automaticamente usando el modulo GeneradorPdf de liteFramework. Los datos presentados son ficticios y se utilizan unicamente con fines demostrativos.');

    $pdf->renderizar();
})->interceptor(AutenticacionInterceptor::class)->nombre('ejemploPdf');

// Modulo generador PDF
$enrutador->get('/generador-pdf', function () {
    (new ModuloControlador())->indice('generadorPdf');
})->interceptor(AutenticacionInterceptor::class)->nombre('generadorPdf');

$enrutador->get('/generador-pdf/listado', function () {
    (new ModuloControlador())->indice('generadorPdf', 'listado');
})->interceptor(AutenticacionInterceptor::class)->nombre('generadorPdf.listado');

// Ejemplo de estadisticas generadas con el framework
$enrutador->get('/ejemploEstadisticas', function () {
    $est = new GeneradorEstadisticas("SELECT nombre_completo, estado_cuenta FROM operador ORDER BY id_operador LIMIT 8");
    $est->establecerTitulo('Resumen de Operadores');
    $est->establecerDescripcion('Estados de cuenta del sistema liteFramework.');
    $est->conAlias(['nombre_completo' => 'Operador', 'estado_cuenta' => 'Estado']);
    $est->comoKpi();
    $est->ejecutar();
    $est->renderizar();
})->interceptor(AutenticacionInterceptor::class)->nombre('ejemploEstadisticas');

// API endpoints (protegidos con ApiAuthInterceptor)
$enrutador->get('/api/refrescar-token', function () {
    header('Content-Type: application/json');
    $token = class_exists('SeguridadServidor') ? SeguridadServidor::generarTokenAntiFalsificacion() : '';
    echo json_encode(['nuevo_token' => $token]);
})->nombre('api.refrescar_token');

$enrutador->post('/api/diagnostico/reparar', function () {
    header('Content-Type: application/json');
    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    $tipo = $payload['tipo'] ?? '';
    $params = $payload['params'] ?? [];
    $resultado = RemediadorError::ejecutarReparacion($tipo, $params);
    echo json_encode($resultado);
})->interceptor(ApiAuthInterceptor::class)->nombre('api.diagnostico.reparar');

$enrutador->get('/api/operadores', function () {
    $operadores = Operador::todos();
    echo json_encode(array_map(function ($op) {
        return $op->aArreglo();
    }, $operadores));
})->interceptor(ApiAuthInterceptor::class)->nombre('api.operadores');

$enrutador->get('/api/operadores/{id}', function ($id) {
    $operador = Operador::buscar((int)$id);
    if (!$operador) {
        http_response_code(404);
        echo json_encode(['error' => 'Operador no encontrado']);
        return;
    }
    echo json_encode($operador->aArreglo());
})->interceptor(ApiAuthInterceptor::class)->nombre('api.operadores.mostrar');

$enrutador->get('/api/roles', function () {
    $roles = Rol::todos();
    echo json_encode(array_map(function ($r) {
        return $r->aArreglo();
    }, $roles));
})->interceptor(ApiAuthInterceptor::class)->nombre('api.roles');

// Rutas de errores (redirigen a la pagina publica)
foreach ([400, 401, 403, 404, 500, 503] as $codigo) {
    $enrutador->get("/error/{$codigo}", function () use ($codigo) {
        header('Location: /src/error.php?code=' . $codigo, true, 302);
        exit;
    })->nombre("error.{$codigo}");
}

Enrutador::registrarInstancia($enrutador);

return $enrutador;
