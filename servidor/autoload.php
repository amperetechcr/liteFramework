<?php

declare(strict_types=1);

$baseDir = __DIR__;

$mapaNamespaced = [
    'LiteFramework\Nucleo\Modelo'                => $baseDir . '/nucleo/Modelo.php',
    'LiteFramework\Nucleo\Validador'             => $baseDir . '/nucleo/Validador.php',
    'LiteFramework\Nucleo\Enrutador'             => $baseDir . '/nucleo/Enrutador.php',
    'LiteFramework\Nucleo\Paginador'             => $baseDir . '/nucleo/Paginador.php',
    'LiteFramework\Nucleo\SubidaArchivos'        => $baseDir . '/nucleo/SubidaArchivos.php',
    'LiteFramework\Nucleo\ManejadorErrores'      => $baseDir . '/nucleo/ManejadorErrores.php',
    'LiteFramework\Nucleo\RutaBuilder'           => $baseDir . '/nucleo/RutaBuilder.php',
    'LiteFramework\Nucleo\Interceptor'           => $baseDir . '/nucleo/Interceptor.php',
    'LiteFramework\Nucleo\Helpers\Helper'        => $baseDir . '/nucleo/Helpers/Helper.php',
    'LiteFramework\Nucleo\Helpers\AyudanteFecha' => $baseDir . '/nucleo/Helpers/AyudanteFecha.php',
    'LiteFramework\Nucleo\Helpers\AyudanteCadena' => $baseDir . '/nucleo/Helpers/AyudanteCadena.php',
    'LiteFramework\Nucleo\Helpers\AyudanteArreglo' => $baseDir . '/nucleo/Helpers/AyudanteArreglo.php',
    'LiteFramework\Nucleo\Helpers\AyudanteGeneral' => $baseDir . '/nucleo/Helpers/AyudanteGeneral.php',
    'LiteFramework\Nucleo\Helpers\AyudanteArchivo' => $baseDir . '/nucleo/Helpers/AyudanteArchivo.php',
    'LiteFramework\Nucleo\Helpers\AyudanteOperador' => $baseDir . '/nucleo/Helpers/AyudanteOperador.php',
    'LiteFramework\Nucleo\Helpers\AyudanteSeguridad' => $baseDir . '/nucleo/Helpers/AyudanteSeguridad.php',
    'LiteFramework\Seguridad\GestorSesiones'     => $baseDir . '/seguridad/GestorSesiones.php',
    'LiteFramework\Seguridad\SeguridadServidor'  => $baseDir . '/seguridad/SeguridadServidor.php',
    'LiteFramework\Seguridad\ValidadorCSRF'      => $baseDir . '/seguridad/ValidadorCSRF.php',
    'LiteFramework\Seguridad\ControlAccesoRBAC'  => $baseDir . '/seguridad/ControlAccesoRBAC.php',
    'LiteFramework\Seguridad\PoliticaContrasena' => $baseDir . '/seguridad/PoliticaContrasena.php',
    'LiteFramework\Seguridad\SanitizadorEntrada' => $baseDir . '/seguridad/SanitizadorEntrada.php',
    'LiteFramework\Seguridad\RegistroAuditoria'  => $baseDir . '/seguridad/RegistroAuditoria.php',
    'LiteFramework\Seguridad\TrazadorPeticiones' => $baseDir . '/seguridad/TrazadorPeticiones.php',
    'LiteFramework\Seguridad\SseGestor'          => $baseDir . '/seguridad/SseGestor.php',
    'LiteFramework\Seguridad\LimitadorPeticiones'  => $baseDir . '/seguridad/LimitadorPeticiones.php',
    'LiteFramework\Config\ConexionBaseDatos'     => $baseDir . '/config/conexion.php',
    'LiteFramework\Config\GestorEntorno'         => $baseDir . '/config/entorno.php',
    'LiteFramework\Config\ConfiguracionSistema'  => $baseDir . '/config/configuracion_sistema.php',
    'LiteFramework\Config\GeneradorIniServidor'  => $baseDir . '/config/generador_ini_servidor.php',
    'LiteFramework\Migraciones\GestorMigraciones' => $baseDir . '/migraciones/GestorMigraciones.php',
    'LiteFramework\Modelos\Operador'             => $baseDir . '/modelos/Operador.php',
    'LiteFramework\Modelos\Rol'                  => $baseDir . '/modelos/Rol.php',
    'LiteFramework\Modelos\Archivo'              => $baseDir . '/modelos/Archivo.php',
    'LiteFramework\Modelos\DocumentoPdf'         => $baseDir . '/modelos/DocumentoPdf.php',
    'LiteFramework\Modelos\Estadistica'          => $baseDir . '/modelos/Estadistica.php',
    'LiteFramework\Controladores\ControladorBase' => $baseDir . '/controladores/ControladorBase.php',
    'LiteFramework\Controladores\ModuloControlador' => $baseDir . '/controladores/ModuloControlador.php',
    'LiteFramework\Controladores\AutenticacionControlador' => $baseDir . '/controladores/AutenticacionControlador.php',
    'LiteFramework\Controladores\SubirArchivosControlador' => $baseDir . '/controladores/SubirArchivosControlador.php',
    'LiteFramework\Middleware\AutenticacionInterceptor' => $baseDir . '/middleware/AutenticacionInterceptor.php',
    'LiteFramework\Middleware\ApiAuthInterceptor' => $baseDir . '/middleware/ApiAuthInterceptor.php',
    'LiteFramework\Middleware\MantenimientoInterceptor' => $baseDir . '/middleware/MantenimientoInterceptor.php',
    'LiteFramework\Api\Controladores\AutenticacionApiControlador' => $baseDir . '/api/controladores/AutenticacionApiControlador.php',
    'LiteFramework\Api\Controladores\OperadorApiControlador' => $baseDir . '/api/controladores/OperadorApiControlador.php',
    'LiteFramework\Api\Controladores\PersonalizacionApiControlador' => $baseDir . '/api/controladores/PersonalizacionApiControlador.php',
    'LiteFramework\Api\Controladores\MigracionApiControlador' => $baseDir . '/api/controladores/MigracionApiControlador.php',
    'LiteFramework\Api\Controladores\ConfiguracionApiControlador' => $baseDir . '/api/controladores/ConfiguracionApiControlador.php',
    'LiteFramework\Api\Controladores\CrudApiControlador' => $baseDir . '/api/controladores/CrudApiControlador.php',
    'LiteFramework\Api\Controladores\GeneradorModuloApiControlador' => $baseDir . '/api/controladores/GeneradorModuloApiControlador.php',
    'LiteFramework\Api\Controladores\GeneradorProyectoApiControlador' => $baseDir . '/api/controladores/GeneradorProyectoApiControlador.php',
    'LiteFramework\Servicios\GeneradorPdf'       => $baseDir . '/servicios/GeneradorPdf.php',
    'LiteFramework\Servicios\GeneradorEstadisticas' => $baseDir . '/servicios/GeneradorEstadisticas.php',
    'LiteFramework\Servicios\GeneradorModulo'    => $baseDir . '/servicios/GeneradorModulo.php',
    'LiteFramework\Servicios\GeneradorProyecto'  => $baseDir . '/servicios/GeneradorProyecto.php',
    'LiteFramework\Servicios\ServicioPaginacion' => $baseDir . '/servicios/ServicioPaginacion.php',
    'LiteFramework\Servicios\AdministradorArchivos' => $baseDir . '/servicios/AdministradorArchivos.php',
    'LiteFramework\Servicios\AutenticacionOAuth' => $baseDir . '/servicios/AutenticacionOAuth.php',
    'LiteFramework\Servicios\Correo' => $baseDir . '/servicios/Correo.php',
    'LiteFramework\Servicios\ContextoError' => $baseDir . '/servicios/ContextoError.php',
    'LiteFramework\Servicios\DiagnosticoError' => $baseDir . '/servicios/DiagnosticoError.php',
    'LiteFramework\Servicios\RemediadorError' => $baseDir . '/servicios/RemediadorError.php',
    'LiteFramework\Servicios\Verificadores\VerificadorArchivos' => $baseDir . '/servicios/Verificadores/VerificadorArchivos.php',
    'LiteFramework\Servicios\Verificadores\VerificadorBaseDatos' => $baseDir . '/servicios/Verificadores/VerificadorBaseDatos.php',
    'LiteFramework\Servicios\Verificadores\VerificadorError' => $baseDir . '/servicios/Verificadores/VerificadorError.php',
    'LiteFramework\Servicios\Verificadores\VerificadorSeguridad' => $baseDir . '/servicios/Verificadores/VerificadorSeguridad.php',
    'LiteFramework\Servicios\Verificadores\VerificadorSistema' => $baseDir . '/servicios/Verificadores/VerificadorSistema.php',
    'LiteFramework\Nucleo\Excepciones\ErrorSeguridad' => $baseDir . '/nucleo/Excepciones/ErrorSeguridad.php',
    'LiteFramework\Nucleo\Excepciones\ErrorAutenticacion' => $baseDir . '/nucleo/Excepciones/ErrorAutenticacion.php',
    'LiteFramework\Nucleo\Excepciones\ErrorValidacion' => $baseDir . '/nucleo/Excepciones/ErrorValidacion.php',
    'LiteFramework\Nucleo\DialectoBaseDatos' => $baseDir . '/nucleo/DialectoBaseDatos.php',
    'LiteFramework\Cli\Consola' => $baseDir . '/cli/Consola.php',
    'Psr\Log\LoggerInterface' => $baseDir . '/psr/Log/LoggerInterface.php',
    'Psr\Log\LogLevel' => $baseDir . '/psr/Log/LogLevel.php',
    'Psr\Log\NullLogger' => $baseDir . '/psr/Log/NullLogger.php',
    'LiteFramework\Log\Logger' => $baseDir . '/log/Logger.php',
];

