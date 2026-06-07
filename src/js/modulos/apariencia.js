(function() {
    'use strict';

    function esperarLite(cb) {
        if (typeof window.aplicarPaleta === 'function') { cb(); return; }
        setTimeout(function() { esperarLite(cb); }, 80);
    }

    function leer(clave, defecto) {
        try { return localStorage.getItem(clave) || defecto; } catch(e) { return defecto; }
    }

    var temporizadorGuardado = null;

    function guardarAlServidor() {
        clearTimeout(temporizadorGuardado);
        temporizadorGuardado = setTimeout(function() {
            if (typeof window.guardarPersonalizacionServidor === 'function') {
                window.guardarPersonalizacionServidor();
            }
        }, 600);
    }

    function syncTodo() {
        var paleta = leer('paleta-lite-framework', 'indigo');
        var estilo = leer('estilo-lite-framework', 'moderno');
        var radio = leer('radio-lite-framework', 'normal');
        var animacion = leer('animacion-lite-framework', 'normal');
        var fuente = leer('fuente-lite-framework', 'sistema');
        var espaciado = leer('espaciado-lite-framework', 'normal');
        var tamano = leer('tamano-lite-framework', 'normal');
        var grosor = leer('grosor-lite-framework', 'normal');
        var sombra = leer('sombra-lite-framework', 'normal');
        var fondo = leer('fondo-lite-framework', 'blanco');
        var textura = leer('textura-lite-framework', 'ninguna');

        document.querySelectorAll('.apariencia-color[data-paleta]').forEach(function(b) {
            b.textContent = b.getAttribute('data-paleta') === paleta ? '\u2713' : '';
            b.classList.toggle('activo', b.getAttribute('data-paleta') === paleta);
        });

        document.querySelectorAll('.apariencia-pill[data-estilo]').forEach(function(b) {
            b.classList.toggle('activo', b.getAttribute('data-estilo') === estilo);
        });

        document.querySelectorAll('.apariencia-pill[data-radio]').forEach(function(b) {
            b.classList.toggle('activo', b.getAttribute('data-radio') === radio);
        });

        document.querySelectorAll('.apariencia-pill[data-animacion]').forEach(function(b) {
            b.classList.toggle('activo', b.getAttribute('data-animacion') === animacion);
        });

        document.querySelectorAll('.apariencia-pill[data-espaciado]').forEach(function(b) {
            b.classList.toggle('activo', b.getAttribute('data-espaciado') === espaciado);
        });

        document.querySelectorAll('.apariencia-pill[data-tamano]').forEach(function(b) {
            b.classList.toggle('activo', b.getAttribute('data-tamano') === tamano);
        });

        document.querySelectorAll('.apariencia-pill[data-grosor]').forEach(function(b) {
            b.classList.toggle('activo', b.getAttribute('data-grosor') === grosor);
        });

        document.querySelectorAll('.apariencia-pill[data-sombra]').forEach(function(b) {
            b.classList.toggle('activo', b.getAttribute('data-sombra') === sombra);
        });

        document.querySelectorAll('.apariencia-color[data-fondo]').forEach(function(b) {
            b.textContent = b.getAttribute('data-fondo') === fondo ? '\u2713' : '';
            b.classList.toggle('activo', b.getAttribute('data-fondo') === fondo);
        });

        document.querySelectorAll('.apariencia-pill[data-textura]').forEach(function(b) {
            b.classList.toggle('activo', b.getAttribute('data-textura') === textura);
        });

        var sel = document.getElementById('selector-fuente');
        if (sel) sel.value = fuente;

        var prev = document.getElementById('preview-fuente');
        if (prev) {
            var map = {sistema:'system-ui, sans-serif',sans:'Inter, sans-serif',serif:'Georgia, serif',mono:'JetBrains Mono, monospace',escritura:'cursive',humanista:'Fira Sans, sans-serif',decorativa:'Playfair Display, serif'};
            prev.style.fontFamily = map[fuente] || 'inherit';
        }

        var raiz = document.documentElement;
        var oscuro = raiz.classList.contains('forzar-iluminacion-oscura');
        var btnC = document.getElementById('btn-tema-claro');
        var btnO = document.getElementById('btn-tema-oscuro');
        if (btnC) btnC.classList.toggle('activo', !oscuro);
        if (btnO) btnO.classList.toggle('activo', oscuro);
    }

    function esColorClaro(r, g, b) {
        return (r*299 + g*587 + b*114) / 1000 > 150;
    }

    function actualizarColoresSwatch() {
        var contenedor = document.getElementById('selector-fondo');
        if (!contenedor) return;
        var esOscuro = document.documentElement.classList.contains('forzar-iluminacion-oscura');
        var tmp = document.createElement('div');
        var fondos = (window.VALORES_UI && window.VALORES_UI.fondos_validos) || [];
        document.body.appendChild(tmp);
        for (var i = 0; i < fondos.length; i++) {
            var f = fondos[i];
            tmp.className = 'fondo-' + f + (esOscuro ? ' forzar-iluminacion-oscura' : '');
            var c = getComputedStyle(tmp).getPropertyValue('--fondo-pantalla').trim();
            var btn = document.querySelector('[data-fondo="' + f + '"]');
            if (btn && c) {
                btn.style.background = c;
                var r, g, b;
                if (c[0] === '#') {
                    var hex = c.substring(1);
                    r = parseInt(hex.substring(0,2), 16);
                    g = parseInt(hex.substring(2,4), 16);
                    b = parseInt(hex.substring(4,6), 16);
                } else {
                    var m = c.match(/\d+/g);
                    if (m && m.length >= 3) { r = +m[0]; g = +m[1]; b = +m[2]; }
                }
                btn.style.color = (r != null && esColorClaro(r,g,b)) ? '#1e293b' : '#ffffff';
            }
        }
        document.body.removeChild(tmp);
    }

    esperarLite(function() {

        document.getElementById('btn-tema-claro').addEventListener('click', function() {
            var raiz = document.documentElement;
            raiz.classList.remove('forzar-iluminacion-oscura');
            raiz.classList.add('forzar-iluminacion-clara');
            try { localStorage.setItem('tema-lite-framework', 'claro'); } catch(e) {}
            syncTodo();
            guardarAlServidor();
        });

        document.getElementById('btn-tema-oscuro').addEventListener('click', function() {
            var raiz = document.documentElement;
            raiz.classList.remove('forzar-iluminacion-clara');
            raiz.classList.add('forzar-iluminacion-oscura');
            try { localStorage.setItem('tema-lite-framework', 'oscuro'); } catch(e) {}
            syncTodo();
            guardarAlServidor();
        });

        document.getElementById('selector-paleta').addEventListener('click', function(e) {
            var btn = e.target.closest('.apariencia-color');
            if (!btn) return;
            var paleta = btn.getAttribute('data-paleta');
            if (paleta && window.aplicarPaleta) {
                window.aplicarPaleta(paleta);
                syncTodo();
                guardarAlServidor();
            }
        });

        document.getElementById('selector-estilo').addEventListener('click', function(e) {
            var btn = e.target.closest('.apariencia-pill[data-estilo]');
            if (!btn) return;
            var estilo = btn.getAttribute('data-estilo');
            if (estilo && window.aplicarEstilo) {
                window.aplicarEstilo(estilo);
                syncTodo();
                guardarAlServidor();
            }
        });

        document.getElementById('selector-fuente').addEventListener('change', function(e) {
            var fuente = e.target.value;
            if (fuente && window.aplicarFuente) {
                window.aplicarFuente(fuente);
                syncTodo();
                guardarAlServidor();
            }
        });

        document.getElementById('selector-espaciado').addEventListener('click', function(e) {
            var btn = e.target.closest('.apariencia-pill[data-espaciado]');
            if (!btn) return;
            var esp = btn.getAttribute('data-espaciado');
            if (esp && window.aplicarEspaciado) {
                window.aplicarEspaciado(esp);
                syncTodo();
                guardarAlServidor();
            }
        });

        document.getElementById('selector-tamano').addEventListener('click', function(e) {
            var btn = e.target.closest('.apariencia-pill[data-tamano]');
            if (!btn) return;
            var tam = btn.getAttribute('data-tamano');
            if (tam && window.aplicarTamano) {
                window.aplicarTamano(tam);
                syncTodo();
                guardarAlServidor();
            }
        });

        document.getElementById('selector-radio').addEventListener('click', function(e) {
            var btn = e.target.closest('.apariencia-pill[data-radio]');
            if (!btn) return;
            var radio = btn.getAttribute('data-radio');
            if (radio && window.aplicarRadio) {
                window.aplicarRadio(radio);
                syncTodo();
                guardarAlServidor();
            }
        });

        document.getElementById('selector-animacion').addEventListener('click', function(e) {
            var btn = e.target.closest('.apariencia-pill[data-animacion]');
            if (!btn) return;
            var animacion = btn.getAttribute('data-animacion');
            if (animacion && window.aplicarAnimacion) {
                window.aplicarAnimacion(animacion);
                syncTodo();
                guardarAlServidor();
            }
        });

        document.getElementById('selector-grosor').addEventListener('click', function(e) {
            var btn = e.target.closest('.apariencia-pill[data-grosor]');
            if (!btn) return;
            var gros = btn.getAttribute('data-grosor');
            if (gros && window.aplicarGrosor) {
                window.aplicarGrosor(gros);
                syncTodo();
                guardarAlServidor();
            }
        });

        document.getElementById('selector-sombra').addEventListener('click', function(e) {
            var btn = e.target.closest('.apariencia-pill[data-sombra]');
            if (!btn) return;
            var sombr = btn.getAttribute('data-sombra');
            if (sombr && window.aplicarSombra) {
                window.aplicarSombra(sombr);
                syncTodo();
                guardarAlServidor();
            }
        });

        document.getElementById('selector-fondo').addEventListener('click', function(e) {
            var btn = e.target.closest('.apariencia-color[data-fondo]');
            if (!btn) return;
            var fond = btn.getAttribute('data-fondo');
            if (fond && window.aplicarFondo) {
                window.aplicarFondo(fond);
                syncTodo();
                guardarAlServidor();
            }
        });

        document.getElementById('selector-textura').addEventListener('click', function(e) {
            var btn = e.target.closest('.apariencia-pill[data-textura]');
            if (!btn) return;
            var text = btn.getAttribute('data-textura');
            if (text && window.aplicarTextura) {
                window.aplicarTextura(text);
                syncTodo();
                guardarAlServidor();
            }
        });

        document.getElementById('btn-restablecer').addEventListener('click', function() {
            if (typeof window.restablecerPersonalizacion === 'function') {
                window.restablecerPersonalizacion();
                syncTodo();
                guardarAlServidor();
            }
        });

        document.getElementById('btn-guardar-servidor').addEventListener('click', function() {
            clearTimeout(temporizadorGuardado);
            if (typeof window.guardarPersonalizacionServidor === 'function') {
                window.guardarPersonalizacionServidor();
            }
        });

        syncTodo();
        actualizarColoresSwatch();
    });

    window.actualizarColoresSwatch = actualizarColoresSwatch;
})();