<?php

return [
        'id' => 'cssTemas',
        'titulo' => 'CSS y temas',
        'icono' => '💅',
        'etiquetas' => 'css tema arquitectura variables responsive breakpoints',
        'descripcion' => 'Arquitectura del sistema CSS: 8 archivos, +4000 lineas, variables custom properties, 8 breakpoints responsive y orden de carga.',
        'contenido' => '
            <h3 class="margen-inferior-pequeno">Arquitectura CSS</h3>
            <table>
                <thead><tr><th>Archivo</th><th>Lineas</th><th>Contenido</th></tr></thead>
                <tbody>
                    <tr><td><code>tema.css</code></td><td>316</td><td>Variables CSS (:root), reset universal, tipografia semantica</td></tr>
                    <tr><td><code>maquetacion.css</code></td><td>515</td><td>Sistema de rejillas, contenedores, sidebar, layout responsive multi-breakpoint</td></tr>
                    <tr><td><code>componentes.css</code></td><td>839</td><td>Botones, formularios, tablas, tarjetas, badges, paginacion, modales</td></tr>
                    <tr><td><code>modales.css</code></td><td>176</td><td>Notificaciones flotantes y modales</td></tr>
                    <tr><td><code>subirArchivos.css</code></td><td>700</td><td>Explorador de archivos, uploads, breadcrumb, barras de progreso</td></tr>
                    <tr><td><code>generadorPdf.css</code></td><td>260</td><td>Panel de personalizacion pre-impresion, @media print</td></tr>
                    <tr><td><code>estilos.css</code></td><td>960</td><td>9 paletas de color, 7 estilos visuales, estilo 3D, componentes especificos</td></tr>
                    <tr><td><code>utilidades.css</code></td><td>464</td><td>Clases atomicas: margen, padding, flex, texto, colores, visibilidad responsive</td></tr>
                    <tr><td><code>personalizacion.css</code></td><td>748</td><td>10 presets visuales, 6 fuentes, 3 densidades, forzado claro/oscuro</td></tr>
                    <tr><td><code>documentacion.css</code></td><td>70</td><td>Tarjetas y modales del modulo de documentacion</td></tr>
                </tbody>
            </table>

            <h3 class="margen-inferior-pequeno">Orden de carga</h3>
            <pre><code>&lt;link rel="stylesheet" href="tema.css"&gt;
&lt;link rel="stylesheet" href="maquetacion.css"&gt;
&lt;link rel="stylesheet" href="componentes.css"&gt;
&lt;link rel="stylesheet" href="modales.css"&gt;
&lt;link rel="stylesheet" href="subirArchivos.css"&gt;
&lt;link rel="stylesheet" href="generadorPdf.css"&gt;
&lt;link rel="stylesheet" href="estilos.css"&gt;
&lt;link rel="stylesheet" href="utilidades.css"&gt;
&lt;link rel="stylesheet" href="personalizacion.css"&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Variables CSS principales</h3>
            <pre><code>:root {
    --fondo-pantalla: #f8fafc;
    --fondo-elemento: #ffffff;
    --fondo-alterno: #f1f5f9;
    --texto-fuerte: #0f172a;
    --texto-base: #334155;
    --texto-suave: #64748b;
    --color-marca: #4f46e5;
    --color-marca-hover: #4338ca;
    --color-marca-claro: #eef2ff;
    --color-exito: #059669;
    --color-peligro: #dc2626;
    --color-advertencia: #d97706;
    --trazo-suave: #e2e8f0;
    --trazo-fuerte: #cbd5e1;
    --radio-redondeado: 0.5rem;
    --sombra-suave: 0 1px 2px ...;
    --sombra-flotante: 0 4px 12px ...;
    --sombra-elevada: 0 10px 24px ...;
    --espacio-pequeno: 0.5rem;
    --espacio-normal: 1rem;
    --espacio-grande: 2rem;
}</code></pre>

            <h3 class="margen-inferior-pequeno">Breakpoints responsive (8)</h3>
            <table>
                <thead><tr><th>Nombre</th><th>Rango</th><th>Clases de visibilidad</th></tr></thead>
                <tbody>
                    <tr><td>xs</td><td>&le; 480px</td><td><code>.oculto-xs</code> <code>.visible-xs</code></td></tr>
                    <tr><td>sm</td><td>481-600px</td><td><code>.oculto-sm</code></td></tr>
                    <tr><td>md</td><td>601-768px</td><td><code>.oculto-md</code></td></tr>
                    <tr><td>lg</td><td>769-900px</td><td><code>.oculto-lg</code></td></tr>
                    <tr><td>xl</td><td>901-1024px</td><td><code>.oculto-xl</code></td></tr>
                    <tr><td>xxl</td><td>1025-1280px</td><td><code>.oculto-xxl</code></td></tr>
                    <tr><td>huge</td><td>1281-1536px</td><td><code>.oculto-huge</code></td></tr>
                    <tr><td>epic</td><td>&ge; 1537px</td><td><code>.oculto-epic</code></td></tr>
                </tbody>
            </table>

            <h3 class="margen-inferior-pequeno">Ejemplo: construir un layout</h3>
            <pre><code>&lt;div class="contenedor-principal"&gt;
    &lt;nav class="barra-lateral"&gt;...&lt;/nav&gt;
    &lt;div class="area-contenido"&gt;
        &lt;main&gt;
            &lt;h1&gt;Titulo&lt;/h1&gt;
            &lt;div class="rejilla-automatica"&gt;
                &lt;article&gt;Tarjeta 1&lt;/article&gt;
                &lt;article&gt;Tarjeta 2&lt;/article&gt;
            &lt;/div&gt;
        &lt;/main&gt;
    &lt;/div&gt;
&lt;/div&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Tema oscuro automatico</h3>
            <p>El tema oscuro se activa automaticamente via <code>prefers-color-scheme: dark</code>. Las variables se redefinen en el media query sin necesidad de clases adicionales.</p>
            <pre><code>@media (prefers-color-scheme: dark) {
    :root {
        --fondo-pantalla: #0b1121;
        --fondo-elemento: #121b31;
        --texto-fuerte: #f1f5f9;
        --texto-base: #cbd5e1;
        ...
    }
}</code></pre>
        ',
    ];
