<?php

return [
        'id' => 'estadisticas',
        'titulo' => 'GeneradorEstadisticas',
        'icono' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>',
        'etiquetas' => 'estadisticas grafico barras pastel kpi consulta sql reporte datos',
        'descripcion' => 'Clase de servicio que ejecuta consultas SQL y renderiza los resultados como tarjetas, barras, pastel o indicadores KPI usando CSS puro del framework.',
        'contenido' => '
            <p><strong>GeneradorEstadisticas</strong> es una clase PHP que ejecuta consultas SQL contra cualquier tabla de la base de datos y genera visualizaciones interactivas con CSS puro. Sin dependencias externas: sin Chart.js, sin librerias de terceros.</p>

            <p>Soporta 4 tipos de visualizacion: <strong>tarjetas</strong> (grid responsive), <strong>barras</strong> (horizontales con tooltip), <strong>pastel</strong> (conic-gradient nativo) e <strong>indicadores KPI</strong> (numeros grandes con etiquetas).</p>

            <h3 class="margen-inferior-pequeno">Caso de uso tipico</h3>
            <p>El desarrollador instancia el generador, le pasa una consulta SQL, elige el tipo de visualizacion, ejecuta y renderiza. El usuario final ve el resultado y puede imprimirlo.</p>

            <pre><code>$est = new GeneradorEstadisticas(\'SELECT producto, SUM(cantidad) AS total FROM ventas GROUP BY producto ORDER BY total DESC LIMIT 10\');

$est->establecerTitulo(\'Top 10 Productos mas Vendidos\');
$est->establecerDescripcion(\'Ventas acumuladas del primer trimestre 2026.\');
$est->comoBarras();
$est->conAlias([\'producto\' => \'Producto\', \'total\' => \'Unidades Vendidas\']);
$est->conColores([\'#4f46e5\', \'#059669\', \'#d97706\']);
$est->ejecutar();
$est->renderizar();

$est->guardar(\'storage/reportes/top_productos.html\');</code></pre>

            <h3 class="margen-inferior-pequeno">Metodos de configuracion</h3>
            <pre><code>$est->establecerConsulta(\'SELECT ... FROM ...\');
$est->establecerTitulo(\'Titulo del reporte\');
$est->establecerDescripcion(\'Descripcion opcional\');
$est->conAlias([\'columna\' => \'Etiqueta visible\']);
$est->conColores([\'#4f46e5\', \'#059669\', \'#d97706\']);</code></pre>

            <h3 class="margen-inferior-pequeno">Tipos de visualizacion (fluent)</h3>
            <pre><code>$est->comoTarjetas();  // Grid de cards, cada fila SQL = una tarjeta
$est->comoBarras();    // Barras horizontales proporcionales
$est->comoPastel();    // Grafico circular con conic-gradient
$est->comoKpi();       // Tarjetas con numeros grandes</code></pre>

            <h3 class="margen-inferior-pequeno">Metodos de salida y utilidad</h3>
            <pre><code>$est->ejecutar();              // Ejecuta la consulta SQL
$est->renderizar();            // echo + exit, pagina completa
$html = $est->generarHtml();   // Devuelve HTML completo
$html = $est->generarHtml(false); // Solo el cuerpo del contenido
$est->guardar(\'ruta/archivo.html\'); // Guarda a disco
$est->obtenerContenido();      // Solo HTML del resultado

$est->desdePlantilla(1);       // Carga una estadistica guardada por ID
$plantillas = GeneradorEstadisticas::listarPlantillas();  // Listar todas
$id = GeneradorEstadisticas::guardarPlantilla(\'Titulo\', \'SELECT ...\', \'barras\');</code></pre>

            <h3 class="margen-inferior-pequeno">Flujo completo</h3>
            <ol>
                <li>El desarrollador instancia <code>new GeneradorEstadisticas()</code> con una consulta SQL</li>
                <li>Configura titulo, descripcion, alias de columnas y colores</li>
                <li>Elige el tipo de visualizacion con <code>->comoBarras()</code> o similar</li>
                <li>Llama a <code>->ejecutar()</code> para correr la consulta</li>
                <li>Llama a <code>->renderizar()</code> que genera la pagina HTML completa</li>
                <li>El usuario ve los resultados con el CSS del framework aplicado</li>
                <li>Puede hacer clic en <strong>Imprimir</strong> para guardar como PDF</li>
            </ol>

            <h3 class="margen-inferior-pequeno">Integracion en tus modulos</h3>
            <p>El generador puede usarse desde cualquier controlador o modulo del framework. La pagina del modulo <strong>Estadisticas</strong> permite guardar y gestionar consultas frecuentes, mientras que <code>GeneradorEstadisticas::desdePlantilla($id)</code> las ejecuta desde codigo.</p>

            <div class="texto-centro margen-superior-normal">
                <a href="' . URL_BASE . '/ejemploEstadisticas" target="_blank" class="accion-boton variante-solida">Ver ejemplo de estadisticas &rarr;</a>
            </div>
        ',
    ];
