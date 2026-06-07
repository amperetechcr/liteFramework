var SOL = '\u2600';
var LUNA = '\u263E';

var DATOS_CLIENTE = null;

function inicializarCliente()
{
    if (DATOS_CLIENTE) {
        return;
    }
    try {
        DATOS_CLIENTE = {
            screen: (screen.width || 0) + 'x' + (screen.height || 0),
            viewport: (window.innerWidth || 0) + 'x' + (window.innerHeight || 0),
            pixel_ratio: window.devicePixelRatio || 1,
            color_depth: screen.colorDepth || null,
            conexion: navigator.connection ? navigator.connection.effectiveType : null,
            memoria: navigator.deviceMemory || null,
            cpu: navigator.hardwareConcurrency || null,
            timezone: new Date().getTimezoneOffset(),
            idiomas: navigator.languages ? navigator.languages.join(',') : null,
            online: navigator.onLine,
            cookies: navigator.cookieEnabled,
            touch: 'ontouchstart' in window,
            plataforma: navigator.platform || null
        };
    } catch (e) {
    }
}

const MAPA_ERRORES_URL = {
    'acceso_denegado': 'Credenciales incorrectas. Verifique su correo y contrase\u00F1a.',
    'cuenta_suspendida': 'Esta cuenta ha sido deshabilitada. Contacte al administrador.',
    'datos_invalidos': 'Los datos enviados no son v\u00E1lidos. Verifique los campos requeridos.',
    'token_invalido': 'Su sesi\u00F3n ha expirado por seguridad. Recargue la p\u00E1gina.',
    'error_interno': 'Error interno del servidor. Intente nuevamente m\u00E1s tarde.',
    'sesion_invalida_o_secuestrada': 'Sesi\u00F3n invalidada por medidas de seguridad activadas.',
    'privilegios_insuficientes': 'No tiene permisos suficientes para acceder a este recurso.',
    'sesion_expirada': 'Su sesi\u00F3n ha expirado. Inicie sesi\u00F3n nuevamente.',
    'sesion_invalida': 'Sesi\u00F3n inv\u00E1lida. Debe iniciar sesi\u00F3n nuevamente.'
};

const MAPA_MENSAJES_URL = {
    'registro_exitoso': 'Registro completado exitosamente. Ya puede iniciar sesi\u00F3n con su nueva cuenta.',
    'sesion_finalizada': 'Ha cerrado sesi\u00F3n correctamente. Vuelva pronto.',
    'respaldo_eliminado': 'Respaldo eliminado correctamente.',
    'respaldo_restaurado': 'Base de datos restaurada exitosamente desde el respaldo.'
};

function inicializarTema()
{
    const raiz = document.documentElement;

    function aplicarTema(oscuro)
    {
        raiz.classList.remove('forzar-iluminacion-clara', 'forzar-iluminacion-oscura');
        raiz.classList.add(oscuro ? 'forzar-iluminacion-oscura' : 'forzar-iluminacion-clara');
        var alternador = document.getElementById('alternador-tema');
        if (alternador) {
            alternador.textContent = oscuro ? LUNA : SOL;
        }
    }

    const temaGuardado = localStorage.getItem('tema-lite-framework');

    if (temaGuardado === 'oscuro') {
        aplicarTema(true);
    } else if (temaGuardado === 'claro') {
        aplicarTema(false);
    } else {
        const oscuro = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        aplicarTema(oscuro);
    }

    var alternador = document.getElementById('alternador-tema');
    if (alternador) {
        alternador.addEventListener('click', function () {
            const esOscuro = raiz.classList.contains('forzar-iluminacion-oscura');
            aplicarTema(!esOscuro);
            localStorage.setItem('tema-lite-framework', esOscuro ? 'claro' : 'oscuro');
            if (typeof window.actualizarColoresSwatch === 'function') {
                window.actualizarColoresSwatch();
            }
        });
    }
}

