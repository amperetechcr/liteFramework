export function vincularFormularioCrud(idFormulario, tipoOperacion, nombreTabla, rutaApi) {
    const formulario = document.getElementById(idFormulario);

    if (!formulario) {
        console.warn(`[Motor Peticiones]: No se encontró ningún elemento formulario con el ID "${idFormulario}".`);
        return;
    }

    if (!rutaApi) {
        rutaApi = window.rutaApi || '/api';
    }

    formulario.addEventListener('submit', async function(evento) {
        evento.preventDefault();

        const datosFormulario = new FormData(formulario);
        const datosEnvio = {};

        datosFormulario.forEach(function(valor, clave) {
            if (clave === 'token_peticion' || clave === 'accion_crud' || clave === 'tabla_destino') {
                return;
            }
            const valorLimpio = typeof valor === 'string' ? valor.trim() : valor;
            if (valorLimpio !== '' || valorLimpio === 0) {
                datosEnvio[clave] = valorLimpio;
            }
        });

        datosEnvio['accion_crud'] = tipoOperacion.toLowerCase();
        datosEnvio['tabla_destino'] = nombreTabla.toLowerCase();

        const elementoToken = formulario.querySelector('input[name="token_peticion"]') || document.getElementById('token_peticion');
        const tokenSeguridad = elementoToken ? elementoToken.value : '';

        if (typeof window.alternarEstadoCarga === 'function') {
            window.alternarEstadoCarga(formulario, true);
        }

        try {
            const respuestaServidor = await fetch(rutaApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': tokenSeguridad
                },
                body: JSON.stringify(datosEnvio)
            });

            let resultadoTransaccion;
            if (typeof window.analizarRespuestaServidor === 'function') {
                try {
                    resultadoTransaccion = await window.analizarRespuestaServidor(respuestaServidor);
                } catch (errorAnalisis) {
                    notificarFalloTransaccion(errorAnalisis.message);
                    return;
                }
            } else {
                resultadoTransaccion = await analizarRespuestaHTTP(respuestaServidor);
            }

            if (resultadoTransaccion.nuevo_token) {
                document.querySelectorAll('[name="token_peticion"], #token_peticion').forEach(function(el) {
                    el.value = resultadoTransaccion.nuevo_token;
                });
            }

            if (resultadoTransaccion.estado_operacion === true) {
                notificarExitoTransaccion(formulario, tipoOperacion, resultadoTransaccion.datos);
            } else {
                const codigo = resultadoTransaccion.codigo_error || '';
                const mensajesError = {
                    'no_autenticado': 'Su sesi\u00F3n ha expirado. Recargue la p\u00E1gina e inicie sesi\u00F3n nuevamente.',
                    'sin_permiso': 'No tiene privilegios suficientes para realizar esta operaci\u00F3n.',
                    'token_invalido': 'La petici\u00F3n fue rechazada por seguridad. Recargue la p\u00E1gina e intente de nuevo.',
                    'datos_invalidos': 'Los datos enviados no son v\u00E1lidos. Verifique los campos e intente de nuevo.'
                };
                const mensajeUsuario = mensajesError[codigo] || resultadoTransaccion.mensaje_error || 'Error inesperado del servidor.';
                notificarFalloTransaccion(mensajeUsuario);
            }

        } catch (errorOperacion) {
            console.error('[Error en Transacci\u00F3n As\u00EDncrona]:', errorOperacion.message);
            notificarFalloTransaccion(errorOperacion.message || 'Incapacidad de comunicaci\u00F3n con el m\u00F3dulo procesador del servidor.');
        } finally {
            if (typeof window.alternarEstadoCarga === 'function') {
                window.alternarEstadoCarga(formulario, false);
            }
        }
    });
}

window.vincularFormularioCrud = vincularFormularioCrud;
window.analizarRespuestaHTTP = analizarRespuestaHTTP;
window.notificarExitoTransaccion = notificarExitoTransaccion;
window.notificarFalloTransaccion = notificarFalloTransaccion;

export async function analizarRespuestaHTTP(respuesta) {
    const tipoContenido = respuesta.headers.get('content-type');

    if (!respuesta.ok) {
        if (tipoContenido && tipoContenido.includes('application/json')) {
            const datosErrorJson = await respuesta.json();
            throw new Error(datosErrorJson.mensaje_error || 'Error indeterminado en el servicio web.');
        }
        throw new Error(`Código de respuesta HTTP anómalo del servidor: ${respuesta.status}`);
    }

    if (!tipoContenido || !tipoContenido.includes('application/json')) {
        throw new Error('El procesador del servidor no devolvió un formato estructurado JSON válido.');
    }

    return await respuesta.json();
}

export function notificarExitoTransaccion(formulario, tipoOperacion, datos) {
    if (tipoOperacion === 'crear') {
        formulario.reset();
    }

    const eventoActualizacionGlobal = new CustomEvent('moduloListaActualizar', {
        detail: {
            operacion: tipoOperacion,
            datos: datos
        }
    });
    document.dispatchEvent(eventoActualizacionGlobal);
}

export function notificarFalloTransaccion(mensaje) {
    if (typeof window.NotificadorHubble !== 'undefined') {
        window.NotificadorHubble.mostrar(mensaje, 'peligro');
    } else if (typeof window.MostrarNotificacionSistema === 'function') {
        window.MostrarNotificacionSistema(mensaje, 'peligro');
    } else {
        console.error(mensaje);
    }
}
