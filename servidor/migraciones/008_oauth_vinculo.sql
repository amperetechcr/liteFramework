CREATE TABLE IF NOT EXISTS `oauth_vinculo` (
    `id_vinculo` INT AUTO_INCREMENT PRIMARY KEY,
    `proveedor` VARCHAR(20) NOT NULL,
    `id_proveedor` VARCHAR(100) NOT NULL,
    `id_operador` INT NOT NULL,
    `fecha_vinculo` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_oauth_proveedor` (`proveedor`, `id_proveedor`),
    UNIQUE KEY `uk_oauth_operador` (`id_operador`, `proveedor`),
    CONSTRAINT `fk_oauth_operador`
        FOREIGN KEY (`id_operador`) REFERENCES `operador` (`id_operador`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
