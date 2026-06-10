(function () {
    'use strict';

    function csrfToken()
    {
        if (typeof window.obtenerTokenCSRF === 'function') {
            return window.obtenerTokenCSRF();
        }
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            return meta.getAttribute('content');
        }
        var input = document.querySelector('[name="token_peticion"]');
        return input ? input.value : '';
    }

    function notificar(mensaje, tipo)
    {
        if (window.NotificadorHubble && typeof window.NotificadorHubble.mostrar === 'function') {
            window.NotificadorHubble.mostrar(mensaje, tipo);
        }
    }

    window.litePdf = {
        imprimir: function () {
            window.print();
        },

        imprimirPlantilla: function (idPlantilla) {
            if (!idPlantilla) {
                return;
            }
            var url = (window.ubicacionBase || '') + '/generadorPdf/imprimir/' + idPlantilla;
            window.open(url, '_blank', 'width=900,height=700');
        },

        guardarPlantilla: async function (titulo, contenidoHtml) {
            if (!titulo || !contenidoHtml) {
                notificar('Titulo y contenido son obligatorios.', 'advertencia');
                return null;
            }

            var token = csrfToken();

            try {
                var res = await fetch((window.rutaApi || '/api'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': token
                    },
                    body: 'accion_crud=crud&entidad=documento_pdf&accion=crear' +
                        '&titulo=' + encodeURIComponent(titulo) +
                        '&contenido_html=' + encodeURIComponent(contenidoHtml) +
                        '&token_peticion=' + encodeURIComponent(token)
                });

                var resultado = await res.json();

                if (resultado.nuevo_token) {
                    document.querySelectorAll('[name="token_peticion"]').forEach(function (el) {
                        el.value = resultado.nuevo_token;
                    });
                }

                if (resultado.estado_operacion === true) {
                    notificar('Plantilla guardada correctamente.', 'exito');
                    return resultado.id || null;
                } else {
                    var msg = resultado.mensaje_error || 'Error al guardar la plantilla.';
                    notificar(msg, 'peligro');
                    return null;
                }
            } catch (e) {
                notificar('Error de conexion con el servidor.', 'peligro');
                return null;
            }
        },

        obtenerTokenCSRF: csrfToken
    };

})();
