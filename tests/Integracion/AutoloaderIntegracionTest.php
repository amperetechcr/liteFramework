<?php
use PHPUnit\Framework\TestCase;

class AutoloaderIntegracionTest extends TestCase {
    private static array $clasesFramework = [
        // Nucleo
        'Enrutador', 'Modelo', 'Validador', 'Paginador', 'SubidaArchivos', 'ManejadorErrores',
        // Seguridad
        'SeguridadServidor', 'GestorSesiones', 'ValidadorCSRF', 'ControlAccesoRBAC',
        'PoliticaContrasena', 'SanitizadorEntrada', 'RegistroAuditoria', 'TrazadorPeticiones',
        // Config
        'ConexionBaseDatos', 'GestorEntorno', 'ConfiguracionSistema', 'GeneradorIniServidor',
        // Migraciones
        'GestorMigraciones',
        // Modelos
        'Operador', 'Rol',
        // Controladores
        'ControladorBase', 'ModuloControlador', 'AutenticacionControlador', 'SubirArchivosControlador',
        // Middleware
        'AutenticacionInterceptor', 'ApiAuthInterceptor',
        // API
        'AutenticacionApiControlador', 'OperadorApiControlador',
        'PersonalizacionApiControlador', 'MigracionApiControlador',
        'ConfiguracionApiControlador', 'CrudApiControlador',
        'GeneradorModuloApiControlador', 'GeneradorProyectoApiControlador',
        // Servicios
        'GeneradorPdf', 'GeneradorEstadisticas', 'GeneradorModulo', 'GeneradorProyecto',
        'AdministradorArchivos',
        // Helpers
        'Helper', 'AyudanteCadena', 'AyudanteFecha', 'AyudanteArreglo', 'AyudanteGeneral',
        'AyudanteArchivo', 'AyudanteOperador', 'AyudanteSeguridad',
        // Alias helpers
        'Cadena', 'Fecha', 'Arreglo', 'General', 'ArchivoH', 'OperadorH', 'Seguridad',
        // Excepciones
        'ErrorSeguridad', 'ErrorAutenticacion', 'ErrorValidacion',
        // Utilidades
        'DialectoBaseDatos',
    ];

