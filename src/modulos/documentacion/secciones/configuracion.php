<?php

return [
        'id' => 'configuracion',
        'titulo' => 'Configuracion',
        'icono' => '⚙',
        'etiquetas' => 'configuracion sistema env variables limites php ini',
        'descripcion' => 'Sistema de configuracion dinamica desde BD con fallback a .env, cache de 30s, optimistic locking y tipos (texto, numero, booleano, json).',
        'contenido' => '
            <p>La configuracion del sistema se almacena en la tabla <code>configuracion_sistema</code> con fallback al archivo <code>.env</code>. La clase <code>ConfiguracionSistema</code> abstrae el acceso con cache estatico de 30 segundos y optimistic locking via columna <code>version</code>.</p>

            <h3 class="margen-inferior-pequeno">Leer configuracion</h3>
            <pre><code>$tamanoMax = (int)ConfiguracionSistema::obtener(\'ARCHIVO_TAMANO_MAXIMO_MB\', 40);
$extensiones = ConfiguracionSistema::obtener(\'ARCHIVO_EXTENSIONES_PERMITIDAS\', \'jpg,png,pdf\');
$modoMantenimiento = ConfiguracionSistema::obtener(\'MODO_MANTENIMIENTO\', false);

$todas = ConfiguracionSistema::obtenerTodas();</code></pre>

            <h3 class="margen-inferior-pequeno">Escribir configuracion</h3>
            <pre><code>ConfiguracionSistema::establecer(\'MODO_MANTENIMIENTO\', true, \'booleano\', \'Activar mantenimiento del sistema\');
ConfiguracionSistema::establecer(\'TITULO_SITIO\', \'Mi Aplicacion\', \'texto\', \'Titulo del sitio web\');
ConfiguracionSistema::establecer(\'ITEMS_POR_PAGINA\', 25, \'numero\', \'Registros por pagina\');</code></pre>

            <h3 class="margen-inferior-pequeno">Tipos soportados</h3>
            <table>
                <thead><tr><th>Tipo</th><th>Ejemplo</th></tr></thead>
                <tbody>
                    <tr><td><code>texto</code></td><td>Cadenas de texto</td></tr>
                    <tr><td><code>numero</code></td><td>Enteros o decimales</td></tr>
                    <tr><td><code>booleano</code></td><td>true / false</td></tr>
                    <tr><td><code>json</code></td><td>Arrays u objetos serializados</td></tr>
                </tbody>
            </table>

            <h3 class="margen-inferior-pequeno">Limites de PHP dinamicos</h3>
            <p>PHP no permite cambiar <code>upload_max_filesize</code> en runtime. La solucion es regenerar <code>.user.ini</code> en el docroot. La clase <code>GeneradorIniServidor</code> maneja esto con escritura atomica y backup.</p>
            <pre><code>$limites = GeneradorIniServidor::limitesActualesPHP();

GeneradorIniServidor::generar([
    \'upload_max_filesize\' => \'50M\',
    \'post_max_size\' => \'60M\',
    \'memory_limit\' => \'512M\',
    \'max_execution_time\' => \'300\',
]);

GeneradorIniServidor::revertir();  // restaurar backup</code></pre>
        ',
    ];
