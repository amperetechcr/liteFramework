(function () {
    'use strict';

    console.log('[DEBUG] estadisticas.js loaded', { url: window.location.href, partial: new URLSearchParams(window.location.search).get('partial') });

    var formulario = document.getElementById('formularioEstadistica');
    var campoTitulo = document.getElementById('titulo');
    var campoDescripcion = document.getElementById('descripcion');
    var campoConsulta = document.getElementById('consultaSql');
    var campoTipoVisualizacion = document.getElementById('tipoVisualizacion');
    var campoColumnas = document.getElementById('columnasMostrar');
    var campoConfiguracion = document.getElementById('configuracionVisual');
    var campoCacheTtl = document.getElementById('cacheTtl');
    var botonSubmit = formulario ? formulario.querySelector('button[type="submit"]') : null;
    var botonCancelar = document.getElementById('botonCancelarEdicion');
    var tokenInicial = document.querySelector('[name="token_peticion"]') ? document.querySelector('[name="token_peticion"]').value : '';
    var baseUrl = window.location.pathname.includes('liteFramework')
        ? '/liteFramework'
        : '';
    var operadorId = document.querySelector('script[data-operador-id]')
        ? document.querySelector('script[data-operador-id]').getAttribute('data-operador-id')
        : '';

    var modal = document.getElementById('modal-vista-estadistica');
    var modalTitulo = document.getElementById('titulo-modal-estadistica');
    var modalCuerpo = document.getElementById('cuerpo-modal-estadistica');
    var btnExportarJson = document.getElementById('btn-exportar-json');
    var btnExportarCsv = document.getElementById('btn-exportar-csv');
    var statActualId = null;

    var esEdicion = false;
    var lista;
    var intervalosWidget = [];
    var observadoresCanvas = [];

    if (!formulario) return;

    function csrfToken() {
        if (typeof window.obtenerTokenCSRF === 'function') return window.obtenerTokenCSRF();
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : tokenInicial;
    }

    function actualizarTokens(nuevoToken) {
        tokenInicial = nuevoToken;
        document.querySelectorAll('[name="token_peticion"]').forEach(function (el) {
            el.value = nuevoToken;
        });
    }

    function notificar(mensaje, tipo) {
        if (window.NotificadorHubble && typeof window.NotificadorHubble.mostrar === 'function') {
            window.NotificadorHubble.mostrar(mensaje, tipo);
        }
    }

    function tiempoRelativo(fechaStr) {
        if (!fechaStr) return '—';
        var diff = Math.floor((Date.now() - new Date(fechaStr.replace(' ', 'T')).getTime()) / 1000);
        if (diff < 5) return 'ahora';
        if (diff < 60) return 'hace ' + diff + 's';
        if (diff < 3600) return 'hace ' + Math.floor(diff / 60) + 'm';
        if (diff < 86400) return 'hace ' + Math.floor(diff / 3600) + 'h';
        return 'hace ' + Math.floor(diff / 86400) + 'd';
    }

    /* ========== TABS ========== */

    function activarTab(tabId) {
        document.querySelectorAll('.pestanas-ui .pestana').forEach(function (btn) {
            btn.classList.toggle('activa', btn.getAttribute('data-tab') === tabId);
            btn.setAttribute('aria-selected', btn.getAttribute('data-tab') === tabId ? 'true' : 'false');
        });
        document.querySelectorAll('.pestana-panel').forEach(function (panel) {
            var esActivo = panel.getAttribute('data-panel') === tabId;
            panel.hidden = !esActivo;
            panel.classList.toggle('activo', esActivo);
        });
        if (tabId === 'dashboard') cargarDashboard();
    }

    document.querySelectorAll('.pestanas-ui .pestana').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activarTab(this.getAttribute('data-tab'));
        });
    });

    /* ========== FORMULARIO ========== */

    function reiniciarFormulario() {
        esEdicion = false;
        if (botonSubmit) botonSubmit.textContent = 'Guardar estadistica';
        if (botonCancelar) botonCancelar.style.display = 'none';
        var tituloForm = document.getElementById('titulo-formulario');
        if (tituloForm) tituloForm.textContent = 'Nueva estadistica';
        formulario.reset();
        if (campoCacheTtl) campoCacheTtl.value = '300';
        var campoAccion = formulario.querySelector('[name="accion"]');
        if (campoAccion) campoAccion.value = 'crear';
        var campoIdOculto = formulario.querySelector('input[name="id_entidad"]');
        if (campoIdOculto) campoIdOculto.remove();
    }

    function llenarFormulario(id, titulo, descripcion, consulta, tipo, columnas, configuracion, clonar) {
        esEdicion = !clonar;
        var accionInput = formulario.querySelector('[name="accion"]');
        if (accionInput) accionInput.value = clonar ? 'crear' : 'actualizar';

        var tablaInput = formulario.querySelector('[name="entidad"]');
        if (tablaInput) tablaInput.value = 'estadistica';

        if (!clonar) {
            var idExistente = formulario.querySelector('input[name="id_entidad"]');
            if (idExistente) {
                idExistente.value = id;
            } else {
                var idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id_entidad';
                idInput.value = id;
                formulario.appendChild(idInput);
            }
            if (botonSubmit) botonSubmit.textContent = 'Actualizar estadistica';
            if (botonCancelar) botonCancelar.style.display = 'inline-block';
            var tituloForm = document.getElementById('titulo-formulario');
            if (tituloForm) tituloForm.textContent = 'Editar estadistica';
        } else {
            if (botonSubmit) botonSubmit.textContent = 'Guardar estadistica';
            if (botonCancelar) botonCancelar.style.display = 'none';
            var tituloForm2 = document.getElementById('titulo-formulario');
            if (tituloForm2) tituloForm2.textContent = 'Clonar estadistica (ajusta el titulo y SQL)';
        }

        campoTitulo.value = titulo + (clonar ? ' (copia)' : '');
        campoDescripcion.value = descripcion || '';
        campoConsulta.value = consulta;
        campoTipoVisualizacion.value = tipo || 'tarjetas';
        campoColumnas.value = columnas || '';
        campoConfiguracion.value = configuracion || '';

        activarTab('nueva');
        campoTitulo.focus();
    }

    /* ========== MODAL VISTA ========== */

    function verEstadistica(id) {
        statActualId = id;
        if (modalTitulo) modalTitulo.textContent = 'Cargando...';
        if (modalCuerpo) modalCuerpo.innerHTML = '<div class="alineacion-centrada"><span class="indicador-cargando">↻</span> Cargando...</div>';
        if (modal) modal.hidden = false;
        document.body.style.overflow = 'hidden';

        if (btnExportarJson) btnExportarJson.setAttribute('data-id', id);
        if (btnExportarCsv) btnExportarCsv.setAttribute('data-id', id);

        fetch((baseUrl || '') + '/estadisticas/vista/' + id + '?_=' + Date.now())
            .then(function (res) {
                if (!res.ok) throw new Error('Error al cargar');
                return res.text();
            })
            .then(function (html) {
                var titulo = document.querySelector('.estadistica-tarjeta[data-id="' + id + '"]');
                if (modalTitulo) modalTitulo.textContent = 'Vista: ' + (titulo ? titulo.getAttribute('data-titulo') : 'Estadistica');
                if (modalCuerpo) modalCuerpo.innerHTML = html;
            })
            .catch(function () {
                if (modalCuerpo) modalCuerpo.innerHTML = '<div class="alerta alerta-peligro"><p>Error al cargar la estadistica.</p></div>';
            });
    }

    function cerrarModal() {
        if (modal) modal.hidden = true;
        document.body.style.overflow = '';
        statActualId = null;
    }

    /* ========== CRUD ========== */

    async function eliminarEstadistica(id) {
        var confirmado = await window.ConfirmadorHubble.mostrar('Eliminar esta estadistica? Esta accion no se puede deshacer.');
        if (!confirmado) return;

        var token = csrfToken();
        try {
            var res = await fetch((window.rutaApi || '/api'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': token
                },
                body: 'accion_crud=crud&entidad=estadistica&accion=eliminar&id=' + id + '&token_peticion=' + encodeURIComponent(token)
            });
            var resultado = await res.json();
            if (resultado.nuevo_token) actualizarTokens(resultado.nuevo_token);
            if (resultado.estado_operacion === true) {
                notificar('Estadistica eliminada correctamente.', 'exito');
                document.dispatchEvent(new CustomEvent('moduloListaActualizar'));
                if (esEdicion) reiniciarFormulario();
                cargarDashboard();
            } else {
                notificar(resultado.mensaje_error || 'Error al eliminar.', 'peligro');
            }
        } catch (e) {
            notificar('Error de conexion con el servidor.', 'peligro');
        }
    }

    async function pinearEstadistica(id, btn) {
        var token = csrfToken();
        try {
            var res = await fetch((baseUrl || '') + '/api/estadisticas/pinear/' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': token
                },
                body: 'token_peticion=' + encodeURIComponent(token)
            });
            var resultado = await res.json();
            if (resultado.estado) {
                if (resultado.pineado) {
                    btn.textContent = '📌 Quitar';
                    btn.classList.add('boton-pineado');
                    notificar('Agregada al dashboard.', 'exito');
                } else {
                    btn.textContent = '📍 Pinear';
                    btn.classList.remove('boton-pineado');
                    notificar('Quitada del dashboard.', 'info');
                }
                cargarDashboard();
            } else {
                notificar(resultado.error || 'Error al cambiar estado.', 'peligro');
            }
        } catch (e) {
            notificar('Error de conexion.', 'peligro');
        }
    }

    function exportarEstadistica(id, formato) {
        var url = (baseUrl || '') + '/estadisticas/exportar/' + id + '/' + formato;
        window.open(url, '_blank');
    }

    /* ========== DASHBOARD ========== */

    function limpiarWidgets() {
        intervalosWidget.forEach(function (id) { clearInterval(id); });
        intervalosWidget = [];
        observadoresCanvas.forEach(function (fn) { fn(); });
        observadoresCanvas = [];
    }

    function inicializarGraficoWidget(widgetId, datosGrafico, colores) {
        var canvas = document.querySelector('#widget-' + widgetId + ' canvas');
        if (!canvas || !datosGrafico || !datosGrafico.datos || datosGrafico.datos.length === 0) return;

        var tipo = datosGrafico.tipo || 'barras';
        var opciones = { colores: colores || datosGrafico.colores };

        var limpiar = null;
        if (tipo === 'pastel') {
            limpiar = Graficos.pastel(canvas, datosGrafico.datos, opciones);
        } else if (tipo === 'barras') {
            limpiar = Graficos.barras(canvas, datosGrafico.datos, opciones);
        } else if (tipo === 'kpi' || tipo === 'tarjetas') {
            return;
        }
        if (limpiar) observadoresCanvas.push(limpiar);
    }

    function cargarDashboard() {
        limpiarWidgets();
        var grid = document.getElementById('dashboard-grid');
        if (!grid) return;

        grid.innerHTML = '<div class="alineacion-centrada" style="grid-column:1/-1;padding:2rem"><span class="indicador-cargando">↻</span> Cargando dashboard...</div>';

        fetch((baseUrl || '') + '/api/estadisticas/dashboard?_=' + Date.now())
            .then(function (res) { return res.json(); })
            .then(function (widgets) {
                if (!widgets || widgets.length === 0) {
                    grid.innerHTML = '<div class="widget-vacio"><p>No hay estadisticas en el dashboard.</p><p class="texto-xs texto-suave">Ve a la pestaña "Estadisticas", busca una y haz clic en "Pinear al dashboard".</p></div>';
                    return;
                }

                grid.innerHTML = '';
                widgets.forEach(function (w) {
                    var widget = document.createElement('div');
                    widget.className = 'widget-estadistica';
                    widget.id = 'widget-' + w.id;
                    widget.setAttribute('data-id', w.id);
                    widget.setAttribute('data-ttl', w.cache_ttl || 300);

                    var tieneGrafico = w.tipo === 'barras' || w.tipo === 'pastel';
                    var tieneError = w.error !== null && w.error !== undefined;

                    widget.innerHTML =
                        '<div class="widget-cabecera">' +
                            '<h3 class="widget-titulo" title="' + w.titulo + '">' + w.titulo + '</h3>' +
                            '<div class="widget-menu">' +
                                '<button type="button" class="widget-refrescar" title="Refrescar">↻</button>' +
                                '<button type="button" class="widget-exportar-json" title="Exportar JSON">{ }</button>' +
                                '<button type="button" class="widget-exportar-csv" title="Exportar CSV">CSV</button>' +
                                '<button type="button" class="widget-quitar color-peligro" title="Quitar del dashboard">✕</button>' +
                            '</div>' +
                        '</div>' +
                        '<div class="widget-cuerpo">' +
                            (tieneError ? '<div class="alerta alerta-peligro texto-xs"><p>' + w.error + '</p></div>' : '') +
                            (tieneGrafico ? '<canvas></canvas>' : w.html || '<p class="texto-xs texto-suave alineacion-centrada">Sin datos</p>') +
                        '</div>' +
                        '<div class="widget-pie">' +
                            '<span>' + (w.ultima_ejecucion ? tiempoRelativo(w.ultima_ejecucion) : '—') + '</span>' +
                            '<span>' + w.tipo + '</span>' +
                        '</div>';

                    grid.appendChild(widget);

                    if (!tieneError) {
                        inicializarGraficoWidget(w.id, w.grafico, w.colores);
                    }

                    var ttl = (w.cache_ttl || 300) * 1000;
                    if (ttl > 0) {
                        var idInterval = setInterval(function () {
                            refrescarWidget(w.id);
                        }, ttl);
                        intervalosWidget.push(idInterval);
                    }
                });

                vincularWidgetBotones();
            })
            .catch(function () {
                grid.innerHTML = '<div class="widget-vacio"><p>Error al cargar el dashboard.</p></div>';
            });
    }

    function refrescarWidget(id) {
        var widget = document.getElementById('widget-' + id);
        if (!widget) return;
        widget.setAttribute('data-cargando', 'true');

        fetch((baseUrl || '') + '/api/estadisticas/refrescar/' + id + '?_=' + Date.now(), {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken() }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data) return;
                var cuerpo = widget.querySelector('.widget-cuerpo');
                if (!cuerpo) return;

                var tieneGrafico = widget.querySelector('canvas') !== null;

                if (data.error) {
                    cuerpo.innerHTML = '<div class="alerta alerta-peligro texto-xs"><p>' + data.error + '</p></div>';
                } else if (tieneGrafico && data.grafico) {
                    cuerpo.innerHTML = '<canvas></canvas>';
                    inicializarGraficoWidget(id, data.grafico);
                } else {
                    cuerpo.innerHTML = data.html || '<p class="texto-xs texto-suave alineacion-centrada">Sin datos</p>';
                }

                widget.removeAttribute('data-cargando');
            })
            .catch(function () {
                widget.removeAttribute('data-cargando');
            });
    }

    function vincularWidgetBotones() {
        document.querySelectorAll('.widget-refrescar').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.closest('.widget-estadistica').getAttribute('data-id');
                if (id) refrescarWidget(id);
            });
        });
        document.querySelectorAll('.widget-exportar-json').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.closest('.widget-estadistica').getAttribute('data-id');
                if (id) exportarEstadistica(id, 'json');
            });
        });
        document.querySelectorAll('.widget-exportar-csv').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.closest('.widget-estadistica').getAttribute('data-id');
                if (id) exportarEstadistica(id, 'csv');
            });
        });
        document.querySelectorAll('.widget-quitar').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.closest('.widget-estadistica').getAttribute('data-id');
                if (id) {
                    var btnLista = document.querySelector('.boton-pinear-estadistica[data-id="' + id + '"]');
                    if (btnLista) pinearEstadistica(id, btnLista);
                }
            });
        });
    }

    /* ========== BOTONES LISTA ========== */

    function vincularBotones() {
        document.querySelectorAll('.boton-ver-estadistica').forEach(function (btn) {
            btn.removeEventListener('click', verHandler);
            btn.addEventListener('click', verHandler);
        });
        document.querySelectorAll('.boton-editar-estadistica').forEach(function (btn) {
            btn.removeEventListener('click', editarHandler);
            btn.addEventListener('click', editarHandler);
        });
        document.querySelectorAll('.boton-clonar-estadistica').forEach(function (btn) {
            btn.removeEventListener('click', clonarHandler);
            btn.addEventListener('click', clonarHandler);
        });
        document.querySelectorAll('.boton-eliminar-estadistica').forEach(function (btn) {
            btn.removeEventListener('click', eliminarHandler);
            btn.addEventListener('click', eliminarHandler);
        });
        document.querySelectorAll('.boton-pinear-estadistica').forEach(function (btn) {
            btn.removeEventListener('click', pinearHandler);
            btn.addEventListener('click', pinearHandler);
        });
    }

    function verHandler() {
        var id = parseInt(this.getAttribute('data-id'));
        if (!isNaN(id) && id > 0) verEstadistica(id);
    }

    function editarHandler() {
        var id = parseInt(this.getAttribute('data-id'));
        if (!id || id <= 0) return;
        var tarjeta = this.closest('.estadistica-tarjeta');
        if (!tarjeta) return;
        llenarFormulario(
            id,
            tarjeta.getAttribute('data-titulo') || '',
            tarjeta.getAttribute('data-descripcion') || '',
            tarjeta.getAttribute('data-consulta') || '',
            tarjeta.getAttribute('data-tipo') || 'tarjetas',
            tarjeta.getAttribute('data-columnas') || '',
            tarjeta.getAttribute('data-configuracion') || '',
            false
        );
    }

    function clonarHandler() {
        var id = parseInt(this.getAttribute('data-id'));
        if (!id || id <= 0) return;
        var tarjeta = this.closest('.estadistica-tarjeta');
        if (!tarjeta) return;
        notificar('Formulario precargado con los datos originales. Ajusta el titulo y guarda.', 'info');
        llenarFormulario(
            id,
            tarjeta.getAttribute('data-titulo') || '',
            tarjeta.getAttribute('data-descripcion') || '',
            tarjeta.getAttribute('data-consulta') || '',
            tarjeta.getAttribute('data-tipo') || 'tarjetas',
            tarjeta.getAttribute('data-columnas') || '',
            tarjeta.getAttribute('data-configuracion') || '',
            true
        );
    }

    function eliminarHandler() {
        var id = parseInt(this.getAttribute('data-id'));
        if (!isNaN(id) && id > 0) eliminarEstadistica(id);
    }

    function pinearHandler() {
        var id = parseInt(this.getAttribute('data-id'));
        if (!isNaN(id) && id > 0) pinearEstadistica(id, this);
    }

    /* ========== LISTA ========== */

    function inicializarLista() {
        if (typeof window.ListaFiltrable !== 'function') return setTimeout(inicializarLista, 100);
        lista = new window.ListaFiltrable({
            baseUrl: '/estadisticas',
            containerId: 'contenedor-lista-estadisticas',
            paginationSelector: '.paginacion',
            contadorId: 'contador-estadisticas',
            contadorSourceId: 'total-estadisticas-partial',
            filtros: [{ id: 'filtroBuscar', paramName: 'buscar' }],
            busquedaId: 'filtroBuscar',
            afterRender: function () { vincularBotones(); }
        });
        lista.inicializarEventos();
        lista.sincronizarConUrl();
        lista.vincularPaginacion();
        document.addEventListener('moduloListaActualizar', function () {
            lista.recargar(1);
            cargarDashboard();
        });
    }

    /* ========== MODAL EVENTS ========== */

    if (botonCancelar) {
        botonCancelar.addEventListener('click', reiniciarFormulario);
    }

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) cerrarModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) cerrarModal();
        });
        var modalCerrar = modal.querySelector('.modal-cerrar');
        if (modalCerrar) modalCerrar.addEventListener('click', cerrarModal);
    }

    if (btnExportarJson) {
        btnExportarJson.addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            if (id) exportarEstadistica(id, 'json');
        });
    }
    if (btnExportarCsv) {
        btnExportarCsv.addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            if (id) exportarEstadistica(id, 'csv');
        });
    }

    /* ========== FORMULARIO SUBMIT ========== */

    formulario.addEventListener('submit', async function (evento) {
        evento.preventDefault();
        var token = csrfToken();
        var titulo = campoTitulo.value.trim();
        var consulta = campoConsulta.value.trim();

        if (!titulo) { notificar('El titulo es obligatorio.', 'advertencia'); campoTitulo.focus(); return; }
        if (!consulta) { notificar('La consulta SQL es obligatoria.', 'advertencia'); campoConsulta.focus(); return; }

        if (typeof window.alternarEstadoCarga === 'function') window.alternarEstadoCarga(formulario, true);

        var accion = esEdicion ? 'actualizar' : 'crear';
        var idEdicion = '';
        var idOculto = formulario.querySelector('input[name="id_entidad"]');
        if (idOculto && esEdicion) idEdicion = idOculto.value;

        var cuerpo = 'accion_crud=crud&entidad=estadistica&accion=' + accion +
            '&titulo=' + encodeURIComponent(titulo) +
            '&descripcion=' + encodeURIComponent(campoDescripcion.value.trim()) +
            '&consulta_sql=' + encodeURIComponent(consulta) +
            '&tipo_visualizacion=' + encodeURIComponent(campoTipoVisualizacion.value) +
            '&columnas_mostrar=' + encodeURIComponent(campoColumnas.value.trim()) +
            '&configuracion_visual=' + encodeURIComponent(campoConfiguracion.value.trim()) +
            '&cache_ttl=' + encodeURIComponent(campoCacheTtl ? campoCacheTtl.value : '300') +
            '&id_operador=' + encodeURIComponent(operadorId) +
            '&token_peticion=' + encodeURIComponent(token);

        if (idEdicion) cuerpo += '&id=' + encodeURIComponent(idEdicion);

        try {
            var res = await fetch((window.rutaApi || '/api'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': token
                },
                body: cuerpo
            });
            var resultado = await res.json();
            if (resultado.nuevo_token) actualizarTokens(resultado.nuevo_token);
            if (resultado.estado_operacion === true) {
                notificar(esEdicion ? 'Estadistica actualizada correctamente.' : 'Estadistica creada correctamente.', 'exito');
                reiniciarFormulario();
                document.dispatchEvent(new CustomEvent('moduloListaActualizar'));
                activarTab('lista');
            } else {
                notificar(resultado.mensaje_error || 'Error al guardar la estadistica.', 'peligro');
            }
        } catch (e) {
            notificar('Error de conexion con el servidor.', 'peligro');
        } finally {
            if (typeof window.alternarEstadoCarga === 'function') window.alternarEstadoCarga(formulario, false);
        }
    });

    /* ========== INIT ========== */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            inicializarLista();
            vincularBotones();
            cargarDashboard();
        });
    } else {
        inicializarLista();
        vincularBotones();
        cargarDashboard();
    }
})();