    private static array $clasesNamespaced = [
        'LiteFramework\Nucleo\Enrutador',
        'LiteFramework\Nucleo\Modelo',
        'LiteFramework\Nucleo\Validador',
        'LiteFramework\Nucleo\Paginador',
        'LiteFramework\Nucleo\SubidaArchivos',
        'LiteFramework\Nucleo\ManejadorErrores',
        'LiteFramework\Seguridad\SeguridadServidor',
        'LiteFramework\Seguridad\GestorSesiones',
        'LiteFramework\Seguridad\ValidadorCSRF',
        'LiteFramework\Seguridad\ControlAccesoRBAC',
        'LiteFramework\Seguridad\PoliticaContrasena',
        'LiteFramework\Seguridad\SanitizadorEntrada',
        'LiteFramework\Seguridad\RegistroAuditoria',
        'LiteFramework\Seguridad\TrazadorPeticiones',
        'LiteFramework\Config\ConexionBaseDatos',
        'LiteFramework\Config\GestorEntorno',
        'LiteFramework\Config\ConfiguracionSistema',
        'LiteFramework\Config\GeneradorIniServidor',
        'LiteFramework\Migraciones\GestorMigraciones',
        'LiteFramework\Modelos\Operador',
        'LiteFramework\Modelos\Rol',
        'LiteFramework\Modelos\Archivo',
        'LiteFramework\Modelos\DocumentoPdf',
        'LiteFramework\Modelos\Estadistica',
        'LiteFramework\Controladores\ControladorBase',
        'LiteFramework\Controladores\ModuloControlador',
        'LiteFramework\Controladores\AutenticacionControlador',
        'LiteFramework\Controladores\SubirArchivosControlador',
        'LiteFramework\Middleware\AutenticacionInterceptor',
        'LiteFramework\Middleware\ApiAuthInterceptor',
        'LiteFramework\Api\Controladores\AutenticacionApiControlador',
        'LiteFramework\Api\Controladores\OperadorApiControlador',
        'LiteFramework\Api\Controladores\PersonalizacionApiControlador',
        'LiteFramework\Api\Controladores\MigracionApiControlador',
        'LiteFramework\Api\Controladores\ConfiguracionApiControlador',
        'LiteFramework\Api\Controladores\CrudApiControlador',
        'LiteFramework\Api\Controladores\GeneradorModuloApiControlador',
        'LiteFramework\Api\Controladores\GeneradorProyectoApiControlador',
        'LiteFramework\Servicios\GeneradorPdf',
        'LiteFramework\Servicios\GeneradorEstadisticas',
        'LiteFramework\Servicios\GeneradorModulo',
        'LiteFramework\Servicios\GeneradorProyecto',
        'LiteFramework\Servicios\AdministradorArchivos',
        'LiteFramework\Nucleo\Helpers\AyudanteCadena',
        'LiteFramework\Nucleo\Helpers\AyudanteFecha',
        'LiteFramework\Nucleo\Helpers\AyudanteArreglo',
        'LiteFramework\Nucleo\Helpers\AyudanteGeneral',
        'LiteFramework\Nucleo\Helpers\AyudanteArchivo',
        'LiteFramework\Nucleo\Helpers\AyudanteOperador',
        'LiteFramework\Nucleo\Helpers\AyudanteSeguridad',
        'LiteFramework\Nucleo\Excepciones\ErrorSeguridad',
        'LiteFramework\Nucleo\Excepciones\ErrorAutenticacion',
        'LiteFramework\Nucleo\Excepciones\ErrorValidacion',
        'LiteFramework\Nucleo\DialectoBaseDatos',
    ];

    public function testTodasLasClasesResuelvenViaAlias(): void {
        $fallidas = [];
        foreach (self::$clasesFramework as $clase) {
            if (!class_exists($clase)) {
                $fallidas[] = $clase;
            }
        }
        $this->assertEmpty(
            $fallidas,
            'Las siguientes clases no se resuelven via alias: ' . implode(', ', $fallidas)
        );
    }

    public function testTodasLasClasesResuelvenViaPSR4(): void {
        $fallidas = [];
        foreach (self::$clasesNamespaced as $clase) {
            if (!class_exists($clase)) {
                $fallidas[] = $clase;
            }
        }
        $this->assertEmpty(
            $fallidas,
            'Las siguientes clases no se resuelven via PSR-4: ' . implode(', ', $fallidas)
        );
    }

    public function testRutasCargarCorrectamente(): void {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $enrutador = require DIRECTORIO_RAIZ . '/rutas/web.php';
        $this->assertInstanceOf(
            'LiteFramework\Nucleo\Enrutador',
            $enrutador,
            'rutas/web.php debe devolver una instancia de Enrutador'
        );
    }

    public function testExcepcionesPersonalizadas(): void {
        $seguridad = new ErrorSeguridad('test');
        $this->assertEquals(403, $seguridad->getCode());

        $autenticacion = new ErrorAutenticacion('test');
        $this->assertEquals(401, $autenticacion->getCode());

        $validacion = new ErrorValidacion('test', ['campo' => 'error']);
        $this->assertEquals(422, $validacion->getCode());
        $this->assertEquals(['campo' => 'error'], $validacion->obtenerErrores());
    }

    public function testEnrutadorUsaReflectionMethod(): void {
        // Verificar que el archivo Enrutador.php tenga use ReflectionMethod
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/servidor/nucleo/Enrutador.php');
        $this->assertStringContainsString(
            'use ReflectionMethod;',
            $contenido,
            'Enrutador.php debe tener use ReflectionMethod;'
        );
    }
}
