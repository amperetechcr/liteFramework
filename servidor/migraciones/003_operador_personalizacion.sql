CREATE TABLE IF NOT EXISTS `operador_personalizacion` (
    `id_operador` INT PRIMARY KEY,
    `configuracion` JSON NOT NULL,
    `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pers_operador` FOREIGN KEY (`id_operador`) REFERENCES `operador` (`id_operador`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
