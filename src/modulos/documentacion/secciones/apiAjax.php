<?php

return [
        'id' => 'apiAjax',
        'titulo' => 'API y AJAX',
        'icono' => '⚡',
        'etiquetas' => 'api ajax fetch json crud csrf token',
        'descripcion' => 'Endpoints CRUD genericos via POST, fetch con CSRF, manejo de tokens y notificaciones. ListaFiltrable para listados con paginacion.',
        'contenido' => '
            <p>El framework expone un endpoint generico <code>POST /api</code> que procesa operaciones CRUD para cualquier entidad registrada. El cliente JS envia los datos como <code>application/x-www-form-urlencoded</code> con token CSRF.</p>

            <h3 class="margen-inferior-pequeno">Fetch AJAX desde el cliente</h3>
            <pre><code>fetch(window.rutaApi || \'/api\', {
    method: \'POST\',
    headers: {
        \'Content-Type\': \'application/x-www-form-urlencoded\',
        \'X-Requested-With\': \'XMLHttpRequest\',
        \'X-CSRF-Token\': csrfToken()
    },
    body: \'accion_crud=crud&entidad=productos&accion=crear\' +
        \'&nombre=\' + encodeURIComponent(nombre) +
        \'&precio=\' + encodeURIComponent(precio) +
        \'&stock=\' + encodeURIComponent(stock) +
        \'&token_peticion=\' + encodeURIComponent(csrfToken())
})
.then(r => r.json())
.then(function(datos) {
    if (datos.estado_operacion === true) {
        notificar(\'Producto creado.\', \'exito\');
    } else {
        notificar(datos.mensaje_error || \'Error.\', \'peligro\');
    }
});</code></pre>

            <h3 class="margen-inferior-pequeno">Acciones CRUD disponibles</h3>
            <table>
                <thead><tr><th>Accion</th><th>Descripcion</th></tr></thead>
                <tbody>
                    <tr><td><code>crear</code></td><td>Inserta un nuevo registro</td></tr>
                    <tr><td><code>leer</code></td><td>Obtiene registro(s) por ID o filtro</td></tr>
                    <tr><td><code>actualizar</code></td><td>Modifica un registro existente</td></tr>
                    <tr><td><code>eliminar</code></td><td>Elimina un registro por ID</td></tr>
                </tbody>
            </table>

            <h3 class="margen-inferior-pequeno">Importante: window.rutaApi</h3>
            <p>Siempre usar <code>window.rutaApi || \'/api\'</code>. Nunca hardcodear <code>/api</code> porque si la aplicacion esta en un subdirectorio (ej: <code>/liteFramework/</code>), la ruta correcta es <code>/liteFramework/api</code>.</p>

            <h3 class="margen-inferior-pequeno">Manejo de token CSRF en JS</h3>
            <pre><code>function csrfToken() {
    if (typeof window.obtenerTokenCSRF === \'function\') {
        return window.obtenerTokenCSRF();
    }
    var meta = document.querySelector(\'meta[name="csrf-token"]\');
    return meta ? meta.getAttribute(\'content\') : \'\';
}</code></pre>

            <h3 class="margen-inferior-pequeno">Clase ListaFiltrable</h3>
            <p>Componente JS reutilizable que encapsula el patron de listado con filtros, busqueda con debounce y paginacion AJAX. Usado por los modulos de operadores y auditoria.</p>
            <pre><code>var lista = new window.ListaFiltrable({
    baseUrl: \'/productos\',
    containerId: \'contenedor-lista\',
    paginationSelector: \'.paginacion\',
    contadorId: \'contador-productos\',
    contadorSourceId: \'total-partial\',
    filtros: [
        { id: \'filtro-buscar\', paramName: \'buscar\' },
        { id: \'filtro-categoria\', paramName: \'categoria\' }
    ],
    busquedaId: \'filtro-buscar\',
    afterRender: function() {
        vincularBotonesEditar();
    }
});
lista.inicializarEventos();
lista.sincronizarConUrl();
lista.vincularPaginacion();</code></pre>
        ',
    ];
