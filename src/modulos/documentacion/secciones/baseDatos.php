<?php

return [
        'id' => 'baseDatos',
        'titulo' => 'Base de datos',
        'icono' => '🗄',
        'etiquetas' => 'base datos conexion pdo mysql sqlite singleton',
        'descripcion' => 'Conexion PDO singleton con soporte MySQL y fallback automatico a SQLite. Consultas parametrizadas obligatorias.',
        'contenido' => '
            <h3 class="margen-inferior-pequeno">Obtener conexion</h3>
            <pre><code>$conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();</code></pre>
            <p>La conexion es un singleton. La primera llamada establece la conexion y las subsiguientes devuelven la misma instancia. Soporta MySQL (produccion) y SQLite (desarrollo/fallback).</p>

            <h3 class="margen-inferior-pequeno">Configuracion en .env</h3>
            <pre><code>DB_HOST=localhost
DB_PORT=3306
DB_NOMBRE=liteframework
DB_USUARIO=root
DB_CLAVE=

DB_TIPO=mysql
DB_ARCHIVO_SQLITE=storage/database.sqlite</code></pre>

            <h3 class="margen-inferior-pequeno">Consultas preparadas (obligatorio)</h3>
            <pre><code>$stmt = $conexion->prepare("SELECT * FROM productos WHERE precio >= :minimo AND stock > :stock");
$stmt->bindValue(\':minimo\', 100);
$stmt->bindValue(\':stock\', 0, PDO::PARAM_INT);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $fila[\'nombre\'];
}</code></pre>

            <h3 class="margen-inferior-pequeno">INSERT, UPDATE, DELETE</h3>
            <pre><code>$stmt = $conexion->prepare("INSERT INTO productos (nombre, precio) VALUES (:nombre, :precio)");
$stmt->execute([\':nombre\' => \'Laptop\', \':precio\' => 999.99]);
$ultimoId = (int)$conexion->lastInsertId();

$stmt = $conexion->prepare("UPDATE productos SET precio = :precio WHERE id = :id");
$stmt->execute([\':precio\' => 899.99, \':id\' => 1]);
$afectadas = $stmt->rowCount();

$stmt = $conexion->prepare("DELETE FROM productos WHERE id = :id");
$stmt->execute([\':id\' => 1]);</code></pre>

            <h3 class="margen-inferior-pequeno">Transacciones</h3>
            <pre><code>$conexion->beginTransaction();
try {
    $conexion->prepare("UPDATE cuentas SET saldo = saldo - :monto WHERE id = :id")
        ->execute([\':monto\' => 100, \':id\' => 1]);
    $conexion->prepare("UPDATE cuentas SET saldo = saldo + :monto WHERE id = :id")
        ->execute([\':monto\' => 100, \':id\' => 2]);
    $conexion->commit();
} catch (Exception $e) {
    $conexion->rollBack();
    throw $e;
}</code></pre>

            <h3 class="margen-inferior-pequeno">Convenciones SQL</h3>
            <ul>
                <li>Nombres de tablas y columnas en <strong>snake_case</strong> español</li>
                <li>Siempre usar consultas preparadas con bindValue/bindParam</li>
                <li>Usar <code>PDO::PARAM_INT</code> para valores enteros en LIMIT/OFFSET</li>
                <li>Usar <code>PDO::FETCH_ASSOC</code> para obtener arrays asociativos</li>
                <li>Usar <code>ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci</code></li>
                <li>Nunca concatenar variables directamente en el SQL</li>
            </ul>
        ',
    ];
