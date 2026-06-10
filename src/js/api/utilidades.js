var metaRuta = document.querySelector('meta[name="api-base"]');
window.rutaApi = metaRuta ? metaRuta.getAttribute('content') : (window.location.pathname.replace(/\/[^/]+$/, '') || '') + '/api';

export function obtenerBasePath()
{
    return window.location.pathname.replace(/\/[^/]+$/, '') || '';
}

export function obtenerTokenCSRF()
{
    var meta = document.querySelector('meta[name="csrf-token"]') ||
        document.querySelector('input[name="csrf_token"]') ||
        document.querySelector('input[name="token_peticion"]');
    return meta ? (meta.content || meta.value || '') : '';
}

export function notificar(mensaje, tipo)
{
    if (window.NotificadorHubble) {
        window.NotificadorHubble.mostrar(mensaje, tipo);
    }
}

export function enriquecerPayload(payload)
{
    if (typeof payload === 'object' && payload !== null && typeof DATOS_CLIENTE !== 'undefined' && DATOS_CLIENTE) {
        payload._cliente = DATOS_CLIENTE;
    }
    return payload;
}

var fetchOriginal = window.fetch;
window.fetch = function (url, opts) {
    if (!opts) {
        opts = {};
    }
    if (!opts.headers) {
        opts.headers = {};
    }
    opts.headers['ngrok-skip-browser-warning'] = 'true';
    if (opts && opts.body && typeof opts.body === 'string') {
        try {
            var payload = JSON.parse(opts.body);
            if (typeof payload === 'object' && payload.accion_crud) {
                enriquecerPayload(payload);
                opts.body = JSON.stringify(payload);
            }
        } catch (e) {
        }
    }
    if (navigator.onLine === false && opts && opts.method !== 'GET') {
        if (typeof COLA_REINTENTOS !== 'undefined') {
            COLA_REINTENTOS.push({ url: url, opts: opts });
            if (typeof window.notificar === 'function') {
                window.notificar('Peticion encolada. Se reenviara cuando tengas conexion.', 'advertencia');
            }
        }
        return Promise.resolve({ json: function () {
            return Promise.resolve({ estado_operacion: false }); } });
    }
    return fetchOriginal.call(window, url, opts);
};

window.obtenerBasePath = obtenerBasePath;
window.obtenerTokenCSRF = obtenerTokenCSRF;
window.notificar = notificar;
window.enriquecerPayload = enriquecerPayload;