function mostrarErroresPorURL()
{
    const cuerpoPagina = document.body;
    if (!cuerpoPagina) {
        return;
    }

    const codigoError = cuerpoPagina.getAttribute('data-error') || '';
    const codigoMensaje = cuerpoPagina.getAttribute('data-mensaje') || '';

    if (codigoError && MAPA_ERRORES_URL[codigoError]) {
        if (window.NotificadorHubble) {
            window.NotificadorHubble.mostrar(MAPA_ERRORES_URL[codigoError], 'peligro', 8000);
        }
    }

    if (codigoMensaje && MAPA_MENSAJES_URL[codigoMensaje]) {
        if (window.NotificadorHubble) {
            window.NotificadorHubble.mostrar(MAPA_MENSAJES_URL[codigoMensaje], 'exito', 5000);
        }
    }
}

function alternarEstadoCarga(formulario, activo, elemento)
{
    elemento = elemento || formulario.querySelector('button[type="submit"]');
    if (!elemento) {
        return;
    }

    if (activo) {
        elemento.disabled = true;
        var textoOriginal = elemento.textContent || elemento.innerText;
        elemento.setAttribute('data-texto-original', textoOriginal);
        elemento.classList.add('cargando');
    } else {
        elemento.disabled = false;
        elemento.classList.remove('cargando');
        var textoGuardado = elemento.getAttribute('data-texto-original');
        if (textoGuardado) {
            elemento.textContent = textoGuardado;
        }
    }
}

function aplicarFondo(nombre)
{
    var raiz = document.documentElement;
    raiz.className = raiz.className.replace(/\bfondo-\S+/g, '').trim();
    raiz.classList.add('fondo-' + nombre);
    try {
        localStorage.setItem('fondo-lite-framework', nombre || 'blanco'); } catch (e) {
        }
}

function inicializarFondo()
{
    try {
        var guardado = localStorage.getItem('fondo-lite-framework');
        if (guardado) {
            aplicarFondo(guardado);
        }
    } catch (e) {
    }
}

function aplicarTextura(nombre)
{
    var raiz = document.documentElement;
    raiz.className = raiz.className.replace(/\btextura-\S+/g, '').trim();
    raiz.classList.add('textura-' + nombre);
    try {
        localStorage.setItem('textura-lite-framework', nombre || 'ninguna'); } catch (e) {
        }
}

function inicializarTextura()
{
    try {
        var guardado = localStorage.getItem('textura-lite-framework');
        if (guardado) {
            aplicarTextura(guardado);
        }
    } catch (e) {
    }
}

function aplicarFuente(nombre)
{
    var raiz = document.documentElement;
    raiz.className = raiz.className.replace(/\bfuente-\S+/g, '').trim();
    if (nombre !== 'sistema') {
        raiz.classList.add('fuente-' + nombre);
    }
    try {
        localStorage.setItem('fuente-lite-framework', nombre); } catch (e) {
        }
}

function inicializarFuente()
{
    try {
        var guardado = localStorage.getItem('fuente-lite-framework');
        if (guardado && guardado !== 'sistema') {
            aplicarFuente(guardado);
        }
    } catch (e) {
    }
}

function aplicarEspaciado(nombre)
{
    var raiz = document.documentElement;
    raiz.className = raiz.className.replace(/\bespaciado-\S+/g, '').trim();
    if (nombre !== 'normal') {
        raiz.classList.add('espaciado-' + nombre);
    }
    try {
        localStorage.setItem('espaciado-lite-framework', nombre); } catch (e) {
        }
}

function inicializarEspaciado()
{
    try {
        var guardado = localStorage.getItem('espaciado-lite-framework');
        if (guardado && guardado !== 'normal') {
            aplicarEspaciado(guardado);
        }
    } catch (e) {
    }
}

function aplicarTamano(nombre)
{
    var raiz = document.documentElement;
    raiz.className = raiz.className.replace(/\btamano-\S+/g, '').trim();
    if (nombre !== 'normal') {
        raiz.classList.add('tamano-' + nombre);
    }
    try {
        localStorage.setItem('tamano-lite-framework', nombre); } catch (e) {
        }
}

function inicializarTamano()
{
    try {
        var guardado = localStorage.getItem('tamano-lite-framework');
        if (guardado && guardado !== 'normal') {
            aplicarTamano(guardado);
        }
    } catch (e) {
    }
}

function aplicarRadio(nombre)
{
    var raiz = document.documentElement;
    raiz.className = raiz.className.replace(/\bradio-\S+/g, '').trim();
    if (nombre !== 'normal') {
        raiz.classList.add('radio-' + nombre);
    }
    try {
        localStorage.setItem('radio-lite-framework', nombre); } catch (e) {
        }
}

