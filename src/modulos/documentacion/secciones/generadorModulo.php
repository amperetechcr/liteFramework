<?php

return [
    'id' => 'generadorModulo',
    'titulo' => 'Generador de Modulos',
    'icono' => '⭐',
    'etiquetas' => 'generador modulo crud scaffolding modelo migracion controlador api vista js',
    'descripcion' => 'Generador rapido de modulos CRUD desde el panel. Crea modelo, migracion, controlador API, vista y JS en un solo clic.',
    'contenido' => '
        <p>El Generador de Modulos permite crear la estructura completa de un modulo CRUD en segundos, directamente desde el panel de control o por linea de comandos. Genera 7 artefactos en una sola operacion.</p>

        <h3 class="margen-inferior-pequeno">Acceso</h3>
        <p>Desde el panel: sidebar → <strong>Generador</strong> o directamente en <code>/generador-modulo</code></p>

        <h3 class="margen-inferior-pequeno">Como funciona</h3>
        <ol>
            <li>Ingresa el nombre de la clase (PascalCase, ej: <code>Producto</code>)</li>
            <li>Opcionalmente especifica el nombre de la tabla (se infiere automaticamente)</li>
            <li>Agrega los campos con nombre, tipo y reglas (requerido, unico)</li>
            <li>Haz clic en "Generar Modulo"</li>
        </ol>

        <h3 class="margen-inferior-pequeno">Archivos generados</h3>
        <table class="tabla-responsiva">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Descripcion</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><code>servidor/migraciones/NNN_crear_TABLA.sql</code></td><td>Migracion SQL con columnas y tipos</td></tr>
                <tr><td><code>servidor/modelos/Clase.php</code></td><td>Modelo ORM extendiendo <code>Modelo</code></td></tr>
                <tr><td><code>servidor/api/controladores/ClaseControlador.php</code></td><td>API CRUD con validacion</td></tr>
                <tr><td><code>src/modulos/clase/clase.php</code></td><td>Vista con tabla, modal formulario y permisos</td></tr>
                <tr><td><code>src/js/modulos/clase.js</code></td><td>JS con fetch, CRUD, modal, notificaciones</td></tr>
                <tr><td><code>rutas/web.php</code></td><td>7 rutas (modulo + 5 API + 1 GET)</td></tr>
                <tr><td><code>servidor/autoload.php</code></td><td>2 entradas (modelo + controlador API)</td></tr>
            </tbody>
        </table>

        <h3 class="margen-inferior-pequeno">Tipos de campo disponibles</h3>
        <table class="tabla-responsiva">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>SQL</th>
                    <th>Input HTML</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><code>string</code></td><td>VARCHAR(255)</td><td>text</td></tr>
                <tr><td><code>text</code></td><td>TEXT</td><td>textarea</td></tr>
                <tr><td><code>int</code></td><td>INTEGER</td><td>number</td></tr>
                <tr><td><code>decimal</code></td><td>DECIMAL(12,2)</td><td>number (step=0.01)</td></tr>
                <tr><td><code>bool</code></td><td>INTEGER DEFAULT 0</td><td>checkbox</td></tr>
                <tr><td><code>email</code></td><td>VARCHAR(255)</td><td>email</td></tr>
                <tr><td><code>date</code></td><td>DATE</td><td>date</td></tr>
                <tr><td><code>datetime</code></td><td>DATETIME</td><td>datetime-local</td></tr>
            </tbody>
        </table>

        <h3 class="margen-inferior-pequeno">Reglas de validacion</h3>
        <ul>
            <li><code>required</code> — campo obligatorio (NOT NULL en BD, required en HTML)</li>
            <li><code>unique</code> — valor unico (UNIQUE en BD, validacion en controlador)</li>
        </ul>

        <h3 class="margen-inferior-pequeno">Linea de comandos</h3>
        <p>Tambien disponible via consola:</p>
        <pre><code>php servidor/consola/generar_modulo.php Producto \
    --campos="nombre:string:required,precio:decimal,stock:int" \
    --tabla=producto</code></pre>

        <h3 class="margen-inferior-pequeno">Lo que genera automaticamente</h3>
        <ul>
            <li><strong>ID autoincremental</strong> — <code>id_tabla INTEGER PRIMARY KEY AUTOINCREMENT</code></li>
            <li><strong>Timestamps</strong> — <code>fecha_creacion</code> y <code>fecha_actualizacion</code> con DEFAULT CURRENT_TIMESTAMP</li>
            <li><strong>Permisos RBAC</strong> — 4 permisos (crear, leer, actualizar, eliminar) asignados al rol Super Admin</li>
            <li><strong>Indices UNIQUE</strong> — para campos marcados como unicos</li>
            <li><strong>Foreign Keys</strong> — para campos que terminan en <code>_id</code> (ej: <code>categoria_id</code> &rarr; REFERENCES categoria(id_categoria) ON DELETE CASCADE)</li>
            <li><strong>Validacion</strong> — reglas <code>required</code>, <code>unique</code>, tipo <code>email</code>/<code>int</code>/<code>decimal</code> en modelo y controlador</li>
            <li><strong>Type casting</strong> — <code>$casts</code> en el modelo para int, float, bool</li>
            <li><strong>fecha_actualizacion</strong> — se actualiza automaticamente al guardar cambios</li>
        </ul>

        <h3 class="margen-inferior-pequeno">Arquitectura del servicio</h3>
        <p>La logica de generacion reside en <code>servidor/servicios/GeneradorModulo.php</code>, que expone dos metodos estaticos:</p>
        <ul>
            <li><code>GeneradorModulo::generar(string $clase, array $campos, ?string $tabla): array</code> — genera todos los archivos</li>
            <li><code>GeneradorModulo::parsearCamposDesdeArgs(array $raw): array</code> — parsea el formato <code>nombre:tipo:reglas</code></li>
        </ul>
        <p>El controlador <code>GeneradorModuloApiControlador</code> expone el endpoint <code>generar_modulo</code> via POST a <code>/api</code>, y el CLI <code>servidor/consola/generar_modulo.php</code> es un wrapper que delega en el servicio.</p>
    ',
];
