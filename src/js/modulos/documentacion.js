(function () {
    'use strict';

    var buscador = document.getElementById('buscadorDocumentacion');
    var rejilla = document.getElementById('rejillaDocumentacion');
    var sinResultados = document.getElementById('sinResultados');
    var paginacionDoc = document.getElementById('paginacion-documentacion');

    if (!buscador || !rejilla) {
        return;
    }

    var tarjetas = rejilla.querySelectorAll('.tarjeta-seccion-doc');

    function normalizar(texto)
    {
        return texto.toLowerCase()
            .replace(/[áàäâ]/g, 'a')
            .replace(/[éèëê]/g, 'e')
            .replace(/[íìïî]/g, 'i')
            .replace(/[óòöô]/g, 'o')
            .replace(/[úùüû]/g, 'u')
            .replace(/ñ/g, 'n')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function leerPaginaActual()
    {
        return parseInt(new URLSearchParams(window.location.search).get('pagina') || '1');
    }

    function mostrarPagina(pagina)
    {
        tarjetas.forEach(function (t) {
            t.style.display = parseInt(t.getAttribute('data-pagina')) === pagina ? '' : 'none';
        });
        if (sinResultados) {
            sinResultados.style.display = 'none';
        }
    }

    function resturarPaginacion()
    {
        mostrarPagina(leerPaginaActual());
    }

    // ---- Paginador asincrono (sin recarga) ----

    function extraerPaginaDeEnlace(el)
    {
        var href = el.getAttribute('href');
        if (!href) {
            return null;
        }
        var m = href.match(/pagina=(\d+)/);
        return m ? parseInt(m[1]) : null;
    }

    function obtenerTotalPaginas()
    {
        if (!paginacionDoc) {
            return 1;
        }
        var nav = paginacionDoc.querySelector('nav');
        if (!nav) {
            return 1;
        }
        var max = 1;
        nav.querySelectorAll('[href*="pagina="]').forEach(function (el) {
            var p = extraerPaginaDeEnlace(el);
            if (p && p > max) {
                max = p;
            }
        });
        var activo = nav.querySelector('.paginador-actual');
        if (activo && /^\d+$/.test(activo.textContent.trim())) {
            max = Math.max(max, parseInt(activo.textContent.trim()));
        }
        return max;
    }

    function crearLink(href, text)
    {
        var a = document.createElement('a');
        a.href = href;
        a.className = 'paginador-enlace';
        a.setAttribute('data-no-spa', '1');
        a.textContent = text;
        return a;
    }

    function crearActivo(text)
    {
        var span = document.createElement('span');
        span.className = 'paginador-enlace paginador-actual';
        span.setAttribute('aria-current', 'page');
        span.textContent = text;
        return span;
    }

    function crearDeshabilitado(text, title)
    {
        var span = document.createElement('span');
        span.className = 'paginador-enlace paginador-deshabilitado';
        if (title) {
            span.setAttribute('title', title);
        }
        span.textContent = text;
        return span;
    }

    function reconstruirPaginador(pagina, total)
    {
        if (!paginacionDoc) {
            return;
        }
        var nav = paginacionDoc.querySelector('nav');
        if (!nav) {
            return;
        }
        nav.innerHTML = '';

        if (pagina > 1) {
            nav.appendChild(crearLink('?pagina=1', '« Primera'));
        } else {
            nav.appendChild(crearDeshabilitado('«', 'Primera página'));
        }

        if (pagina > 1) {
            nav.appendChild(crearLink('?pagina=' + (pagina - 1), '‹ Anterior'));
        } else {
            nav.appendChild(crearDeshabilitado('‹', 'Página anterior'));
        }

        for (var i = 1; i <= total; i++) {
            nav.appendChild(i === pagina ? crearActivo(String(i)) : crearLink('?pagina=' + i, String(i)));
        }

        if (pagina < total) {
            nav.appendChild(crearLink('?pagina=' + (pagina + 1), 'Siguiente ›'));
        } else {
            nav.appendChild(crearDeshabilitado('›', 'Página siguiente'));
        }

        if (pagina < total) {
            nav.appendChild(crearLink('?pagina=' + total, 'Última »'));
        } else {
            nav.appendChild(crearDeshabilitado('»', 'Última página'));
        }
    }

    function irPagina(pagina)
    {
        pagina = Math.max(1, parseInt(pagina) || 1);
        mostrarPagina(pagina);

        var url = new URL(window.location.href);
        url.searchParams.set('pagina', String(pagina));
        history.pushState({ pagina: pagina }, '', url.toString());

        var total = obtenerTotalPaginas();
        reconstruirPaginador(pagina, total);
    }

    if (paginacionDoc) {
        paginacionDoc.addEventListener('click', function (e) {
            var enlace = e.target.closest('a');
            if (!enlace) {
                return;
            }
            var pagina = extraerPaginaDeEnlace(enlace);
            if (!pagina) {
                return;
            }
            e.preventDefault();
            irPagina(pagina);
        });
    }

    window.addEventListener('popstate', function () {
        var pagina = leerPaginaActual();
        mostrarPagina(pagina);
        var total = obtenerTotalPaginas();
        reconstruirPaginador(pagina, total);
        if (buscador && buscador.value) {
            buscador.value = '';
        }
    });

    // ---- Busqueda ----

    function buscar()
    {
        var termino = normalizar(this.value);
        var palabras = termino.split(/\s+/).filter(function (p) {
            return p.length > 0; });

        if (!termino) {
            resturarPaginacion();
            return;
        }

        var encontrados = 0;

        tarjetas.forEach(function (tarjeta) {
            var textoBusqueda = normalizar(
                (tarjeta.getAttribute('data-titulo') || '') + ' ' +
                (tarjeta.getAttribute('data-etiquetas') || '') + ' ' +
                (tarjeta.getAttribute('data-descripcion') || '') + ' ' +
                (tarjeta.getAttribute('data-contenido') || '')
            );

            var coincide = palabras.every(function (palabra) {
                return textoBusqueda.indexOf(palabra) !== -1;
            });

            tarjeta.style.display = coincide ? '' : 'none';
            if (coincide) {
                encontrados++;
            }
        });

        if (sinResultados) {
            sinResultados.style.display = encontrados > 0 ? 'none' : '';
        }
    }

    buscador.addEventListener('input', buscar);

    // ---- Modales ----

    var modales = document.querySelectorAll('.modal-documentacion');

    function abrirModal(modal)
    {
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        var cuerpo = modal.querySelector('.modal-documentacion-cuerpo');
        if (cuerpo) {
            cuerpo.scrollTop = 0;
        }
    }

    function cerrarModal(modal)
    {
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    function cerrarTodos()
    {
        modales.forEach(function (m) {
            cerrarModal(m); });
    }

    tarjetas.forEach(function (tarjeta) {
        tarjeta.addEventListener('click', function () {
            var id = this.getAttribute('data-seccion-id');
            var modal = document.getElementById('modal-' + id);
            if (modal) {
                abrirModal(modal);
            }
        });

        tarjeta.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    modales.forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                cerrarModal(modal);
            }
        });

        var cerrarBtn = modal.querySelector('.modal-cerrar');
        if (cerrarBtn) {
            cerrarBtn.addEventListener('click', function () {
                cerrarModal(modal); });
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            cerrarTodos();
        }
    });

})();