function inicializarRadio()
{
    try {
        var guardado = localStorage.getItem('radio-lite-framework');
        if (guardado && guardado !== 'normal') {
            aplicarRadio(guardado);
        }
    } catch (e) {
    }
}

function aplicarAnimacion(nombre)
{
    var raiz = document.documentElement;
    raiz.className = raiz.className.replace(/\banimacion-\S+/g, '').trim();
    if (nombre !== 'normal') {
        raiz.classList.add('animacion-' + nombre);
    }
    try {
        localStorage.setItem('animacion-lite-framework', nombre); } catch (e) {
        }
}

function inicializarAnimacion()
{
    try {
        var guardado = localStorage.getItem('animacion-lite-framework');
        if (guardado && guardado !== 'normal') {
            aplicarAnimacion(guardado);
        }
    } catch (e) {
    }
}

function aplicarGrosor(nombre)
{
    var raiz = document.documentElement;
    raiz.className = raiz.className.replace(/\bgrosor-\S+/g, '').trim();
    if (nombre !== 'normal') {
        raiz.classList.add('grosor-' + nombre);
    }
    try {
        localStorage.setItem('grosor-lite-framework', nombre); } catch (e) {
        }
}

function inicializarGrosor()
{
    try {
        var guardado = localStorage.getItem('grosor-lite-framework');
        if (guardado && guardado !== 'normal') {
            aplicarGrosor(guardado);
        }
    } catch (e) {
    }
}

function aplicarSombra(nombre)
{
    var raiz = document.documentElement;
    raiz.className = raiz.className.replace(/\bsombra-\S+/g, '').trim();
    if (nombre !== 'normal') {
        raiz.classList.add('sombra-' + nombre);
    }
    try {
        localStorage.setItem('sombra-lite-framework', nombre); } catch (e) {
        }
}

function inicializarSombra()
{
    try {
        var guardado = localStorage.getItem('sombra-lite-framework');
        if (guardado && guardado !== 'normal') {
            aplicarSombra(guardado);
        }
    } catch (e) {
    }
}

function aplicarPaleta(nombre)
{
    var raiz = document.documentElement;
    raiz.className = raiz.className.replace(/\bpaleta-\S+/g, '').trim();
    raiz.classList.add('paleta-' + nombre);
    try {
        localStorage.setItem('paleta-lite-framework', nombre); } catch (e) {
        }
}

function aplicarEstilo(nombre)
{
    var raiz = document.documentElement;
    raiz.className = raiz.className.replace(/\bestilo-\S+/g, '').trim();
    raiz.classList.add('estilo-' + nombre);
    try {
        localStorage.setItem('estilo-lite-framework', nombre); } catch (e) {
        }
}

function inicializarPaleta()
{
    try {
        var paletaGuardada = localStorage.getItem('paleta-lite-framework');
        if (paletaGuardada) {
            aplicarPaleta(paletaGuardada);
        }
    } catch (e) {
    }
}

function inicializarEstilo()
{
    try {
        var estiloGuardado = localStorage.getItem('estilo-lite-framework');
        if (estiloGuardado) {
            aplicarEstilo(estiloGuardado);
        }
    } catch (e) {
    }
}

function restablecerPersonalizacion()
{
    aplicarPaleta('indigo');
    aplicarEstilo('moderno');
    aplicarFondo('blanco');
    aplicarTextura('ninguna');
    aplicarFuente('sistema');
    aplicarEspaciado('normal');
    aplicarTamano('normal');
    aplicarRadio('normal');
    aplicarAnimacion('normal');
    aplicarGrosor('normal');
    aplicarSombra('normal');
    ['tema-lite-framework', 'paleta-lite-framework', 'estilo-lite-framework',
     'fondo-lite-framework', 'textura-lite-framework',
     'fuente-lite-framework',
     'espaciado-lite-framework', 'tamano-lite-framework',
     'radio-lite-framework', 'animacion-lite-framework',
     'grosor-lite-framework', 'sombra-lite-framework'].forEach(function (k) {
        try {
            localStorage.removeItem(k); } catch (e) {
            }
     });
    var raiz = document.documentElement;
    raiz.classList.remove('forzar-iluminacion-clara', 'forzar-iluminacion-oscura');
    var alternador = document.getElementById('alternador-tema');
    if (alternador) {
        var oscuro = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        raiz.classList.remove('forzar-iluminacion-clara', 'forzar-iluminacion-oscura');
        raiz.classList.add(oscuro ? 'forzar-iluminacion-oscura' : 'forzar-iluminacion-clara');
        alternador.textContent = oscuro ? LUNA : SOL;
    }
}

