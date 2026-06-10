(function () {
    'use strict';

    // Boton de volver atras
    document.querySelector('[data-accion="volver-atras"]')?.addEventListener('click', function (e) {
        e.preventDefault();
        history.back();
    });

    // Boton de reparar via API
    var repararBtns = document.querySelectorAll('[data-accion="reparar-api"]');
    repararBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var tipo = this.getAttribute('data-reparar-tipo') || '';
            var token = document.querySelector('meta[name="csrf-token"]');
            var csrf = token ? token.getAttribute('content') : '';
            var estadoEl = document.getElementById('estado-reparacion');

            if (estadoEl) {
                estadoEl.textContent = 'Ejecutando reparacion...';
                estadoEl.className = 'error-reparacion-estado';
            }

            this.disabled = true;
            this.textContent = 'Reparando...';

            fetch('/api/diagnostico/reparar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrf
                },
                body: JSON.stringify({
                    tipo: tipo,
                    token_peticion: csrf
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.exito) {
                    if (estadoEl) {
                        estadoEl.textContent = 'Reparacion exitosa. Recargando...';
                        estadoEl.className = 'error-reparacion-estado error-reparacion-exito';
                    }
                    setTimeout(function () { window.location.reload(); }, 1500);
                } else {
                    if (estadoEl) {
                        estadoEl.textContent = data.mensaje || 'No se pudo reparar automaticamente.';
                        estadoEl.className = 'error-reparacion-estado error-reparacion-fallo';
                    }
                    btn.disabled = false;
                    btn.textContent = 'Reintentar reparar';
                }
            })
            .catch(function () {
                if (estadoEl) {
                    estadoEl.textContent = 'Error de conexion al intentar reparar.';
                    estadoEl.className = 'error-reparacion-estado error-reparacion-fallo';
                }
                btn.disabled = false;
                btn.textContent = 'Reintentar reparar';
            });
        });
    });
})();
