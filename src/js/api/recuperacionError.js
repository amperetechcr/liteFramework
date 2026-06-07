(function() {
    'use strict';

    var RecuperacionError = {
        _requestPendiente: null,

        ejecutarDesdeRespuesta: function(respuesta) {
            if (!respuesta) return;

            if (respuesta.accion && respuesta.accion.tipo) {
                this.ejecutarAccion(respuesta.accion, respuesta);
                return;
            }

            if (respuesta.sugerencias && respuesta.sugerencias.length > 0) {
                this.mostrarSugerencias(respuesta);
            }
        },

        ejecutarAccion: function(accion, respuesta) {
            switch (accion.tipo) {
                case 'regenerar_token':
                    this.regenerarToken(respuesta);
                    break;
                case 'redirigir_login':
                    this.redirigirLogin(respuesta);
                    break;
                case 'redireccion':
                    this.redirigir(accion);
                    break;
                case 'recargar':
                    window.location.reload();
                    break;
                default:
                    if (respuesta.sugerencias && respuesta.sugerencias.length > 0) {
                        this.mostrarSugerencias(respuesta);
                    }
            }
        },

        regenerarToken: function(respuesta) {
            var requestOriginal = this._requestPendiente;
            fetch('/api/refrescar-token', { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.nuevo_token) {
                        var meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) meta.setAttribute('content', data.nuevo_token);
                        var inputs = document.querySelectorAll('input[name="csrf_token"]');
                        inputs.forEach(function(inp) { inp.value = data.nuevo_token; });
                        if (window.actualizarTokenCSRF) window.actualizarTokenCSRF(data.nuevo_token);
                    }
                    if (typeof requestOriginal === 'function') {
                        requestOriginal();
                    }
                })
                .catch(function() {
                    if (window.NotificadorHubble) {
                        window.NotificadorHubble.mostrar('No se pudo renovar el token de seguridad. Recargue la pagina.', 'peligro', 6000);
                    }
                });
        },

        redirigirLogin: function(respuesta) {
            var destino = '/ingreso';
            var redirect = window.location.pathname + window.location.search;
            if (redirect !== destino && redirect !== '/') {
                destino += (destino.indexOf('?') === -1 ? '?' : '&') + 'redirect=' + encodeURIComponent(redirect);
            }
            window.location.href = destino;
        },

        redirigir: function(accion) {
            var destino = accion.destino || '/';
            window.location.href = destino;
        },

        mostrarSugerencias: function(respuesta) {
            if (!respuesta.sugerencias || !respuesta.sugerencias.length) return;
            var msg = respuesta.mensaje_error || '';
            var tipo = 'peligro';
            if (window.NotificadorHubble) {
                if (typeof window.NotificadorHubble.mostrarConSugerencias === 'function') {
                    window.NotificadorHubble.mostrarConSugerencias(msg, respuesta.sugerencias, tipo, 10000);
                } else {
                    var html = msg ? '<p>' + msg + '</p>' : '';
                    html += '<ul style="margin:8px 0 0 0;padding-left:16px;font-size:12px;text-align:left">';
                    respuesta.sugerencias.forEach(function(s) {
                        html += '<li style="margin-bottom:4px">' + s + '</li>';
                    });
                    html += '</ul>';
                    window.NotificadorHubble.mostrar(html, tipo, 10000);
                }
            }
        }
    };

    window.RecuperacionError = RecuperacionError;
})();