function obtenerConfiguracionUI()
{
    try {
        return {
            paleta: localStorage.getItem('paleta-lite-framework') || 'indigo',
            estilo: localStorage.getItem('estilo-lite-framework') || 'moderno',
            fondo: localStorage.getItem('fondo-lite-framework') || 'blanco',
            textura: localStorage.getItem('textura-lite-framework') || 'ninguna',
            fuente: localStorage.getItem('fuente-lite-framework') || 'sistema',
            espaciado: localStorage.getItem('espaciado-lite-framework') || 'normal',
            tamano: localStorage.getItem('tamano-lite-framework') || 'normal',
            radio: localStorage.getItem('radio-lite-framework') || 'normal',
            animacion: localStorage.getItem('animacion-lite-framework') || 'normal',
            grosor: localStorage.getItem('grosor-lite-framework') || 'normal',
            sombra: localStorage.getItem('sombra-lite-framework') || 'normal',
            tema: localStorage.getItem('tema-lite-framework') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'oscuro' : 'claro')
        };
    } catch (e) {
        return { paleta: 'indigo', estilo: 'moderno', fondo: 'blanco', textura: 'ninguna', fuente: 'sistema', espaciado: 'normal', tamano: 'normal', radio: 'normal', animacion: 'normal', grosor: 'normal', sombra: 'normal', tema: 'claro' };
    }
}

function csrfToken()
{
    if (typeof window.obtenerTokenCSRF === 'function') {
        return window.obtenerTokenCSRF();
    }
    var meta = document.querySelector('meta[name="csrf-token"]') ||
        document.querySelector('input[name="token_peticion"]');
    return meta ? meta.value : '';
}

function actualizarTokenCSRF(nuevoToken)
{
    if (!nuevoToken) {
        return;
    }
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        meta.setAttribute('content', nuevoToken); }
    var input = document.querySelector('input[name="token_peticion"]');
    if (input) {
        input.value = nuevoToken; }
}

function guardarPersonalizacionServidor()
{
    var config = obtenerConfiguracionUI();
    var rutaApi = window.rutaApi || '/api';
    return fetch(rutaApi, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken(),
        },
        body: JSON.stringify({
            accion_crud: 'guardar_personalizacion_ui',
            token_peticion: csrfToken(),
            paleta: config.paleta,
            estilo: config.estilo,
            fondo: config.fondo,
            textura: config.textura,
            fuente: config.fuente,
            espaciado: config.espaciado,
            tamano: config.tamano,
            radio: config.radio,
            animacion: config.animacion,
            grosor: config.grosor,
            sombra: config.sombra,
            tema: config.tema
        })
    })
    .then(function (r) {
        return r.json(); })
    .then(function (resp) {
        actualizarTokenCSRF(resp.nuevo_token);
        if (resp.estado_operacion && window.NotificadorHubble) {
            window.NotificadorHubble.mostrar('Preferencias guardadas en el servidor.', 'exito', 4000);
        } else if (!resp.estado_operacion && window.NotificadorHubble) {
            window.NotificadorHubble.mostrar(resp.mensaje_error || 'Error al guardar preferencias.', 'peligro', 5000);
        }
        return resp;
    })
    .catch(function (err) {
        if (window.NotificadorHubble) {
            window.NotificadorHubble.mostrar('Error de red al guardar preferencias.', 'peligro', 5000);
        }
    });
}

