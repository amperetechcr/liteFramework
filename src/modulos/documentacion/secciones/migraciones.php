<?php

return [
        'id' => 'migraciones',
        'titulo' => 'Migraciones',
        'icono' => '⬆',
        'etiquetas' => 'migraciones sql base datos tabla permisos',
        'descripcion' => 'Sistema de migraciones SQL numeradas con ejecucion desde panel o CLI. Soporta respaldos automaticos antes de cada ejecucion.',
        'contenido' => '
            <p>Las migraciones son archivos SQL numerados almacenados en <code>servidor/migraciones/</code>. Se ejecutan en orden secuencial y el sistema lleva registro de cuales ya fueron aplicadas.</p>

            <h3 class="margen-inferior-pequeno">Crear una migracion</h3>
            <pre><code>-- servidor/migraciones/003_crear_productos.sql
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_precio (precio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permisos (clave_permiso, descripcion) VALUES
    (\'productos.crear\', \'Crear productos\'),
    (\'productos.leer\', \'Consultar productos\'),
    (\'productos.actualizar\', \'Modificar productos\'),
    (\'productos.eliminar\', \'Eliminar productos\');

INSERT IGNORE INTO permisos_rol (id_rol, id_permiso)
SELECT 1, id_permiso FROM permisos WHERE clave_permiso LIKE \'productos.%\';</code></pre>

            <h3 class="margen-inferior-pequeno">Ejecutar migraciones</h3>
            <p>Desde el panel de control: <strong>Migraciones → Ejecutar pendientes</strong>. Por CLI:</p>
            <pre><code>php servidor/migrar.php list       # listar migraciones y su estado
php servidor/migrar.php ejecutar    # ejecutar pendientes</code></pre>

            <h3 class="margen-inferior-pequeno">Convenciones</h3>
            <ul>
                <li>Los archivos se nombran <code>NNN_descripcion.sql</code> (ej: <code>003_crear_productos.sql</code>)</li>
                <li>Siempre usar <code>IF NOT EXISTS</code> para CREATE TABLE</li>
                <li>Siempre usar <code>INSERT IGNORE INTO</code> para datos semilla</li>
                <li>Usar <code>ENGINE=InnoDB DEFAULT CHARSET=utf8mb4</code> en todas las tablas</li>
                <li>Crear indices para columnas usadas en WHERE, JOIN y ORDER BY</li>
                <li>Agregar los permisos RBAC correspondientes en la misma migracion</li>
                <li>Asignar permisos al rol Super Admin (id_rol=1) por defecto</li>
            </ul>
        ',
    ];
