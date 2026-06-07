-- Rate limiting genérico para API y rutas
CREATE TABLE IF NOT EXISTS rate_limit (
    id_limite INTEGER PRIMARY KEY AUTO_INCREMENT,
    clave_hash VARCHAR(64) NOT NULL,
    ventana_inicio INTEGER UNSIGNED NOT NULL,
    contador INTEGER UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uk_rate_limit (clave_hash, ventana_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