function cargarPersonalizacionServidor()
{
    var rutaApi = window.rutaApi || '/api';
    return fetch(rutaApi, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken(),
        },
        body: JSON.stringify({
            accion_crud: 'obtener_personalizacion_ui',
            token_peticion: csrfToken()
        })
    })
    .then(function (r) {
        return r.json(); })
    .then(function (resp) {
        if (resp.nuevo_token) {
            actualizarTokenCSRF(resp.nuevo_token);
        }
        if (resp.estado_operacion && resp.datos && Object.keys(resp.datos).length > 0) {
            var d = resp.datos;
            localStorage.setItem('paleta-lite-framework', d.paleta || 'indigo');
            localStorage.setItem('estilo-lite-framework', d.estilo || 'moderno');
            localStorage.setItem('fondo-lite-framework', d.fondo || 'blanco');
            localStorage.setItem('textura-lite-framework', d.textura || 'ninguna');
            localStorage.setItem('fuente-lite-framework', d.fuente || 'sistema');
            localStorage.setItem('espaciado-lite-framework', d.espaciado || 'normal');
            localStorage.setItem('tamano-lite-framework', d.tamano || 'normal');
            localStorage.setItem('radio-lite-framework', d.radio || 'normal');
            localStorage.setItem('animacion-lite-framework', d.animacion || 'normal');
            localStorage.setItem('tema-lite-framework', d.tema || 'claro');
            if (d.fondo) {
                aplicarFondo(d.fondo);
            }
            if (d.textura && d.textura !== 'ninguna') {
                aplicarTextura(d.textura);
            }
            if (d.paleta) {
                aplicarPaleta(d.paleta);
            }
            if (d.estilo) {
                aplicarEstilo(d.estilo);
            }
            if (d.fuente) {
                aplicarFuente(d.fuente);
            }
            if (d.espaciado) {
                aplicarEspaciado(d.espaciado);
            }
            if (d.tamano) {
                aplicarTamano(d.tamano);
            }
            if (d.radio) {
                aplicarRadio(d.radio);
            }
            if (d.animacion) {
                aplicarAnimacion(d.animacion);
            }
            if (d.tema) {
                var raiz = document.documentElement;
                raiz.classList.remove('forzar-iluminacion-clara', 'forzar-iluminacion-oscura');
                raiz.classList.add(d.tema === 'oscuro' ? 'forzar-iluminacion-oscura' : 'forzar-iluminacion-clara');
                var alternador = document.getElementById('alternador-tema');
                if (alternador) {
                    alternador.textContent = d.tema === 'oscuro' ? LUNA : SOL;
                }
            }
        }
        return resp;
    })
    .catch(function () {});
}

function alternarTransicionSuave(activo)
{
    var raiz = document.documentElement;
    if (activo) {
        raiz.classList.add('transicion-personalizacion');
    } else {
        raiz.classList.remove('transicion-personalizacion');
    }
}

window.aplicarPaleta = aplicarPaleta;
window.aplicarEstilo = aplicarEstilo;
window.aplicarFondo = aplicarFondo;
window.aplicarFuente = aplicarFuente;
window.aplicarEspaciado = aplicarEspaciado;
window.aplicarTamano = aplicarTamano;
window.aplicarRadio = aplicarRadio;
window.aplicarAnimacion = aplicarAnimacion;
window.aplicarGrosor = aplicarGrosor;
window.aplicarSombra = aplicarSombra;
window.aplicarTextura = aplicarTextura;
window.restablecerPersonalizacion = restablecerPersonalizacion;
window.obtenerConfiguracionUI = obtenerConfiguracionUI;
window.alternarTransicionSuave = alternarTransicionSuave;
window.alternarEstadoCarga = alternarEstadoCarga;
window.guardarPersonalizacionServidor = guardarPersonalizacionServidor;
window.cargarPersonalizacionServidor = cargarPersonalizacionServidor;
export { alternarEstadoCarga, inicializarTema, inicializarPaleta, inicializarEstilo, inicializarFondo, inicializarTextura, inicializarFuente, inicializarEspaciado, inicializarTamano, inicializarRadio, inicializarAnimacion, inicializarGrosor, inicializarSombra, inicializarCliente, aplicarPaleta, aplicarEstilo, aplicarFondo, aplicarTextura, aplicarFuente, aplicarEspaciado, aplicarTamano, aplicarRadio, aplicarAnimacion, aplicarGrosor, aplicarSombra, restablecerPersonalizacion, obtenerConfiguracionUI, alternarTransicionSuave, guardarPersonalizacionServidor, cargarPersonalizacionServidor, mostrarErroresPorURL, MAPA_ERRORES_URL, MAPA_MENSAJES_URL };