$alias = [
    'Modelo' => 'LiteFramework\Nucleo\Modelo',
    'Validador' => 'LiteFramework\Nucleo\Validador',
    'Enrutador' => 'LiteFramework\Nucleo\Enrutador',
    'Paginador' => 'LiteFramework\Nucleo\Paginador',
    'SubidaArchivos' => 'LiteFramework\Nucleo\SubidaArchivos',
    'ManejadorErrores' => 'LiteFramework\Nucleo\ManejadorErrores',
    'Helper' => 'LiteFramework\Nucleo\Helpers\Helper',
    'AyudanteFecha' => 'LiteFramework\Nucleo\Helpers\AyudanteFecha',
    'AyudanteCadena' => 'LiteFramework\Nucleo\Helpers\AyudanteCadena',
    'AyudanteArreglo' => 'LiteFramework\Nucleo\Helpers\AyudanteArreglo',
    'AyudanteGeneral' => 'LiteFramework\Nucleo\Helpers\AyudanteGeneral',
    'AyudanteArchivo' => 'LiteFramework\Nucleo\Helpers\AyudanteArchivo',
    'AyudanteOperador' => 'LiteFramework\Nucleo\Helpers\AyudanteOperador',
    'AyudanteSeguridad' => 'LiteFramework\Nucleo\Helpers\AyudanteSeguridad',
    'Fecha' => 'LiteFramework\Nucleo\Helpers\AyudanteFecha',
    'Cadena' => 'LiteFramework\Nucleo\Helpers\AyudanteCadena',
    'Arreglo' => 'LiteFramework\Nucleo\Helpers\AyudanteArreglo',
    'General' => 'LiteFramework\Nucleo\Helpers\AyudanteGeneral',
    'ArchivoH' => 'LiteFramework\Nucleo\Helpers\AyudanteArchivo',
    'OperadorH' => 'LiteFramework\Nucleo\Helpers\AyudanteOperador',
    'Seguridad' => 'LiteFramework\Nucleo\Helpers\AyudanteSeguridad',
    'GestorSesiones' => 'LiteFramework\Seguridad\GestorSesiones',
    'SeguridadServidor' => 'LiteFramework\Seguridad\SeguridadServidor',
    'ValidadorCSRF' => 'LiteFramework\Seguridad\ValidadorCSRF',
    'ControlAccesoRBAC' => 'LiteFramework\Seguridad\ControlAccesoRBAC',
    'PoliticaContrasena' => 'LiteFramework\Seguridad\PoliticaContrasena',
    'SanitizadorEntrada' => 'LiteFramework\Seguridad\SanitizadorEntrada',
    'RegistroAuditoria' => 'LiteFramework\Seguridad\RegistroAuditoria',
    'TrazadorPeticiones' => 'LiteFramework\Seguridad\TrazadorPeticiones',
    'SseGestor' => 'LiteFramework\Seguridad\SseGestor',
    'ConexionBaseDatos' => 'LiteFramework\Config\ConexionBaseDatos',
    'GestorEntorno' => 'LiteFramework\Config\GestorEntorno',
    'ConfiguracionSistema' => 'LiteFramework\Config\ConfiguracionSistema',
    'GeneradorIniServidor' => 'LiteFramework\Config\GeneradorIniServidor',
    'GestorMigraciones' => 'LiteFramework\Migraciones\GestorMigraciones',
    'Operador' => 'LiteFramework\Modelos\Operador',
    'Rol' => 'LiteFramework\Modelos\Rol',
    'Archivo' => 'LiteFramework\Modelos\Archivo',
    'DocumentoPdf' => 'LiteFramework\Modelos\DocumentoPdf',
    'Estadistica' => 'LiteFramework\Modelos\Estadistica',
    'ControladorBase' => 'LiteFramework\Controladores\ControladorBase',
    'ModuloControlador' => 'LiteFramework\Controladores\ModuloControlador',
    'AutenticacionControlador' => 'LiteFramework\Controladores\AutenticacionControlador',
    'SubirArchivosControlador' => 'LiteFramework\Controladores\SubirArchivosControlador',
    'AutenticacionInterceptor' => 'LiteFramework\Middleware\AutenticacionInterceptor',
    'ApiAuthInterceptor' => 'LiteFramework\Middleware\ApiAuthInterceptor',
    'AutenticacionApiControlador' => 'LiteFramework\Api\Controladores\AutenticacionApiControlador',
    'OperadorApiControlador' => 'LiteFramework\Api\Controladores\OperadorApiControlador',
    'PersonalizacionApiControlador' => 'LiteFramework\Api\Controladores\PersonalizacionApiControlador',
    'MigracionApiControlador' => 'LiteFramework\Api\Controladores\MigracionApiControlador',
    'ConfiguracionApiControlador' => 'LiteFramework\Api\Controladores\ConfiguracionApiControlador',
    'CrudApiControlador' => 'LiteFramework\Api\Controladores\CrudApiControlador',
    'GeneradorModuloApiControlador' => 'LiteFramework\Api\Controladores\GeneradorModuloApiControlador',
    'GeneradorProyectoApiControlador' => 'LiteFramework\Api\Controladores\GeneradorProyectoApiControlador',
    'GeneradorPdf' => 'LiteFramework\Servicios\GeneradorPdf',
    'GeneradorEstadisticas' => 'LiteFramework\Servicios\GeneradorEstadisticas',
    'GeneradorModulo' => 'LiteFramework\Servicios\GeneradorModulo',
    'GeneradorProyecto' => 'LiteFramework\Servicios\GeneradorProyecto',
    'AdministradorArchivos' => 'LiteFramework\Servicios\AdministradorArchivos',
    'ContextoError' => 'LiteFramework\Servicios\ContextoError',
    'DiagnosticoError' => 'LiteFramework\Servicios\DiagnosticoError',
    'RemediadorError' => 'LiteFramework\Servicios\RemediadorError',
    'VerificadorArchivos' => 'LiteFramework\Servicios\Verificadores\VerificadorArchivos',
    'VerificadorBaseDatos' => 'LiteFramework\Servicios\Verificadores\VerificadorBaseDatos',
    'VerificadorError' => 'LiteFramework\Servicios\Verificadores\VerificadorError',
    'VerificadorSeguridad' => 'LiteFramework\Servicios\Verificadores\VerificadorSeguridad',
    'VerificadorSistema' => 'LiteFramework\Servicios\Verificadores\VerificadorSistema',
    'ErrorSeguridad' => 'LiteFramework\Nucleo\Excepciones\ErrorSeguridad',
    'ErrorAutenticacion' => 'LiteFramework\Nucleo\Excepciones\ErrorAutenticacion',
    'ErrorValidacion' => 'LiteFramework\Nucleo\Excepciones\ErrorValidacion',
    'DialectoBaseDatos' => 'LiteFramework\Nucleo\DialectoBaseDatos',
    'Logger' => 'LiteFramework\Log\Logger',
];

spl_autoload_register(function (string $clase) use ($mapaNamespaced, $alias): void {
    if (isset($mapaNamespaced[$clase])) {
        require_once $mapaNamespaced[$clase];
        return;
    }
    if (isset($alias[$clase])) {
        $nsClase = $alias[$clase];
        require_once $mapaNamespaced[$nsClase];
        if (!class_exists($clase, false)) {
            class_alias($nsClase, $clase);
        }
    }
    $prefijo = 'LiteFramework\\';
    if (str_starts_with($clase, $prefijo)) {
        $rutaRel = str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
        $archivo = __DIR__ . '/' . $rutaRel;
        if (file_exists($archivo)) {
            require_once $archivo;
        }
    }
});

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
