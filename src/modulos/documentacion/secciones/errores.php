<?php

return [
        'id' => 'errores',
        'titulo' => 'Manejo de errores',
        'icono' => '⚠',
        'etiquetas' => 'errores manejo excepciones log trazabilidad debug',
        'descripcion' => 'Sistema de manejo de errores y excepciones, registro de trazabilidad, logs y pagina de error personalizada.',
        'contenido' => '
            <h3 class="margen-inferior-pequeno">Registrar el manejador global</h3>
            <pre><code>ManejadorErrores::registrar();</code></pre>
            <p>Esto captura errores de PHP, excepciones no manejadas y shutdown fatal errors. Los registra en <code>storage/logs/</code> y muestra una pagina de error amigable.</p>

            <h3 class="margen-inferior-pequeno">Pagina de error</h3>
            <p>El archivo <code>src/error.php</code> muestra paginas de error estilizadas para los codigos 400, 401, 403, 404, 500 y 503.</p>
            <pre><code>http_response_code(404);
require DIRECTORIO_RAIZ . \'/src/error.php\';
return;</code></pre>

            <h3 class="margen-inferior-pequeno">Trazador de peticiones</h3>
            <p>Cada peticion recibe un <code>X-Trace-Id</code> unico para trazabilidad. Se puede usar para seguir una peticion a traves de los logs.</p>
            <pre><code>$trazaId = TrazadorPeticiones::obtenerIdTraza();

header(\'X-Trace-Id: \' . $trazaId);</code></pre>

            <h3 class="margen-inferior-pequeno">Logs de trazabilidad</h3>
            <p>El framework escribe automaticamente en <code>storage/logs/trazabilidad.log</code>:</p>
            <pre><code>RegistroAuditoria::info(\'MiModulo\', \'Operacion exitosa\', [\'id\' => 123]);
RegistroAuditoria::advertencia(\'MiModulo\', \'Alerta\', [\'detalle\' => \'...\']);
RegistroAuditoria::error(\'MiModulo\', \'Error critico\', [\'excepcion\' => $e->getMessage()]);</code></pre>

            <h3 class="margen-inferior-pequeno">Manejo try/catch en modulos</h3>
            <pre><code>try {
    $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
    $stmt = $conexion->query("SELECT * FROM tabla");
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    RegistroAuditoria::error(\'Modulo\', \'Error de base de datos\', [
        \'error\' => $e->getMessage(),
    ]);
    $datos = [];
}</code></pre>
        ',
    ];
