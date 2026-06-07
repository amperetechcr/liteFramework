<?php

return [
        'id' => 'seguridad',
        'titulo' => 'Seguridad y RBAC',
        'icono' => '🔒',
        'etiquetas' => 'seguridad rbac permisos roles csrf sesion auditoria',
        'descripcion' => 'Control de acceso basado en roles, proteccion CSRF, sesiones estrictas con huella digital, registro de auditoria y sanitizacion de entrada.',
        'contenido' => '
            <p>El sistema de seguridad del framework opera en multiples capas: autenticacion de sesion, control de acceso por permisos RBAC, proteccion contra CSRF, sanitizacion de datos de entrada, y trazabilidad completa via registro de auditoria.</p>

            <h3 class="margen-inferior-pequeno">Verificar permisos RBAC</h3>
            <p>Los permisos siguen el formato <code>entidad.accion</code>. Se verifican en cada endpoint que requiera control de acceso.</p>
            <pre><code>if (!ControlAccesoRBAC::tienePermiso(\'productos.crear\')) {
    http_response_code(403);
    echo json_encode([\'error\' => \'Sin permiso para crear productos\']);
    return;
}</code></pre>

            <h3 class="margen-inferior-pequeno">Proteccion CSRF</h3>
            <p>Cada formulario debe incluir un token CSRF generado por el servidor y validado al procesar la peticion.</p>
            <pre><code>$token = SeguridadServidor::generarTokenAntiFalsificacion();

&lt;input type="hidden" name="token_peticion" value="&lt;?= $token ?&gt;"&gt;

if (!SeguridadServidor::validarTokenAntiFalsificacion($_POST[\'token_peticion\'] ?? \'\')) {
    http_response_code(403);
    echo json_encode([\'error\' => \'Token CSRF invalido o expirado\']);
    exit;
}</code></pre>

            <h3 class="margen-inferior-pequeno">Registro de auditoria</h3>
            <p>Toda accion sensible debe registrarse en la bitacora del sistema para trazabilidad.</p>
            <pre><code>RegistroAuditoria::auditoria(\'producto.crear\', \'Creacion de producto\', [
    \'producto_id\' => $producto->id,
    \'nombre\' => $producto->nombre,
    \'precio\' => $producto->precio,
]);

RegistroAuditoria::info(\'Modulo\', \'accion\', $detalles);
RegistroAuditoria::advertencia(\'Modulo\', \'accion\', $detalles);
RegistroAuditoria::error(\'Modulo\', \'accion\', $detalles);</code></pre>

            <h3 class="margen-inferior-pequeno">Sanitizacion de entrada</h3>
            <pre><code>SanitizadorEntrada::limpiar($_POST);
$datosSeguros = SanitizadorEntrada::limpiarArreglo($_POST);</code></pre>

            <h3 class="margen-inferior-pequeno">Interceptores de ruta</h3>
            <p>Las rutas se protegen con interceptores que verifican autenticacion antes de ejecutar el handler.</p>
            <pre><code>$enrutador->get(\'/productos\', function() {
    (new ModuloControlador())->indice(\'productos\');
})->interceptor(AutenticacionInterceptor::class)->nombre(\'productos\');

$enrutador->get(\'/api/productos\', function() {
    echo json_encode(Producto::todos());
})->interceptor(ApiAuthInterceptor::class)->nombre(\'api.productos\');</code></pre>
        ',
    ];
