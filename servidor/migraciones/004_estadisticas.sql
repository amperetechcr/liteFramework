CREATE TABLE IF NOT EXISTS `estadistica` (
    `id_estadistica` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(255) NOT NULL,
    `descripcion` TEXT NULL,
    `consulta_sql` TEXT NOT NULL,
    `tipo_visualizacion` VARCHAR(30) DEFAULT 'tarjetas',
    `columnas_mostrar` TEXT NULL,
    `configuracion_visual` TEXT NULL,
    `id_operador` INT NULL,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_estadistica_operador`
        FOREIGN KEY (`id_operador`) REFERENCES `operador` (`id_operador`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_estadistica_operador` ON `estadistica` (`id_operador`);
CREATE INDEX `idx_estadistica_fecha` ON `estadistica` (`fecha_creacion`);

INSERT IGNORE INTO `permisos` (`clave_permiso`, `descripcion`) VALUES
    ('estadistica.crear', 'Crear estadisticas'),
    ('estadistica.leer', 'Consultar estadisticas'),
    ('estadistica.actualizar', 'Modificar estadisticas'),
    ('estadistica.eliminar', 'Eliminar estadisticas');

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 1, `id_permiso` FROM `permisos` WHERE `clave_permiso` LIKE 'estadistica.%';

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 2, `id_permiso` FROM `permisos`
WHERE `clave_permiso` IN ('estadistica.crear', 'estadistica.leer');

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 3, `id_permiso` FROM `permisos`
WHERE `clave_permiso` = 'estadistica.leer';
