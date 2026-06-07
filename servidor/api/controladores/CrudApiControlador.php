<?php

declare(strict_types=1);

namespace LiteFramework\Api\Controladores;

use PDO;
use LiteFramework\Seguridad\SeguridadServidor;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Seguridad\SseGestor;
use LiteFramework\Config\ConexionBaseDatos;

class CrudApiControlador
{
    private function determinarTipoPDO($valor): int
    {
        if (is_null($valor)) {
            return PDO::PARAM_NULL;
        }
        if (is_bool($valor)) {
            return PDO::PARAM_BOOL;
        }
        if (is_int($valor)) {
            return PDO::PARAM_INT;
        }
        if (is_string($valor) && ctype_digit($valor)) {
            return PDO::PARAM_INT;
        }
        return PDO::PARAM_STR;
    }

    private function obtenerColumnasEntidad($conexion, string $entidad): array
    {
        $claveCache = 'esquema_cache_' . $entidad;
        $claveTiempo = 'esquema_cache_tiempo_' . $entidad;
        if (isset($_SESSION[$claveCache], $_SESSION[$claveTiempo]) && (time() - $_SESSION[$claveTiempo]) < 60) {
            return $_SESSION[$claveCache];
        }
        $stmt = $conexion->prepare("DESCRIBE {$entidad}");
        $stmt->execute();
        $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $_SESSION[$claveCache] = $columnas;
        $_SESSION[$claveTiempo] = time();
        return $columnas;
    }

