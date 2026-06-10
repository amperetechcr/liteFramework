<?php

return [
        'id' => 'orm',
        'titulo' => 'ORM — Modelo Active Record',
        'icono' => 'DB',
        'etiquetas' => 'orm base datos modelo crud query builder relaciones',
        'descripcion' => 'Clase base Modelo con CRUD, query builder fluido, relaciones (perteneceA, tieneMuchos) y casteo automatico de tipos.',
        'contenido' => '
            <p>El ORM del framework implementa el patron <strong>Active Record</strong>. Cada tabla de la base de datos se representa con una clase que extiende <code>Modelo</code>. No se require escribir SQL manual para operaciones basicas — el ORM genera consultas parametrizadas automaticamente, protegiendo contra inyeccion SQL.</p>

            <h3 class="margen-inferior-pequeno">Definir un modelo</h3>
            <p>Cada modelo declara la tabla, la columna ID, los campos rellenables (proteccion contra mass-assignment) y los tipos para casteo automatico.</p>
            <pre><code>class Producto extends Modelo {
    protected static $tabla = \'productos\';
    protected static $idColumna = \'id\';
    protected static $rellenable = [\'nombre\', \'descripcion\', \'precio\', \'stock\'];
    protected static $tipos = [
        \'id\' => \'int\',
        \'precio\' => \'float\',
        \'stock\' => \'int\',
        \'activo\' => \'bool\',
    ];

    public function categoria() {
        return $this->perteneceA(Categoria::class, \'categoria_id\', \'id\');
    }

    public function ventas() {
        return $this->tieneMuchos(Venta::class, \'producto_id\', \'id\');
    }
}</code></pre>

            <h3 class="margen-inferior-pequeno">CRUD basico</h3>
            <pre><code>$producto = Producto::buscar(1);       // buscar por ID
$todos = Producto::todos();             // todos los registros
$total = Producto::contar();            // contar registros
$existe = Producto::existe(\'nombre\', \'Laptop\');  // verificar existencia

$nuevo = Producto::crear([
    \'nombre\' => \'Laptop Pro\',
    \'precio\' => 999.99,
    \'stock\' => 50,
]);

$producto->precio = 899.99;
$producto->guardar();                   // UPDATE
$producto->eliminar();                  // DELETE</code></pre>

            <h3 class="margen-inferior-pequeno">Query Builder fluido</h3>
            <p>Para consultas con filtros se usa el query builder encadenable. <strong>Importante:</strong> para paginacion usar consultas PDO directas, no el ORM (ver nota en documentacion de paginacion).</p>
            <pre><code>$productos = Producto::donde(\'precio\', \'>=\', 100)
    ->yDonde(\'stock\', \'>\', 0)
    ->ordenarPor(\'nombre\', \'ASC\')
    ->limite(20)
    ->obtener();

$primero = Producto::donde(\'activo\', \'=\', 1)
    ->ordenarPor(\'fecha_creacion\', \'DESC\')
    ->primero();</code></pre>

            <h3 class="margen-inferior-pequeno">Relaciones</h3>
            <pre><code>$producto = Producto::buscar(1);
$cat = $producto->categoria();   // perteneceA
$ventas = $producto->ventas();   // tieneMuchos

$producto->aArreglo();           // convertir a array asociativo</code></pre>

            <h3 class="margen-inferior-pequeno">Cuando usar PDO directo</h3>
            <p>Para paginacion o consultas muy complejas, usar PDO directo. El ORM puede tener problemas de estado estatico compartido con <code>limite()</code> y <code>saltar()</code> encadenados.</p>
            <pre><code>$conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
$stmt = $conexion->prepare("SELECT * FROM productos WHERE precio >= :min ORDER BY nombre LIMIT :lim OFFSET :off");
$stmt->bindValue(\':min\', 100);
$stmt->bindValue(\':lim\', 20, PDO::PARAM_INT);
$stmt->bindValue(\':off\', 0, PDO::PARAM_INT);
$stmt->execute();
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);</code></pre>
        ',
    ];
