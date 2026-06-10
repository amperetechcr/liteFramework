<?php

return [
        'id' => 'personalizacion',
        'titulo' => 'Personalizacion UI',
        'icono' => '🎨',
        'etiquetas' => 'personalizacion ui tema paleta estilo fuente espaciado tamano color',
        'descripcion' => 'Sistema de personalizacion visual: 13 paletas de color, 8 estilos, 8 fondos, 7 fuentes, 5 niveles de espaciado, tamano y mas.',
        'contenido' => '
            <p>El framework incluye un sistema completo de personalizacion que permite cambiar la apariencia visual mediante variables CSS. La configuracion se carga desde <code>servidor/config/ui.php</code> con valores por defecto y se puede modificar via GET, BD o desde el panel de Configuracion.</p>

            <h3 class="margen-inferior-pequeno">Paletas de color (13)</h3>
            <p>Afectan <code>--color-marca</code>, <code>--color-marca-hover</code>, <code>--color-marca-claro</code>. Se aplican con la clase <code>paleta-*</code> en <code>&lt;html&gt;</code>.</p>
            <pre><code>&lt;html class="paleta-indigo"&gt;   &lt;!-- default --&gt;
&lt;html class="paleta-azul"&gt;
&lt;html class="paleta-esmeralda"&gt;
&lt;html class="paleta-rosa"&gt;
&lt;html class="paleta-ambar"&gt;
&lt;html class="paleta-violeta"&gt;
&lt;html class="paleta-pizarra"&gt;
&lt;html class="paleta-cereza"&gt;
&lt;html class="paleta-cielo"&gt;
&lt;html class="paleta-teal"&gt;
&lt;html class="paleta-lima"&gt;
&lt;html class="paleta-naranja"&gt;
&lt;html class="paleta-fucsia"&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Estilos visuales (8)</h3>
            <pre><code>&lt;html class="estilo-moderno"&gt;     &lt;!-- sombras suaves, bordes redondeados --&gt;
&lt;html class="estilo-minimalista"&gt;  &lt;!-- sin sombras, bordes planos --&gt;
&lt;html class="estilo-elegante"&gt;     &lt;!-- tipografia serif, mayusculas --&gt;
&lt;html class="estilo-redondeado"&gt;   &lt;!-- bordes muy redondeados --&gt;
&lt;html class="estilo-contraste"&gt;    &lt;!-- accesibilidad, alto contraste --&gt;
&lt;html class="estilo-jugueton"&gt;    &lt;!-- animaciones elasticas --&gt;
&lt;html class="estilo-corporativo"&gt; &lt;!-- sobrio, profesional --&gt;
&lt;html class="estilo-3d-moderno"&gt;  &lt;!-- profundidad, sombras 3D --&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Colores de fondo (10)</h3>
            <p>Definen solo los colores de fondo (pantalla, elemento, alterno, flotante) con texturas y gradientes sutiles. No afectan texto ni trazos.</p>
            <pre><code>&lt;html class="fondo-blanco"&gt;     &lt;!-- #fafafa, limpio, default --&gt;
&lt;html class="fondo-lavanda"&gt;    &lt;!-- #f5f3ff, purpura suave --&gt;
&lt;html class="fondo-rosa"&gt;       &lt;!-- #fff1f2, rosa calido --&gt;
&lt;html class="fondo-melon"&gt;      &lt;!-- #fff7ed, coral durazno --&gt;
&lt;html class="fondo-cielo"&gt;      &lt;!-- #f0f9ff, azul celeste --&gt;
&lt;html class="fondo-menta"&gt;      &lt;!-- #f0fdf4, verde menta --&gt;
&lt;html class="fondo-arena"&gt;      &lt;!-- #faf8f5, beige arena --&gt;
&lt;html class="fondo-lila"&gt;       &lt;!-- #faf5ff, violeta claro --&gt;
&lt;html class="fondo-selva"&gt;      &lt;!-- verde bosque profundo --&gt;
&lt;html class="fondo-medianoche"&gt; &lt;!-- azul noche profundo --&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Fuentes (7)</h3>
            <pre><code>&lt;html class="fuente-sistema"&gt;     &lt;!-- system-ui, Segoe UI --&gt;
&lt;html class="fuente-serif"&gt;       &lt;!-- Georgia, Times New Roman --&gt;
&lt;html class="fuente-sans"&gt;        &lt;!-- Inter, Segoe UI --&gt;
&lt;html class="fuente-mono"&gt;        &lt;!-- JetBrains Mono, monospace --&gt;
&lt;html class="fuente-escritura"&gt;   &lt;!-- cursive, handwriting --&gt;
&lt;html class="fuente-humanista"&gt;  &lt;!-- Fira Sans, Nunito --&gt;
&lt;html class="fuente-decorativa"&gt; &lt;!-- Playfair Display, serif --&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Espaciado (5)</h3>
            <pre><code>&lt;html class="espaciado-muy-estrecho"&gt;
&lt;html class="espaciado-estrecho"&gt;
&lt;html class="espaciado-normal"&gt;    &lt;!-- default --&gt;
&lt;html class="espaciado-amplio"&gt;
&lt;html class="espaciado-muy-amplio"&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Tamano de letra (5)</h3>
            <pre><code>&lt;html class="tamano-muy-pequeno"&gt;
&lt;html class="tamano-pequeno"&gt;
&lt;html class="tamano-normal"&gt;       &lt;!-- default --&gt;
&lt;html class="tamano-grande"&gt;
&lt;html class="tamano-muy-grande"&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Forzar tema claro/oscuro</h3>
            <pre><code>&lt;html class="forzar-iluminacion-clara"&gt;
&lt;html class="forzar-iluminacion-oscura"&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Aplicar via GET</h3>
            <p>Todos los parametros se pueden pasar por URL para previsualizar cambios:</p>
            <pre><code>/panelControl?paleta=azul&estilo=minimalista&fuente=sans&espaciado=amplio&tamano=grande&fondo=crema</code></pre>
        ',
    ];
