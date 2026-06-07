(function() {
    'use strict';

    var lista;

    function inicializarLista() {
        if (typeof window.ListaFiltrable !== 'function') {
            return setTimeout(inicializarLista, 100);
        }
        lista = new window.ListaFiltrable({
            baseUrl: '/auditoria',
            containerId: 'resultados-auditoria',
            paginationSelector: '#paginacion-auditoria',
            contadorId: 'total-eventos',
            contadorSourceId: 'total-eventos-partial',
            filtros: [
                { id: 'filtro-modulo', paramName: 'modulo' },
                { id: 'filtro-nivel', paramName: 'nivel' },
                { id: 'filtro-ip', paramName: 'ip' },
                { id: 'filtro-desde', paramName: 'desde' },
                { id: 'filtro-hasta', paramName: 'hasta' },
                { id: 'filtro-busqueda', paramName: 'busqueda' }
            ],
            busquedaId: 'filtro-busqueda'
        });
        lista.inicializarEventos();
        lista.sincronizarConUrl();
        lista.vincularPaginacion();
    }

    var modalDetalle = document.getElementById('modal-detalle-auditoria');
    var cuerpoDetalle = document.getElementById('detalle-auditoria-cuerpo');

    function abrirModal(modal) {
        if (!modal) return;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal(modal) {
        if (!modal) return;
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    function cerrarTodosLosModales() {
        document.querySelectorAll('.modal-superposicion').forEach(function(m) {
            m.hidden = true;
        });
        document.body.style.overflow = '';
    }

    var codigosHttp = {
        200: 'OK', 201: 'Creado', 204: 'Sin contenido',
        301: 'Movido permanentemente', 302: 'Encontrado', 304: 'No modificado',
        400: 'Solicitud incorrecta', 401: 'No autorizado', 403: 'Prohibido',
        404: 'No encontrado', 405: 'Metodo no permitido', 409: 'Conflicto',
        413: 'Solicitud demasiado grande', 415: 'Tipo no soportado',
        422: 'Entidad no procesable', 429: 'Demasiadas solicitudes',
        500: 'Error interno del servidor', 502: 'Puerta de enlace erronea',
        503: 'Servicio no disponible', 504: 'Tiempo de espera agotado'
    };

    function formatearBytes(v) {
        if (v === 0) return '0 B';
        var u = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(v) / Math.log(1024));
        i = Math.min(i, u.length - 1);
        return (v / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1) + ' ' + u[i];
    }

    function formatearDuracion(ms) {
        if (ms < 1) return '< 1 ms';
        if (ms < 1000) return ms.toFixed(1) + ' ms';
        return (ms / 1000).toFixed(2) + ' s';
    }

    function formatearCodigoHttp(c) {
        c = parseInt(c, 10);
        var txt = codigosHttp[c];
        return txt ? c + ' ' + txt : String(c);
    }

    function formatearTimestamp(iso) {
        try {
            var d = new Date(iso);
            if (isNaN(d.getTime())) return iso;
            var pad = function(n) { return n < 10 ? '0' + n : String(n); };
            return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear()
                + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
        } catch (e) {
            return iso;
        }
    }

    function truncar(s, max) {
        return s.length > max ? s.substring(0, max) + '...' : s;
    }

    function esEntero(v) {
        return typeof v === 'number' && v % 1 === 0;
    }

    function renderizarObjetoTabla(obj) {
        var html = '<div class="margen-superior-pequeno fondo-pantalla" style="padding:8px;border-radius:4px;font-size:12px;overflow:auto;max-height:300px"><table style="width:100%;border-collapse:collapse">';
        for (var k in obj) {
            if (!obj.hasOwnProperty(k)) continue;
            var v = obj[k];
            var vStr = typeof v === 'object' && v !== null ? JSON.stringify(v) : String(v);
            html += '<tr><td style="padding:2px 6px;border-bottom:1px solid var(--trazo-suave, #e5e7eb);color:var(--texto-suave, #6b7280);white-space:nowrap">' + k + '</td><td style="padding:2px 6px;border-bottom:1px solid var(--trazo-suave, #e5e7eb)">' + truncar(vStr, 200) + '</td></tr>';
        }
        html += '</table></div>';
        return html;
    }

    function formatearCampo(clave, valor) {
        var etiqueta = '<span class="texto-negrita">' + clave.replace(/_/g, ' ') + ':</span>';

        if (valor === undefined || valor === null || valor === '') {
            return '<div class="agrupador-flexible-filas distribucion-espaciada margen-inferior-pequeno">' + etiqueta + '<span class="texto-suave">No disponible</span></div>';
        }

        var valorFormateado;

        switch (clave) {
            case 'trace_id':
                valorFormateado = '<span title="' + valor + '">' + truncar(valor, 12) + '</span>';
                break;
            case 'session_id':
                valorFormateado = '<span title="' + valor + '">' + truncar(valor, 12) + '</span>';
                break;
            case 'timestamp':
                valorFormateado = formatearTimestamp(valor);
                break;
            case 'codigo_respuesta':
                valorFormateado = formatearCodigoHttp(valor);
                break;
            case 'tamano_bytes':
                valorFormateado = formatearBytes(parseInt(valor, 10));
                break;
            case 'duracion_ms':
                valorFormateado = formatearDuracion(parseFloat(valor));
                break;
            case 'ip':
                valorFormateado = String(valor);
                break;
            case 'host':
                valorFormateado = String(valor);
                break;
            default:
                valorFormateado = String(valor).substring(0, 300);
        }

        if (typeof valor === 'object' && !Array.isArray(valor)) {
            var tab = renderizarObjetoTabla(valor);
            return '<div class="margen-inferior-pequeno">' + etiqueta + tab + '</div>';
        }

        if (Array.isArray(valor)) {
            var arrStr = JSON.stringify(valor).substring(0, 200);
            return '<div class="margen-inferior-pequeno">' + etiqueta + '<span class="texto-pequeno">' + arrStr + '</span></div>';
        }

        return '<div class="agrupador-flexible-filas distribucion-espaciada margen-inferior-pequeno">' + etiqueta + '<span>' + valorFormateado + '</span></div>';
    }

    function mostrarDetalle(detalle) {
        if (!cuerpoDetalle) return;
        var secciones = [
            { titulo: 'Generales', campos: ['trace_id', 'nivel', 'timestamp', 'ip', 'agente_usuario'] },
            { titulo: 'Servidor', campos: ['host', 'metodo', 'ruta', 'codigo_respuesta', 'tamano_bytes', 'duracion_ms'] },
            { titulo: 'Seguridad', campos: ['session_id', 'http_referer', 'operador_id', 'operador_nombre'] },
            { titulo: 'Datos cliente', campos: ['cliente'] },
            { titulo: 'Solicitud', campos: ['parametros_solicitud'] },
            { titulo: 'Detalle adicional', campos: ['detalle'] }
        ];
        var html = '';
        secciones.forEach(function(sec) {
            var items = '';
            sec.campos.forEach(function(c) {
                if (detalle[c] !== undefined) {
                    items += formatearCampo(c, detalle[c]);
                }
            });
            if (items) {
                html += '<div class="margen-inferior-normal">';
                html += '<h4 class="margen-inferior-pequeno texto-color-marca" style="font-size:13px;text-transform:uppercase;letter-spacing:0.5px">' + sec.titulo + '</h4>';
                html += items;
                html += '</div>';
            }
        });
        cuerpoDetalle.innerHTML = html;
        abrirModal(modalDetalle);
    }

    function enlazarDetalle() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-detalle-evento');
            if (!btn) return;
            try {
                var detalle = JSON.parse(btn.getAttribute('data-detalle') || '{}');
                mostrarDetalle(detalle);
            } catch (err) {}
        });
    }

    document.querySelectorAll('.modal-cerrar').forEach(function(btn) {
        btn.addEventListener('click', cerrarTodosLosModales);
    });

    if (modalDetalle) {
        modalDetalle.addEventListener('click', function(e) {
            if (e.target === modalDetalle) cerrarModal(modalDetalle);
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var abierto = document.querySelector('.modal-superposicion:not([hidden])');
            if (abierto) cerrarTodosLosModales();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { inicializarLista(); enlazarDetalle(); });
    } else {
        inicializarLista();
        enlazarDetalle();
    }
})();