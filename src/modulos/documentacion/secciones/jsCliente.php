<?php

return [
        'id' => 'jsCliente',
        'titulo' => 'JS del lado cliente',
        'icono' => '⎔',
        'etiquetas' => 'javascript cliente litePdf imprimir guardar token',
        'descripcion' => 'Libreria window.litePdf para disparar impresion y guardar plantillas. Utilidades compartidas: obtenerTokenCSRF, notificar, obtenerBasePath.',
        'contenido' => '
            <p>El framework incluye utilidades JavaScript como modulos ES y como funciones en <code>window</code> para usar desde cualquier pagina de la aplicacion.</p>

            <h3 class="margen-inferior-pequeno">window.litePdf — Generacion de PDF</h3>
            <pre><code>&lt;script src="/src/js/generadorPdfCliente.js"&gt;&lt;/script&gt;

&lt;button onclick="litePdf.imprimir()"&gt;Descargar PDF&lt;/button&gt;

litePdf.guardarPlantilla(\'Reporte\', contenidoHtml).then(function(id) {
    window.notificar(\'Plantilla guardada con ID: \' + id, \'exito\');
});

var token = litePdf.obtenerTokenCSRF();</code></pre>

            <h3 class="margen-inferior-pequeno">Utilidades globales</h3>
            <pre><code>var token = window.obtenerTokenCSRF();
window.notificar(\'Operacion completada\', \'exito\');
var base = window.obtenerBasePath();

window.alternarEstadoCarga(formulario, true);   // mostrar spinner
window.alternarEstadoCarga(formulario, false);  // ocultar spinner</code></pre>

            <h3 class="margen-inferior-pequeno">Notificaciones (NotificadorHubble)</h3>
            <pre><code>if (window.NotificadorHubble) {
    window.NotificadorHubble.mostrar(\'Mensaje de exito\', \'exito\');
    window.NotificadorHubble.mostrar(\'Mensaje de error\', \'peligro\');
    window.NotificadorHubble.mostrar(\'Advertencia\', \'advertencia\');
}</code></pre>

            <h3 class="margen-inferior-pequeno">Estructura de archivos JS</h3>
            <pre><code>src/js/
├── principal.js              # entry point — imports
├── seguridad.js              # SeguridadSistema
├── generadorPdfCliente.js    # window.litePdf
├── api/
│   ├── utilidades.js         # obtenerTokenCSRF, notificar, etc.
│   ├── ListaFiltrable.js     # clase lista + filtros + paginacion
│   ├── formularioCrud.js     # CRUD via AJAX
│   └── manejoErrores.js      # manejo global de errores
├── modulos/
│   ├── operadores.js         # logica del modulo operadores
│   ├── auditoria.js          # logica del modulo auditoria
│   └── documentacion.js      # buscador + modales
└── ui/
    ├── lite.js               # personalizacion UI (tema, paleta)
    ├── navegacion.js         # navegacion SPA
    └── notificaciones.js     # NotificadorHubble</code></pre>
        ',
    ];
