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
            error_log('[ConexionBaseDatos] Error de conexión PDO: ' . $e->getMessage());
            throw new RuntimeException('Fallo crítico del sistema: No se pudo conectar a la base de datos.', 500, $e);
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
            error_log('[ConexionBaseDatos] Error en conexión test: ' . $e->getMessage());
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

        CREATE TABLE IF NOT EXISTS rate_limit (
            clave_hash TEXT NOT NULL,
            ventana_inicio INTEGER NOT NULL,
            contador INTEGER NOT NULL DEFAULT 1,
            UNIQUE (clave_hash, ventana_inicio)
        );
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
