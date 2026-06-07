export function vincularFormularioAutenticacion(idFormulario, tipo, rutaApi) {
    const formulario = document.getElementById(idFormulario);
    if (!formulario) return;

    if (!rutaApi) rutaApi = window.rutaApi || '/api';

    formulario.addEventListener('submit', async function(evento) {
        evento.preventDefault();

        if (formulario.querySelector('.campo-error')) {
            return;
        }

        const datosFormulario = new FormData(formulario);
        const cuerpo = {};

        datosFormulario.forEach(function(valor, clave) {
            if (clave === 'token_peticion') return;
            cuerpo[clave] = typeof valor === 'string' ? valor.trim() : valor;
        });

        const tokenInput = formulario.querySelector('[name="token_peticion"]');
        cuerpo['token_peticion'] = (tokenInput || {}).value || '';
        cuerpo['accion_crud'] = tipo;

        if (typeof window.alternarEstadoCarga === 'function') {
            window.alternarEstadoCarga(formulario, true);
        }

        try {
            const res = await fetch(rutaApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': cuerpo['token_peticion']
                },
                body: JSON.stringify(cuerpo)
            });

            let resultado;
            if (typeof window.analizarRespuestaServidor === 'function') {
                resultado = await window.analizarRespuestaServidor(res);
            } else {
                if (!res.ok) throw new Error('Error del servidor.');
                resultado = await res.json();
            }

            if (resultado.nuevo_token) {
                document.querySelectorAll('[name="token_peticion"]').forEach(function(el) {
                    el.value = resultado.nuevo_token;
                });
            }

            if (resultado.estado_operacion === true) {

                if (tipo === 'iniciar_sesion') {
                    window.location.href = resultado.redireccion || '/inicio';
                    return;
                }

                if (tipo === 'registrar_operador') {
                    formulario.reset();
                    if (window.NotificadorHubble) {
                        window.NotificadorHubble.mostrar('Operador registrado exitosamente.', 'exito', 5000);
                    }
                    const eventoActualizacionGlobal = new CustomEvent('moduloListaActualizar', {
                        detail: {
                            operacion: 'crear',
                            datos: resultado.datos
                        }
                    });
                    document.dispatchEvent(eventoActualizacionGlobal);
                    return;
                }

            } else {
                const codigo = resultado.codigo_error || '';
                const mensajes = {
                    'acceso_denegado': 'Credenciales incorrectas.',
                    'cuenta_suspendida': 'Cuenta deshabilitada temporalmente.',
                    'bloqueo_temporal': 'Demasiados intentos. Cuenta bloqueada temporalmente.',
                    'datos_invalidos': 'Datos no válidos. Verifica los campos.',
                    'token_invalido': 'Sesión expirada. Recarga la página.',
                    'error_interno': 'Error del servidor. Intenta más tarde.'
                };
                const msg = mensajes[codigo] || resultado.mensaje_error || 'Error inesperado.';
                if (window.NotificadorHubble) {
                    window.NotificadorHubble.mostrar(msg, 'peligro', 5000);
                }
            }

        } catch (e) {
            if (e.respuesta && e.respuesta.nuevo_token) {
                document.querySelectorAll('[name="token_peticion"]').forEach(function(el) {
                    el.value = e.respuesta.nuevo_token;
                });
            }
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar(
                    e.message || 'Error de conexión con el servidor.',
                    'peligro', 5000
                );
            }
        } finally {
            if (typeof window.alternarEstadoCarga === 'function') {
                window.alternarEstadoCarga(formulario, false);
            }
        }
    });
}

window.vincularFormularioAutenticacion = vincularFormularioAutenticacion;