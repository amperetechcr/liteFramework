<?php

return [
        'id' => 'componentes',
        'titulo' => 'Componentes UI',
        'icono' => '◧',
        'etiquetas' => 'css html formularios nav tarjetas tablas botones layout grid flex',
        'descripcion' => 'Sistema de clases CSS para construir interfaces: rejillas, formularios, tarjetas, tablas, botones y navegacion.',
        'contenido' => '
            <p>El framework incluye <strong>+4000 lineas de CSS</strong> organizadas en 7 archivos. Todas las clases usan variables CSS (<code>--color-marca</code>, <code>--fondo-pantalla</code>, <code>--radio-redondeado</code>) que responden automaticamente al tema (claro/oscuro) y a la configuracion de apariencia del sistema.</p>

            <h3 class="margen-inferior-pequeno">Formularios</h3>
            <p>Usa <code>.grupo-campo</code> para agrupar label + input con espaciado automatico. El framework estiliza todos los tipos de input, select y textarea con foco visual.</p>
            <pre><code>&lt;form class="agrupador-flexible-columnas"&gt;
    &lt;input type="hidden" name="token_peticion" value="&lt;?= $tokenCSRF ?&gt;"&gt;

    &lt;div class="grupo-campo campo-agrupado"&gt;
        &lt;label for="nombre"&gt;Nombre completo&lt;/label&gt;
        &lt;input type="text" id="nombre" name="nombre" placeholder="Ej: Juan Perez" required&gt;
        &lt;span class="error-campo"&gt;&lt;/span&gt;
    &lt;/div&gt;

    &lt;div class="grupo-campo campo-agrupado"&gt;
        &lt;label for="correo"&gt;Correo electronico&lt;/label&gt;
        &lt;input type="email" id="correo" name="correo" placeholder="correo@dominio.com" required&gt;
    &lt;/div&gt;

    &lt;div class="grupo-campo campo-agrupado"&gt;
        &lt;label for="rol"&gt;Rol asignado&lt;/label&gt;
        &lt;select id="rol" name="id_rol"&gt;
            &lt;option value="1"&gt;Administrador&lt;/option&gt;
            &lt;option value="2"&gt;Operador&lt;/option&gt;
        &lt;/select&gt;
    &lt;/div&gt;

    &lt;div class="grupo-campo campo-agrupado"&gt;
        &lt;label for="notas"&gt;Notas&lt;/label&gt;
        &lt;textarea id="notas" name="notas" rows="4"&gt;&lt;/textarea&gt;
    &lt;/div&gt;

    &lt;div class="agrupador-flexible-filas brecha-normal"&gt;
        &lt;button type="submit"&gt;Guardar&lt;/button&gt;
        &lt;button type="button" data-variante="borde"&gt;Cancelar&lt;/button&gt;
    &lt;/div&gt;
&lt;/form&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Botones</h3>
            <p>Los botones tienen 4 variantes via <code>data-variante</code> y 2 tamanos via <code>data-tamano</code>. Tambien funcionan sobre <code>&lt;a&gt;</code> con <code>role="button"</code>.</p>
            <pre><code>&lt;button type="submit"&gt;Guardar&lt;/button&gt;
&lt;button type="button" data-variante="borde"&gt;Cancelar&lt;/button&gt;
&lt;button type="button" data-variante="texto"&gt;Editar&lt;/button&gt;
&lt;button type="button" data-variante="peligro"&gt;Eliminar&lt;/button&gt;
&lt;button type="button" data-variante="exito"&gt;Aprobar&lt;/button&gt;
&lt;button type="button" data-tamano="pequeno"&gt;Compacto&lt;/button&gt;
&lt;button type="button" data-tamano="grande"&gt;Grande&lt;/button&gt;
&lt;a href="/panel" role="button"&gt;Ir al panel&lt;/a&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Tarjetas (cards)</h3>
            <p>Las tarjetas usan el elemento <code>&lt;article&gt;</code> o la clase <code>.tarjeta</code>. Tienen sombra suave, borde redondeado y animacion de entrada. Al hacer hover se eleva la sombra.</p>
            <pre><code>&lt;div class="rejilla-automatica"&gt;
    &lt;article&gt;
        &lt;h3&gt;Titulo de la tarjeta&lt;/h3&gt;
        &lt;p&gt;Contenido descriptivo dentro de la tarjeta.&lt;/p&gt;
        &lt;span class="etiqueta etiqueta-marca"&gt;Activo&lt;/span&gt;
    &lt;/article&gt;

    &lt;article class="alineacion-centrada"&gt;
        &lt;p class="texto-2xl texto-negrita color-marca"&gt;847&lt;/p&gt;
        &lt;p class="texto-pequeno texto-negrita"&gt;Total de registros&lt;/p&gt;
    &lt;/article&gt;
&lt;/div&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Tablas</h3>
            <p>Las tablas van dentro de <code>.tabla-responsiva</code> para scroll horizontal en moviles. Usan los elementos nativos <code>&lt;table&gt;</code>, <code>&lt;thead&gt;</code>, <code>&lt;tbody&gt;</code>.</p>
            <pre><code>&lt;div class="tabla-responsiva"&gt;
    &lt;table&gt;
        &lt;thead&gt;
            &lt;tr&gt;
                &lt;th&gt;Nombre&lt;/th&gt;
                &lt;th&gt;Correo&lt;/th&gt;
                &lt;th&gt;Rol&lt;/th&gt;
                &lt;th&gt;Estado&lt;/th&gt;
            &lt;/tr&gt;
        &lt;/thead&gt;
        &lt;tbody&gt;
            &lt;tr&gt;
                &lt;td&gt;Maria Lopez&lt;/td&gt;
                &lt;td&gt;maria@correo.com&lt;/td&gt;
                &lt;td&gt;Administrador&lt;/td&gt;
                &lt;td&gt;&lt;span class="etiqueta etiqueta-exito"&gt;Activo&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
        &lt;/tbody&gt;
    &lt;/table&gt;
&lt;/div&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Layout y rejillas</h3>
            <p>El sistema de layout incluye grid responsivo y flex. La clase <code>.rejilla-automatica</code> crea columnas que se adaptan al espacio disponible.</p>
            <pre><code>&lt;div class="rejilla-automatica"&gt;
    &lt;article&gt;...&lt;/article&gt;
    &lt;article&gt;...&lt;/article&gt;
    &lt;article&gt;...&lt;/article&gt;
&lt;/div&gt;

&lt;div class="agrupador-flexible-filas distribucion-espaciada"&gt;
    &lt;div&gt;Izquierda&lt;/div&gt;
    &lt;div&gt;Derecha&lt;/div&gt;
&lt;/div&gt;

&lt;div class="agrupador-flexible-columnas brecha-normal"&gt;
    &lt;div&gt;Elemento 1&lt;/div&gt;
    &lt;div&gt;Elemento 2&lt;/div&gt;
&lt;/div&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Etiquetas y badges</h3>
            <pre><code>&lt;span class="etiqueta etiqueta-marca"&gt;Nuevo&lt;/span&gt;
&lt;span class="etiqueta etiqueta-exito"&gt;Completado&lt;/span&gt;
&lt;span class="etiqueta etiqueta-peligro"&gt;Error&lt;/span&gt;
&lt;span class="etiqueta etiqueta-advertencia"&gt;Pendiente&lt;/span&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Utilidades atomicas</h3>
            <p>Clases utilitarias para margenes, padding, colores de texto, alineacion, visibilidad responsive y mas. Ver <code>src/css/utilidades.css</code> para la lista completa.</p>
            <pre><code>&lt;div class="texto-centro margen-inferior-normal relleno-normal"&gt;...&lt;/div&gt;
&lt;span class="texto-pequeno texto-suave"&gt;...&lt;/span&gt;
&lt;p class="texto-negrita color-marca"&gt;...&lt;/p&gt;
&lt;div class="oculto-movil"&gt;Solo visible en escritorio&lt;/div&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Modales</h3>
            <p>Estructura para modales accesibles con overlay, cierre por Escape y click fuera.</p>
            <pre><code>&lt;div id="miModal" class="modal-superposicion" role="dialog" aria-modal="true" hidden&gt;
    &lt;div class="modal-contenido"&gt;
        &lt;div class="modal-cabecera"&gt;
            &lt;h2&gt;Titulo del modal&lt;/h2&gt;
            &lt;button type="button" class="modal-cerrar"&gt;&times;&lt;/button&gt;
        &lt;/div&gt;
        &lt;form class="agrupador-flexible-columnas"&gt;
            &lt;!-- campos --&gt;
            &lt;button type="submit"&gt;Guardar&lt;/button&gt;
        &lt;/form&gt;
    &lt;/div&gt;
&lt;/div&gt;</code></pre>
        ',
    ];
