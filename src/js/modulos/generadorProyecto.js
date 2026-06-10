(function () {
    'use strict';

    var pasoActual = 1;
    var totalPasos = 6;

    var MODULOS_DISPONIBLES = [
    { clave: 'inicio', etiqueta: 'Inicio', descripcion: 'Dashboard principal' },
    { clave: 'panelControl', etiqueta: 'Panel', descripcion: 'Panel de control' },
    { clave: 'archivos', etiqueta: 'Archivos', descripcion: 'Gestor de archivos' },
    { clave: 'operadores', etiqueta: 'Operadores', descripcion: 'Usuarios del sistema' },
    { clave: 'auditoria', etiqueta: 'Auditoria', descripcion: 'Bitacora del sistema' },
    { clave: 'configuracion', etiqueta: 'Configuracion', descripcion: 'Configuracion del sistema' },
    { clave: 'apariencia', etiqueta: 'Apariencia', descripcion: 'Personalizacion UI' },
    { clave: 'documentacion', etiqueta: 'Documentacion', descripcion: 'Documentacion tecnica' },
    { clave: 'generadorModulo', etiqueta: 'Generador', descripcion: 'Generar modulos CRUD' },
    { clave: 'migraciones', etiqueta: 'Migraciones', descripcion: 'Gestor de BD' },
    { clave: 'generadorPdf', etiqueta: 'PDF', descripcion: 'Generar documentos PDF' },
    { clave: 'estadisticas', etiqueta: 'Estadisticas', descripcion: 'Graficos y metricas' },
    ];

    var MODULOS_PREDETERMINADOS = ['inicio', 'panelControl', 'operadores', 'auditoria', 'configuracion', 'apariencia', 'migraciones'];

    function navegarPaso(paso)
    {
        if (paso < 1 || paso > totalPasos) {
            return;
        }
        pasoActual = paso;
        var contenidos = document.querySelectorAll('.paso-contenido');
        for (var ci = 0; ci < contenidos.length; ci++) {
            contenidos[ci].classList.remove('activo');
        }
        var nuevo = document.querySelector('.paso-contenido[data-paso="' + paso + '"]');
        if (nuevo) {
            nuevo.classList.add('activo');
        }

        var indicadores = document.querySelectorAll('.paso-indicador');
        for (var ii = 0; ii < indicadores.length; ii++) {
            var el = indicadores[ii];
            el.classList.remove('activo', 'completado');
            var num = parseInt(el.getAttribute('data-paso'));
            if (num === paso) {
                el.classList.add('activo');
            } else if (num < paso) {
                el.classList.add('completado');
            }
        }
        if (paso === 6) {
            actualizarPreview();
        }
    }

    function enlazarPasos()
    {
        var sigs = document.querySelectorAll('.paso-siguiente');
        for (var si = 0; si < sigs.length; si++) {
            sigs[si].addEventListener('click', function () {
                var paso = parseInt(this.getAttribute('data-paso'));
                if (validarPaso(paso)) {
                    navegarPaso(paso + 1);
                }
            });
        }
        var ants = document.querySelectorAll('.paso-anterior');
        for (var ai = 0; ai < ants.length; ai++) {
            ants[ai].addEventListener('click', function () {
                var paso = parseInt(this.getAttribute('data-paso'));
                navegarPaso(paso - 1);
            });
        }
        var indics = document.querySelectorAll('.paso-indicador');
        for (var di = 0; di < indics.length; di++) {
            indics[di].addEventListener('click', function () {
                var paso = parseInt(this.getAttribute('data-paso'));
                navegarPaso(paso);
            });
        }
    }

    function notificar(mensaje, tipo)
    {
        if (window.NotificadorHubble && typeof window.NotificadorHubble.mostrar === 'function') {
            window.NotificadorHubble.mostrar(mensaje, tipo);
        }
    }

    function escapar(texto)
    {
        if (!texto) {
            return '';
        }
        return texto.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function validarPaso(paso)
    {
        var pasoEl = document.querySelector('.paso-contenido[data-paso="' + paso + '"]');
        if (!pasoEl) {
            return true;
        }
        var inputs = pasoEl.querySelectorAll('[required]');
        for (var i = 0; i < inputs.length; i++) {
            if (!inputs[i].value.trim()) {
                inputs[i].focus();
                notificar('Complete todos los campos requeridos antes de continuar.', 'peligro');
                return false;
            }
        }
        return true;
    }

    function inicializarModulos()
    {
        var container = document.getElementById('lista-modulos');
        if (!container) {
            return;
        }
        container.innerHTML = '';
        for (var mi = 0; mi < MODULOS_DISPONIBLES.length; mi++) {
            var mod = MODULOS_DISPONIBLES[mi];
            var checked = MODULOS_PREDETERMINADOS.indexOf(mod.clave) !== -1;
            var div = document.createElement('div');
            div.className = 'item-modulo' + (checked ? ' seleccionado' : '');
            div.innerHTML = '<input type="checkbox" value="' + mod.clave + '" ' + (checked ? 'checked' : '') + '>' +
            '<span class="item-modulo-icono"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></span>' +
            '<div><strong>' + mod.etiqueta + '</strong><br><span class="texto-pequeno texto-suave">' + mod.descripcion + '</span></div>';
            var checkbox = div.querySelector('input');
            checkbox.addEventListener('change', function () {
                this.parentNode.classList.toggle('seleccionado', this.checked);
            });
            div.addEventListener('click', function (e) {
                if (e.target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                    div.classList.toggle('seleccionado', checkbox.checked);
                }
            });
            container.appendChild(div);
        }
    }

    function agregarEntidad(clase, campos)
    {
        var id = Date.now() + '_' + Math.random().toString(36).substr(2, 5);
        var container = document.getElementById('lista-entidades');
        if (!container) {
            return null;
        }
        var div = document.createElement('div');
        div.className = 'tarjeta-entidad';
        div.innerHTML = '<div class="tarjeta-entidad-cabecera">' +
        '<div><strong>Entidad: </strong><input type="text" class="campo-entidad-clase campo-entrada" placeholder="Ej: Producto" value="' + (clase || '') + '"></div>' +
        '<button type="button" class="accion-boton variante-texto color-peligro eliminar-entidad" title="Eliminar entidad">&times;</button>' +
        '</div>' +
        '<div class="campos-entidad" data-entidad="' + id + '"></div>' +
        '<button type="button" class="accion-boton variante-borde agregar-campo-entidad" data-entidad="' + id + '">+ Campo</button>';
        container.appendChild(div);

        div.querySelector('.eliminar-entidad').addEventListener('click', function () {
            div.remove();
        });
        div.querySelector('.agregar-campo-entidad').addEventListener('click', function () {
            agregarCampo(id, '', '', false, false);
        });

        if (campos && campos.length > 0) {
            for (var ci = 0; ci < campos.length; ci++) {
                var c = campos[ci];
                var req = c.reglas && c.reglas.indexOf('required') !== -1;
                var uni = c.reglas && c.reglas.indexOf('unique') !== -1;
                agregarCampo(id, c.nombre, c.tipo, req, uni);
            }
        } else {
            agregarCampo(id, 'nombre', 'string', true, false);
            agregarCampo(id, 'descripcion', 'text', false, false);
        }
        return div;
    }

    function agregarCampo(entidadId, nombre, tipo, requerido, unico)
    {
        var container = document.querySelector('.campos-entidad[data-entidad="' + entidadId + '"]');
        if (!container) {
            return;
        }
        var div = document.createElement('div');
        div.className = 'campo-fila';
        div.innerHTML = '<input type="text" class="campo-nombre-input" placeholder="nombre_campo" value="' + (nombre || '') + '">' +
        '<select class="campo-tipo-select">' +
        '<option value="string">Texto</option>' +
        '<option value="text">Texto largo</option>' +
        '<option value="int">Entero</option>' +
        '<option value="decimal">Decimal</option>' +
        '<option value="bool">Booleano</option>' +
        '<option value="email">Correo</option>' +
        '<option value="date">Fecha</option>' +
        '<option value="datetime">Fecha+Hora</option>' +
        '</select>' +
        '<label><input type="checkbox" class="campo-requerido" ' + (requerido ? 'checked' : '') + '> Req</label>' +
        '<label><input type="checkbox" class="campo-unico" ' + (unico ? 'checked' : '') + '> Unico</label>' +
        '<button type="button" class="accion-boton variante-texto color-peligro eliminar-campo" title="Eliminar campo">&times;</button>';
        if (tipo) {
            div.querySelector('.campo-tipo-select').value = tipo;
        }
        div.querySelector('.eliminar-campo').addEventListener('click', function () {
            div.remove();
        });
        container.appendChild(div);
    }

    function obtenerDatosEntidades()
    {
        var entidades = [];
        var tarjetas = document.querySelectorAll('.tarjeta-entidad');
        for (var ti = 0; ti < tarjetas.length; ti++) {
            var entEl = tarjetas[ti];
            var clase = entEl.querySelector('.campo-entidad-clase').value.trim();
            if (!clase) {
                continue;
            }
            var campos = [];
            var filas = entEl.querySelectorAll('.campo-fila');
            for (var fi = 0; fi < filas.length; fi++) {
                var campoEl = filas[fi];
                var nom = campoEl.querySelector('.campo-nombre-input').value.trim();
                if (!nom) {
                    continue;
                }
                var tipo = campoEl.querySelector('.campo-tipo-select').value;
                var reglas = [];
                if (campoEl.querySelector('.campo-requerido').checked) {
                    reglas.push('required');
                }
                if (campoEl.querySelector('.campo-unico').checked) {
                    reglas.push('unique');
                }
                campos.push({ nombre: nom, tipo: tipo, reglas: reglas.join('|') });
            }
            if (campos.length > 0) {
                entidades.push({ clase: clase, tabla: null, campos: campos });
            }
        }
        return entidades;
    }

    function obtenerModulosSeleccionados()
    {
        var modulos = [];
        var checks = document.querySelectorAll('#lista-modulos input[type="checkbox"]:checked');
        for (var mi2 = 0; mi2 < checks.length; mi2++) {
            modulos.push(checks[mi2].value);
        }
        return modulos;
    }

    function actualizarPreview()
    {
        var preview = document.getElementById('preview-resumen');
        if (!preview) {
            return;
        }
        var nombre = (document.getElementById('proy-nombre') || {}).value || 'Sin nombre';
        var codigo = (document.getElementById('proy-codigo') || {}).value || 'sin-codigo';
        var modulos = obtenerModulosSeleccionados();
        var entidades = obtenerDatosEntidades();
        var directorio = (document.getElementById('proy-directorio') || {}).value || '(no especificado)';
        var paleta = (document.getElementById('apariencia-paleta') || {}).value || '';
        var estilo = (document.getElementById('apariencia-estilo') || {}).value || '';
        var adminCorreo = (document.getElementById('admin-correo') || {}).value || '(no especificado)';

        var html = '<strong>Resumen del proyecto:</strong>\n';
        html += '  Nombre: ' + nombre + '\n';
        html += '  Codigo: ' + codigo + '\n';
        html += '  Directorio: ' + directorio + '\n';
        html += '  Apariencia: ' + paleta + ', ' + estilo + '\n';
        html += '  Modulos activos: ' + modulos.length + ' (' + modulos.join(', ') + ')\n';
        html += '  Entidades: ' + entidades.length + '\n';
        for (var ei = 0; ei < entidades.length; ei++) {
            html += '    - ' + entidades[ei].clase + ' (' + entidades[ei].campos.length + ' campos)\n';
        }
        html += '  Admin: ' + adminCorreo + '\n';
        preview.innerHTML = html;
    }

    function construirDefinicion()
    {
        function val(id)
        {
            return (document.getElementById(id) || {}).value || ''; }
        var nombre = val('proy-nombre').trim() || 'Mi Aplicacion';
        var codigo = val('proy-codigo').trim() || 'miapp';
        var modulos = obtenerModulosSeleccionados();
        return {
            proyecto: {
                nombre: nombre,
                codigo: codigo,
                descripcion: val('proy-descripcion').trim() || 'Panel de control',
                version: val('proy-version').trim() || '1.0.0'
            },
            empresa: {
                nombre: val('proy-empresa').trim() || nombre,
                correo_seguridad: 'seguridad@' + codigo + '.com'
            },
            directorio_salida: val('proy-directorio').trim(),
            base_datos: {
                anfitrion: val('db-anfitrion').trim() || 'localhost',
                nombre: val('db-nombre').trim() || codigo + '_db',
                usuario: val('db-usuario').trim() || 'root',
                clave: val('db-clave')
            },
            apariencia: {
                paleta: val('apariencia-paleta') || 'indigo',
                estilo: val('apariencia-estilo') || 'moderno',
                locale: 'es-CR'
            },
            modulos_activados: modulos,
            entidades: obtenerDatosEntidades(),
            operador_inicial: {
                nombre: val('admin-nombre').trim() || 'Administrador',
                correo: val('admin-correo').trim() || ('admin@' + codigo + '.com'),
                clave: val('admin-clave').trim() || 'Admin123!'
            }
        };
    }

    function csrfToken()
    {
        if (typeof window.obtenerTokenCSRF === 'function') {
            return window.obtenerTokenCSRF();
        }
        return '';
    }

    function actualizarTokenCSRF(nuevoToken)
    {
        if (!nuevoToken) {
            return;
        }
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('content', nuevoToken);
        }
        var input = document.querySelector('input[name="token_peticion"]');
        if (input) {
            input.value = nuevoToken;
        }
    }

    function enviarGeneracion()
    {
        if (!validarPaso(6)) {
            return;
        }
        var btn = document.getElementById('btn-generar-proyecto');
        if (!btn) {
            return;
        }
        btn.disabled = true;
        btn.textContent = 'Generando...';

        var definicion = construirDefinicion();
        var token = csrfToken();

        fetch(window.rutaApi || '/api', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': token
            },
            body: JSON.stringify({
                accion_crud: 'generar_proyecto',
                token_peticion: token,
                definicion_proyecto: definicion
            })
        })
        .then(function (r) {
            return r.json(); })
        .then(function (resp) {
            btn.disabled = false;
            btn.textContent = 'Generar Proyecto';
            actualizarTokenCSRF(resp.nuevo_token);
            mostrarResultado(resp);
        })
        .catch(function (err) {
            btn.disabled = false;
            btn.textContent = 'Generar Proyecto';
            notificar('Error de conexion con el servidor.', 'peligro');
            mostrarResultado({ estado_operacion: false, mensaje_error: 'Error de conexion', codigo_error: 'error_conexion' });
        });
    }

    function mostrarResultado(resp)
    {
        var container = document.getElementById('resultado-generacion');
        var contenido = document.getElementById('resultado-generacion-contenido');
        if (!container || !contenido) {
            return;
        }
        container.hidden = false;

        if (resp.estado_operacion === true) {
            var d = resp.datos || {};
            contenido.innerHTML = '<div class="alerta alinear-alerta color-exito margen-inferior-normal"><strong>Proyecto generado exitosamente</strong></div>' +
            '<div class="tarjeta-simple"><table class="tabla-datos"><tbody>' +
            '<tr><td><strong>Directorio</strong></td><td>' + escapar(d.directorio) + '</td></tr>' +
            '<tr><td><strong>Archivos procesados</strong></td><td>' + escapar(String(d.archivos_procesados || 0)) + '</td></tr>' +
            (d.entidades && d.entidades.length ? '<tr><td><strong>Entidades</strong></td><td>' + d.entidades.map(escapar).join('<br>') + '</td></tr>' : '') +
            '<tr><td><strong>Modulos</strong></td><td>' + (d.modulos_activados || []).map(escapar).join(', ') + '</td></tr>' +
            '</tbody></table></div>' +
            '<div class="margen-superior-normal"><strong>Pasos siguientes:</strong><ol>' +
            (d.pasos_siguientes || []).map(function (p) {
                return '<li>' + escapar(p) + '</li>'; }).join('') +
            '</ol></div>';
        } else {
            contenido.innerHTML = '<div class="alerta alinear-alerta color-peligro margen-inferior-normal"><strong>Error:</strong> ' + escapar(resp.mensaje_error || 'Error desconocido') + '</div>';
        }
        container.scrollIntoView({ behavior: 'smooth' });
    }

    function init()
    {
        enlazarPasos();
        inicializarModulos();

        var btnAgregarEntidad = document.getElementById('agregar-entidad');
        if (btnAgregarEntidad) {
            btnAgregarEntidad.addEventListener('click', function () {
                agregarEntidad('', null); });
            agregarEntidad('Producto', [
            { nombre: 'nombre', tipo: 'string', reglas: 'required|unique' },
            { nombre: 'precio', tipo: 'decimal', reglas: 'required' },
            { nombre: 'descripcion', tipo: 'text', reglas: '' }
            ]);
            agregarEntidad('Cliente', [
                { nombre: 'nombre', tipo: 'string', reglas: 'required' },
                { nombre: 'correo', tipo: 'email', reglas: 'required|unique' },
                { nombre: 'telefono', tipo: 'string', reglas: '' }
            ]);
        }

        var form = document.getElementById('form-generar-proyecto');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault(); enviarGeneracion(); });
        }

        var codigoInput = document.getElementById('proy-codigo');
        var dirInput = document.getElementById('proy-directorio');
        var dbNombreInput = document.getElementById('db-nombre');
        if (codigoInput && dirInput) {
            codigoInput.addEventListener('input', function () {
                if (!dirInput.value || dirInput.value.indexOf('/' + codigoInput.defaultValue) > -1 || dirInput.value.indexOf('\\' + codigoInput.defaultValue) > -1) {
                    dirInput.value = dirInput.value.replace(/[\/\\][^\/\\]*$/, '') + '/' + this.value;
                }
                if (dbNombreInput && !dbNombreInput.value) {
                    dbNombreInput.value = this.value + '_db';
                }
            });
        }

        var inputsForm = document.querySelectorAll('#form-generar-proyecto input, #form-generar-proyecto select');
        for (var ii = 0; ii < inputsForm.length; ii++) {
            inputsForm[ii].addEventListener('change', function () {
                if (pasoActual === 6) {
                    actualizarPreview();
                } });
            inputsForm[ii].addEventListener('input', function () {
                if (pasoActual === 6) {
                    actualizarPreview();
                } });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
