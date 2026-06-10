(function () {
    'use strict';

    var URL_BASE = window.location.origin + (window.__basePath || '');
    var contenedorCampos = document.getElementById('contenedor-campos');
    var btnAgregar = document.getElementById('btn-agregar-campo');
    var formulario = document.getElementById('form-generador');
    var contadorCampos = document.getElementById('contador-campos');
    var previewDiv = document.getElementById('preview-generador');
    var claseInput = document.getElementById('clase_nombre');
    var tablaInput = document.getElementById('tabla');
    var tablaInferida = document.getElementById('tabla-inferida');

    var TIPOS = ['string', 'text', 'int', 'decimal', 'bool', 'email', 'date', 'datetime'];

    function pascalToSnake(str)
    {
        return str.replace(/([a-z])([A-Z])/g, '$1_$2').toLowerCase();
    }

    function inferirTabla(clase)
    {
        if (!clase) {
            return '';
        }
        var plural = pascalToSnake(clase);
        return plural;
    }

    function actualizarPreview()
    {
        var clase = claseInput.value.trim();
        var tabla = tablaInput.value.trim() || inferirTabla(clase);
        var campos = obtenerCampos();

        if (tablaInferida) {
            tablaInferida.textContent = tabla || '—';
        }

        if (!clase || campos.length === 0) {
            previewDiv.innerHTML = '<div class="preview-vacio' + (clase ? '' : '') + '">' +
                (!clase ? 'Ingresa el nombre de la clase' : 'Agrega al menos un campo') +
                '</div>';
            return;
        }

        var html = '';
        html += '<div class="preview-campo"><span class="preview-tipo">class</span> <span class="preview-clase">' + escapeHtml(clase) + '</span> extends Modelo {</div>';
        html += '<div class="preview-campo" style="padding-left:1rem"><span class="preview-nombre">// Tabla: ' + escapeHtml(tabla || inferirTabla(clase)) + '</span></div>';

        campos.forEach(function (c) {
            var tipoMostrar = mapearTipoPHPDoc(c.tipo);
            var reglasStr = '';
            if (c.requerido) {
                reglasStr += ' required';
            }
            if (c.unico) {
                reglasStr += ' unique';
            }
            html += '<div class="preview-campo" style="padding-left:1rem">' +
                '<span class="preview-nombre">' + escapeHtml(pascalToSnake(c.nombre)) + '</span>' +
                '<span class="preview-tipo">' + tipoMostrar + '</span>' +
                (reglasStr ? '<span class="preview-regla">' + reglasStr + '</span>' : '') +
                '</div>';
        });

        html += '<div class="preview-campo"><span class="preview-tipo">}</span></div>';
        previewDiv.innerHTML = html;
    }

    function mapearTipoPHPDoc(tipo)
    {
        var mapa = { string: 'VARCHAR(255)', text: 'TEXT', int: 'INTEGER', decimal: 'DECIMAL(12,2)', bool: 'BOOL', email: 'VARCHAR(255)', date: 'DATE', datetime: 'DATETIME' };
        return mapa[tipo] || 'VARCHAR(255)';
    }

    function escapeHtml(str)
    {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function actualizarContador()
    {
        var count = contenedorCampos.children.length;
        if (contadorCampos) {
            contadorCampos.textContent = count + ' campo' + (count !== 1 ? 's' : '');
        }
    }

    function obtenerCampos()
    {
        var campos = [];
        var filas = contenedorCampos.querySelectorAll('.fila-campo-repetible');
        filas.forEach(function (fila) {
            var nombre = fila.querySelector('.campo-nombre').value.trim();
            var tipo = fila.querySelector('.campo-tipo').value;
            var requerido = fila.querySelector('.regla-requerido').checked;
            var unico = fila.querySelector('.regla-unico').checked;
            if (nombre) {
                campos.push({ nombre: nombre, tipo: tipo, requerido: requerido, unico: unico });
            }
        });
        return campos;
    }

    function crearFilaCampo(datos)
    {
        datos = datos || {};
        var nombre = datos.nombre || '';
        var tipo = datos.tipo || 'string';
        var requerido = datos.requerido || false;
        var unico = datos.unico || false;

        var div = document.createElement('div');
        div.className = 'fila-campo-repetible';

        var html = '';
        html += '<input type="text" class="campo-entrada campo-nombre" placeholder="nombre_campo" value="' + escapeHtml(nombre) + '" required>';

        html += '<select class="campo-entrada campo-tipo">';
        TIPOS.forEach(function (t) {
            html += '<option value="' + t + '"' + (t === tipo ? ' selected' : '') + '>' + t + '</option>';
        });
        html += '</select>';

        html += '<span class="campo-reglas">';
        html += '<label><input type="checkbox" class="regla-requerido"' + (requerido ? ' checked' : '') + '> Requerido</label>';
        html += '<label><input type="checkbox" class="regla-unico"' + (unico ? ' checked' : '') + '> Único</label>';
        html += '</span>';

        html += '<button type="button" class="accion-boton variante-texto color-peligro eliminar-campo" title="Eliminar campo" data-tamano="pequeno">&times;</button>';

        div.innerHTML = html;

        var inputs = div.querySelectorAll('input, select');
        inputs.forEach(function (inp) {
            inp.addEventListener('change', actualizarPreview);
            inp.addEventListener('input', actualizarPreview);
        });

        return div;
    }

    function agregarCampo(datos)
    {
        var fila = crearFilaCampo(datos);
        contenedorCampos.appendChild(fila);
        actualizarContador();
        actualizarPreview();
        return fila;
    }

    // --- Inicializar con 2 campos de ejemplo ---
    agregarCampo({ nombre: 'nombre', tipo: 'string', requerido: true });
    agregarCampo({ nombre: 'descripcion', tipo: 'text' });

    // --- Evento: Agregar campo ---
    btnAgregar.addEventListener('click', function () {
        var fila = agregarCampo({});
        var nombreInput = fila.querySelector('.campo-nombre');
        if (nombreInput) {
            nombreInput.focus();
        }
    });

    // --- Evento: Eliminar campo ---
    contenedorCampos.addEventListener('click', function (e) {
        if (e.target.classList.contains('eliminar-campo')) {
            var fila = e.target.closest('.fila-campo-repetible');
            if (fila && contenedorCampos.children.length > 1) {
                fila.classList.add('eliminando');
                setTimeout(function () {
                    fila.remove();
                    actualizarContador();
                    actualizarPreview();
                }, 200);
            }
        }
    });

    // --- Evento: Inferir tabla en vivo ---
    claseInput.addEventListener('input', function () {
        var val = this.value.trim();
        if (tablaInput.value.trim() === '' || tablaInput.dataset.auto === 'true' || !tablaInput.dataset.auto) {
            tablaInput.value = '';
            tablaInput.dataset.auto = 'true';
        }
        actualizarPreview();
    });

    tablaInput.addEventListener('input', function () {
        if (this.value.trim()) {
            this.dataset.auto = 'false';
        } else {
            this.dataset.auto = 'true';
        }
        actualizarPreview();
    });

    // --- Evento: Submit ---
    formulario.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = document.getElementById('btn-generar');
        btn.disabled = true;
        btn.innerHTML = '<span class="indicador-carga"></span> Generando...';

        var claseNombre = claseInput.value.trim();
        var campos = obtenerCampos();

        var camposRaw = campos.map(function (c) {
            var reglas = [];
            if (c.requerido) {
                reglas.push('required');
            }
            if (c.unico) {
                reglas.push('unique');
            }
            return c.nombre + ':' + c.tipo + (reglas.length ? ':' + reglas.join('|') : '');
        });

        var payload = {
            token_peticion: window.obtenerTokenCSRF ? window.obtenerTokenCSRF() : '',
            accion_crud: 'generar_modulo',
            clase_nombre: claseNombre,
            campos: camposRaw,
            tabla: tablaInput.value.trim() || null
        };

        fetch(URL_BASE + '/api', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function (r) {
            return r.json(); })
        .then(function (res) {
            btn.disabled = false;
            btn.textContent = 'Generar Módulo';

            var contenedor = document.getElementById('resultado-generador');
            if (!res.estado_operacion) {
                var mensaje = res.mensaje_error || 'Error desconocido';
                var errores = res.errores || [];
                var html = '<div class="alerta alinear-alerta color-peligro flex brecha-normal">' +
                    '<span class="texto-xl">✕</span>' +
                    '<div><p class="texto-negrita">' + escapeHtml(mensaje) + '</p>';
                if (errores.length > 0) {
                    html += '<ul class="margen-superior-pequeno margen-0">';
                    errores.forEach(function (e) {
                        html += '<li class="texto-sm">' + escapeHtml(e) + '</li>'; });
                    html += '</ul>';
                }
                html += '</div></div>';
                contenedor.innerHTML = html;
                return;
            }

            var archivos = res.archivos || [];
            var pasos = res.pasos_siguientes || [];

            var html = '<div class="alerta alinear-alerta color-exito flex brecha-normal margen-inferior-normal">' +
                '<span class="texto-xl">✓</span>' +
                '<div><p class="texto-negrita">Módulo generado exitosamente</p></div></div>';

            if (archivos.length > 0) {
                html += '<div class="margen-inferior-normal"><p class="texto-negrita texto-sm margen-inferior-pequeno">Archivos generados</p>';
                archivos.forEach(function (a) {
                    var icono = '';
                    switch (a.tipo) {
                        case 'Migracion': icono = '🗄'; break;
                        case 'Modelo': icono = '📄'; break;
                        case 'Controlador API': icono = '🌐'; break;
                        case 'Vista modulo': icono = '🖥'; break;
                        case 'JS modulo': icono = '⚡'; break;
                        case 'Rutas': icono = '🔗'; break;
                        case 'Autoload': icono = '🔧'; break;
                        default: icono = '📄';
                    }
                    html += '<div class="resultado-archivo">' +
                        '<span class="icono-archivo">' + icono + '</span>' +
                        '<code class="ruta-archivo">' + escapeHtml(a.ruta) + '</code>' +
                        '<span class="tipo-archivo">' + escapeHtml(a.tipo) + '</span>' +
                        '</div>';
                });
                html += '</div>';
            }

            if (pasos.length > 0) {
                html += '<div><p class="texto-negrita texto-sm margen-inferior-pequeno">Pasos siguientes</p>';
                pasos.forEach(function (p) {
                    html += '<div class="resultado-paso">' + escapeHtml(p) + '</div>';
                });
                html += '</div>';
            }

            contenedor.innerHTML = html;
        })
        .catch(function () {
            btn.disabled = false;
            btn.textContent = 'Generar Módulo';
            document.getElementById('resultado-generador').innerHTML =
                '<div class="alerta alinear-alerta color-peligro flex brecha-normal"><span class="texto-xl">✕</span><div><p class="texto-negrita">Error de conexión con el servidor</p></div></div>';
        });
    });

    // --- Keyboard shortcut: Ctrl+Enter ---
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            formulario.dispatchEvent(new Event('submit'));
            e.preventDefault();
        }
    });
})();
