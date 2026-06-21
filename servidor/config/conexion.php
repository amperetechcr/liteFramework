<?php

declare(strict_types=1);

namespace LiteFramework\Config;

use LiteFramework\Modelos\Operador;
use PDO;
use PDOException;
use RuntimeException;

class ConexionBaseDatos
{
    private static ?ConexionBaseDatos $instancia = null;
    private PDO $conexion_pdo;

    private string $codificacion = 'utf8mb4';

    private function __construct()
    {
        $this->conectar();
    }

    private function conectar(): void
    {
        if (defined('TESTS_RUNNING') && TESTS_RUNNING) {
            $this->conectarTest();
            return;
        }

        GestorEntorno::cargar();
        $cadena_conexion = "mysql:host=" . DB_ANFITRION . ";dbname=" . DB_NOMBRE . ";charset={$this->codificacion}";
        $opciones_seguridad = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
        ];
        try {
            $this->conexion_pdo = new PDO($cadena_conexion, DB_USUARIO, DB_CLAVE, $opciones_seguridad);
        } catch (PDOException $error) {
            error_log('[ConexionBaseDatos] MySQL no disponible, usando SQLite: ' . $error->getMessage());
            $this->conectarFallback();
        }
    }

    private function conectarFallback(): void
    {
        try {
            $this->conexion_pdo = new PDO('sqlite::memory:', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->crearEsquemaTemporal();
        } catch (PDOException $e) {
            error_log('[ConexionBaseDatos] Error de conexiÃ³n PDO: ' . $e->getMessage());
            throw new RuntimeException('Fallo crÃ­tico del sistema: No se pudo conectar a la base de datos.', 500, $e);
        }
    }

    private function conectarTest(): void
    {
        try {
            $this->conexion_pdo = new PDO('sqlite::memory:', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->crearEsquemaTemporal();
        } catch (PDOException $e) {
            error_log('[ConexionBaseDatos] Error en conexiÃ³n test: ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar a la base de datos de pruebas.', 500, $e);
        }
    }

    private function crearEsquemaTemporal(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS rbac_rol (
            id_rol INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre_rol TEXT NOT NULL,
            descripcion_rol TEXT,
            estado_rol INTEGER DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS operador (
            id_operador INTEGER PRIMARY KEY AUTOINCREMENT,
            id_rol INTEGER NOT NULL DEFAULT 2,
            nombre_completo TEXT NOT NULL,
            correo_electronico TEXT UNIQUE NOT NULL,
            clave_acceso TEXT NOT NULL,
            estado_cuenta INTEGER DEFAULT 1,
            fecha_registro TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS bitacora_sistema (
            id_registro INTEGER PRIMARY KEY AUTOINCREMENT,
            id_operador INTEGER,
            modulo TEXT NOT NULL,
            accion_realizada TEXT NOT NULL,
            nivel TEXT NOT NULL DEFAULT 'INFO',
            ip_direccion TEXT DEFAULT NULL,
            detalles_json TEXT,
            fecha_registro TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS permisos (
            id_permiso INTEGER PRIMARY KEY AUTOINCREMENT,
            clave_permiso TEXT UNIQUE NOT NULL,
            descripcion TEXT
        );

        CREATE TABLE IF NOT EXISTS permisos_rol (
            id_rol INTEGER,
            id_permiso INTEGER,
            PRIMARY KEY (id_rol, id_permiso)
        );

        CREATE TABLE IF NOT EXISTS archivo (
            id_archivo INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre_original TEXT NOT NULL,
            nombre_generado TEXT NOT NULL,
            ruta_archivo TEXT NOT NULL,
            tipo_mime TEXT NOT NULL,
            tamano_bytes INTEGER DEFAULT 0,
            id_operador INTEGER,
            modulo_origen TEXT,
            etiquetas TEXT,
            descripcion TEXT,
            fecha_subida TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS configuracion_sistema (
            id_config INTEGER PRIMARY KEY AUTOINCREMENT,
            clave TEXT NOT NULL UNIQUE,
            valor TEXT NOT NULL,
            tipo_dato TEXT NOT NULL DEFAULT 'texto',
            version INTEGER NOT NULL DEFAULT 1,
            descripcion TEXT,
            actualizado_por INTEGER,
            fecha_actualizacion TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS documento_pdf (
            id_documento INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            contenido_html TEXT NOT NULL,
            id_operador INTEGER,
            fecha_creacion TEXT DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TEXT DEFAULT NULL
        );

        CREATE TABLE IF NOT EXISTS operador_personalizacion (
            id_operador INTEGER PRIMARY KEY,
            configuracion TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS estadistica (
            id_estadistica INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            descripcion TEXT,
            consulta_sql TEXT NOT NULL,
            tipo_visualizacion TEXT DEFAULT 'tarjetas',
            columnas_mostrar TEXT,
            configuracion_visual TEXT,
            id_operador INTEGER,
            fecha_creacion TEXT DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TEXT DEFAULT NULL
        );

        CREATE TABLE IF NOT EXISTS intento_acceso (
            id_intento INTEGER PRIMARY KEY AUTOINCREMENT,
            correo_intentado TEXT NOT NULL,
            direccion_ip TEXT NOT NULL,
            exitoso INTEGER DEFAULT 0,
            fecha_intento TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS sse_evento (
            id_evento INTEGER PRIMARY KEY AUTOINCREMENT,
            id_operador INTEGER NOT NULL,
            tipo TEXT NOT NULL,
            datos TEXT NOT NULL,
            fecha_creacion TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS _migraciones (
            id_migracion INTEGER PRIMARY KEY AUTOINCREMENT,
            archivo TEXT NOT NULL UNIQUE,
            hash_contenido TEXT NOT NULL,
            fecha_aplicacion TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS oauth_vinculo (
            id_vinculo INTEGER PRIMARY KEY AUTOINCREMENT,
            proveedor TEXT NOT NULL,
            id_proveedor TEXT NOT NULL,
            id_operador INTEGER NOT NULL,
            fecha_vinculo TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (proveedor, id_proveedor),
            UNIQUE (id_operador, proveedor)
        );

        INSERT OR IGNORE INTO rbac_rol (id_rol, nombre_rol, descripcion_rol) VALUES
            (1, 'Super Administrador', 'Acceso total al sistema'),
            (2, 'Administrador', 'Acceso administrativo'),
            (3, 'Operador Estandar', 'Acceso operativo basico'),
            (4, 'Consultor', 'Acceso solo lectura');

        INSERT OR IGNORE INTO permisos (id_permiso, clave_permiso, descripcion) VALUES
            (1, 'operador.crear', 'Crear nuevos operadores en el sistema'),
            (2, 'operador.leer', 'Consultar informacion de operadores'),
            (3, 'operador.actualizar', 'Modificar datos de operadores existentes'),
            (4, 'operador.eliminar', 'Eliminar operadores del sistema'),
            (5, 'rbac_rol.leer', 'Consultar roles del sistema'),
            (6, 'rbac_rol.crear', 'Crear nuevos roles'),
            (7, 'rbac_rol.actualizar', 'Modificar roles existentes'),
            (8, 'rbac_rol.eliminar', 'Eliminar roles del sistema'),
            (9, 'bitacora_sistema.leer', 'Consultar registros de la bitacora de auditoria'),
            (10, 'bitacora_sistema.crear', 'Registrar eventos en la bitacora'),
            (11, 'archivo.subir', 'Subir archivos al servidor'),
            (12, 'archivo.leer', 'Consultar archivos subidas'),
            (13, 'archivo.eliminar', 'Eliminar archivos del servidor'),
            (14, 'configuracion.gestionar', 'Modificar configuracion global del sistema'),
            (15, 'documentoPdf.crear', 'Crear documentos PDF'),
            (16, 'documentoPdf.leer', 'Consultar documentos PDF'),
            (17, 'documentoPdf.actualizar', 'Modificar documentos PDF'),
            (18, 'documentoPdf.eliminar', 'Eliminar documentos PDF'),
            (19, 'estadistica.crear', 'Crear estadisticas'),
            (20, 'estadistica.leer', 'Consultar estadisticas'),
            (21, 'estadistica.actualizar', 'Modificar estadisticas'),
            (22, 'estadistica.eliminar', 'Eliminar estadisticas');

        INSERT OR IGNORE INTO permisos_rol (id_rol, id_permiso) VALUES
            (1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8),
            (1, 9), (1, 10), (1, 11), (1, 12), (1, 13), (1, 14),
            (1, 15), (1, 16), (1, 17), (1, 18), (1, 19), (1, 20), (1, 21), (1, 22),
            (2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 9),
            (2, 11), (2, 12), (2, 15), (2, 16), (2, 19), (2, 20),
            (3, 2), (3, 3), (3, 12), (3, 16), (3, 20),
            (4, 2), (4, 5), (4, 9);

        INSERT OR IGNORE INTO configuracion_sistema (clave, valor, tipo_dato, version, descripcion) VALUES
            ('ARCHIVO_TAMANO_MAXIMO_MB', '40', 'numero', 1, 'Tamano maximo por archivo en MB'),
            ('ARCHIVO_TIPOS_MIME_PERMITIDOS', 'imagenes,documentos', 'texto', 1, 'Categorias MIME permitidas separadas por comas'),
            ('ARCHIVO_CUOTA_USUARIO_MB', '100', 'numero', 1, 'Cuota de almacenamiento por usuario en MB'),
            ('ARCHIVO_EXTENSIONES_PERMITIDAS', 'jpg,jpeg,png,gif,webp,bmp,pdf,doc,docx,xls,xlsx,txt,csv', 'texto', 1, 'Extensiones permitidas separadas por comas'),
            ('ARCHIVO_MEMORIA_PHP_MB', '512', 'numero', 1, 'memory_limit para PHP en MB'),
            ('ARCHIVO_TIEMPO_EJECUCION_SEG', '300', 'numero', 1, 'max_execution_time en segundos'),
            ('ARCHIVO_MAXIMO_SUBIDAS_SIMULTANEAS', '20', 'numero', 1, 'max_file_uploads simultaneos'),
            ('ARCHIVO_POST_MAX_SIZE_MB', '50', 'numero', 1, 'post_max_size en MB (debe ser mayor a upload_max_filesize)');

        CREATE TABLE IF NOT EXISTS plantilla_prompt (
            id_plantilla INTEGER PRIMARY KEY AUTOINCREMENT,
            categoria TEXT NOT NULL,
            nombre TEXT NOT NULL,
            plantilla_humano TEXT NOT NULL,
            plantilla_ia TEXT NOT NULL,
            descripcion TEXT DEFAULT '',
            version INTEGER NOT NULL DEFAULT 1,
            uso_total INTEGER NOT NULL DEFAULT 0,
            uso_exitoso INTEGER NOT NULL DEFAULT 0,
            fecha_creacion TEXT DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TEXT DEFAULT NULL
        );
        CREATE TABLE IF NOT EXISTS traduccion_score (
            id_score INTEGER PRIMARY KEY AUTOINCREMENT,
            categoria TEXT NOT NULL UNIQUE,
            aciertos INTEGER NOT NULL DEFAULT 0,
            fallos INTEGER NOT NULL DEFAULT 0,
            total_uso INTEGER NOT NULL DEFAULT 0,
            confianza REAL NOT NULL DEFAULT 0.50,
            ultima_calibracion TEXT DEFAULT NULL,
            fecha_creacion TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS traduccion_patron_personalizado (
            id_patron INTEGER PRIMARY KEY AUTOINCREMENT,
            categoria TEXT NOT NULL,
            patron_regex TEXT NOT NULL,
            plantilla_ia TEXT NOT NULL,
            aciertos INTEGER NOT NULL DEFAULT 0,
            fallos INTEGER NOT NULL DEFAULT 0,
            activo INTEGER NOT NULL DEFAULT 1,
            fecha_creacion TEXT DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TEXT DEFAULT NULL
        );
        CREATE TABLE IF NOT EXISTS traduccion_historial (
            id_historial INTEGER PRIMARY KEY AUTOINCREMENT,
            prompt_original TEXT NOT NULL,
            prompt_traducido TEXT NOT NULL,
            categoria_detectada TEXT NOT NULL DEFAULT 'general',
            confianza REAL NOT NULL DEFAULT 0.00,
            cache_hit INTEGER NOT NULL DEFAULT 0,
            tiempo_procesamiento_ms INTEGER NOT NULL DEFAULT 0,
            feedback_recibido INTEGER DEFAULT NULL,
            fecha_creacion TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS traduccion_benchmark (
            id_benchmark INTEGER PRIMARY KEY AUTOINCREMENT,
            version_algoritmo TEXT NOT NULL DEFAULT '1.0.0',
            total_pruebas INTEGER NOT NULL DEFAULT 0,
            aciertos INTEGER NOT NULL DEFAULT 0,
            precision_general REAL NOT NULL DEFAULT 0.00,
            precision_por_categoria TEXT DEFAULT NULL,
            fecha_ejecucion TEXT DEFAULT CURRENT_TIMESTAMP
        );
        INSERT OR IGNORE INTO plantilla_prompt (categoria, nombre, plantilla_humano, plantilla_ia, descripcion, version) VALUES
        ('generar_modulo', 'Crear modulo CRUD', 'Crear un modulo CRUD para %s con campos: %s', 'generar modulo CRUD para ENTIDAD con campos: CAMPOS', 'Generacion de modulos CRUD completos', 1),
        ('generar_modulo', 'Generar modelo', 'Generar modelo para %s con tabla %s', 'crear modelo para ENTIDAD en tabla TABLA', 'Creacion de modelos individuales', 1),
        ('ejecutar_migracion', 'Migrar BD', 'Ejecutar migraciones pendientes de la base de datos', 'ejecutar migraciones pendientes de base de datos', 'Aplicar migraciones SQL pendientes', 1),
        ('ejecutar_pruebas', 'Correr tests', 'Ejecutar las pruebas unitarias con filtro %s', 'ejecutar pruebas unitarias con filtro FILTRO', 'Ejecucion de PHPUnit', 1),
        ('diagnosticar', 'Diagnosticar sistema', 'Hacer un diagnostico completo del sistema', 'diagnosticar sistema completo', 'Diagnostico de BD, archivos, seguridad', 1),
        ('seguridad', 'Validar CSRF', 'Validar token CSRF: %s', 'validar token csrf TOKEN', 'Validacion de tokens CSRF', 1),
        ('seguridad', 'Verificar permiso', 'Verificar si tengo permiso para %s', 'tiene permiso CLAVE', 'Verificacion de permisos RBAC', 1),
        ('crud_leer', 'Listar registros', 'Listar todos los registros de %s', 'listar registros de TABLA', 'Lectura de registros de tabla', 1),
        ('crud_escribir', 'Crear registro', 'Crear un registro en %s con datos: %s', 'crear registro en TABLA con datos DATOS', 'Insercion de nuevos registros', 1),
        ('editar_archivo', 'Modificar archivo', 'Editar el archivo %s cambiando %s por %s', 'editar archivo RUTA reemplazar ORIGINAL por NUEVO', 'Modificacion de archivos del proyecto', 1),
        ('leer_archivo', 'Leer archivo', 'Leer el contenido del archivo %s', 'leer archivo RUTA', 'Lectura de archivos del proyecto', 1),
        ('buscar_codigo', 'Buscar en archivos', 'Buscar %s en los archivos del proyecto', 'buscar PATRON en archivos del proyecto', 'Busqueda de texto con grep/glob', 1),
        ('general', 'Ayuda general', 'Necesito ayuda con %s', 'ayuda con CONSULTA', 'Consulta generica al sistema', 1);
        INSERT OR IGNORE INTO traduccion_score (categoria, aciertos, fallos, total_uso, confianza) VALUES
        ('generar_modulo', 0, 0, 0, 0.50),
        ('ejecutar_migracion', 0, 0, 0, 0.50),
        ('ejecutar_pruebas', 0, 0, 0, 0.50),
        ('diagnosticar', 0, 0, 0, 0.50),
        ('seguridad', 0, 0, 0, 0.50),
        ('crud_leer', 0, 0, 0, 0.50),
        ('crud_escribir', 0, 0, 0, 0.50),
        ('editar_archivo', 0, 0, 0, 0.50),
        ('leer_archivo', 0, 0, 0, 0.50),
        ('buscar_codigo', 0, 0, 0, 0.50),
        ('general', 0, 0, 0, 0.50);
        ";

        $this->conexion_pdo->exec($sql);
    }

    private function __clone()
    {
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function obtenerConector(): PDO
    {
        return $this->conexion_pdo;
    }

    public static function resetearInstancia(): void
    {
        self::$instancia = null;
    }
}
