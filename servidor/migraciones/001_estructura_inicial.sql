CREATE TABLE IF NOT EXISTS `rbac_rol` (
    `id_rol` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre_rol` VARCHAR(50) NOT NULL UNIQUE,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `rbac_rol` (`id_rol`, `nombre_rol`) VALUES
    (1, 'Super Administrador'),
    (2, 'Administrador'),
    (3, 'Operador Estandar'),
    (4, 'Consultor');

CREATE TABLE IF NOT EXISTS `operador` (
    `id_operador` INT AUTO_INCREMENT PRIMARY KEY,
    `id_rol` INT NOT NULL,
    `nombre_completo` VARCHAR(150) NOT NULL,
    `correo_electronico` VARCHAR(100) NOT NULL UNIQUE,
    `clave_acceso` VARCHAR(255) NOT NULL,
    `estado_cuenta` TINYINT NOT NULL DEFAULT 1,
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_operador_rol`
        FOREIGN KEY (`id_rol`) REFERENCES `rbac_rol` (`id_rol`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bitacora_sistema` (
    `id_bitacora` INT AUTO_INCREMENT PRIMARY KEY,
    `id_operador` INT NULL,
    `modulo` VARCHAR(50) NOT NULL,
    `accion_realizada` VARCHAR(100) NOT NULL,
    `detalles_json` JSON NULL,
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bitacora_operador`
        FOREIGN KEY (`id_operador`) REFERENCES `operador` (`id_operador`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_operador_correo` ON `operador` (`correo_electronico`);
CREATE INDEX `idx_bitacora_operador` ON `bitacora_sistema` (`id_operador`);
CREATE INDEX `idx_bitacora_fecha` ON `bitacora_sistema` (`fecha_registro`);
CREATE INDEX `idx_bitacora_modulo` ON `bitacora_sistema` (`modulo`);

CREATE TABLE IF NOT EXISTS `permisos` (
    `id_permiso` INT AUTO_INCREMENT PRIMARY KEY,
    `clave_permiso` VARCHAR(100) NOT NULL UNIQUE,
    `descripcion` VARCHAR(255) NOT NULL,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permisos` (`clave_permiso`, `descripcion`) VALUES
    ('operador.crear',       'Crear nuevos operadores en el sistema'),
    ('operador.leer',        'Consultar informacion de operadores'),
    ('operador.actualizar',  'Modificar datos de operadores existentes'),
    ('operador.eliminar',    'Eliminar operadores del sistema'),
    ('rbac_rol.leer',        'Consultar roles del sistema'),
    ('rbac_rol.crear',       'Crear nuevos roles'),
    ('rbac_rol.actualizar',  'Modificar roles existentes'),
    ('rbac_rol.eliminar',    'Eliminar roles del sistema'),
    ('bitacora_sistema.leer',   'Consultar registros de la bitacora de auditoria'),
    ('bitacora_sistema.crear',  'Registrar eventos en la bitacora'),
    ('archivo.subir',       'Subir archivos al servidor'),
    ('archivo.leer',        'Consultar archivos subidas'),
    ('archivo.eliminar',    'Eliminar archivos del servidor'),
    ('configuracion.gestionar', 'Modificar configuracion global del sistema');

CREATE TABLE IF NOT EXISTS `permisos_rol` (
    `id_permiso_rol` INT AUTO_INCREMENT PRIMARY KEY,
    `id_rol` INT NOT NULL,
    `id_permiso` INT NOT NULL,
    `fecha_asignacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_permisosrol_rol`
        FOREIGN KEY (`id_rol`) REFERENCES `rbac_rol` (`id_rol`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_permisosrol_permiso`
        FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `uq_rol_permiso` UNIQUE (`id_rol`, `id_permiso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 1, id_permiso FROM `permisos`;

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 2, id_permiso FROM `permisos`
WHERE `clave_permiso` IN (
    'operador.crear', 'operador.leer', 'operador.actualizar', 'operador.eliminar',
    'rbac_rol.leer',
    'bitacora_sistema.leer'
);

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 3, id_permiso FROM `permisos`
WHERE `clave_permiso` IN (
    'operador.leer', 'operador.actualizar'
);

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 4, id_permiso FROM `permisos`
WHERE `clave_permiso` IN (
    'operador.leer',
    'rbac_rol.leer',
    'bitacora_sistema.leer'
);

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 1, id_permiso FROM `permisos`
WHERE `clave_permiso` LIKE 'archivo.%';

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 2, id_permiso FROM `permisos`
WHERE `clave_permiso` IN ('archivo.subir', 'archivo.leer');

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 3, id_permiso FROM `permisos`
WHERE `clave_permiso` = 'archivo.leer';

INSERT IGNORE INTO `permisos_rol` (`id_rol`, `id_permiso`)
SELECT 1, id_permiso FROM `permisos`
WHERE `clave_permiso` = 'configuracion.gestionar';

CREATE INDEX `idx_permisosrol_rol` ON `permisos_rol` (`id_rol`);
CREATE INDEX `idx_permisosrol_permiso` ON `permisos_rol` (`id_permiso`);

CREATE TABLE IF NOT EXISTS `archivo` (
    `id_archivo` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre_original` VARCHAR(255) NOT NULL,
    `nombre_generado` VARCHAR(64) NOT NULL,
    `ruta_archivo` VARCHAR(500) NOT NULL,
    `tipo_mime` VARCHAR(100) NOT NULL,
    `tamano_bytes` BIGINT NOT NULL DEFAULT 0,
    `id_operador` INT NULL,
    `modulo_origen` VARCHAR(100) NULL,
    `etiquetas` VARCHAR(500) NULL,
    `descripcion` TEXT NULL,
    `fecha_subida` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_archivo_operador`
        FOREIGN KEY (`id_operador`) REFERENCES `operador` (`id_operador`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_archivo_operador` ON `archivo` (`id_operador`);
CREATE INDEX `idx_archivo_fecha` ON `archivo` (`fecha_subida`);
CREATE INDEX `idx_archivo_modulo` ON `archivo` (`modulo_origen`);

CREATE TABLE IF NOT EXISTS `configuracion_sistema` (
    `id_config` INT AUTO_INCREMENT PRIMARY KEY,
    `clave` VARCHAR(100) NOT NULL UNIQUE,
    `valor` TEXT NOT NULL,
    `tipo_dato` ENUM('texto', 'numero', 'booleano', 'json') DEFAULT 'texto',
    `version` INT NOT NULL DEFAULT 1,
    `descripcion` VARCHAR(255),
    `actualizado_por` INT NULL,
    `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_configuracion_operador`
        FOREIGN KEY (`actualizado_por`) REFERENCES `operador` (`id_operador`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_configuracion_clave` ON `configuracion_sistema` (`clave`);
CREATE INDEX `idx_configuracion_fecha` ON `configuracion_sistema` (`fecha_actualizacion`);

INSERT IGNORE INTO `configuracion_sistema` (`clave`, `valor`, `tipo_dato`, `version`, `descripcion`) VALUES
    ('ARCHIVO_TAMANO_MAXIMO_MB', '40', 'numero', 1, 'Tamano maximo por archivo en MB'),
    ('ARCHIVO_TIPOS_MIME_PERMITIDOS', 'imagenes,documentos', 'texto', 1, 'Categorias MIME permitidas separadas por comas'),
    ('ARCHIVO_CUOTA_USUARIO_MB', '100', 'numero', 1, 'Cuota de almacenamiento por usuario en MB'),
    ('ARCHIVO_EXTENSIONES_PERMITIDAS', 'jpg,jpeg,png,gif,webp,bmp,pdf,doc,docx,xls,xlsx,txt,csv', 'texto', 1, 'Extensiones permitidas separadas por comas'),
    ('ARCHIVO_MEMORIA_PHP_MB', '512', 'numero', 1, 'memory_limit para PHP en MB'),
    ('ARCHIVO_TIEMPO_EJECUCION_SEG', '300', 'numero', 1, 'max_execution_time en segundos'),
    ('ARCHIVO_MAXIMO_SUBIDAS_SIMULTANEAS', '20', 'numero', 1, 'max_file_uploads simultaneos'),
    ('ARCHIVO_POST_MAX_SIZE_MB', '50', 'numero', 1, 'post_max_size en MB (debe ser mayor a upload_max_filesize)');

-- =============================================
-- Migración 002: Documentos PDF
-- =============================================
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

-- =============================================
-- Migración 003: Personalización de operador
-- =============================================
CREATE TABLE IF NOT EXISTS `operador_personalizacion` (
    `id_operador` INT PRIMARY KEY,
    `configuracion` JSON NOT NULL,
    `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pers_operador` FOREIGN KEY (`id_operador`) REFERENCES `operador` (`id_operador`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Migración 004: Estadísticas
-- =============================================
CREATE TABLE IF NOT EXISTS `estadistica` (
    `id_estadistica` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(255) NOT NULL,
    `descripcion` TEXT NULL,
    `consulta_sql` TEXT NOT NULL,
    `tipo_visualizacion` VARCHAR(30) DEFAULT 'tarjetas',
    `columnas_mostrar` TEXT NULL,
    `configuracion_visual` TEXT NULL,
    `cache_ttl` INT NOT NULL DEFAULT 300,
    `en_dashboard` TINYINT NOT NULL DEFAULT 0,
    `dashboard_orden` INT NOT NULL DEFAULT 0,
    `ultima_ejecucion` DATETIME NULL DEFAULT NULL,
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

-- =============================================
-- Migración 005: Protección fuerza bruta
-- =============================================
CREATE TABLE IF NOT EXISTS `intento_acceso` (
    `id_intento` INT AUTO_INCREMENT PRIMARY KEY,
    `correo_intentado` VARCHAR(100) NOT NULL,
    `direccion_ip` VARCHAR(45) NOT NULL,
    `exitoso` TINYINT NOT NULL DEFAULT 0,
    `fecha_intento` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_intento_busqueda` (`correo_intentado`, `direccion_ip`, `fecha_intento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Migración 006: Eventos SSE
-- =============================================
CREATE TABLE IF NOT EXISTS `sse_evento` (
    `id_evento` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_operador` INT NOT NULL,
    `tipo` VARCHAR(100) NOT NULL,
    `datos` JSON NOT NULL,
    `fecha_creacion` TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3),
    INDEX `idx_sse_operador_evento` (`id_operador`, `tipo`),
    INDEX `idx_sse_creacion` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Migración 007: Rate limiting
-- =============================================
CREATE TABLE IF NOT EXISTS `rate_limit` (
    `id_limite` INT AUTO_INCREMENT PRIMARY KEY,
    `clave_hash` VARCHAR(64) NOT NULL,
    `ventana_inicio` INT UNSIGNED NOT NULL,
    `contador` INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY `uk_rate_limit` (`clave_hash`, `ventana_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Migración 008: OAuth vinculo
-- =============================================
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