    public function procesar(array $payload): array
    {
        $respuesta = ['estado_operacion' => false, 'mensaje_error' => null, 'codigo_error' => null];

        if (empty($_SESSION['operador_id'])) {
            $respuesta['mensaje_error'] = 'Su sesión ha expirado. Recargue la página e inicie sesión.';
            $respuesta['codigo_error'] = 'no_autenticado';
            return [401, $respuesta];
        }

        $tablaDestino = $payload['tabla_destino'] ?? '';
        $entidadDinamica = preg_replace('/[^a-zA-Z_\x80-\xff]/', '', $tablaDestino);

        if (empty($entidadDinamica)) {
            $respuesta['mensaje_error'] = 'Entidad de datos no especificada.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [400, $respuesta];
        }

        $entidadesPermitidas = ['operador', 'rbac_rol', 'bitacora_sistema', 'estadistica'];
        if (!in_array($entidadDinamica, $entidadesPermitidas, true)) {
            RegistroAuditoria::seguridad('Intento de acceso a entidad no autorizada', [
                'entidad' => $entidadDinamica,
                'tabla_original' => $tablaDestino,
            ]);
            $respuesta['mensaje_error'] = 'Entidad de datos no autorizada.';
            $respuesta['codigo_error'] = 'acceso_denegado';
            return [403, $respuesta];
        }

        $operacionCrud = $payload['accion_crud'] ?? '';
        $idEntidad = $payload['id_entidad'] ?? 0;
        $camposEntidad = array_diff_key($payload, array_flip(['token_peticion', 'accion_crud', 'tabla_destino', 'id_entidad']));
        $conexion = null;
        $paginaActual = 1;
        $limiteRegistros = 0;
        $totalPaginas = 1;

        if ($operacionCrud === 'crear') {
            if (!SeguridadServidor::tienePermiso($entidadDinamica . '.crear')) {
                $respuesta['mensaje_error'] = 'No tiene permiso para crear registros en esta entidad.';
                $respuesta['codigo_error'] = 'sin_permiso';
                return [403, $respuesta];
            }
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $columnasValidas = $this->obtenerColumnasEntidad($conexion, $entidadDinamica);
            $columnasInvalidas = array_diff(array_keys($camposEntidad), $columnasValidas);
            if (!empty($columnasInvalidas)) {
                $respuesta['mensaje_error'] = 'Columnas no existentes en la entidad: ' . implode(', ', $columnasInvalidas);
                $respuesta['codigo_error'] = 'datos_invalidos';
                return [400, $respuesta];
            }
            $columnas = implode(', ', array_keys($camposEntidad));
            $marcadores = ':' . implode(', :', array_keys($camposEntidad));
            $instruccionSQL = "INSERT INTO {$entidadDinamica} ({$columnas}) VALUES ({$marcadores})";
        } elseif ($operacionCrud === 'actualizar') {
            if (!SeguridadServidor::tienePermiso($entidadDinamica . '.actualizar')) {
                $respuesta['mensaje_error'] = 'No tiene permiso para modificar registros en esta entidad.';
                $respuesta['codigo_error'] = 'sin_permiso';
                return [403, $respuesta];
            }
            if ($idEntidad === 0) {
                $idAlternativo = (int)($payload['id_' . $entidadDinamica] ?? 0);
                if ($idAlternativo !== 0) {
                    $idEntidad = $idAlternativo;
                } else {
                    $respuesta['mensaje_error'] = 'Identificador de registro no proporcionado.';
                    $respuesta['codigo_error'] = 'datos_invalidos';
                    return [400, $respuesta];
                }
            }
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $columnasValidas = $this->obtenerColumnasEntidad($conexion, $entidadDinamica);
            $columnasInvalidas = array_diff(array_keys($camposEntidad), $columnasValidas);
            if (!empty($columnasInvalidas)) {
                $respuesta['mensaje_error'] = 'Columnas no existentes en la entidad: ' . implode(', ', $columnasInvalidas);
                $respuesta['codigo_error'] = 'datos_invalidos';
                return [400, $respuesta];
            }
            $asignaciones = implode(', ', array_map(function ($col) {
                return "{$col} = :{$col}";
            }, array_keys($camposEntidad)));
            $instruccionSQL = "UPDATE {$entidadDinamica} SET {$asignaciones} WHERE id_{$entidadDinamica} = :id_entidad";
            $camposEntidad['id_entidad'] = $idEntidad;
        } elseif ($operacionCrud === 'eliminar') {
            if (!SeguridadServidor::tienePermiso($entidadDinamica . '.eliminar')) {
                $respuesta['mensaje_error'] = 'No tiene permiso para eliminar registros en esta entidad.';
                $respuesta['codigo_error'] = 'sin_permiso';
                return [403, $respuesta];
            }
            if ($idEntidad === 0) {
                $respuesta['mensaje_error'] = 'Identificador de registro no proporcionado.';
                $respuesta['codigo_error'] = 'datos_invalidos';
                return [400, $respuesta];
            }
            $instruccionSQL = "DELETE FROM {$entidadDinamica} WHERE id_{$entidadDinamica} = :id_entidad";
            $camposEntidad = ['id_entidad' => $idEntidad];
        } elseif ($operacionCrud === 'leer') {
            if (!SeguridadServidor::tienePermiso($entidadDinamica . '.leer')) {
                $respuesta['mensaje_error'] = 'No tiene permiso para consultar esta entidad de datos.';
                $respuesta['codigo_error'] = 'sin_permiso';
                return [403, $respuesta];
            }
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $columnasValidas = $this->obtenerColumnasEntidad($conexion, $entidadDinamica);

            if (!empty($idEntidad)) {
                $instruccionSQL = "SELECT * FROM {$entidadDinamica} WHERE id_{$entidadDinamica} = :id_entidad LIMIT 1";
                $camposEntidad = ['id_entidad' => $idEntidad];
            } else {
                $filtros = $payload['filtros'] ?? [];
                $condicionesFiltro = [];
                foreach ($filtros as $columna => $valor) {
                    $columnaLimpia = preg_replace('/[^a-zA-Z_\x80-\xff]/', '', $columna);
                    if ($columnaLimpia !== '' && in_array($columnaLimpia, $columnasValidas, true)) {
                        $claveParam = 'filtro_' . str_replace('.', '_', $columnaLimpia);
                        $condicionesFiltro[] = "{$columnaLimpia} = :{$claveParam}";
                        $camposEntidad[$claveParam] = $valor;
                    }
                }
                $clausulaWhere = !empty($condicionesFiltro) ? 'WHERE ' . implode(' AND ', $condicionesFiltro) : '';

                $limiteRegistros = min(max((int)($payload['limite'] ?? 50), 1), 200);
                $inicioDesde = max((int)($payload['inicio'] ?? 0), 0);
                $ordenColumna = $payload['ordenar_por'] ?? 'id_' . $entidadDinamica;
                if (!in_array($ordenColumna, $columnasValidas, true)) {
                    $ordenColumna = 'id_' . $entidadDinamica;
                }
                $ordenDireccion = strtoupper($payload['direccion_orden'] ?? 'DESC');
                $ordenDireccion = in_array($ordenDireccion, ['ASC', 'DESC'], true) ? $ordenDireccion : 'DESC';

                $stmtTotal = $conexion->prepare("SELECT COUNT(*) FROM {$entidadDinamica} {$clausulaWhere}");
                foreach ($camposEntidad as $clave => $valor) {
                    $claveParam = (strpos($clave, ':') === 0) ? $clave : ":{$clave}";
                    $stmtTotal->bindValue($claveParam, $valor, $this->determinarTipoPDO($valor));
                }
                $stmtTotal->execute();
                $totalRegistros = (int)$stmtTotal->fetchColumn();
                $totalPaginas = (int)ceil($totalRegistros / (float)$limiteRegistros);
                $paginaActual = (int)floor($inicioDesde / (float)$limiteRegistros) + 1;

                $instruccionSQL = "SELECT * FROM {$entidadDinamica} {$clausulaWhere} ORDER BY {$ordenColumna} {$ordenDireccion} LIMIT {$limiteRegistros} OFFSET {$inicioDesde}";
            }
        } elseif ($operacionCrud === 'buscar') {
            if (!SeguridadServidor::tienePermiso($entidadDinamica . '.leer')) {
                $respuesta['mensaje_error'] = 'No tiene permiso para buscar en esta entidad.';
                $respuesta['codigo_error'] = 'sin_permiso';
                return [403, $respuesta];
            }
            $terminoBusqueda = $payload['termino_busqueda'] ?? '';
            if (empty($terminoBusqueda)) {
                $respuesta['mensaje_error'] = 'Término de búsqueda vacío.';
                $respuesta['codigo_error'] = 'datos_invalidos';
                return [400, $respuesta];
            }
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $consultaEsquema = $conexion->prepare("DESCRIBE {$entidadDinamica}");
            $consultaEsquema->execute();
            $columnas = $consultaEsquema->fetchAll(PDO::FETCH_COLUMN);
            $condicionesBusqueda = implode(' OR ', array_map(function ($col) {
                return "{$col} LIKE :termino_busqueda";
            }, $columnas));
            $instruccionSQL = "SELECT * FROM {$entidadDinamica} WHERE ({$condicionesBusqueda}) LIMIT 50";
            $camposEntidad = [':termino_busqueda' => "%{$terminoBusqueda}%"];
        } else {
            $respuesta['mensaje_error'] = 'Operación desconocida.';
            $respuesta['codigo_error'] = 'datos_invalidos';
            return [400, $respuesta];
        }

        $conexion = $conexion ?? ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $manejadorConsulta = $conexion->prepare($instruccionSQL);

        foreach ($camposEntidad as $clave => $valor) {
            $claveParam = (strpos($clave, ':') === 0) ? $clave : ":{$clave}";
            $tipoParametro = $this->determinarTipoPDO($valor);
            $manejadorConsulta->bindValue($claveParam, $valor, $tipoParametro);
        }

        if ($manejadorConsulta->execute()) {
            if ($operacionCrud === 'crear') {
                $idAfectado = (int)$conexion->lastInsertId();
                $respuesta['datos'] = ['id_afectado' => $idAfectado];
                RegistroAuditoria::auditoria($entidadDinamica, 'Crear registro', [
                    'id' => $idAfectado,
                    'tabla' => $entidadDinamica,
                    'datos_enviados' => array_diff_key($camposEntidad, ['id_entidad' => 0]),
                ]);
            } elseif ($operacionCrud === 'actualizar') {
                $idAfectado = (int)$idEntidad;
                $respuesta['datos'] = ['id_afectado' => $idAfectado];
                $datosNuevos = $payload['datos'] ?? [];
                RegistroAuditoria::auditoria($entidadDinamica, 'Actualizar registro', [
                    'id' => $idAfectado,
                    'tabla' => $entidadDinamica,
                    'campos_modificados' => array_keys($datosNuevos),
                    'datos_nuevos' => $datosNuevos,
                ]);
                if ($entidadDinamica === 'operador') {
                    SseGestor::emitirATodos('operador.actualizado', ['id' => $idAfectado]);
                }
            } elseif ($operacionCrud === 'eliminar') {
                $idAfectado = (int)$idEntidad;
                $respuesta['datos'] = ['id_afectado' => $idAfectado];
                RegistroAuditoria::auditoria($entidadDinamica, 'Eliminar registro', [
                    'id' => $idAfectado,
                    'tabla' => $entidadDinamica,
                ]);
            } elseif ($operacionCrud === 'leer') {
                $respuesta['datos'] = $manejadorConsulta->fetchAll(PDO::FETCH_ASSOC);
                if (empty($idEntidad) && isset($totalRegistros)) {
                    $respuesta['total'] = $totalRegistros;
                    $respuesta['pagina'] = $paginaActual;
                    $respuesta['por_pagina'] = $limiteRegistros;
                    $respuesta['total_paginas'] = $totalPaginas;
                }
            } elseif ($operacionCrud === 'buscar') {
                $respuesta['datos'] = $manejadorConsulta->fetchAll(PDO::FETCH_ASSOC);
            }
            $respuesta['estado_operacion'] = true;
            $respuesta['mensaje_error'] = null;
            $respuesta['codigo_error'] = null;
        } else {
            $respuesta['mensaje_error'] = 'Error al ejecutar la operación en la base de datos.';
            $respuesta['codigo_error'] = 'error_interno';
        }

        return [200, $respuesta];
    }
}
