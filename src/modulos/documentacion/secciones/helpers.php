<?php

return [
        'id' => 'helpers',
        'titulo' => 'Helpers y utilidades',
        'icono' => '🛠',
        'etiquetas' => 'helpers utilidades fecha cadena arreglo h escape html',
        'descripcion' => 'Funciones helper globales: escape HTML, formateo de fechas, manipulacion de cadenas, arreglos y utilidades generales.',
        'contenido' => '
            <h3 class="margen-inferior-pequeno">h() — Escape HTML (anti-XSS)</h3>
            <p>Disponible globalmente. Equivale a <code>htmlspecialchars($texto, ENT_QUOTES, \'UTF-8\')</code>. Usar siempre al mostrar datos de usuario.</p>
            <pre><code>&lt;?= h($operador->nombre_completo) ?&gt;
&lt;?= h($comentario) ?&gt;
&lt;?= h($_GET[\'buscar\']) ?&gt;</code></pre>

            <h3 class="margen-inferior-pequeno">Fecha (AyudanteFecha)</h3>
            <pre><code>use Fecha;

Fecha::formatear(\'2026-06-05 14:30:00\', \'d/m/Y H:i\');   // 05/06/2026 14:30
Fecha::formatear(\'2026-06-05\', \'d/m/Y\');                // 05/06/2026
Fecha::formatear(\'2026-06-05\', \'d \de F \de Y\');       // 05 de junio de 2026
Fecha::fechaActual(\'Y-m-d\');                              // 2026-06-05
Fecha::fechaHoraActual(\'Y-m-d H:i:s\');                    // 2026-06-05 14:30:00
Fecha::diferencia(\'2026-01-01\', \'2026-06-05\');          // 155 dias
Fecha::esAnterior(\'2025-12-31\', \'2026-01-01\');          // true
Fecha::obtenerMesNombre(6);                                 // junio
Fecha::obtenerDiaSemanaNombre(\'2026-06-05\');              // viernes</code></pre>

            <h3 class="margen-inferior-pequeno">Cadena (AyudanteCadena)</h3>
            <pre><code>use Cadena;

Cadena::limitar(\'Texto muy largo...\', 50);              // trunca con "..."
Cadena::slug(\'Mi Producto Nuevo\');                       // mi-producto-nuevo
Cadena::comenzarCon(\'Hola mundo\', \'Hola\');              // true
Cadena::terminarCon(\'archivo.pdf\', \'.pdf\');            // true
Cadena::contiene(\'Hola mundo\', \'mun\');                  // true
Cadena::reemplazar(\'{nombre}\', \'Juan\', \'Hola {nombre}\'); // Hola Juan
Cadena::aMinuscula(\'TEXTO\');                              // texto
Cadena::aMayuscula(\'texto\');                              // TEXTO
Cadena::aCamelCase(\'mi-variable\');                        // miVariable
Cadena::generarAleatorio(16);                               // string aleatorio
Cadena::encriptar(\'texto\', \'clave\');                    // encriptacion simple
Cadena::desencriptar($hash, \'clave\');                     // desencriptacion</code></pre>

            <h3 class="margen-inferior-pequeno">Arreglo (AyudanteArreglo)</h3>
            <pre><code>use Arreglo;

Arreglo::obtener($datos, \'nombre\', \'default\');          // acceso seguro con fallback
Arreglo::solo($datos, [\'nombre\', \'correo\']);            // filtra keys
Arreglo::excepto($datos, [\'clave\', \'token\']);          // excluye keys
Arreglo::ordenarPor($items, \'fecha\', \'desc\');         // ordena array de arrays
Arreglo::agruparPor($items, \'categoria_id\');             // agrupa por key
Arreglo::aplastar($anidado);                                // aplana array multidimensional
Arreglo::primero($datos);                                   // primer elemento
Arreglo::ultimo($datos);                                    // ultimo elemento
Arreglo::esAsociativo($datos);                              // true si tiene keys string</code></pre>

            <h3 class="margen-inferior-pequeno">General (AyudanteGeneral)</h3>
            <pre><code>use General;

General::redirigir(\'/panelControl\');
General::redirigirConMensaje(\'/panelControl\', \'exito\', \'Guardado correctamente\');
General::esPeticionAjax();                                  // detecta AJAX
General::esPeticionPost();                                  // detecta POST
General::obtenerIpCliente();                                // IP del cliente
General::obtenerAgenteUsuario();                            // User-Agent
General::generarUuid();                                     // UUID v4
General::formatearBytes(1048576);                           // 1 MB
General::formatearNumero(1250.5, 2);                        // 1,250.50
General::esJson(\'{"a":1}\');                              // true
General::sanitizarNombreArchivo(\'mi archivo (1).pdf\');    // mi-archivo-1.pdf</code></pre>
        ',
    ];
