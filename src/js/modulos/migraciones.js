(function() {
    'use strict';

    var ejecutando = false;
    var modalConfirmar = document.getElementById('modal-confirmar');
    var modalSql = document.getElementById('modal-sql');
    var contenedorLista = document.getElementById('contenedor-lista-migraciones');
    function csrfToken() {
        var input = document.querySelector('[name="token_peticion"]');
        return input ? input.value : '';
    }

    function rotarToken(nuevoToken) {
        if (!nuevoToken) return;
        document.querySelectorAll('[name="token_peticion"]').forEach(function(el) {
            el.value = nuevoToken;
        });
    }

    function escapar(texto) {
        if (!texto) return '';
        return texto.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function mostrarResultado(detalle) {
        var contenedor = document.getElementById('resultado-ejecucion');
        if (!contenedor) return;
        contenedor.style.display = 'block';

        var html = '';
        var ok = 0;
        var fallos = 0;

        for (var i = 0; i < detalle.length; i++) {
            var r = detalle[i];
            if (r.estado === 'aplicada') {
                ok++;
                html += '<p class="texto-exito">&#10003; ' + escapar(r.archivo) + '</p>';
            } else if (r.estado === 'error') {
                fallos++;
                html += '<p class="texto-peligro">&#10007; ' + escapar(r.archivo) + ': ' + escapar(r.mensaje || 'Error desconocido') + '</p>';
            } else {
                html += '<p class="texto-suave">&#8212; ' + escapar(r.archivo) + ' (' + escapar(r.mensaje || '') + ')</p>';
            }
        }

        var resumenTexto = ok + ' aplicada(s)';
        if (fallos > 0) resumenTexto += ', ' + fallos + ' error(es)';
        var claseResumen = fallos > 0 ? 'texto-peligro' : 'texto-exito';

        contenedor.innerHTML = '<div class="margen-inferior-normal"><p class="texto-negrita ' + claseResumen + '">' + resumenTexto + '</p></div>' + html;
    }

    function actualizarResumen(resumen) {
        if (!resumen) return;
        var totalEl = document.getElementById('resumen-total');
        var aplEl = document.getElementById('resumen-aplicadas');
        var pendEl = document.getElementById('resumen-pendientes');
        var contadorEl = document.getElementById('contador-migraciones');
        if (totalEl) totalEl.textContent = resumen.total;
        if (aplEl) aplEl.textContent = resumen.aplicadas;
        if (pendEl) pendEl.textContent = resumen.pendientes;
        if (contadorEl) contadorEl.textContent = resumen.total;
    }

    async function refrescarLista() {
        try {
            var res = await fetch(window.location.pathname + '?partial=lista', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            var html = await res.text();
            if (contenedorLista) contenedorLista.innerHTML = html;
        } catch (e) {
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar('Error al recargar la lista.', 'peligro');
            }
        }
    }

    async function llamarApi(accionCrud, datosExtra) {
        var datos = Object.assign({
            accion_crud: accionCrud,
            token_peticion: csrfToken()
        }, datosExtra || {});

        var res = await fetch(window.rutaApi || '/api', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': datos.token_peticion
            },
            body: JSON.stringify(datos)
        });

        var resultado = await res.json();
        rotarToken(resultado.nuevo_token);
        return resultado;
    }

    async function ejecutarTodos() {
        if (ejecutando) return;
        ejecutando = true;
        if (modalConfirmar) modalConfirmar.hidden = true;

        var contenedorResultado = document.getElementById('resultado-ejecucion');
        if (contenedorResultado) contenedorResultado.style.display = 'none';

        try {
            var resultado = await llamarApi('migraciones_ejecutar');

            if (resultado.estado_operacion === true) {
                await refrescarLista();
                if (resultado.datos && resultado.datos.detalle) {
                    mostrarResultado(resultado.datos.detalle);
                }
                if (resultado.datos && resultado.datos.resumen) {
                    actualizarResumen(resultado.datos.resumen);
                }
                if (window.NotificadorHubble) {
                    window.NotificadorHubble.mostrar(resultado.mensaje_error || 'Migraciones ejecutadas.', 'exito');
                }
            } else {
                await refrescarLista();
                mostrarResultado([{
                    archivo: 'Error',
                    estado: 'error',
                    mensaje: resultado.mensaje_error || 'Error al ejecutar migraciones'
                }]);
                if (window.NotificadorHubble) {
                    window.NotificadorHubble.mostrar(resultado.mensaje_error || 'Error al ejecutar migraciones.', 'peligro');
                }
            }
        } catch (e) {
            await refrescarLista();
            mostrarResultado([{
                archivo: 'Conexion',
                estado: 'error',
                mensaje: 'Error de conexion al ejecutar migraciones.'
            }]);
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar('Error de conexion con el servidor.', 'peligro');
            }
        } finally {
            ejecutando = false;
        }
    }

    async function ejecutarIndividual(archivo) {
        if (ejecutando) return;
        ejecutando = true;

        try {
            var resultado = await llamarApi('migraciones_ejecutar_individual', { archivo: archivo });

            await refrescarLista();
            if (resultado.datos && resultado.datos.detalle) {
                mostrarResultado(resultado.datos.detalle);
            }
            if (resultado.datos && resultado.datos.resumen) {
                actualizarResumen(resultado.datos.resumen);
            }
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar(
                    resultado.mensaje_error || (resultado.estado_operacion ? 'Migracion aplicada.' : 'Error.'),
                    resultado.estado_operacion ? 'exito' : 'peligro'
                );
            }
        } catch (e) {
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar('Error de conexion con el servidor.', 'peligro');
            }
        } finally {
            ejecutando = false;
        }
    }

    async function resetear(archivo) {
        var confirmado = await window.ConfirmadorHubble.mostrar('Resetear "' + archivo + '"? Podra ser re-aplicada.');
        if (!confirmado) return;

        try {
            var resultado = await llamarApi('migraciones_resetear', { archivo: archivo });

            await refrescarLista();
            if (resultado.datos && resultado.datos.resumen) {
                actualizarResumen(resultado.datos.resumen);
            }
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar(
                    resultado.mensaje_error || 'Error al resetear.',
                    resultado.estado_operacion ? 'exito' : 'peligro'
                );
            }
        } catch (e) {
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar('Error de conexion con el servidor.', 'peligro');
            }
        }
    }

    async function verSql(archivo) {
        try {
            var resultado = await llamarApi('migraciones_ver_sql', { archivo: archivo });
            rotarToken(resultado.nuevo_token);

            if (resultado.estado_operacion !== true) {
                if (window.NotificadorHubble) window.NotificadorHubble.mostrar(resultado.mensaje_error || 'Error al cargar el SQL.', 'peligro');
                return;
            }

            var codigo = document.getElementById('codigo-sql');
            var titulo = document.getElementById('titulo-modal-sql');
            if (codigo) codigo.textContent = resultado.datos.sql;
            if (titulo) titulo.textContent = resultado.datos.archivo;
            if (modalSql) modalSql.hidden = false;
        } catch (e) {
            if (window.NotificadorHubble) window.NotificadorHubble.mostrar('Error de conexion al cargar el SQL.', 'peligro');
        }
    }

    async function crearRespaldo() {
        try {
            var resultado = await llamarApi('migraciones_respaldo');

            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar(
                    resultado.mensaje_error || 'Error al crear respaldo.',
                    resultado.estado_operacion ? 'exito' : 'peligro'
                );
            }
        } catch (e) {
            if (window.NotificadorHubble) {
                window.NotificadorHubble.mostrar('Error de conexion con el servidor.', 'peligro');
            }
        }
    }

    function abrirModalConfirmar() {
        if (!modalConfirmar) {
            ejecutarTodos();
            return;
        }

        var previews = document.querySelectorAll('.preview-sql[data-estado="pendiente"]');
        var mensaje = document.getElementById('mensaje-modal');
        if (mensaje) {
            mensaje.textContent = 'Se ejecutaran ' + previews.length + ' migracion(es) pendiente(s). Esta accion modificara la base de datos.';
        }

        modalConfirmar.hidden = false;
    }

    function cerrarModales() {
        if (modalConfirmar) modalConfirmar.hidden = true;
        if (modalSql) modalSql.hidden = true;
    }

    document.addEventListener('click', function(e) {
        var target = e.target;

        if (target.id === 'boton-ejecutar') {
            abrirModalConfirmar();
            return;
        }

        if (target.id === 'boton-respaldo') {
            crearRespaldo();
            return;
        }

        if (target.id === 'confirmar-ejecutar') {
            ejecutarTodos();
            return;
        }

        if (target.classList.contains('btn-ejecutar-individual')) {
            var archivo = target.dataset.archivo;
            if (archivo) ejecutarIndividual(archivo);
            return;
        }

        if (target.classList.contains('btn-resetear')) {
            var archivo = target.dataset.archivo;
            if (archivo) resetear(archivo);
            return;
        }

        if (target.classList.contains('btn-ver-sql')) {
            var archivo = target.dataset.archivo;
            if (archivo) verSql(archivo);
            return;
        }

        if (target.classList.contains('btn-descargar-respaldo')) {
            var archivo = target.dataset.archivo;
            if (archivo) descargarRespaldo(archivo);
            return;
        }

        if (target.classList.contains('btn-eliminar-respaldo')) {
            var archivo = target.dataset.archivo;
            if (archivo) eliminarRespaldo(archivo);
            return;
        }

        if (target.classList.contains('btn-restaurar-respaldo')) {
            var archivo = target.dataset.archivo;
            if (archivo) restaurarRespaldo(archivo);
            return;
        }
    });

    function descargarRespaldo(archivo) {
        window.open(window.obtenerBasePath() + '/migraciones/respaldos/descargar/' + encodeURIComponent(archivo), '_blank');
    }

    function eliminarRespaldo(archivo) {
        window.ConfirmadorHubble.mostrar('Eliminar el respaldo "' + archivo + '"? Esta accion no se puede deshacer.').then(function(confirmado) {
            if (!confirmado) return;

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = window.obtenerBasePath() + '/migraciones/respaldos/eliminar';
            form.style.display = 'none';
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'archivo';
            input.value = archivo;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        });
    }

    function restaurarRespaldo(archivo) {
        var modalRestaurar = document.getElementById('modal-restaurar-respaldo');
        var mensaje = document.getElementById('mensaje-modal-restaurar');
        if (!modalRestaurar || !mensaje) return;

        mensaje.textContent = 'Se restaurara la base de datos desde el respaldo "' + archivo + '".';
        modalRestaurar.dataset.archivo = archivo;
        modalRestaurar.hidden = false;
    }

    function ejecutarRestauracion() {
        var modalRestaurar = document.getElementById('modal-restaurar-respaldo');
        var archivo = modalRestaurar ? modalRestaurar.dataset.archivo : '';
        if (!archivo) return;

        modalRestaurar.hidden = true;

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.obtenerBasePath() + '/migraciones/respaldos/restaurar';
        form.style.display = 'none';
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'archivo';
        input.value = archivo;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }

    function cerrarModalesRespaldos() {
        var modalRestaurar = document.getElementById('modal-restaurar-respaldo');
        if (modalRestaurar) modalRestaurar.hidden = true;
    }

    var modalRestaurarRespaldo = document.getElementById('modal-restaurar-respaldo');
    if (modalRestaurarRespaldo) {
        modalRestaurarRespaldo.addEventListener('click', function(e) {
            if (e.target === modalRestaurarRespaldo) cerrarModalesRespaldos();
        });
    }

    document.querySelectorAll('.modal-cerrar-respaldo').forEach(function(btn) {
        btn.addEventListener('click', cerrarModalesRespaldos);
    });

    document.querySelectorAll('.modal-cerrar').forEach(function(btn) {
        btn.addEventListener('click', cerrarModales);
    });

    var btnConfirmarRestaurar = document.getElementById('confirmar-restaurar');
    if (btnConfirmarRestaurar) {
        btnConfirmarRestaurar.addEventListener('click', ejecutarRestauracion);
    }

    if (modalConfirmar) {
        modalConfirmar.addEventListener('click', function(e) {
            if (e.target === modalConfirmar) cerrarModales();
        });
    }

    if (modalSql) {
        modalSql.addEventListener('click', function(e) {
            if (e.target === modalSql) cerrarModales();
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModales();
            cerrarModalesRespaldos();
        }
    });
})();
