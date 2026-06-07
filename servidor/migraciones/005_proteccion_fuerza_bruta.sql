CREATE TABLE IF NOT EXISTS `intento_acceso` (
    `id_intento` INT AUTO_INCREMENT PRIMARY KEY,
    `correo_intentado` VARCHAR(100) NOT NULL,
    `direccion_ip` VARCHAR(45) NOT NULL,
    `exitoso` TINYINT NOT NULL DEFAULT 0,
    `fecha_intento` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_intento_busqueda` (`correo_intentado`, `direccion_ip`, `fecha_intento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
