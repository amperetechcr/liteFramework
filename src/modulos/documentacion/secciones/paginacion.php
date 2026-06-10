<?php

return [
        'id' => 'paginacion',
        'titulo' => 'Paginacion',
        'icono' => '↕',
        'etiquetas' => 'paginacion PDO paginador lista pagina offset limite',
        'descripcion' => 'Patron de paginacion con consultas PDO directas y la clase Paginador. Regla critica: nunca usar el ORM para paginar.',
        'contenido' => '
            <p>Para listados con paginacion, el framework provee la clase <code>Paginador</code> y <strong>requiere usar consultas PDO directas</strong>. El query builder del ORM (<code>limite()</code> + <code>saltar()</code>) puede fallar con error 500 por estado estatico compartido.</p>

            <div class="alerta alerta-peligro margen-inferior-normal">
                <strong>Regla critica:</strong> nunca usar <code>Modelo::todos()->limite()->saltar()</code>. Siempre usar consultas PDO directas para paginacion.
            </div>

            <h3 class="margen-inferior-pequeno">Patron correcto (PDO directo)</h3>
            <pre><code>try {
    $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();

    $condiciones = [];
    $parametros = [];

    if ($busqueda !== \'\') {
        $condiciones[] = "nombre LIKE :buscar";
        $parametros[\':buscar\'] = \'%\' . $busqueda . \'%\';
    }

    $clausulaWhere = !empty($condiciones) ? \'WHERE \' . implode(\' AND \', $condiciones) : \'\';

    $sqlTotal = "SELECT COUNT(*) FROM productos {$clausulaWhere}";
    $stmtTotal = $conexion->prepare($sqlTotal);
    foreach ($parametros as $clave => $valor) {
        $stmtTotal->bindValue($clave, $valor);
    }
    $stmtTotal->execute();
    $totalRegistros = (int)$stmtTotal->fetchColumn();

    $porPagina = 12;
    $paginaActual = isset($_GET[\'pagina\']) ? max(1, (int)$_GET[\'pagina\']) : 1;
    $totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));
    if ($paginaActual > $totalPaginas) $paginaActual = $totalPaginas;
    $inicio = ($paginaActual - 1) * $porPagina;

    $paginador = Paginador::crear($totalRegistros, $porPagina);

    $sql = "SELECT * FROM productos {$clausulaWhere} ORDER BY id DESC LIMIT :limite OFFSET :inicio";
    $consulta = $conexion->prepare($sql);
    foreach ($parametros as $clave => $valor) {
        $consulta->bindValue($clave, $valor);
    }
    $consulta->bindValue(\':limite\', $porPagina, PDO::PARAM_INT);
    $consulta->bindValue(\':inicio\', $inicio, PDO::PARAM_INT);
    $consulta->execute();
    $registros = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $registros = [];
    $paginador = Paginador::crear(0, $porPagina);
}

echo $paginador->render();</code></pre>

            <h3 class="margen-inferior-pequeno">API del Paginador</h3>
            <p>Propiedades publicas (no metodos):</p>
            <pre><code>$paginador->paginaActual;
$paginador->porPagina;
$paginador->totalPaginas;

$paginador->render();
$paginador->esPaginaActual(3);
$paginador->anterior();
$paginador->siguiente();
$paginador->tieneAnterior();
$paginador->tieneSiguiente();
$paginador->offset();
$paginador->aArreglo();</code></pre>

            <h3 class="margen-inferior-pequeno">Patron incorrecto (NO usar)</h3>
            <pre><code>$total = Producto::contar();
$paginador = Paginador::crear($total, 12);
$registros = Producto::todos()
    ->ordenarPor(\'fecha\', \'DESC\')
    ->limite(12)
    ->saltar($paginador->offset())
    ->obtener();</code></pre>
        ',
    ];
