import { inicializarTema, inicializarPaleta, inicializarEstilo, inicializarFondo, inicializarTextura, inicializarFuente, inicializarEspaciado, inicializarTamano, inicializarRadio, inicializarAnimacion, inicializarGrosor, inicializarSombra, inicializarCliente, mostrarErroresPorURL, cargarPersonalizacionServidor } from './ui/lite.js';
import { SeguridadSistema } from './seguridad.js';
import './api/utilidades.js';
import './api/ListaFiltrable.js';
import './ui/notificaciones.js';
import './ui/navegacion.js';
import './api/formularioAutenticacion.js';
import './api/inicioSesion.js';
import './api/formularioCrud.js';
import { iniciarManejoGlobalErrores } from './api/manejoErrores.js';
import { LiteSse } from './eventos.js';
import './ui/confirmaciones.js';

var COLA_REINTENTOS = [];

function crearBannerOffline()
{
    var b = document.createElement('div');
    b.id = 'banner-offline';
    b.style.cssText = 'display:none;position:fixed;top:0;left:0;right:0;z-index:999999;background:var(--color-peligro,#dc2626);color:#fff;text-align:center;padding:8px 16px;font-size:14px;';
    b.textContent = 'Sin conexion. Los cambios se guardaran cuando recuperes la conexion.';
    document.body.prepend(b);
    return b;
}

var bannerOffline = crearBannerOffline();

window.addEventListener('online', function () {
    bannerOffline.style.display = 'none';
    document.documentElement.classList.remove('offline');
    if (typeof COLA_REINTENTOS !== 'undefined' && COLA_REINTENTOS.length > 0) {
        var pendientes = COLA_REINTENTOS.slice();
        COLA_REINTENTOS = [];
        pendientes.forEach(function (p) {
            fetch(p.url, p.opts).then(function (r) {
                return r.json(); }).then(function (resp) {
                    if (resp.nuevo_token && typeof actualizarTokenCSRF === 'function') {
                        actualizarTokenCSRF(resp.nuevo_token);
                    }
                }).catch(function () {});
        });
    }
});

window.addEventListener('offline', function () {
    bannerOffline.style.display = 'block';
    document.documentElement.classList.add('offline');
});

function aplicarClasesCliente()
{
    var dc = typeof DATOS_CLIENTE !== 'undefined' ? DATOS_CLIENTE : null;
    if (!dc) {
        return;
    }
    var r = document.documentElement;
    if (dc.touch) {
        r.classList.add('touch-device');
    }
    if (dc.online === false) {
        r.classList.add('offline');
    }
    if (dc.conexion === '2g' || dc.conexion === 'slow-2g') {
        r.classList.add('slow-conexion');
    }

}

document.addEventListener('DOMContentLoaded', () => {
    iniciarManejoGlobalErrores();
    inicializarCliente();
    aplicarClasesCliente();
    inicializarTamano();
    inicializarRadio();
    inicializarAnimacion();
    inicializarGrosor();
    inicializarSombra();
    inicializarFuente();
    inicializarEspaciado();
    inicializarPaleta();
    inicializarEstilo();
    inicializarFondo();
    inicializarTextura();
    inicializarTema();
    mostrarErroresPorURL();
    SeguridadSistema.inicializarProtocolos();

    var esLogin = document.getElementById('formularioInicioSesion') !== null;

    if (!esLogin && !window.__liteSse) {
        var apiBase = document.querySelector('meta[name="api-base"]')?.content;
        var metaUrl = apiBase ? apiBase.replace('/api', '/src/sse.php') : 'src/sse.php';
        window.__liteSse = new LiteSse(metaUrl);
        window.liteSse = window.__liteSse;
        window.__liteSse.subscribir('auditoria_alerta', function (datos) {
            var msg = '[' + datos.nivel + '] ' + datos.modulo + ': ' + datos.accion;
            if (datos.ip) {
                msg += ' | IP: ' + datos.ip;
            }
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar(msg, datos.nivel === 'SEGURIDAD' ? 'peligro' : 'advertencia', 8000);
            }
        });
    }

    if (!esLogin && typeof cargarPersonalizacionServidor === 'function') {
        var yaCargada = sessionStorage.getItem('pers-cargada-servidor');
        if (!yaCargada) {
            cargarPersonalizacionServidor();
            sessionStorage.setItem('pers-cargada-servidor', '1');
        }
    }
});