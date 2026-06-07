<?php

return [
        'id' => 'modulos',
        'titulo' => 'Crear un modulo',
        'icono' => '⊞',
        'etiquetas' => 'modulo crear panel controlador vista ruta sidebar',
        'descripcion' => 'Guia paso a paso para crear nuevos modulos en el panel de control: archivos necesarios, rutas, sidebar y dashboard.',
        'contenido' => '
            <p>Crear un modulo en el panel de control requiere tocar <strong>4 archivos</strong>. No es necesario crear un controlador nuevo — el framework usa <code>ModuloControlador</code> generico que carga vistas desde <code>src/modulos/</code>.</p>

            <h3 class="margen-inferior-pequeno">Paso 1: Crear el archivo del modulo</h3>
            <p>Crear <code>src/modulos/miModulo/miModulo.php</code> con la estructura estandar:</p>
            <pre><code>&lt;?php
require_once __DIR__ . \'/../../plantillas/modulo_cabecera.php\';

$partial = $_GET[\'partial\'] ?? \'\';

if ($partial === \'lista\') {
    require __DIR__ . \'/listado.php\';
    return;
}

if ($esAjax && !$partial) {
    echo \'&lt;div data-titulo-pagina="Mi Modulo"&gt;&lt;/div&gt;\';
}

if (!$esAjax) {
    $tituloPagina = \'Mi Modulo\';
    $moduloActivo = \'miModulo\';
    require DIRECTORIO_RAIZ . \'/src/plantillas/encabezado.php\';
}
?&gt;

&lt;h1&gt;Mi Modulo&lt;/h1&gt;
&lt;!-- contenido --&gt;

&lt;script src="&lt;?= URL_BASE ?&gt;/src/js/modulos/miModulo.js"&gt;&lt;/script&gt;

&lt;?php if (!$esAjax): require DIRECTORIO_RAIZ . \'/src/plantillas/pie.php\'; endif; ?&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Paso 2: Registrar ruta</h3>
            <p>En <code>rutas/web.php</code>:</p>
            <pre><code>$enrutador->get(\'/miModulo\', function() {
    (new ModuloControlador())->indice(\'miModulo\');
})->interceptor(AutenticacionInterceptor::class)->nombre(\'miModulo\');</code></pre>

            <h3 class="margen-inferior-pequeno">Paso 3: Agregar al sidebar</h3>
            <p>En <code>src/plantillas/encabezado.php</code>, agregar entrada al array <code>$enlacesNav</code>:</p>
            <pre><code>\'miModulo\' => [\'ruta\' => \'/miModulo\', \'etiqueta\' => \'Mi Modulo\', \'icono\' => \'&lt;svg...&gt;&lt;/svg&gt;\'],</code></pre>

            <h3 class="margen-inferior-pequeno">Paso 4: Agregar al dashboard</h3>
            <p>En <code>src/modulos/panelControl/panelControl.php</code>, agregar entrada al array <code>$modulos</code>:</p>
            <pre><code>\'miModulo\' => [\'ruta\' => \'/miModulo\', \'titulo\' => \'Mi Modulo\', \'desc\' => \'Descripcion breve.\', \'stats\' => \'Info\'],</code></pre>

            <h3 class="margen-inferior-pequeno">Paso 5 (opcional): Crear modelo</h3>
            <p>Si el modulo usa base de datos, crear <code>servidor/modelos/MiModelo.php</code> extendiendo <code>Modelo</code> y registrarlo en <code>servidor/autoload.php</code>.</p>

            <h3 class="margen-inferior-pequeno">Paso 6 (opcional): Acciones personalizadas</h3>
            <p>Si el modulo necesita acciones que no encajan en el CRUD generico (subidas, descargas, generacion de PDF), crear un controlador dedicado en <code>servidor/controladores/</code> que extienda <code>ControladorBase</code>.</p>

            <h3 class="margen-inferior-pequeno">Patron de partials con AJAX</h3>
            <p>Los modulos usan partials para recargar solo la seccion de listado sin refrescar toda la pagina. El parametro <code>?partial=lista&amp;ajax=1</code> se envia via fetch y el partial solo retorna HTML.</p>
            <pre><code>if ($partial === \'lista\') {
    require __DIR__ . \'/listado.php\';
    return;  // no cargar layout
}</code></pre>

            <h3 class="margen-inferior-pequeno">Convenciones</h3>
            <ul>
                <li><strong>Nombre del modulo:</strong> camelCase (ej: <code>miModulo</code>)</li>
                <li><strong>URL de la ruta:</strong> kebab-case (ej: <code>/mi-modulo</code>)</li>
                <li><strong>Directorio:</strong> <code>src/modulos/nombreModulo/</code></li>
                <li><strong>Archivo principal:</strong> mismo nombre que el directorio</li>
                <li><strong>Partials:</strong> archivos separados en el mismo directorio</li>
                <li><strong>JS del modulo:</strong> <code>src/js/modulos/nombreModulo.js</code> (patron IIFE)</li>
                <li><strong>Paginacion:</strong> siempre con PDO directo, nunca con ORM</li>
                <li><strong>CSRF:</strong> siempre incluir token en formularios</li>
            </ul>
        ',
    ];
