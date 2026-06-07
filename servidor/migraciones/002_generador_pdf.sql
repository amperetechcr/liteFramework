CREATE TABLE IF NOT EXISTS `documento_pdf` (
    `id_documento` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(255) NOT NULL,
    `contenido_html` LONGTEXT NOT NULL,
    `id_operador` INT NULL,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_documento_operador`
        FOREIGN KEY (`id_operador`) REFERENCES `operador` (`id_operador`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_documento_operador` ON `documento_pdf` (`id_operador`);
CREATE INDEX `idx_documento_fecha` ON `documento_pdf` (`fecha_creacion`);

INSERT IGNORE INTO `permisos` (`clave_permiso`, `descripcion`) VALUES
    ('documentoPdf.crear', 'Crear documentos PDF'),
    ('documentoPdf.leer', 'Consultar documentos PDF'),
    ('documentoPdf.actualizar', 'Modificar documentos PDF'),
    ('documentoPdf.eliminar', 'Eliminar documentos PDF');

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 1, `id_permiso` FROM `permisos` WHERE `clave_permiso` LIKE 'documentoPdf.%';

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 2, `id_permiso` FROM `permisos`
WHERE `clave_permiso` IN ('documentoPdf.crear', 'documentoPdf.leer');

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 3, `id_permiso` FROM `permisos`
WHERE `clave_permiso` = 'documentoPdf.leer';
