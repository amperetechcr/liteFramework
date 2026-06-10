<?php

return [
        'id' => 'validacion',
        'titulo' => 'Validacion',
        'icono' => '✓',
        'etiquetas' => 'validacion formulario reglas requerido correo unico',
        'descripcion' => 'Clase Validador con 14 reglas encadenables. Soporta validacion de formularios, archivos, unicidad en BD y mensajes de error en español.',
        'contenido' => '
            <p>La clase <code>Validador</code> procesa arrays de datos (tipicamente <code>$_POST</code>) contra un conjunto de reglas definidas con sintaxis de pipe (<code>|</code>). Los errores se devuelven en español.</p>

            <h3 class="margen-inferior-pequeno">Uso basico</h3>
            <pre><code>$validador = new Validador($_POST, [
    \'nombre\' => \'requerido|minimo:3|maximo:150\',
    \'correo\' => \'requerido|correo|unico:operador,correo_electronico\',
    \'precio\' => \'requerido|numero|minimo:0\',
    \'categoria_id\' => \'requerido|entero\',
    \'clave\' => \'requerido|minimo:8|regex:/[A-Z]/|confirmado\',
]);

if ($validador->falla()) {
    $errores = $validador->obtenerErrores();
    // [\'nombre\' => \'El campo nombre es obligatorio.\', ...]
}

if ($validador->pasa()) {
    $datosLimpios = $validador->obtenerDatosValidados();
    Producto::crear($datosLimpios);
}</code></pre>

            <h3 class="margen-inferior-pequeno">14 reglas disponibles</h3>
            <table>
                <thead><tr><th>Regla</th><th>Ejemplo</th><th>Descripcion</th></tr></thead>
                <tbody>
                    <tr><td><code>requerido</code></td><td><code>nombre</code></td><td>No puede estar vacio</td></tr>
                    <tr><td><code>correo</code></td><td><code>email</code></td><td>Formato de email valido</td></tr>
                    <tr><td><code>minimo:n</code></td><td><code>minimo:3</code></td><td>Minimo n caracteres</td></tr>
                    <tr><td><code>maximo:n</code></td><td><code>maximo:150</code></td><td>Maximo n caracteres</td></tr>
                    <tr><td><code>numero</code></td><td><code>precio</code></td><td>Debe ser numerico</td></tr>
                    <tr><td><code>entero</code></td><td><code>stock</code></td><td>Debe ser entero</td></tr>
                    <tr><td><code>unico:tabla,col</code></td><td><code>unico:operador,correo</code></td><td>Valor unico en BD</td></tr>
                    <tr><td><code>regex:patron</code></td><td><code>regex:/[A-Z]/</code></td><td>Debe coincidir con regex</td></tr>
                    <tr><td><code>confirmado</code></td><td><code>clave</code></td><td>Requiere campo <code>clave_confirmacion</code></td></tr>
                    <tr><td><code>archivo</code></td><td><code>documento</code></td><td>Debe ser archivo valido</td></tr>
                    <tr><td><code>imagen</code></td><td><code>foto</code></td><td>Debe ser imagen</td></tr>
                    <tr><td><code>maxTamano:n</code></td><td><code>maxTamano:5</code></td><td>Tamano max en MB</td></tr>
                    <tr><td><code>diferente:campo</code></td><td><code>diferente:nombre</code></td><td>Distinto a otro campo</td></tr>
                    <tr><td><code>en:val1,val2</code></td><td><code>en:activo,inactivo</code></td><td>Valor dentro de lista</td></tr>
                </tbody>
            </table>

            <h3 class="margen-inferior-pequeno">Validacion programatica</h3>
            <p>Tambien se pueden agregar reglas una por una:</p>
            <pre><code>$validador = new Validador($datos);
$validador->agregarRegla(\'nombre\', \'requerido\');
$validador->agregarRegla(\'correo\', \'correo\');
$validador->agregarRegla(\'precio\', \'numero\');
$validador->agregarRegla(\'precio\', \'minimo:0\');
$validador->agregarRegla(\'sku\', \'unico:productos,sku\');</code></pre>
        ',
    ];
