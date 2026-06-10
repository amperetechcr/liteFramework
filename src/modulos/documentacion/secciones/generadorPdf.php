<?php

return [
        'id' => 'generadorPdf',
        'titulo' => 'GeneradorPdf',
        'icono' => 'PDF',
        'etiquetas' => 'pdf imprimir reporte documento descargar navegador',
        'descripcion' => 'Clase de servicio que genera HTML listo para imprimir como PDF usando window.print() nativo del navegador con el CSS del framework.',
        'contenido' => '
            <p><strong>GeneradorPdf</strong> es una clase PHP que genera documentos HTML completos con todo el CSS del framework aplicado. El usuario final ve una previsualizacion fiel del documento con un panel de personalizacion (tamano de pagina, orientacion, margenes) y hace clic en <strong>Descargar</strong> para que el navegador guade el PDF via su dialogo nativo de impresion.</p>

            <p><strong>No requiere dependencias externas:</strong> sin Node, sin npm, sin librerias de terceros. Usa exclusivamente <code>window.print()</code> del navegador y el CSS existente del framework.</p>

            <h3 class="margen-inferior-pequeno">Caso de uso tipico</h3>
            <p>El desarrollador crea un controlador que consulta datos reales de la base de datos, los formatea con el GeneradorPdf y renderiza la pagina. El usuario final ve el reporte, ajusta parametros si lo desea, y descarga el PDF.</p>

            <pre><code>$pdf = new GeneradorPdf(\'vertical\', \'A4\');

$pdf->establecerTitulo(\'Reporte Mensual de Ventas\');
$pdf->establecerEncabezado(\'Mi Empresa S.A. | \' . date(\'d/m/Y\'));
$pdf->establecerPie(\'Generado con liteFramework\');
$pdf->establecerMargen(\'normal\');

$pdf->agregarParrafo(\'Resultados del periodo analizado.\');
$pdf->agregarTabla($filas, [\'Producto\', \'Ventas\', \'Total\']);
$pdf->agregarTarjeta(\'Resumen\', \'Total facturado: $12,450.00\');
$pdf->agregarSaltoPagina();
$pdf->agregarLista([\'Observacion 1\', \'Observacion 2\']);

$pdf->desdePlantilla(1, [\'nombre\' => \'Juan\', \'fecha\' => date(\'d/m/Y\')]);

$pdf->renderizar();

$pdf->guardar(\'storage/reportes/ventas_junio.html\');</code></pre>

            <h3 class="margen-inferior-pequeno">Metodos de configuracion</h3>
            <pre><code>$pdf->establecerTitulo(\'Titulo del documento\');
$pdf->establecerOrientacion(\'horizontal\');  // vertical | horizontal
$pdf->establecerTamanoPagina(\'Carta\');      // A4 | Carta | Oficio | A3 | A5
$pdf->establecerMargen(\'estrecho\');         // estrecho | normal | amplio
$pdf->establecerEncabezado(\'&lt;strong&gt;Logo&lt;/strong&gt;\');
$pdf->establecerPie(\'Pagina generada automaticamente\');
$pdf->sinEstilos();  // omitir CSS del framework</code></pre>

            <h3 class="margen-inferior-pequeno">Metodos de contenido</h3>
            <pre><code>$pdf->agregarTitulo(\'Seccion\', 2);   // nivel h1-h6
$pdf->agregarParrafo(\'Texto del parrafo\');
$pdf->agregarHtml(\'&lt;div class="rejilla-automatica"&gt;...&lt;/div&gt;\');
$pdf->agregarTabla($filas, [\'Col1\', \'Col2\'], [30, 70]);
$pdf->agregarTarjeta(\'Titulo tarjeta\', \'Contenido\');
$pdf->agregarSeccion(\'Titulo seccion\', \'Contenido\');
$pdf->agregarLista([\'Item 1\', \'Item 2\'], true);  // true = ordenada
$pdf->agregarImagen(\'/ruta/imagen.png\', \'Descripcion\');
$pdf->agregarSaltoPagina();
$pdf->agregarLineaSeparadora();</code></pre>

            <h3 class="margen-inferior-pequeno">Metodos de salida y utilidad</h3>
            <pre><code>$pdf->renderizar();         // echo + exit, muestra la pagina completa
$html = $pdf->generarHtml(); // devuelve string sin imprimir
$pdf->guardar(\'ruta/archivo.html\');  // guarda en disco
$pdf->obtenerContenido();   // solo el HTML del cuerpo (sin head/body)

$pdf->desdePlantilla(1, [\'clave\' => \'valor\']);  // carga plantilla BD
$plantillas = GeneradorPdf::listarPlantillas();   // listar todas
$id = GeneradorPdf::guardarPlantilla(\'Titulo\', \'&lt;h1&gt;Hola&lt;/h1&gt;\');</code></pre>

            <h3 class="margen-inferior-pequeno">Flujo completo</h3>
            <ol>
                <li>El desarrollador instancia <code>new GeneradorPdf()</code> en su controlador</li>
                <li>Consulta datos de la BD (ventas, usuarios, reportes)</li>
                <li>Agrega contenido con los metodos del GeneradorPdf</li>
                <li>Llama a <code>$pdf->renderizar()</code> que genera la pagina HTML completa</li>
                <li>El usuario ve la previsualizacion con el CSS del framework aplicado</li>
                <li>El usuario puede cambiar tamano, orientacion y margenes en el panel superior</li>
                <li>Al hacer clic en <strong>Descargar</strong>, el navegador abre el dialogo "Guardar como PDF"</li>
                <li>El PDF se genera nativamente con todo el CSS respetado</li>
            </ol>

            <div class="texto-centro margen-superior-normal">
                <a href="' . URL_BASE . '/ejemploPdf" target="_blank" class="accion-boton variante-solida">Ver reporte de ejemplo &rarr;</a>
            </div>
        ',
    ];
