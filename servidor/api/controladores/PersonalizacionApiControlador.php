<?php

declare(strict_types=1);

namespace LiteFramework\Api\Controladores;

use PDO;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Nucleo\DialectoBaseDatos;
use Exception;

class PersonalizacionApiControlador
{
    public function guardar(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        if (empty($_SESSION['operador_id'])) {
            $respuesta['mensaje_error'] = 'Debe iniciar sesion para guardar preferencias.';
            $respuesta['codigo_error'] = 'acceso_denegado';
            return [401, $respuesta];
        }

        $camposPermitidos = ['paleta', 'estilo', 'fondo', 'textura', 'fuente', 'espaciado', 'tamano', 'radio', 'animacion', 'grosor', 'sombra', 'tema'];
        $personalizacion = [];

        foreach ($camposPermitidos as $campo) {
            if (isset($payload[$campo])) {
                $personalizacion[$campo] = preg_replace('/[^a-z0-9_-]/i', '', $payload[$campo]);
            }
        }

        if (empty($personalizacion)) {
            $respuesta['mensaje_error'] = 'No se recibieron preferencias para guardar.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [400, $respuesta];
        }

        $idOperador = (int)$_SESSION['operador_id'];
        $configuracionJson = json_encode($personalizacion);

        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();

            if (DialectoBaseDatos::esSQLite($conexion)) {
                $sql = "INSERT INTO operador_personalizacion (id_operador, configuracion) VALUES (:id, :conf) ON CONFLICT(id_operador) DO UPDATE SET configuracion = :conf2";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([':id' => $idOperador, ':conf' => $configuracionJson, ':conf2' => $configuracionJson]);
            } else {
                $stmt = $conexion->prepare("INSERT INTO operador_personalizacion (id_operador, configuracion) VALUES (:id, :conf) ON DUPLICATE KEY UPDATE configuracion = VALUES(configuracion)");
                $stmt->execute([':id' => $idOperador, ':conf' => $configuracionJson]);
            }

            $_SESSION['personalizacion_ui'] = $personalizacion;

            $respuesta['estado_operacion'] = true;
            $respuesta['mensaje_error'] = null;
            $respuesta['codigo_error'] = null;
            $respuesta['datos'] = $personalizacion;
            return [200, $respuesta];
        } catch (Exception $e) {
            $respuesta['mensaje_error'] = 'Error al guardar preferencias.';
            $respuesta['codigo_error'] = 'error_interno';
            return [500, $respuesta];
        }
    }

    public function obtener(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        if (empty($_SESSION['operador_id'])) {
            $respuesta['mensaje_error'] = 'Debe iniciar sesion para cargar preferencias.';
            $respuesta['codigo_error'] = 'acceso_denegado';
            return [401, $respuesta];
        }

        $idOperador = (int)$_SESSION['operador_id'];
        $valores = [];

        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $stmt = $conexion->prepare("SELECT configuracion FROM operador_personalizacion WHERE id_operador = :id LIMIT 1");
            $stmt->execute([':id' => $idOperador]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fila) {
                $valores = json_decode($fila['configuracion'], true) ?: [];
                $_SESSION['personalizacion_ui'] = $valores;
            }
        } catch (Exception $e) {
            $valores = [];
        }

        $respuesta['estado_operacion'] = true;
        $respuesta['mensaje_error'] = null;
        $respuesta['codigo_error'] = null;
        $respuesta['datos'] = $valores;
        return [200, $respuesta];
    }
}
