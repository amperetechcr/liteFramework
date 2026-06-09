(function () {
    'use strict';

    var URL_BASE = window.location.pathname.includes('liteFramework')
        ? '/liteFramework'
        : '';

    function csrfToken() {
        if (typeof window.obtenerTokenCSRF === 'function') return window.obtenerTokenCSRF();
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.value : '';
    }

    function msFormateado(ms) {
        if (ms === 0 || ms === null || ms === undefined) return '—';
        if (ms < 1) return (ms * 1000).toFixed(0) + '\u00b5s';
        if (ms < 10) return ms.toFixed(2) + ' ms';
        if (ms < 100) return ms.toFixed(1) + ' ms';
        return Math.round(ms) + ' ms';
    }

    function bytesFormateado(bytes) {
        if (!bytes || bytes === 0) return '—';
        if (bytes < 1024) return bytes.toFixed(1) + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(2) + ' MB';
    }

    function parseMemoria(str) {
        if (!str) return 0;
        var num = parseFloat(str);
        if (str.includes('GB')) return num * 1073741824;
        if (str.includes('MB')) return num * 1048576;
        if (str.includes('KB')) return num * 1024;
        return num;
    }

    function tiempoRelativo(timestamp) {
        var diff = Math.floor((Date.now() - timestamp * 1000) / 1000);
        if (diff < 5) return 'ahora';
        if (diff < 60) return 'hace ' + diff + 's';
        if (diff < 3600) return 'hace ' + Math.floor(diff / 60) + 'm';
        return 'hace ' + Math.floor(diff / 3600) + 'h';
    }

    function renderizarDistribucion(dist, total) {
        var valores = [
            dist['0-50'] || 0,
            dist['50-100'] || 0,
            dist['100-200'] || 0,
            dist['200-500'] || 0,
            dist['500+'] || 0,
        ];
        var suma = valores.reduce(function (a, b) { return a + b; }, 0) || 1;
        var etiquetas = ['0-50ms', '50-100ms', '100-200ms', '200-500ms', '500ms+'];
        var colores = ['var(--color-exito)', 'var(--color-info)', 'var(--color-advertencia)', 'var(--color-peligro)', 'var(--color-peligro)'];

        var barras = document.querySelectorAll('#rend-distribucion .rend-barra');
        barras.forEach(function (barra, i) {
            var porcentaje = Math.max((valores[i] / suma) * 100, valores[i] > 0 ? 2 : 0);
            var pctDelTotal = total > 0 ? ((valores[i] / total) * 100).toFixed(1) : 0;
            barra.style.width = porcentaje + '%';
            barra.title = valores[i] + ' solicitudes (' + pctDelTotal + '% del total)';
        });

        var leyenda = document.getElementById('rend-leyenda');
        if (leyenda) {
            leyenda.innerHTML = valores.map(function (v, i) {
                var pct = total > 0 ? ((v / total) * 100).toFixed(1) : 0;
                return '<span class="rend-leyenda-item"><span class="rend-leyenda-color" style="background:' + colores[i] + '"></span>' + etiquetas[i] + ': ' + v + ' (' + pct + '%)</span>';
            }).join('');
        }
    }

    function renderizarUltimos(ultimos) {
        var contenedor = document.getElementById('rend-ultimos');
        if (!contenedor) return;

        if (!ultimos || ultimos.length === 0) {
            contenedor.innerHTML = '<p class="texto-xs texto-suave">Sin datos disponibles.</p>';
            return;
        }

        var html = '<div class="rend-tabla-scroll"><table class="rend-tabla"><thead><tr><th>Metodo</th><th>URI</th><th>Tiempo</th><th>Memoria</th></tr></thead><tbody>';
        ultimos.forEach(function (r) {
            var claseTiempo = r.tiempo >= 500 ? 'rend-rojo' : r.tiempo >= 200 ? 'rend-amarillo' : 'rend-verde';
            var metodoClase = r.metodo === 'GET' ? 'rend-metodo-get' : r.metodo === 'POST' ? 'rend-metodo-post' : '';
            html += '<tr><td><span class="rend-metodo ' + metodoClase + '">' + r.metodo + '</span></td><td class="rend-uri" title="' + r.uri + '">' + r.uri + '</td><td class="' + claseTiempo + ' texto-negrita">' + msFormateado(r.tiempo) + '</td><td class="texto-xs">' + r.memoria + '</td></tr>';
        });
        html += '</tbody></table></div>';
        contenedor.innerHTML = html;
    }

    function actualizarEstado(data) {
        var estado = document.getElementById('rend-estado');
        if (!estado) return;

        var p95 = data.p95 || 0;
        var lentosPct = data.porcentajeLentos || 0;
        var total = data.total || 0;

        if (total === 0) {
            estado.textContent = 'Sin datos';
            estado.className = 'rend-estado rend-estado-neutral';
            return;
        }

        var texto = '';
        var clase = '';
        if (p95 < 100 && lentosPct < 1) {
            texto = 'Rendimiento optimo';
            clase = 'rend-estado-exito';
        } else if (p95 < 300 && lentosPct < 5) {
            texto = 'Rendimiento aceptable';
            clase = 'rend-estado-advertencia';
        } else {
            texto = 'Rendimiento degradado';
            clase = 'rend-estado-peligro';
        }

        estado.textContent = texto;
        estado.className = 'rend-estado ' + clase;
    }

    function actualizarRendimiento() {
        var cargando = document.getElementById('rend-cargando');
        if (cargando) cargando.style.display = 'inline';

        fetch(URL_BASE + '/api/rendimiento?_=' + Date.now(), {
            headers: { 'X-CSRF-Token': csrfToken() }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.error) return;

                var set = function (id, val) {
                    var el = document.getElementById(id);
                    if (el) el.textContent = val;
                };

                set('rend-total', data.total || 0);
                set('rend-promedio', msFormateado(data.promedio));
                set('rend-p95', msFormateado(data.p95));
                set('rend-p99', msFormateado(data.p99));
                set('rend-maximo', msFormateado(data.maximo));
                set('rend-mediana', msFormateado(data.mediana));
                set('rend-lentos', data.lentos || 0);

                var pctLentos = document.getElementById('rend-porcentaje-lentos');
                if (pctLentos) {
                    var pct = data.porcentajeLentos || 0;
                    pctLentos.textContent = pct + '% del total';
                    pctLentos.className = 'rend-sub' + (pct > 5 ? ' texto-peligro' : pct > 1 ? ' texto-advertencia' : '');
                }

                var memoria = document.getElementById('rend-memoria');
                if (memoria) {
                    var memBytes = parseMemoria(data.memoriaPromedio);
                    memoria.textContent = bytesFormateado(memBytes);
                }

                if (data.distribucion) {
                    renderizarDistribucion(data.distribucion, data.total);
                }

                if (data.ultimos) {
                    renderizarUltimos(data.ultimos);
                }

                actualizarEstado(data);

                var actualizado = document.getElementById('rend-actualizado');
                if (actualizado && data.timestamp) {
                    actualizado.textContent = tiempoRelativo(data.timestamp);
                }

                if (cargando) cargando.style.display = 'none';
            })
            .catch(function () {
                if (cargando) cargando.style.display = 'none';
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.getElementById('seccion-rendimiento')) return;

        actualizarRendimiento();
        setInterval(actualizarRendimiento, 15000);

        var actualizado = document.getElementById('rend-actualizado');
        if (actualizado) {
            setInterval(function () {
                var ts = actualizado.getAttribute('data-timestamp');
                if (ts) actualizado.textContent = tiempoRelativo(parseInt(ts));
            }, 10000);
        }
    });
})();
