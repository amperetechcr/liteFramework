var ListaFiltrable = (function(global) {
    'use strict';

    function ListaFiltrable(config) {
        this.baseUrl = config.baseUrl;
        this.containerId = config.containerId;
        this.paginationSelector = config.paginationSelector;
        this.contadorId = config.contadorId;
        this.contadorSourceId = config.contadorSourceId;
        this.filtroConfigs = config.filtros || [];
        this.busquedaId = config.busquedaId;
        this.debounceMs = config.debounceMs || 350;
        this.pageParam = config.pageParam || 'pagina';
        this.afterRender = config.afterRender;

        this._basePath = typeof global.obtenerBasePath === 'function'
            ? global.obtenerBasePath()
            : (window.location.pathname.replace(/\/[^/]*$/, '') || '/');
        this._timeout = null;
        this._paginaActual = 1;
    }

    ListaFiltrable.prototype._obtenerFiltros = function() {
        var params = {};
        for (var i = 0; i < this.filtroConfigs.length; i++) {
            var fc = this.filtroConfigs[i];
            var el = document.getElementById(fc.id);
            if (el && el.value) params[fc.paramName] = el.value;
        }
        return params;
    };

    ListaFiltrable.prototype.recargar = function(pagina) {
        var self = this;
        pagina = pagina || 1;
        self._paginaActual = pagina;

        var filtros = self._obtenerFiltros();
        var params = new URLSearchParams();
        params.set('ajax', '1');
        params.set('partial', 'lista');
        params.set(self.pageParam, pagina);

        for (var key in filtros) {
            if (filtros.hasOwnProperty(key) && filtros[key]) {
                params.set(key, filtros[key]);
            }
        }

        var contenedor = document.getElementById(self.containerId);
        if (contenedor) contenedor.style.opacity = '0.5';

        fetch(self._basePath + self.baseUrl + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) {
            if (!res.ok) throw new Error('Error ' + res.status);
            return res.text();
        })
        .then(function(html) {
            if (contenedor) {
                contenedor.innerHTML = html;
                contenedor.style.opacity = '1';
                self.vincularPaginacion();
                self._actualizarContador();
                self._actualizarUrlNavegador(pagina, filtros);
                if (typeof self.afterRender === 'function') {
                    self.afterRender();
                }
            }
        })
        .catch(function() {
            if (contenedor) contenedor.style.opacity = '1';
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar('Error al actualizar la lista.', 'peligro');
            }
        });
    };

    ListaFiltrable.prototype.vincularPaginacion = function() {
        var self = this;
        var nav = document.querySelector(self.paginationSelector);
        if (!nav) return;
        nav.querySelectorAll('a.paginador-enlace').forEach(function(enlace) {
            enlace.addEventListener('click', function(e) {
                e.preventDefault();
                var href = this.getAttribute('href');
                var match = href.match(/[?&]pagina=(\d+)/);
                var pagina = match ? parseInt(match[1]) : null;
                if (pagina && !isNaN(pagina)) {
                    self.recargar(pagina);
                }
            });
        });
    };

    ListaFiltrable.prototype._actualizarContador = function() {
        var spanTotal = document.getElementById(this.contadorSourceId);
        if (spanTotal) {
            var total = spanTotal.getAttribute('data-total');
            var contador = document.getElementById(this.contadorId);
            if (contador) contador.textContent = total;
        }
    };

    ListaFiltrable.prototype._actualizarUrlNavegador = function(pagina, filtros) {
        var params = new URLSearchParams();
        if (pagina > 1) params.set(this.pageParam, pagina);
        for (var key in filtros) {
            if (filtros.hasOwnProperty(key) && filtros[key]) {
                params.set(key, filtros[key]);
            }
        }
        var nuevaUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState(window.history.state || {}, '', nuevaUrl);
    };

    ListaFiltrable.prototype.sincronizarConUrl = function() {
        var paramsIniciales = new URLSearchParams(window.location.search);
        for (var i = 0; i < this.filtroConfigs.length; i++) {
            var fc = this.filtroConfigs[i];
            var el = document.getElementById(fc.id);
            if (paramsIniciales.get(fc.paramName) && el) {
                el.value = paramsIniciales.get(fc.paramName);
            }
        }
    };

    ListaFiltrable.prototype.inicializarEventos = function() {
        var self = this;

        if (self.busquedaId) {
            var inputBuscar = document.getElementById(self.busquedaId);
            if (inputBuscar) {
                inputBuscar.addEventListener('input', function() {
                    clearTimeout(self._timeout);
                    self._timeout = setTimeout(function() {
                        self.recargar(1);
                    }, self.debounceMs);
                });
            }
        }

        for (var i = 0; i < self.filtroConfigs.length; i++) {
            var fc = self.filtroConfigs[i];
            if (fc.id === self.busquedaId) continue;
            var el = document.getElementById(fc.id);
            if (el) {
                el.addEventListener('change', function() {
                    self.recargar(1);
                });
            }
        }
    };

    return ListaFiltrable;
})(window);

export { ListaFiltrable };
window.ListaFiltrable = ListaFiltrable;
