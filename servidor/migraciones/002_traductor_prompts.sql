-- =============================================
-- Migracion 002: Traductor de Prompts (IA <-> Humano)
-- Creado: 2026-06-11
-- =============================================

-- Tabla de plantillas de prompt optimizadas
CREATE TABLE IF NOT EXISTS plantilla_prompt (
    id_plantilla INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    plantilla_humano TEXT NOT NULL,
    plantilla_ia TEXT NOT NULL,
    descripcion VARCHAR(255) DEFAULT '',
    version INT NOT NULL DEFAULT 1,
    uso_total INT NOT NULL DEFAULT 0,
    uso_exitoso INT NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_plantilla_categoria (categoria),
    INDEX idx_plantilla_uso (uso_total DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seeds: plantillas iniciales por categoria
INSERT IGNORE INTO plantilla_prompt (categoria, nombre, plantilla_humano, plantilla_ia, descripcion, version) VALUES
('generar_modulo', 'Crear modulo CRUD',
 'Crear un modulo CRUD para %s con campos: %s',
 'generar modulo CRUD para ENTIDAD con campos: CAMPOS',
 'Generacion de modulos CRUD completos', 1),
('generar_modulo', 'Generar modelo',
 'Generar modelo para %s con tabla %s',
 'crear modelo para ENTIDAD en tabla TABLA',
 'Creacion de modelos individuales', 1),
('generar_proyecto', 'Nuevo proyecto',
 'Crear un proyecto nuevo llamado %s con BD %s',
 'generar proyecto NOMBRE con base de datos BD',
 'Creacion de proyectos desde JSON', 1),
('ejecutar_migracion', 'Migrar BD',
 'Ejecutar migraciones pendientes de la base de datos',
 'ejecutar migraciones pendientes de base de datos',
 'Aplicar migraciones SQL pendientes', 1),
('ejecutar_pruebas', 'Correr tests',
 'Ejecutar las pruebas unitarias con filtro %s',
 'ejecutar pruebas unitarias con filtro FILTRO',
 'Ejecucion de PHPUnit', 1),
('diagnosticar', 'Diagnosticar sistema',
 'Hacer un diagnostico completo del sistema',
 'diagnosticar sistema completo',
 'Diagnostico de BD, archivos, seguridad', 1),
('seguridad', 'Validar CSRF',
 'Validar token CSRF: %s',
 'validar token csrf TOKEN',
 'Validacion de tokens CSRF', 1),
('seguridad', 'Verificar permiso',
 'Verificar si tengo permiso para %s',
 'tiene permiso CLAVE',
 'Verificacion de permisos RBAC', 1),
('crud_leer', 'Listar registros',
 'Listar todos los registros de %s',
 'listar registros de TABLA',
 'Lectura de registros de tabla', 1),
('crud_leer', 'Buscar registro',
 'Buscar en %s donde %s = %s',
 'buscar en TABLA donde CAMPO = VALOR',
 'Busqueda de registros especificos', 1),
('crud_escribir', 'Crear registro',
 'Crear un registro en %s con datos: %s',
 'crear registro en TABLA con datos DATOS',
 'Insercion de nuevos registros', 1),
('crud_escribir', 'Actualizar registro',
 'Actualizar %s ID %s con %s',
 'actualizar TABLA ID VALOR con datos DATOS',
 'Modificacion de registros existentes', 1),
('crud_escribir', 'Eliminar registro',
 'Eliminar registro de %s con ID %s',
 'eliminar registro de TABLA con ID VALOR',
 'Eliminacion de registros', 1),
('editar_archivo', 'Modificar archivo',
 'Editar el archivo %s cambiando %s por %s',
 'editar archivo RUTA reemplazar ORIGINAL por NUEVO',
 'Modificacion de archivos del proyecto', 1),
('leer_archivo', 'Leer archivo',
 'Leer el contenido del archivo %s',
 'leer archivo RUTA',
 'Lectura de archivos del proyecto', 1),
('buscar_codigo', 'Buscar en archivos',
 'Buscar %s en los archivos del proyecto',
 'buscar PATRON en archivos del proyecto',
 'Busqueda de texto con grep/glob', 1),
('optimizar', 'Optimizar consulta',
 'Optimizar la consulta SQL: %s',
 'optimizar consulta SQL CONSULTA',
 'Optimizacion de consultas a BD', 1),
('documentar', 'Documentar modulo',
 'Generar documentacion para %s',
 'generar documentacion para MODULO',
 'Generacion de documentacion', 1),
('general', 'Ayuda general',
 'Necesito ayuda con %s',
 'ayuda con CONSULTA',
 'Consulta generica al sistema', 1);

-- Tabla de scores de traduccion
CREATE TABLE IF NOT EXISTS traduccion_score (
    id_score INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(50) NOT NULL,
    aciertos INT NOT NULL DEFAULT 0,
    fallos INT NOT NULL DEFAULT 0,
    total_uso INT NOT NULL DEFAULT 0,
    confianza DECIMAL(5,2) NOT NULL DEFAULT 0.50,
    ultima_calibracion TIMESTAMP NULL DEFAULT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_score_categoria (categoria),
    INDEX idx_score_confianza (confianza DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO traduccion_score (categoria, aciertos, fallos, total_uso, confianza) VALUES
('generar_modulo', 0, 0, 0, 0.50),
('generar_proyecto', 0, 0, 0, 0.50),
('ejecutar_migracion', 0, 0, 0, 0.50),
('ejecutar_pruebas', 0, 0, 0, 0.50),
('diagnosticar', 0, 0, 0, 0.50),
('seguridad', 0, 0, 0, 0.50),
('crud_leer', 0, 0, 0, 0.50),
('crud_escribir', 0, 0, 0, 0.50),
('editar_archivo', 0, 0, 0, 0.50),
('leer_archivo', 0, 0, 0, 0.50),
('buscar_codigo', 0, 0, 0, 0.50),
('optimizar', 0, 0, 0, 0.50),
('documentar', 0, 0, 0, 0.50),
('general', 0, 0, 0, 0.50);

-- Tabla de patrones personalizados (aprendizaje)
CREATE TABLE IF NOT EXISTS traduccion_patron_personalizado (
    id_patron INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(50) NOT NULL,
    patron_regex VARCHAR(500) NOT NULL,
    plantilla_ia TEXT NOT NULL,
    aciertos INT NOT NULL DEFAULT 0,
    fallos INT NOT NULL DEFAULT 0,
    activo TINYINT NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patron_categoria (categoria),
    INDEX idx_patron_activo (activo),
    INDEX idx_patron_fallos (fallos)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de historial de traducciones
CREATE TABLE IF NOT EXISTS traduccion_historial (
    id_historial BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prompt_original TEXT NOT NULL,
    prompt_traducido TEXT NOT NULL,
    categoria_detectada VARCHAR(50) NOT NULL DEFAULT 'general',
    confianza DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    cache_hit TINYINT NOT NULL DEFAULT 0,
    tiempo_procesamiento_ms INT NOT NULL DEFAULT 0,
    feedback_recibido TINYINT DEFAULT NULL COMMENT '1=acierto, 0=fallo, NULL=pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_historial_categoria (categoria_detectada),
    INDEX idx_historial_feedback (feedback_recibido),
    INDEX idx_historial_fecha (fecha_creacion DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de benchmarks de calibracion
CREATE TABLE IF NOT EXISTS traduccion_benchmark (
    id_benchmark INT AUTO_INCREMENT PRIMARY KEY,
    version_algoritmo VARCHAR(20) NOT NULL DEFAULT '1.0.0',
    total_pruebas INT NOT NULL DEFAULT 0,
    aciertos INT NOT NULL DEFAULT 0,
    precision_general DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    precision_por_categoria JSON NULL,
    fecha_ejecucion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_benchmark_version (version_algoritmo DESC),
    INDEX idx_benchmark_fecha (fecha_ejecucion DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
