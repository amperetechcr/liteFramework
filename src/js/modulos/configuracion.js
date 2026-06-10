(function () {
    'use strict';

    function csrfToken()
    {
        if (typeof window.obtenerTokenCSRF === 'function') {
            return window.obtenerTokenCSRF();
        }
        var meta = document.querySelector('meta[name="csrf-token"]') ||
            document.querySelector('input[name="token_peticion"]');
        return meta ? meta.value : '';
    }

    function serializarFormulario(form)
    {
        var formData = new FormData(form);
        var datos = {};
        formData.forEach(function (valor, clave) {
            if (clave in datos) {
                if (!Array.isArray(datos[clave])) {
                    datos[clave] = [datos[clave]];
                }
                datos[clave].push(valor);
            } else {
                datos[clave] = valor;
            }
        });

        var categorias = [];
        var checks = form.querySelectorAll('input[name="categorias_mime[]"]:checked');
        checks.forEach(function (c) {
            categorias.push(c.value); });
        datos['tipos_mime_permitidos'] = categorias.join(',');

        var campos = form.querySelectorAll('input[data-version]');
        datos['versiones'] = {};
        campos.forEach(function (campo) {
            datos['versiones'][campo.name] = parseInt(campo.getAttribute('data-version'), 10);
        });

        return datos;
    }

    function actualizarEstadoBoton(form)
    {
        var input = form.querySelector('#cfg-confirmacion');
        var boton = form.querySelector('#btn-guardar-config');
        if (input && boton) {
            boton.disabled = input.value.trim().toUpperCase() !== 'CONFIRMAR';
        }
    }

    function enviarConfiguracion(form, mensajeEl)
    {
        var datos = serializarFormulario(form);
        delete datos['versiones'];

        var modoRepositorio = document.getElementById('cfg-modo-repositorio')?.checked || false;

        var payload = {
            token_peticion: csrfToken(),
            accion_crud: 'actualizar_configuracion_archivos',
            confirmacion: datos.confirmacion,
            tamano_maximo_mb: parseInt(datos.tamano_maximo_mb, 10),
            tipos_mime_permitidos: modoRepositorio ? '*' : datos.tipos_mime_permitidos,
            cuota_usuario_mb: parseInt(datos.cuota_usuario_mb, 10),
            extensiones_permitidas: modoRepositorio ? '*' : datos.extensiones_permitidas,
            memoria_php_mb: parseInt(datos.memoria_php_mb, 10),
            tiempo_ejecucion_seg: parseInt(datos.tiempo_ejecucion_seg, 10),
            maximo_subidas_simultaneas: parseInt(datos.maximo_subidas_simultaneas, 10),
            post_max_size_mb: parseInt(datos.post_max_size_mb, 10),
            modo_repositorio: modoRepositorio ? 1 : 0,
        };

        fetch(window.rutaApi || '/api', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken(),
            },
            body: new URLSearchParams(payload).toString()
        })
        .then(function (r) {
            return r.json().then(function (resp) {
                if (r.ok && resp.estado_operacion) {
                    var html = '<div class="notificacion-flotante estado-visible" data-variante="exito">Configuracion guardada correctamente.</div>';
                    if (resp.datos && resp.datos.hubo_conflictos) {
                        html += '<div class="notificacion-flotante estado-visible" data-variante="advertencia"><strong>Aviso:</strong> Tu version era antigua, sobrescribiste cambios recientes.</div>';
                    }
                    if (resp.datos && resp.datos.limites_php) {
                        var lp = resp.datos.limites_php;
                        html += '<div class="notificacion-flotante estado-visible" data-variante="informacion">Nuevos limites PHP: upload_max_filesize=' + lp.upload_max_filesize + 'M, post_max_size=' + lp.post_max_size + 'M</div>';
                    }
                    if (resp.datos && resp.datos.advertencia_ini) {
                        html += '<div class="notificacion-flotante estado-visible" data-variante="advertencia"><strong>Advertencia:</strong> ' + resp.datos.advertencia_ini + '</div>';
                    }
                    if (resp.datos && resp.datos.contenido_htaccess) {
                        var pre = document.querySelector('#contenido-user-ini');
                        if (pre) {
                            pre.textContent = resp.datos.contenido_htaccess;
                        }
                    }
                    mensajeEl.innerHTML = html;
                    form.querySelector('#cfg-confirmacion').value = '';
                    actualizarEstadoBoton(form);
                } else {
                    mensajeEl.innerHTML = '<div class="notificacion-flotante estado-visible" data-variante="peligro">Error: ' + (resp.mensaje_error || 'Error desconocido del servidor') + '</div>';
                }
            });
        })
        .catch(function (err) {
            mensajeEl.innerHTML = '<div class="notificacion-flotante estado-visible" data-variante="peligro">Error de red: ' + err.message + '</div>';
        });
    }

    function alternarModoRepositorio()
    {
        var checkbox = document.getElementById('cfg-modo-repositorio');
        var seccionExts = document.getElementById('cfg-seccion-extensiones');
        if (!checkbox || !seccionExts) {
            return;
        }

        var desactivar = checkbox.checked;
        seccionExts.style.opacity = desactivar ? '0.4' : '1';
        seccionExts.querySelectorAll('input, textarea, button, select').forEach(function (el) {
            el.disabled = desactivar;
        });

        var checks = document.querySelectorAll('input[name="categorias_mime[]"]');
        checks.forEach(function (c) {
            c.disabled = desactivar; });
        var labels = document.querySelectorAll('input[name="categorias_mime[]"]');
        labels.forEach(function (l) {
            var label = l.closest('label');
            if (label) {
                label.style.opacity = desactivar ? '0.4' : '1';
            }
        });

        var fieldset = document.querySelector('fieldset.tarjeta');
        if (fieldset) {
            fieldset.style.opacity = desactivar ? '0.4' : '1';
        }
    }

    function inicializarPestanas()
    {
        var pestanas = document.querySelectorAll('.pestana');
        var paneles = document.querySelectorAll('.pestana-panel');

        pestanas.forEach(function (pestana) {
            pestana.addEventListener('click', function () {
                var tab = this.getAttribute('data-tab');

                pestanas.forEach(function (p) {
                    p.classList.remove('activa'); });
                this.classList.add('activa');

                paneles.forEach(function (panel) {
                    if (panel.getAttribute('data-panel') === tab) {
                        panel.hidden = false;
                        panel.classList.add('activo');
                    } else {
                        panel.hidden = true;
                        panel.classList.remove('activo');
                    }
                });
            });
        });
    }

    function inicializar()
    {
        inicializarPestanas();

        var form = document.getElementById('formularioConfiguracionArchivos');
        if (form) {
            var inputConfirmar = form.querySelector('#cfg-confirmacion');
            if (inputConfirmar) {
                inputConfirmar.addEventListener('input', function () {
                    actualizarEstadoBoton(form);
                });
            }

            var modoRepoCheck = document.getElementById('cfg-modo-repositorio');
            if (modoRepoCheck) {
                modoRepoCheck.addEventListener('change', alternarModoRepositorio);
                alternarModoRepositorio();
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var mensajeEl = document.getElementById('mensaje-config-archivos');
                enviarConfiguracion(form, mensajeEl);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }
})();
