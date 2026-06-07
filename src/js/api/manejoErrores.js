var MANEJADOR_ERRORES_EN_USO = false;

function iniciarManejoGlobalErrores()
{
    if (MANEJADOR_ERRORES_EN_USO) {
        return;
    }
    MANEJADOR_ERRORES_EN_USO = true;

    window.addEventListener('unhandledrejection', function (evento) {
        var error = evento.reason;
        var mensaje = 'Error inesperado en la aplicación.';

        if (error instanceof TypeError) {
            if (error.message.includes('fetch')) {
                mensaje = 'No se pudo conectar con el servidor. Verifica tu conexión.';
            }
        } else if (error && error.message) {
            mensaje = error.message;
        }

        console.error('[Error Global]', error);
        if (window.NotificadorHubble) {
            window.NotificadorHubble.mostrar(mensaje, 'peligro', 6000);
        }
    });

    window.addEventListener('error', function (evento) {
        console.error('[Error Global]', evento.error || evento.message);
    });
}

function manejarErrorFetch(error, mensajePorDefecto)
{
    mensajePorDefecto = mensajePorDefecto || 'Error de comunicación con el servidor.';

    console.error('[Fetch Error]', error);

    if (error instanceof TypeError && error.message.includes('fetch')) {
        error.mensajeUsuario = 'No se pudo conectar con el servidor. Verifica tu conexión.';
    } else if (error && error.mensajeUsuario) {
        // ya tiene mensaje usuario
    } else if (error && error.message) {
        error.mensajeUsuario = error.message;
    } else {
        error.mensajeUsuario = mensajePorDefecto;
    }

    if (window.NotificadorHubble) {
        window.NotificadorHubble.mostrar(error.mensajeUsuario, 'peligro', 6000);
    }

    if (error && error.respuesta && window.RecuperacionError) {
        window.RecuperacionError.ejecutarDesdeRespuesta(error.respuesta);
    }

    return error;
}

async function analizarRespuestaServidor(respuesta)
{
    if (!respuesta) {
        throw new Error('El servidor no respondió.');
    }

    var traceId = respuesta.headers.get('X-Trace-Id');
    var tipoContenido = respuesta.headers.get('content-type');

    if (!respuesta.ok) {
        if (tipoContenido && tipoContenido.includes('application/json')) {
            var cuerpoError = await respuesta.json();
            var mensaje = cuerpoError.mensaje_error || 'Error en el servidor.';

            if (cuerpoError.codigo_error === 'token_invalido') {
                mensaje = 'Sesión expirada. Recarga la página e inicia sesión nuevamente.';
            }

            var error = new Error(mensaje);
            error.codigo = cuerpoError.codigo_error;
            error.traceId = traceId || cuerpoError.trace_id;
            error.respuesta = cuerpoError;
            throw error;
        }

        var textoError = await respuesta.text().catch(function () {
            return ''; });
        var mensajeError;
        if (respuesta.status === 500) {
            mensajeError = 'Error interno del servidor.';
        } else if (respuesta.status === 403) {
            mensajeError = 'Acceso denegado.';
        } else if (respuesta.status === 404) {
            mensajeError = 'Recurso no encontrado en el servidor.';
        } else {
            mensajeError = 'Error del servidor (código ' + respuesta.status + ').';
        }

        if (traceId) {
            mensajeError += ' [Traza: ' + traceId + ']';
        }

        var error = new Error(mensajeError);
        error.traceId = traceId;
        throw error;
    }

    if (!tipoContenido || !tipoContenido.includes('application/json')) {
        var textoInesperado = await respuesta.text().catch(function () {
            return ''; });
        var mensajeHtml = 'El servidor no devolvió JSON.';
        if (textoInesperado.length > 0 && textoInesperado.length < 200) {
            mensajeHtml += ' Respuesta: ' + textoInesperado.substring(0, 200);
        }
        var error = new Error(mensajeHtml);
        error.traceId = traceId;
        throw error;
    }

    var datos = await respuesta.json();
    datos._traceId = traceId;

    if (datos.accion && window.RecuperacionError) {
        window.RecuperacionError._requestPendiente = null;
        window.RecuperacionError.ejecutarAccion(datos.accion, datos);
    }

    return datos;
}

export { iniciarManejoGlobalErrores, manejarErrorFetch, analizarRespuestaServidor };

window.iniciarManejoGlobalErrores = iniciarManejoGlobalErrores;
window.manejarErrorFetch = manejarErrorFetch;
window.analizarRespuestaServidor = analizarRespuestaServidor;