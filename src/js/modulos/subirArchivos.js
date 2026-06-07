(function() {
    'use strict';

    var rutaActual = new URLSearchParams(window.location.search).get('ruta') || '';

    function obtenerBasePathLocal() {
        if (typeof window.obtenerBasePath === 'function') {
            return window.obtenerBasePath();
        }
        return window.location.pathname.replace(/\/[^/]*$/, '') || '';
    }

    var basePath = obtenerBasePathLocal();

    function csrfToken() {
        if (typeof window.obtenerTokenCSRF === 'function') {
            return window.obtenerTokenCSRF();
        }
        var meta = document.querySelector('meta[name="csrf-token"]') ||
            document.querySelector('input[name="csrf_token"]') ||
            document.querySelector('input[name="token_peticion"]');
        return meta ? meta.value : '';
    }

    function actualizarTokenCSRF(nuevoToken) {
        if (!nuevoToken) return;
        document.querySelectorAll('input[name="csrf_token"], input[name="token_peticion"]').forEach(function(el) {
            el.value = nuevoToken;
        });
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.content = nuevoToken;
    }

    function escapeHtml(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function formatearMB(bytes) {
        if (bytes == null || isNaN(bytes)) return '0.00 MB';
        var mb = bytes / 1048576;
        if (mb < 0.01) return (bytes / 1024).toFixed(2) + ' KB';
        return mb.toFixed(2) + ' MB';
    }

    function formatearVelocidadMB(bytesPorSeg) {
        if (!isFinite(bytesPorSeg) || bytesPorSeg <= 0) return '0.00 MB/s';
        return (bytesPorSeg / 1048576).toFixed(2) + ' MB/s';
    }

    function formatearTiempoRestante(segundos) {
        if (!isFinite(segundos) || segundos <= 0 || segundos === Infinity) return 'calculando...';
        if (segundos < 1) return 'menos de 1s';
        if (segundos < 60) return Math.round(segundos) + 's';
        if (segundos < 3600) {
            var m = Math.floor(segundos / 60);
            var s = Math.round(segundos % 60);
            return m + 'm ' + s + 's';
        }
        var h = Math.floor(segundos / 3600);
        var rm = Math.round((segundos % 3600) / 60);
        return h + 'h ' + rm + 'm';
    }

    function mostrarNotificacion(elemento, mensaje, tipo) {
        if (!elemento) return;
        elemento.className = 'notificacion-flotante estado-visible';
        elemento.setAttribute('data-variante', tipo === 'exito' ? 'exito' : (tipo === 'error' ? 'peligro' : 'informacion'));
        elemento.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
        elemento.textContent = mensaje;
        if (window.NotificadorHubble) {
            window.NotificadorHubble.mostrar(mensaje, tipo === 'exito' ? 'exito' : (tipo === 'error' ? 'peligro' : 'informacion'));
        }
        setTimeout(function() {
            elemento.classList.remove('estado-visible');
        }, 6000);
    }

    function crearBarraProgreso(contenedor, archivo, restantes, total) {
        if (!contenedor) return;
        var totalArchivos = total || 0;
        var numArchivo = totalArchivos - (restantes || 0);
        var progresoTexto = totalArchivos > 1 ? 'Archivo ' + numArchivo + ' de ' + totalArchivos : '';
        var html = '' +
            '<article class="archivos-tarjeta-progreso">' +
            '  <div class="archivos-progreso-info">' +
            '    <div class="archivos-progreso-nombre" id="progreso-nombre" title="' + escapeHtml(archivo.name) + '">' + escapeHtml(archivo.name) + '</div>' +
            '    <span class="etiqueta etiqueta-marca" id="progreso-porcentaje">0%</span>' +
            '  </div>' +
            '  <progress id="progreso-barra" max="100" value="0" aria-label="Progreso de subida" class="archivos-progreso-barra"></progress>' +
            '  <div class="archivos-progreso-info">' +
            '    <span class="texto-suave" id="progreso-detalle">0.00 MB de ' + formatearMB(archivo.size) + '</span>' +
            '    ' + (progresoTexto ? '<span class="texto-pequeno texto-negrita">' + progresoTexto + '</span>' : '') +
            '  </div>' +
            '  <div class="archivos-progreso-estadisticas">' +
            '    <span><strong>Velocidad:</strong> <span id="progreso-velocidad">0.00 MB/s</span></span>' +
            '    <span><strong>Subido:</strong> <span id="progreso-subido">0.00 MB</span></span>' +
            '    <span><strong>Restante:</strong> <span id="progreso-restante">calculando...</span></span>' +
            '  </div>' +
            '  <div class="archivos-progreso-acciones">' +
            '    <button type="button" id="progreso-cancelar" class="accion-boton variante-peligro tamano-pequeno">Cancelar subida</button>' +
            '  </div>' +
            '</article>';
        contenedor.innerHTML = html;
        contenedor.style.display = 'block';
    }

    function actualizarProgreso(contenedor, loaded, total, velocidad, restante) {
        if (!contenedor) return;
        var porcentaje = total > 0 ? (loaded / total) * 100 : 0;
        var barra = contenedor.querySelector('#progreso-barra');
        var pctEl = contenedor.querySelector('#progreso-porcentaje');
        var detalleEl = contenedor.querySelector('#progreso-detalle');
        var velEl = contenedor.querySelector('#progreso-velocidad');
        var subidoEl = contenedor.querySelector('#progreso-subido');
        var restEl = contenedor.querySelector('#progreso-restante');
        if (barra) barra.value = porcentaje;
        if (pctEl) pctEl.textContent = porcentaje.toFixed(1) + '%';
        if (detalleEl) detalleEl.textContent = formatearMB(loaded) + ' de ' + formatearMB(total);
        if (velEl) velEl.textContent = formatearVelocidadMB(velocidad);
        if (subidoEl) subidoEl.textContent = formatearMB(loaded);
        if (restEl) restEl.textContent = formatearTiempoRestante(restante);
    }

    function confirmarExitoProgreso(contenedor) {
        if (!contenedor) return;
        var tarjeta = contenedor.querySelector('.archivos-tarjeta-progreso');
        if (tarjeta) tarjeta.classList.add('exito');
    }

    function ocultarBarraProgreso(contenedor) {
        if (!contenedor) return;
        contenedor.style.display = 'none';
        contenedor.innerHTML = '';
    }

    function renderizarTarjetaArchivo(archivo) {
        var mime = (archivo.tipo_mime || '').toLowerCase();
        var tipo = 'base';
        if (archivo.es_imagen || archivo.esImagen) tipo = 'imagen';
        else if (mime.includes('video')) tipo = 'video';
        else if (mime.includes('audio')) tipo = 'audio';
        else if (mime.includes('pdf') || mime.includes('document') || mime.includes('sheet') || mime.includes('text')) tipo = 'documento';
        else if (mime.includes('zip') || mime.includes('rar') || mime.includes('tar') || mime.includes('gzip') || mime.includes('7z')) tipo = 'comprimido';
        else if (mime.includes('dosexec') || mime.includes('msdownload') || mime.includes('executable') || mime.includes('x-msi')) tipo = 'ejecutable';

        var iconoHtml;
        if (archivo.es_imagen || archivo.esImagen) {
            iconoHtml = '<img src="' + escapeHtml(archivo.enlace_descarga || archivo.enlaceDescarga) + '" alt="' + escapeHtml(archivo.nombre_original) + '">';
        } else {
            var icono = { video: '🎬', audio: '🎵', comprimido: '📦', documento: '📝', ejecutable: '⚙', imagen: '🖼' }[tipo] || '📄';
            iconoHtml = '<span class="icono-' + tipo + '">' + icono + '</span>';
        }

        var etiquetas = '';
        if (archivo.etiquetas) {
            etiquetas = '<div class="etiquetas">';
            archivo.etiquetas.split(',').forEach(function(et) {
                etiquetas += '<span class="etiqueta">' + escapeHtml(et.trim()) + '</span>';
            });
            etiquetas += '</div>';
        }

        return '' +
            '<article class="tarjeta-archivo" data-id="' + archivo.id + '">' +
                '<span class="etiqueta-tipo-archivo" data-tipo="' + tipo + '">' + tipo.charAt(0).toUpperCase() + tipo.slice(1) + '</span>' +
                '<div class="miniatura">' + iconoHtml + '</div>' +
                '<div class="info">' +
                    '<p class="nombre" title="' + escapeHtml(archivo.nombre_original) + '">' + escapeHtml(archivo.nombre_original) + '</p>' +
                    '<p class="meta">' + escapeHtml(archivo.tipo_mime) + ' &middot; ' + escapeHtml(archivo.tamano_formateado || archivo.tamanoFormateado) + ' &middot; ' + escapeHtml(archivo.fecha_subida) + '</p>' +
                    etiquetas +
                '</div>' +
                '<div class="acciones">' +
                    (archivo.es_imagen || archivo.esImagen
                        ? '<a href="' + escapeHtml(archivo.enlace_descarga || archivo.enlaceDescarga) + '" target="_blank" class="accion-boton variante-borde tamano-pequeno">Ver</a>'
                        : '') +
                    '<a href="' + escapeHtml(archivo.enlace_descarga || archivo.enlaceDescarga) + '" download="' + escapeHtml(archivo.nombre_original) + '" class="accion-boton variante-borde tamano-pequeno">Descargar</a>' +
                    '<button type="button" class="accion-boton variante-peligro tamano-pequeno btn-eliminar-archivo" data-id="' + archivo.id + '">Eliminar</button>' +
                '</div>' +
            '</article>';
    }

    function navegarACarpeta(ruta) {
        rutaActual = ruta;
        var url = basePath + '/archivos?ajax=1&partial=lista' + (ruta ? '&ruta=' + encodeURIComponent(ruta) : '');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var contenedor = document.getElementById('contenedor-lista-archivos');
                if (contenedor) contenedor.innerHTML = html;
            })
            .catch(function(err) {
                if (window.NotificadorHubble) window.NotificadorHubble.mostrar('Error al cargar la carpeta', 'peligro');
            });
    }

    function agregarTarjetaAlListado(archivo) {
        var rutaArchivo = archivo.ruta_mostrar || '';
        if (rutaArchivo !== rutaActual) return;

        var destino = document.querySelector('.explorador-archivos > .rejilla-automatica');
        if (!destino) return;

        var tarjetaHtml = renderizarTarjetaArchivo(archivo);
        var div = document.createElement('div');
        div.innerHTML = tarjetaHtml;
        var nodo = div.firstElementChild;
        if (!nodo) return;
        nodo.style.opacity = '0';
        nodo.style.transform = 'translateY(-10px)';
        nodo.style.transition = 'opacity 0.3s, transform 0.3s';

        destino.insertBefore(nodo, destino.firstChild);
        requestAnimationFrame(function() { nodo.style.opacity = '1'; nodo.style.transform = 'translateY(0)'; });
        actualizarContador(1);
        ocultarAvisoVacio();
    }

    function actualizarContador(delta) {
        var global = document.querySelector('#contenedor-lista-archivos .etiqueta-marca');
        if (global && delta) {
            var actual = parseInt(global.textContent, 10) || 0;
            global.textContent = (actual + delta);
        }
    }

    function ocultarAvisoVacio() {
        var aviso = document.querySelector('.archivos-aviso-vacio');
        if (aviso) aviso.style.display = 'none';
    }

    function eliminarTarjetaUI(boton) {
        var fila = boton.closest('.tarjeta-archivo');
        if (!fila) return;
        fila.style.transition = 'opacity 0.3s, transform 0.3s';
        fila.style.opacity = '0';
        fila.style.transform = 'translateX(20px)';
        setTimeout(function() { fila.remove(); actualizarContador(-1); }, 300);
    }

    var colaSubida = [];
    var subidaCancelada = false;
    var subidaEnProgreso = false;
    var totalSubidos = 0;

    function subirArchivoIndividual(form, archivo, mensajeEl, progresoEl, etiquetas, modulo, descripcion, restantes, totalEnCola, callback) {
        var input = document.getElementById('archivo');
        var maxBytes = parseInt(input.getAttribute('data-max-size') || '41943040', 10);
        if (archivo.size > maxBytes) {
            var maxMB = maxBytes / 1048576;
            mostrarNotificacion(mensajeEl, '"' + archivo.name + '" (' + (archivo.size / 1048576).toFixed(2) + ' MB) excede el limite de ' + maxMB.toFixed(0) + ' MB', 'error');
            callback(false);
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', (basePath + '/archivos/subir'), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-Token', csrfToken());
        xhr.setRequestHeader('Accept', 'application/json');

        var ultimoTiempoCalculo = Date.now();
        var ultimosBytesRecibidos = 0;
        var velocidadSuavizada = 0;
        var historialVelocidad = [];

        crearBarraProgreso(progresoEl, archivo, restantes, totalEnCola);
        actualizarProgreso(progresoEl, 0, archivo.size, 0, 0);

        var btnCancelar = progresoEl.querySelector('#progreso-cancelar');
        if (btnCancelar) {
            btnCancelar.addEventListener('click', function() {
                subidaCancelada = true;
                xhr.abort();
            });
        }

        xhr.upload.addEventListener('progress', function(e) {
            if (!e.lengthComputable) return;
            var ahora = Date.now();
            var deltaT = (ahora - ultimoTiempoCalculo) / 1000;
            if (deltaT >= 0.3) {
                var deltaBytes = e.loaded - ultimosBytesRecibidos;
                var velInstant = deltaBytes / deltaT;
                historialVelocidad.push(velInstant);
                if (historialVelocidad.length > 5) historialVelocidad.shift();
                velocidadSuavizada = historialVelocidad.reduce(function(a,b){return a+b;},0) / historialVelocidad.length;
                ultimoTiempoCalculo = ahora;
                ultimosBytesRecibidos = e.loaded;
            }
            var restante = velocidadSuavizada > 0 ? (e.total - e.loaded) / velocidadSuavizada : 0;
            actualizarProgreso(progresoEl, e.loaded, e.total, velocidadSuavizada, restante);
        });

        xhr.addEventListener('load', function() {
            if (subidaCancelada) return;
            try {
                var datos = JSON.parse(xhr.responseText);
                if (xhr.status >= 200 && xhr.status < 300 && datos.exito) {
                    totalSubidos++;
                    actualizarTokenCSRF(datos.nuevo_token);
                    confirmarExitoProgreso(progresoEl);
                    if (datos.archivo) agregarTarjetaAlListado(datos.archivo);
                    callback(true);
                } else {
                    var msg = datos.error || ('Error del servidor: ' + xhr.status);
                    if (datos.codigo === 'excede_limite_php' && datos.limite_php) msg += ' (PHP: ' + datos.limite_php + ')';
                    else if (datos.codigo === 'cuota_excedida' && datos.cuota_mb) msg += ' (Cuota: ' + datos.cuota_mb + ' MB)';
                    mostrarNotificacion(mensajeEl, '"' + archivo.name + '": ' + msg, 'error');
                    callback(false);
                }
            } catch (e) {
                mostrarNotificacion(mensajeEl, '"' + archivo.name + '": respuesta invalida del servidor (HTTP ' + xhr.status + ')', 'error');
                callback(false);
            }
        });

        xhr.addEventListener('error', function() {
            mostrarNotificacion(mensajeEl, '"' + archivo.name + '": Error de conexion', 'error');
            callback(false);
        });

        xhr.addEventListener('abort', function() {
            if (!subidaCancelada) {
                mostrarNotificacion(mensajeEl, '"' + archivo.name + '": Subida cancelada', 'error');
            }
            callback(false);
        });

        var fd = new FormData();
        var csrfInput = form.querySelector('input[name="csrf_token"]');
        fd.append('csrf_token', csrfInput ? csrfInput.value : csrfToken());
        fd.append('archivo', archivo);
        if (archivo.webkitRelativePath) fd.append('ruta_relativa', archivo.webkitRelativePath);
        if (etiquetas) fd.append('etiquetas', etiquetas);
        if (modulo) fd.append('modulo_origen', modulo);
        if (descripcion) fd.append('descripcion', descripcion);
        xhr.send(fd);
    }

    function procesarColaSubida(form, mensajeEl, progresoEl) {
        if (colaSubida.length === 0 || subidaCancelada) {
            subidaEnProgreso = false;
            if (!subidaCancelada && totalSubidos > 0) {
                mostrarNotificacion(mensajeEl, totalSubidos + ' archivo(s) subido(s) correctamente', 'exito');
                navegarACarpeta(rutaActual);
                var tokenActual = csrfToken();
                form.reset();
                if (tokenActual) actualizarTokenCSRF(tokenActual);
                limpiarSeleccion();
            }
            setTimeout(function() { ocultarBarraProgreso(progresoEl); }, totalSubidos > 0 ? 1500 : 0);
            totalSubidos = 0;
            return;
        }

        subidaEnProgreso = true;
        var info = colaSubida.shift();
        var restantes = colaSubida.length;
        var totalEnCola = restantes + 1;
        subirArchivoIndividual(form, info.archivo, mensajeEl, progresoEl, info.etiquetas, info.modulo, info.descripcion, restantes, totalEnCola, function(exito) {
            procesarColaSubida(form, mensajeEl, progresoEl);
        });
    }

    function obtenerArchivosSeleccionados() {
        var archivos = [];
        var inputArchivos = document.getElementById('archivo');
        var inputCarpeta = document.getElementById('archivo-carpeta');

        if (inputArchivos && inputArchivos.files) {
            for (var i = 0; i < inputArchivos.files.length; i++) {
                archivos.push(inputArchivos.files[i]);
            }
        }
        if (inputCarpeta && inputCarpeta.files) {
            for (var i = 0; i < inputCarpeta.files.length; i++) {
                archivos.push(inputCarpeta.files[i]);
            }
        }
        return archivos;
    }

    function actualizarResumenSeleccion() {
        var resumen = document.getElementById('archivos-seleccion-resumen');
        var texto = document.getElementById('archivos-seleccion-texto');
        var detalle = document.getElementById('archivos-seleccion-detalle');
        var boton = document.getElementById('archivos-contador-boton');
        if (!resumen || !texto) return;

        var archivos = obtenerArchivosSeleccionados();

        if (archivos.length > 0) {
            var totalBytes = 0;
            for (var i = 0; i < archivos.length; i++) totalBytes += archivos[i].size;

            texto.textContent = archivos.length + ' archivo(s) seleccionado(s) — ' + formatearMB(totalBytes) + ' total';

            if (detalle) {
                var carpetas = {};
                for (var i = 0; i < archivos.length; i++) {
                    var rp = archivos[i].webkitRelativePath || '';
                    var idx = rp.indexOf('/');
                    if (idx > 0) carpetas[rp.substring(0, idx)] = true;
                }
                var nombres = Object.keys(carpetas);
                detalle.textContent = 'Carpetas: ' + (nombres.length > 0 ? nombres.join(', ') : '(raiz)');
                detalle.style.display = '';
            }

            resumen.classList.add('visible');
            if (boton) boton.textContent = '(' + archivos.length + ')';
        } else {
            resumen.classList.remove('visible');
            if (boton) boton.textContent = '';
            if (detalle) detalle.style.display = 'none';
        }
    }

    function limpiarSeleccion() {
        var inputArchivos = document.getElementById('archivo');
        var inputCarpeta = document.getElementById('archivo-carpeta');
        if (inputArchivos) inputArchivos.value = '';
        if (inputCarpeta) inputCarpeta.value = '';
        actualizarResumenSeleccion();
    }

    function inicializarEventos() {
        var form = document.getElementById('form-subir-archivo');
        var mensajeEl = document.getElementById('mensaje-subida');
        var progresoEl = document.getElementById('contenedor-progreso');

        var archivoInput = document.getElementById('archivo');
        var carpetaInput = document.getElementById('archivo-carpeta');

        if (archivoInput) {
            archivoInput.removeAttribute('webkitdirectory');
            archivoInput.removeAttribute('directory');
            archivoInput.addEventListener('change', actualizarResumenSeleccion);
        }

        if (carpetaInput) {
            carpetaInput.addEventListener('change', actualizarResumenSeleccion);
        }

        var btnArchivos = document.getElementById('archivos-subir-archivos');
        if (btnArchivos && archivoInput) {
            btnArchivos.addEventListener('click', function() {
                archivoInput.click();
            });
        }

        var btnCarpeta = document.getElementById('archivos-subir-carpeta');
        if (btnCarpeta && carpetaInput) {
            btnCarpeta.addEventListener('click', function() {
                carpetaInput.click();
            });
        }

        var btnLimpiarSel = document.getElementById('archivos-limpiar-seleccion');
        if (btnLimpiarSel) {
            btnLimpiarSel.addEventListener('click', limpiarSeleccion);
        }

        var btnLimpiarForm = document.getElementById('archivos-limpiar-formulario');
        if (btnLimpiarForm) {
            btnLimpiarForm.addEventListener('click', function(e) {
                e.preventDefault();
                form.reset();
                limpiarSeleccion();
            });
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (subidaEnProgreso) {
                    mostrarNotificacion(mensajeEl, 'Ya hay una subida en curso.', 'error');
                    return;
                }
                subirArchivos(form, mensajeEl, progresoEl);
            });
        }

        document.addEventListener('click', function(e) {
            var btnArchivo = e.target.closest('.btn-eliminar-archivo');
            if (btnArchivo) {
                var id = btnArchivo.getAttribute('data-id');
                if (id) eliminarArchivo(id, btnArchivo);
                return;
            }

            var btnCarpeta = e.target.closest('.btn-eliminar-carpeta');
            if (btnCarpeta) {
                var ruta = btnCarpeta.getAttribute('data-ruta');
                var nombre = btnCarpeta.getAttribute('data-nombre');
                if (ruta) eliminarCarpeta(ruta, nombre);
                return;
            }

            var carpeta = e.target.closest('.tarjeta-carpeta');
            if (carpeta) {
                var ruta = carpeta.getAttribute('data-ruta');
                if (ruta) navegarACarpeta(ruta);
                return;
            }

            var miga = e.target.closest('.miga');
            if (miga) {
                e.preventDefault();
                var ruta = miga.getAttribute('data-ruta');
                navegarACarpeta(ruta);
            }
        });
    }

    function subirArchivos(form, mensajeEl, progresoEl) {
        var archivos = obtenerArchivosSeleccionados();

        if (archivos.length === 0) {
            mostrarNotificacion(mensajeEl, 'Selecciona al menos un archivo o carpeta', 'error');
            return;
        }

        if (subidaEnProgreso) {
            mostrarNotificacion(mensajeEl, 'Ya hay una subida en curso. Espera a que termine.', 'error');
            return;
        }

        subidaCancelada = false;
        totalSubidos = 0;
        colaSubida = [];

        var etiquetas = form.querySelector('#etiquetas')?.value || '';
        var modulo = form.querySelector('#modulo_origen')?.value || '';
        var descripcion = form.querySelector('#descripcion')?.value || '';

        for (var i = 0; i < archivos.length; i++) {
            colaSubida.push({
                archivo: archivos[i],
                etiquetas: etiquetas,
                modulo: modulo,
                descripcion: descripcion,
            });
        }

        procesarColaSubida(form, mensajeEl, progresoEl);
    }

    function eliminarArchivo(id, boton) {
        window.ConfirmadorHubble.mostrar('¿Estas seguro de eliminar este archivo?').then(function(confirmado) {
            if (!confirmado) return;

            boton.disabled = true;
            var fila = boton.closest('.tarjeta-archivo');
            if (fila) fila.style.opacity = '0.5';

            var formData = new FormData();
            formData.append('id', id);
            formData.append('csrf_token', csrfToken());

            fetch(basePath + '/archivos/eliminar', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken(),
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function(r) {
            if (r.status === 204) return { exito: true, mensaje: 'Eliminado' };
            return r.text().then(function(texto) {
                try { return JSON.parse(texto); }
                catch (e) { return { exito: false, error: 'Respuesta invalida (HTTP ' + r.status + ')' }; }
            });
        })
        .then(function(datos) {
            if (datos.exito) {
                actualizarTokenCSRF(datos.nuevo_token);
                if (window.NotificadorHubble) window.NotificadorHubble.mostrar(datos.mensaje || 'Archivo eliminado', 'exito');
                eliminarTarjetaUI(boton);
            } else {
                if (fila) fila.style.opacity = '1';
                boton.disabled = false;
                if (window.NotificadorHubble) window.NotificadorHubble.mostrar(datos.error || 'Error al eliminar', 'peligro');
            }
        })
        .catch(function(err) {
            if (fila) fila.style.opacity = '1';
            boton.disabled = false;
            if (window.NotificadorHubble) window.NotificadorHubble.mostrar('Error de red: ' + err.message, 'peligro');
        });
        });
    }

    function eliminarCarpeta(ruta, nombre) {
        window.ConfirmadorHubble.mostrar('¿Estas seguro de eliminar la carpeta "' + nombre + '" y todos sus archivos?').then(function(confirmado) {
            if (!confirmado) return;

            var formData = new FormData();
            formData.append('ruta', ruta);
            formData.append('csrf_token', csrfToken());

            fetch(basePath + '/archivos/eliminar-carpeta', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken(),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function(r) { return r.text(); })
            .then(function(texto) {
                try { return JSON.parse(texto); }
                catch (e) { return { exito: false, error: 'Respuesta invalida (HTTP ' + r.status + ')' }; }
            })
            .then(function(datos) {
                if (datos.exito) {
                    actualizarTokenCSRF(datos.nuevo_token);
                    if (window.NotificadorHubble) window.NotificadorHubble.mostrar(datos.mensaje || 'Carpeta eliminada', 'exito');
                    navegarACarpeta(rutaActual);
                } else {
                    if (window.NotificadorHubble) window.NotificadorHubble.mostrar(datos.error || 'Error al eliminar la carpeta', 'peligro');
                }
            })
            .catch(function(err) {
                if (window.NotificadorHubble) window.NotificadorHubble.mostrar('Error de red: ' + err.message, 'peligro');
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarEventos);
    } else {
        inicializarEventos();
    }
})();
