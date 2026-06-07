(function() {
    'use strict';

    var metaApi = document.querySelector('meta[name="api-base"]');
    var rutaApi = (metaApi ? metaApi.content : '') || window.rutaApi || '/api';

    var modalNuevo = document.getElementById('modalNuevoOperador');
    var modalEditar = document.getElementById('modalEditarOperador');
    var modalConfirmar = document.getElementById('modalConfirmarEstado');
    var formularioEditar = document.getElementById('formularioEditarOperador');
    var campoId = document.getElementById('campo-id-operador');
    var campoNombre = document.getElementById('modal-nombre');
    var campoCorreo = document.getElementById('modal-correo');
    var campoRol = document.getElementById('modal-rol');
    var campoEstado = document.getElementById('modal-estado');

    var lista;
    var accionEstadoPendiente = null;

    function abrirModal(el) {
        if (!el) return;
        el.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function cerrarTodosLosModales() {
        document.querySelectorAll('.modal-superposicion').forEach(function(m) { m.hidden = true; });
        document.body.style.overflow = '';
    }

    function vincularBotonesEditar() {
        document.querySelectorAll('.boton-editar').forEach(function(boton) {
            boton.addEventListener('click', function() {
                var tarjeta = this.closest('.operador-tarjeta');
                if (!tarjeta) return;
                campoId.value = tarjeta.getAttribute('data-id');
                campoNombre.value = tarjeta.getAttribute('data-nombre');
                campoCorreo.value = tarjeta.getAttribute('data-correo');
                campoRol.value = parseInt(tarjeta.getAttribute('data-rol-id'));
                campoEstado.value = tarjeta.getAttribute('data-estado') === 'activo' ? 1 : 0;
                abrirModal(modalEditar);
            });
        });
    }

    function vincularBotonesEstado() {
        document.querySelectorAll('.boton-suspender, .boton-activar').forEach(function(boton) {
            boton.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                var nombre = this.getAttribute('data-nombre');
                var esSuspender = this.classList.contains('boton-suspender');
                var mensaje = document.getElementById('mensaje-confirmar-estado');
                var btnConfirmar = document.getElementById('confirmar-cambio-estado');
                mensaje.textContent = esSuspender
                    ? '¿Estás seguro de suspender a "' + nombre + '"? Podrás activarlo después.'
                    : '¿Estás seguro de activar a "' + nombre + '"?';
                btnConfirmar.className = 'accion-boton ' + (esSuspender ? 'variante-peligro' : 'variante-exito');
                btnConfirmar.textContent = esSuspender ? 'Suspender' : 'Activar';
                accionEstadoPendiente = {
                    id: id,
                    accion: esSuspender ? 'operador_suspender' : 'operador_activar',
                    mensajeExito: esSuspender ? 'Operador suspendido.' : 'Operador activado.'
                };
                abrirModal(modalConfirmar);
            });
        });
    }

    document.getElementById('confirmar-cambio-estado').addEventListener('click', async function() {
        if (!accionEstadoPendiente) return;

        var datos = {
            id_entidad: parseInt(accionEstadoPendiente.id),
            accion_crud: accionEstadoPendiente.accion,
            token_peticion: document.querySelector('[name="token_peticion"]')?.value || ''
        };

        if (typeof window.alternarEstadoCarga === 'function') {
            window.alternarEstadoCarga(modalConfirmar, true);
        }

        try {
            var res = await fetch(rutaApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': datos.token_peticion
                },
                body: JSON.stringify(datos)
            });
            var resultado = await res.json();
            if (resultado.nuevo_token) {
                document.querySelectorAll('[name="token_peticion"]').forEach(function(el) {
                    el.value = resultado.nuevo_token;
                });
            }
            if (resultado.estado_operacion === true) {
                if (window.NotificadorHubble) {
                    window.NotificadorHubble.mostrar(accionEstadoPendiente.mensajeExito, 'exito');
                }
                cerrarTodosLosModales();
                if (lista) lista.recargar(1);
            } else {
                if (window.NotificadorHubble) {
                    window.NotificadorHubble.mostrar(resultado.mensaje_error || 'Error.', 'peligro');
                }
            }
        } catch (e) {
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar('Error de conexion.', 'peligro');
            }
        } finally {
            if (typeof window.alternarEstadoCarga === 'function') {
                window.alternarEstadoCarga(modalConfirmar, false);
            }
            accionEstadoPendiente = null;
        }
    });

    formularioEditar.addEventListener('submit', async function(evento) {
        evento.preventDefault();
        var idValor = parseInt(campoId.value);
        if (isNaN(idValor) || idValor <= 0) {
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar('Error: No se ha seleccionado ningun operador para editar.', 'peligro');
            }
            return;
        }
        var datos = {
            id_entidad: idValor,
            nombre_completo: campoNombre.value.trim(),
            correo_electronico: campoCorreo.value.trim(),
            id_rol: parseInt(campoRol.value),
            estado_cuenta: parseInt(campoEstado.value),
            accion_crud: 'actualizar',
            tabla_destino: 'operador',
            token_peticion: formularioEditar.querySelector('[name="token_peticion"]').value
        };
        if (typeof window.alternarEstadoCarga === 'function') {
            window.alternarEstadoCarga(formularioEditar, true);
        }
        try {
            var res = await fetch(rutaApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': datos.token_peticion
                },
                body: JSON.stringify(datos)
            });
            var resultado = await res.json();
            if (resultado.nuevo_token) {
                document.querySelectorAll('[name="token_peticion"]').forEach(function(el) {
                    el.value = resultado.nuevo_token;
                });
            }
            if (resultado.estado_operacion === true) {
                if (window.NotificadorHubble) {
                    window.NotificadorHubble.mostrar('Operador actualizado correctamente.', 'exito');
                }
                cerrarTodosLosModales();
                document.dispatchEvent(new CustomEvent('moduloListaActualizar'));
            } else {
                if (window.NotificadorHubble) {
                    window.NotificadorHubble.mostrar(resultado.mensaje_error || 'Error al actualizar.', 'peligro');
                }
            }
        } catch (e) {
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar('Error de conexion con el servidor.', 'peligro');
            }
        } finally {
            if (typeof window.alternarEstadoCarga === 'function') {
                window.alternarEstadoCarga(formularioEditar, false);
            }
        }
    });

    if (document.getElementById('boton-nuevo-operador')) {
        document.getElementById('boton-nuevo-operador').addEventListener('click', function() {
            abrirModal(modalNuevo);
        });
    }

    if (typeof vincularFormularioAutenticacion === 'function') {
        vincularFormularioAutenticacion('formularioRegistroOperador', 'registrar_operador', rutaApi);
    }

    function actualizarResumen() {
        var srcActivos = document.getElementById('total-activos-partial');
        var srcSuspendidos = document.getElementById('total-suspendidos-partial');
        var srcTotal = document.getElementById('total-operadores-partial');
        if (srcActivos) {
            var el = document.getElementById('total-activos');
            if (el) el.textContent = srcActivos.getAttribute('data-total');
        }
        if (srcSuspendidos) {
            var el = document.getElementById('total-suspendidos');
            if (el) el.textContent = srcSuspendidos.getAttribute('data-total');
        }
        if (srcTotal) {
            var el = document.getElementById('total-operadores-resumen');
            if (el) el.textContent = srcTotal.getAttribute('data-total');
        }
    }

    function inicializarLista() {
        if (typeof window.ListaFiltrable !== 'function') {
            return setTimeout(inicializarLista, 100);
        }
        lista = new window.ListaFiltrable({
            baseUrl: '/operadores',
            containerId: 'contenedor-lista-operadores',
            paginationSelector: '.paginacion',
            contadorId: 'contador-operadores',
            contadorSourceId: 'total-operadores-partial',
            filtros: [
                { id: 'filtro-buscar', paramName: 'buscar' },
                { id: 'filtro-rol', paramName: 'rol' },
                { id: 'filtro-estado', paramName: 'estado' }
            ],
            busquedaId: 'filtro-buscar',
            afterRender: function() {
                vincularBotonesEditar();
                vincularBotonesEstado();
                actualizarResumen();
            }
        });
        lista.inicializarEventos();
        lista.sincronizarConUrl();
        lista.vincularPaginacion();
        document.addEventListener('moduloListaActualizar', function() {
            lista.recargar(1);
        });
    }

    if (window.liteSse) {
        window.liteSse.subscribir('operador.registrado', function() {
            if (lista) lista.recargar(1);
        });
        window.liteSse.subscribir('operador.actualizado', function() {
            if (lista) lista.recargar(1);
        });
    }

    document.querySelectorAll('.modal-superposicion').forEach(function(m) {
        m.addEventListener('click', function(e) {
            if (e.target === m) { m.hidden = true; document.body.style.overflow = ''; }
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var abierto = document.querySelector('.modal-superposicion:not([hidden])');
            if (abierto) { abierto.hidden = true; document.body.style.overflow = ''; }
        }
    });

    document.querySelectorAll('.modal-cerrar').forEach(function(btn) {
        btn.addEventListener('click', cerrarTodosLosModales);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarLista);
    } else {
        inicializarLista();
    }

    vincularBotonesEditar();
    vincularBotonesEstado();
})();
