<?php

declare(strict_types=1);

namespace LiteFramework\Controladores;

use PDO;
use LiteFramework\Nucleo\SubidaArchivos;
use LiteFramework\Seguridad\SeguridadServidor;
use LiteFramework\Seguridad\ControlAccesoRBAC;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Config\ConfiguracionSistema;
use LiteFramework\Config\GeneradorIniServidor;
use LiteFramework\Modelos\Archivo;
use LiteFramework\Servicios\AdministradorArchivos;
use Exception;

class SubirArchivosControlador extends ControladorBase
{
    private AdministradorArchivos $adminArchivos;

    public function __construct()
    {
        $this->adminArchivos = new AdministradorArchivos(
            DIRECTORIO_RAIZ . '/storage/archivos',
            URL_BASE,
        );
    }

    public function indice(): void
    {
        $this->verificarAutenticacion();

        if (!ControlAccesoRBAC::tienePermiso('archivo.leer')) {
            http_response_code(403);
            echo json_encode(['error' => 'Sin permiso para ver archivos']);
            return;
        }

        require DIRECTORIO_RAIZ . '/src/modulos/subirArchivos/subirArchivos.php';
    }

    public function subir(): void
    {
        $this->verificarAutenticacion();

        header('Content-Type: application/json; charset=utf-8');

        if (!ControlAccesoRBAC::tienePermiso('archivo.subir')) {
            http_response_code(403);
            echo json_encode(['error' => 'Sin permiso para subir archivos']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Metodo no permitido']);
            exit;
        }

        $tokenRecibido = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!SeguridadServidor::validarTokenAntiFalsificacion($tokenRecibido)) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalido o expirado']);
            exit;
        }

        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] === UPLOAD_ERR_NO_FILE) {
            http_response_code(400);
            echo json_encode(['error' => 'No se recibio ningun archivo']);
            exit;
        }

        if ($_FILES['archivo']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['archivo']['error'] === UPLOAD_ERR_FORM_SIZE) {
            $limitePhp = ini_get('upload_max_filesize');
            $limiteConfig = ConfiguracionSistema::obtener('ARCHIVO_TAMANO_MAXIMO_MB', 40) . 'M';
            http_response_code(413);
            echo json_encode([
                'error' => "El archivo excede el limite del servidor (PHP: {$limitePhp}, Configurado: {$limiteConfig}). " .
                           "Solicite al administrador que aumente el limite en Configuracion > Limites de subida.",
                'codigo' => 'excede_limite_php',
                'limite_php' => $limitePhp,
                'limite_configurado' => $limiteConfig,
            ]);
            exit;
        }

        $resultado = $this->adminArchivos->subir($_FILES['archivo'], $this->obtenerIdOperador(), [
            'modulo_origen' => $_POST['modulo_origen'] ?? 'general',
            'etiquetas' => $_POST['etiquetas'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'ruta_relativa' => $_POST['ruta_relativa'] ?? '',
        ]);

        if (!$resultado['estado_operacion']) {
            if ($resultado['codigo_error'] === 'cuota_excedida') {
                http_response_code(413);
                $usadoMB = round($resultado['usado_bytes'] / 1024 / 1024, 2);
                $cuotaMB = round($resultado['cuota_bytes'] / 1024 / 1024, 2);
                echo json_encode([
                    'error' => "Cuota excedida. Has usado {$usadoMB}MB de {$cuotaMB}MB.",
                    'codigo' => 'cuota_excedida',
                    'usado_mb' => $usadoMB,
                    'cuota_mb' => $cuotaMB,
                ]);
                exit;
            }
            http_response_code(400);
            echo json_encode(['error' => $resultado['mensaje_error'], 'codigo' => $resultado['codigo_error']]);
            exit;
        }

        RegistroAuditoria::auditoria('archivo.subir', 'Subida de archivo', [
            'archivo_id' => $resultado['archivo_id'],
            'nombre_original' => $_FILES['archivo']['name'],
            'tamano_bytes' => $_FILES['archivo']['size'],
        ]);

        echo json_encode([
            'exito' => true,
            'mensaje' => 'Archivo subido correctamente',
            'archivo' => $resultado['datos'],
            'nuevo_token' => $_SESSION['token_seguridad_peticion'] ?? '',
        ]);
        exit;
    }

    public function eliminar(int $id): void
    {
        $this->verificarAutenticacion();

        header('Content-Type: application/json; charset=utf-8');

        if (!ControlAccesoRBAC::tienePermiso('archivo.eliminar')) {
            http_response_code(403);
            echo json_encode(['error' => 'Sin permiso para eliminar archivos']);
            exit;
        }

        $tokenRecibido = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!SeguridadServidor::validarTokenAntiFalsificacion($tokenRecibido)) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalido o expirado']);
            exit;
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de archivo invalido']);
            exit;
        }

        $resultado = $this->adminArchivos->eliminar($id);

        if (!$resultado['estado_operacion']) {
            http_response_code(404);
            echo json_encode(['error' => $resultado['mensaje_error']]);
            exit;
        }

        RegistroAuditoria::auditoria('archivo.eliminar', 'Eliminacion de archivo', [
            'archivo_id' => $id,
        ]);

        echo json_encode([
            'exito' => true,
            'mensaje' => 'Archivo eliminado correctamente',
            'nuevo_token' => $_SESSION['token_seguridad_peticion'] ?? '',
        ]);
        exit;
    }

    public function eliminarCarpeta(): void
    {
        $this->verificarAutenticacion();

        header('Content-Type: application/json; charset=utf-8');

        if (!ControlAccesoRBAC::tienePermiso('archivo.eliminar')) {
            http_response_code(403);
            echo json_encode(['error' => 'Sin permiso para eliminar archivos']);
            exit;
        }

        $tokenRecibido = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!SeguridadServidor::validarTokenAntiFalsificacion($tokenRecibido)) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalido o expirado']);
            exit;
        }

        $ruta = $_POST['ruta'] ?? '';
        if ($ruta === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Ruta de carpeta requerida']);
            exit;
        }

        $resultado = $this->adminArchivos->eliminarCarpeta($ruta);

        if (!$resultado['estado_operacion']) {
            http_response_code(400);
            echo json_encode(['error' => $resultado['mensaje_error']]);
            exit;
        }

        RegistroAuditoria::auditoria('archivo.eliminar_carpeta', 'Eliminacion de carpeta', [
            'ruta' => $ruta,
            'archivos_eliminados' => $resultado['datos']['eliminados'],
        ]);

        echo json_encode([
            'exito' => true,
            'mensaje' => 'Carpeta eliminada correctamente',
            'eliminados' => $resultado['datos']['eliminados'],
            'nuevo_token' => $_SESSION['token_seguridad_peticion'] ?? '',
        ]);
        exit;
    }

    public function listar(): void
    {
        $this->verificarAutenticacion();

        header('Content-Type: application/json; charset=utf-8');

        if (!ControlAccesoRBAC::tienePermiso('archivo.leer')) {
            http_response_code(403);
            echo json_encode(['error' => 'Sin permiso para consultar archivos']);
            exit;
        }

        echo json_encode($this->adminArchivos->listar());
        exit;
    }

    public function descargar(int $id): void
    {
        $this->verificarAutenticacion();

        if (!ControlAccesoRBAC::tienePermiso('archivo.leer')) {
            http_response_code(403);
            echo 'Sin permiso para descargar archivos';
            return;
        }

        $resultado = $this->adminArchivos->descargar($id);

        if (!$resultado['estado_operacion']) {
            http_response_code(404);
            echo $resultado['mensaje_error'];
            return;
        }

        $datos = $resultado['datos'];

        RegistroAuditoria::auditoria('Archivos', 'Descarga de archivo', [
            'id_archivo' => $id,
            'nombre' => $datos['nombre_original'],
            'tamano' => $datos['tamano_bytes'],
            'tipo' => $datos['tipo_mime'],
        ]);

        header('Content-Type: ' . $datos['tipo_mime']);
        header('Content-Disposition: attachment; filename="' . basename($datos['nombre_original']) . '"');
        header('Content-Length: ' . $datos['tamano_bytes']);
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($datos['ruta']);
        exit;
    }

    public function descargarCarpeta(): void
    {
        $this->verificarAutenticacion();

        if (!ControlAccesoRBAC::tienePermiso('archivo.leer')) {
            http_response_code(403);
            echo 'Sin permiso para descargar archivos';
            return;
        }

        $ruta = $_GET['ruta'] ?? '';
        if ($ruta === '') {
            http_response_code(400);
            echo 'Ruta de carpeta requerida';
            return;
        }

        $resultado = $this->adminArchivos->descargarCarpeta($ruta);

        if (!$resultado['estado_operacion']) {
            http_response_code(404);
            echo $resultado['mensaje_error'];
            return;
        }

        $datos = $resultado['datos'];

        RegistroAuditoria::auditoria('Archivos', 'Descarga de carpeta', [
            'ruta' => $ruta,
            'archivos_comprimidos' => $datos['tamano_bytes'] ?? 0,
        ]);

        header('Content-Type: ' . $datos['tipo_mime']);
        header('Content-Disposition: attachment; filename="' . basename($datos['nombre_original']) . '"');
        header('Content-Length: ' . $datos['tamano_bytes']);
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($datos['ruta']);
        if (!empty($datos['temp'])) {
            @unlink($datos['ruta']);
        }
        exit;
    }

    public function configuracion(): void
    {
        $this->verificarAutenticacion();

        header('Content-Type: application/json; charset=utf-8');

        if (!ControlAccesoRBAC::tienePermiso('archivo.leer')) {
            http_response_code(403);
            echo json_encode(['error' => 'Sin permiso']);
            exit;
        }

        $detalles = $this->adminArchivos->obtenerConfiguracion();
        $usoUsuario = round($this->adminArchivos->calcularUsoUsuario($this->obtenerIdOperador()) / 1024 / 1024, 2);

        $configuracion = [
            'tamano_maximo_mb' => (int)ConfiguracionSistema::obtener('ARCHIVO_TAMANO_MAXIMO_MB', 40),
            'cuota_usuario_mb' => (int)ConfiguracionSistema::obtener('ARCHIVO_CUOTA_USUARIO_MB', 100),
            'tipos_mime_permitidos' => ConfiguracionSistema::obtener('ARCHIVO_TIPOS_MIME_PERMITIDOS', 'imagenes,documentos,codigo,datos'),
            'extensiones_permitidas' => ConfiguracionSistema::obtener('ARCHIVO_EXTENSIONES_PERMITIDAS', 'jpg,jpeg,png,gif,webp,bmp,pdf,doc,docx,xls,xlsx,txt,csv,php,js,css,sql,md,json,xml,log,ini,env,example,backup'),
            'limites_php' => GeneradorIniServidor::limitesActualesPHP(),
            'uso_usuario_mb' => $usoUsuario,
            'detalles' => $detalles,
        ];

        echo json_encode($configuracion);
        exit;
    }

    public function obtenerUsoUsuarioMB(int $idOperador): float
    {
        $bytes = $this->adminArchivos->calcularUsoUsuario($idOperador);
        return round($bytes / 1024 / 1024, 2);
    }
}
