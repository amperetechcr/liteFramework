CREATE TABLE IF NOT EXISTS `sse_evento` (
    `id_evento` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_operador` INT NOT NULL,
    `tipo` VARCHAR(100) NOT NULL,
    `datos` JSON NOT NULL,
    `fecha_creacion` TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3),
    INDEX `idx_sse_operador_evento` (`id_operador`, `tipo`),
    INDEX `idx_sse_creacion` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
