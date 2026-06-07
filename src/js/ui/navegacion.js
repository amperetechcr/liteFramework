var contenidoPrincipal = document.getElementById('contenido-principal');
var tituloOriginal = document.title;
var barraLateral = document.getElementById('barra-lateral');
var capaLateral = document.getElementById('capa-lateral');
var botonMenu = document.getElementById('boton-menu-lateral');
var envoltura = document.getElementById('envoltura-principal');

function cerrarSidebar() {
    if (barraLateral) barraLateral.classList.remove('activa');
    if (capaLateral) capaLateral.classList.remove('activa');
    if (envoltura) envoltura.classList.remove('barra-lateral-activa');
    if (botonMenu) {
        botonMenu.setAttribute('aria-expanded', 'false');
        botonMenu.textContent = '\u2630';
    }
}

function abrirSidebar() {
    if (barraLateral) barraLateral.classList.add('activa');
    if (capaLateral) capaLateral.classList.add('activa');
    if (envoltura) envoltura.classList.add('barra-lateral-activa');
    if (botonMenu) {
        botonMenu.setAttribute('aria-expanded', 'true');
        botonMenu.textContent = '\u00D7';
    }
}

if (botonMenu) {
    botonMenu.addEventListener('click', function() {
        if (barraLateral && barraLateral.classList.contains('activa')) {
            cerrarSidebar();
        } else {
            abrirSidebar();
        }
    });
}

if (capaLateral) {
    capaLateral.addEventListener('click', function() {
        cerrarSidebar();
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && barraLateral && barraLateral.classList.contains('activa')) {
        cerrarSidebar();
    }
});

function ejecutarScripts(contenedor) {
    var scripts = contenedor.querySelectorAll('script');
    scripts.forEach(function(scriptViejo) {
        var scriptNuevo = document.createElement('script');
        if (scriptViejo.src) {
            scriptNuevo.src = scriptViejo.src;
        } else {
            scriptNuevo.textContent = scriptViejo.textContent;
        }
        document.body.appendChild(scriptNuevo);
        scriptViejo.remove();
    });
}

function navegarModulo(url, modulo, esPop) {
    if (!contenidoPrincipal) return;

    var esLento = typeof DATOS_CLIENTE !== 'undefined' && DATOS_CLIENTE &&
        (DATOS_CLIENTE.conexion === '2g' || DATOS_CLIENTE.conexion === 'slow-2g');
    if (esLento) {
        contenidoPrincipal.style.opacity = '0.4';
        contenidoPrincipal.style.transition = 'opacity 0.2s';
    }

    var separador = url.indexOf('?') > -1 ? '&' : '?';
    fetch(url + separador + 'ajax=1', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) {
        if (!res.ok) throw new Error('Error ' + res.status);
        return res.text();
    })
    .then(function(html) {
        contenidoPrincipal.style.opacity = '1';
        contenidoPrincipal.innerHTML = html;

        ejecutarScripts(contenidoPrincipal);

        document.title = tituloOriginal;
        var tituloPagina = contenidoPrincipal.querySelector('[data-titulo-pagina]');
        if (tituloPagina) {
            document.title = tituloPagina.getAttribute('data-titulo-pagina') + ' — Lite Framework';
        }

        if (esPop) {
            window.history.replaceState({ modulo: modulo, url: url }, '', url);
        } else {
            window.history.pushState({ modulo: modulo, url: url }, '', url);
        }

        var enlacesNav = document.querySelectorAll('.barra-lateral-enlace');
        enlacesNav.forEach(function(el) {
            el.classList.remove('enlace-activo');
            el.removeAttribute('aria-current');
            if (el.getAttribute('data-modulo') === modulo) {
                el.classList.add('enlace-activo');
                el.setAttribute('aria-current', 'page');
            }
        });

        cerrarSidebar();
    })
    .catch(function(error) {
        var mensaje = 'Error al cargar el módulo.';

        if (error && error.message) {
            if (error.message.includes('Error 500')) {
                mensaje = 'Error interno al cargar el módulo.';
            } else if (error.message.includes('Error 403')) {
                mensaje = 'No tienes permisos para acceder a este módulo.';
            } else if (error.message.includes('Error 404')) {
                mensaje = 'El módulo solicitado no existe.';
            } else if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                mensaje = 'Error de conexión con el servidor. Verifica tu conexión.';
            } else {
                mensaje = error.message;
            }
        }

        if (typeof window.manejarErrorFetch === 'function') {
            window.manejarErrorFetch(error, mensaje);
        } else if (window.NotificadorHubble) {
            window.NotificadorHubble.mostrar(mensaje, 'peligro', 6000);
        }
    });
}

window.addEventListener('popstate', function(e) {
    if (e.state && e.state.url) {
        navegarModulo(e.state.url, e.state.modulo || '', true);
    }
});

window.navegarModulo = navegarModulo;

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.barra-lateral-enlace').forEach(function(enlace) {
        enlace.addEventListener('click', function(e) {
            var modulo = this.getAttribute('data-modulo');
            if (modulo && typeof window.navegarModulo === 'function') {
                e.preventDefault();
                window.navegarModulo(this.href, modulo);
            }
        });
    });

    cerrarSidebar();
});
