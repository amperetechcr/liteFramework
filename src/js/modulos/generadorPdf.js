(function() {
    'use strict';

    var formulario = document.getElementById('formularioDocumento');
    var campoIdDocumento = document.getElementById('idDocumentoEditar');
    var campoTitulo = document.getElementById('titulo');
    var campoContenido = document.getElementById('contenidoHtml');
    var botonSubmit = formulario ? formulario.querySelector('button[type="submit"]') : null;
    var tokenInicial = document.querySelector('[name="token_peticion"]') ? document.querySelector('[name="token_peticion"]').value : '';
    var baseUrl = document.querySelector('script[data-base-url]');
    var baseUrlValor = baseUrl ? baseUrl.getAttribute('data-base-url') : '';

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
        if (campoIdDocumento) campoIdDocumento.remove();
        if (botonSubmit) botonSubmit.textContent = 'Guardar plantilla';
        formulario.reset();
        var campoAccion = formulario.querySelector('[name="accion"]');
        if (campoAccion) campoAccion.value = 'crear';
        var campoIdOculto = formulario.querySelector('input[name="id_entidad"]');
        if (campoIdOculto) campoIdOculto.remove();
    }

    function prepararEdicion(id, titulo, contenido) {
        esEdicion = true;

        var accionInput = formulario.querySelector('[name="accion"]');
        if (accionInput) accionInput.value = 'actualizar';

        var tablaInput = formulario.querySelector('[name="entidad"]');
        if (tablaInput) tablaInput.value = 'documento_pdf';

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
        campoContenido.value = contenido;

        if (botonSubmit) botonSubmit.textContent = 'Guardar plantilla';

        campoTitulo.focus();
        campoTitulo.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function imprimirDocumento(id) {
        var url = (baseUrlValor || '') + '/generadorPdf/imprimir/' + id;
        window.open(url, '_blank', 'width=900,height=700');
    }

    async     function eliminarDocumento(id) {
        var confirmado = await window.ConfirmadorHubble.mostrar('Eliminar esta plantilla? Esta accion no se puede deshacer.');
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
                body: 'accion_crud=crud&entidad=documento_pdf&accion=eliminar&id=' + id + '&token_peticion=' + encodeURIComponent(token)
            });

            var resultado = await res.json();

            if (resultado.nuevo_token) {
                actualizarTokens(resultado.nuevo_token);
            }

            if (resultado.estado_operacion === true) {
                notificar('Plantilla eliminada correctamente.', 'exito');
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
        document.querySelectorAll('.boton-imprimir-documento').forEach(function(btn) {
            btn.removeEventListener('click', imprimirHandler);
            btn.addEventListener('click', imprimirHandler);
        });

        document.querySelectorAll('.boton-editar-documento').forEach(function(btn) {
            btn.removeEventListener('click', editarHandler);
            btn.addEventListener('click', editarHandler);
        });

        document.querySelectorAll('.boton-eliminar-documento').forEach(function(btn) {
            btn.removeEventListener('click', eliminarHandler);
            btn.addEventListener('click', eliminarHandler);
        });
    }

    function imprimirHandler() {
        var id = parseInt(this.getAttribute('data-id'));
        if (!isNaN(id) && id > 0) imprimirDocumento(id);
    }

    function editarHandler() {
        var id = parseInt(this.getAttribute('data-id'));
        var titulo = this.getAttribute('data-titulo') || '';
        var contenido = this.getAttribute('data-contenido') || '';

        var tarjeta = this.closest('.documento-tarjeta');
        if (tarjeta) {
            titulo = tarjeta.getAttribute('data-titulo') || titulo;
        }

        if (!isNaN(id) && id > 0) {
            prepararEdicion(id, titulo, contenido);
        }
    }

    function eliminarHandler() {
        var id = parseInt(this.getAttribute('data-id'));
        if (!isNaN(id) && id > 0) eliminarDocumento(id);
    }

    function inicializarLista() {
        if (typeof window.ListaFiltrable !== 'function') {
            return setTimeout(inicializarLista, 100);
        }
        lista = new window.ListaFiltrable({
            baseUrl: '/generadorPdf',
            containerId: 'contenedor-lista-documentos',
            paginationSelector: '.paginacion',
            contadorId: 'contador-documentos',
            contadorSourceId: 'total-documentos-partial',
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
        var contenido = campoContenido.value.trim();

        if (!titulo) {
            notificar('El titulo es obligatorio.', 'advertencia');
            campoTitulo.focus();
            return;
        }
        if (!contenido) {
            notificar('El contenido HTML es obligatorio.', 'advertencia');
            campoContenido.focus();
            return;
        }

        if (typeof window.alternarEstadoCarga === 'function') {
            window.alternarEstadoCarga(formulario, true);
        }

        var tablaDestino = 'documento_pdf';
        var accion = esEdicion ? 'actualizar' : 'crear';
        var idEdicion = '';

        var idOculto = formulario.querySelector('input[name="id_entidad"]');
        if (idOculto && esEdicion) {
            idEdicion = idOculto.value;
        }

        var cuerpo = 'accion_crud=crud&entidad=' + tablaDestino + '&accion=' + accion +
            '&titulo=' + encodeURIComponent(titulo) +
            '&contenido_html=' + encodeURIComponent(contenido) +
            '&id_operador=' + encodeURIComponent(document.querySelector('script[data-operador-id]') ? document.querySelector('script[data-operador-id]').getAttribute('data-operador-id') : '') +
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
                var mensaje = esEdicion ? 'Plantilla actualizada correctamente.' : 'Plantilla creada correctamente.';
                notificar(mensaje, 'exito');
                reiniciarFormulario();
                document.dispatchEvent(new CustomEvent('moduloListaActualizar'));
            } else {
                var msg = resultado.mensaje_error || 'Error al guardar la plantilla.';
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
