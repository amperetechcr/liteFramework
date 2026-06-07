(function() {
    'use strict';

    var formulario = document.getElementById('formularioEstadistica');
    var campoTitulo = document.getElementById('titulo');
    var campoDescripcion = document.getElementById('descripcion');
    var campoConsulta = document.getElementById('consultaSql');
    var campoTipoVisualizacion = document.getElementById('tipoVisualizacion');
    var campoColumnas = document.getElementById('columnasMostrar');
    var campoConfiguracion = document.getElementById('configuracionVisual');
    var botonSubmit = formulario ? formulario.querySelector('button[type="submit"]') : null;
    var tokenInicial = document.querySelector('[name="token_peticion"]') ? document.querySelector('[name="token_peticion"]').value : '';
    var baseUrl = document.querySelector('script[data-base-url]');
    var baseUrlValor = baseUrl ? baseUrl.getAttribute('data-base-url') : '';
    var operadorId = document.querySelector('script[data-operador-id]') ? document.querySelector('script[data-operador-id]').getAttribute('data-operador-id') : '';

    if (!formulario) return;

    var esEdicion = false;
    var lista;

    function csrfToken() {
        if (typeof window.obtenerTokenCSRF === 'function') {
            return window.obtenerTokenCSRF();
        }
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : tokenInicial;
    }

    function actualizarTokens(nuevoToken) {
        tokenInicial = nuevoToken;
        document.querySelectorAll('[name="token_peticion"]').forEach(function(el) {
            el.value = nuevoToken;
        });
    }

    function notificar(mensaje, tipo) {
        if (window.NotificadorHubble && typeof window.NotificadorHubble.mostrar === 'function') {
            window.NotificadorHubble.mostrar(mensaje, tipo);
        }
    }

    function reiniciarFormulario() {
        esEdicion = false;
        if (botonSubmit) botonSubmit.textContent = 'Guardar estadistica';
        formulario.reset();
        var campoAccion = formulario.querySelector('[name="accion"]');
        if (campoAccion) campoAccion.value = 'crear';
        var campoIdOculto = formulario.querySelector('input[name="id_entidad"]');
        if (campoIdOculto) campoIdOculto.remove();
    }

    function prepararEdicion(id, titulo, descripcion, consulta, tipo, columnas, configuracion) {
        esEdicion = true;

        var accionInput = formulario.querySelector('[name="accion"]');
        if (accionInput) accionInput.value = 'actualizar';

        var tablaInput = formulario.querySelector('[name="entidad"]');
        if (tablaInput) tablaInput.value = 'estadistica';

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

        campoTitulo.value = titulo;
        campoDescripcion.value = descripcion || '';
        campoConsulta.value = consulta;
        campoTipoVisualizacion.value = tipo || 'tarjetas';
        campoColumnas.value = columnas || '';
        campoConfiguracion.value = configuracion || '';

        if (botonSubmit) botonSubmit.textContent = 'Guardar estadistica';

        campoTitulo.focus();
        campoTitulo.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function verEstadistica(id) {
        var url = (baseUrlValor || '') + '/estadisticas/ver/' + id;
        window.open(url, '_blank', 'width=1000,height=750');
    }

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

            if (resultado.nuevo_token) {
                actualizarTokens(resultado.nuevo_token);
            }

            if (resultado.estado_operacion === true) {
                notificar('Estadistica eliminada correctamente.', 'exito');
                document.dispatchEvent(new CustomEvent('moduloListaActualizar'));
                if (esEdicion) reiniciarFormulario();
            } else {
                var msg = resultado.mensaje_error || 'Error al eliminar.';
                notificar(msg, 'peligro');
            }
        } catch (e) {
            notificar('Error de conexion con el servidor.', 'peligro');
        }
    }

    function vincularBotones() {
        document.querySelectorAll('.boton-ver-estadistica').forEach(function(btn) {
            btn.removeEventListener('click', verHandler);
            btn.addEventListener('click', verHandler);
        });

        document.querySelectorAll('.boton-editar-estadistica').forEach(function(btn) {
            btn.removeEventListener('click', editarHandler);
            btn.addEventListener('click', editarHandler);
        });

        document.querySelectorAll('.boton-eliminar-estadistica').forEach(function(btn) {
            btn.removeEventListener('click', eliminarHandler);
            btn.addEventListener('click', eliminarHandler);
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
        var titulo = tarjeta ? (tarjeta.getAttribute('data-titulo') || '') : '';
        var descripcion = tarjeta ? (tarjeta.getAttribute('data-descripcion') || '') : '';
        var consulta = tarjeta ? (tarjeta.getAttribute('data-consulta') || '') : '';
        var tipo = tarjeta ? (tarjeta.getAttribute('data-tipo') || 'tarjetas') : 'tarjetas';
        var columnas = tarjeta ? (tarjeta.getAttribute('data-columnas') || '') : '';
        var configuracion = tarjeta ? (tarjeta.getAttribute('data-configuracion') || '') : '';

        prepararEdicion(id, titulo, descripcion, consulta, tipo, columnas, configuracion);
    }

    function eliminarHandler() {
        var id = parseInt(this.getAttribute('data-id'));
        if (!isNaN(id) && id > 0) eliminarEstadistica(id);
    }

    function inicializarLista() {
        if (typeof window.ListaFiltrable !== 'function') {
            return setTimeout(inicializarLista, 100);
        }
        lista = new window.ListaFiltrable({
            baseUrl: '/estadisticas',
            containerId: 'contenedor-lista-estadisticas',
            paginationSelector: '.paginacion',
            contadorId: 'contador-estadisticas',
            contadorSourceId: 'total-estadisticas-partial',
            filtros: [
                { id: 'filtroBuscar', paramName: 'buscar' }
            ],
            busquedaId: 'filtroBuscar',
            afterRender: function() {
                vincularBotones();
            }
        });
        lista.inicializarEventos();
        lista.sincronizarConUrl();
        lista.vincularPaginacion();

        document.addEventListener('moduloListaActualizar', function() {
            lista.recargar(1);
        });
    }

    formulario.addEventListener('submit', async function(evento) {
        evento.preventDefault();

        var token = csrfToken();
        var titulo = campoTitulo.value.trim();
        var consulta = campoConsulta.value.trim();

        if (!titulo) {
            notificar('El titulo es obligatorio.', 'advertencia');
            campoTitulo.focus();
            return;
        }
        if (!consulta) {
            notificar('La consulta SQL es obligatoria.', 'advertencia');
            campoConsulta.focus();
            return;
        }

        if (typeof window.alternarEstadoCarga === 'function') {
            window.alternarEstadoCarga(formulario, true);
        }

        var tablaDestino = 'estadistica';
        var accion = esEdicion ? 'actualizar' : 'crear';
        var idEdicion = '';

        var idOculto = formulario.querySelector('input[name="id_entidad"]');
        if (idOculto && esEdicion) {
            idEdicion = idOculto.value;
        }

        var cuerpo = 'accion_crud=crud&entidad=' + tablaDestino + '&accion=' + accion +
            '&titulo=' + encodeURIComponent(titulo) +
            '&descripcion=' + encodeURIComponent(campoDescripcion.value.trim()) +
            '&consulta_sql=' + encodeURIComponent(consulta) +
            '&tipo_visualizacion=' + encodeURIComponent(campoTipoVisualizacion.value) +
            '&columnas_mostrar=' + encodeURIComponent(campoColumnas.value.trim()) +
            '&configuracion_visual=' + encodeURIComponent(campoConfiguracion.value.trim()) +
            '&id_operador=' + encodeURIComponent(operadorId) +
            '&token_peticion=' + encodeURIComponent(token);

        if (idEdicion) {
            cuerpo += '&id=' + encodeURIComponent(idEdicion);
        }

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

            if (resultado.nuevo_token) {
                actualizarTokens(resultado.nuevo_token);
            }

            if (resultado.estado_operacion === true) {
                var mensaje = esEdicion ? 'Estadistica actualizada correctamente.' : 'Estadistica creada correctamente.';
                notificar(mensaje, 'exito');
                reiniciarFormulario();
                document.dispatchEvent(new CustomEvent('moduloListaActualizar'));
            } else {
                var msg = resultado.mensaje_error || 'Error al guardar la estadistica.';
                notificar(msg, 'peligro');
            }
        } catch (e) {
            notificar('Error de conexion con el servidor.', 'peligro');
        } finally {
            if (typeof window.alternarEstadoCarga === 'function') {
                window.alternarEstadoCarga(formulario, false);
            }
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarLista);
    } else {
        inicializarLista();
    }

    vincularBotones();
})();
