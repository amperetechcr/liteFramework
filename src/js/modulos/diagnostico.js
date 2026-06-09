(function () {
    'use strict';

    var btnExportar = document.getElementById('btn-exportar');
    var exportDataEl = document.getElementById('export-data');

    if (btnExportar && exportDataEl) {
        btnExportar.addEventListener('click', function () {
            try {
                var raw = exportDataEl.textContent || exportDataEl.innerText;
                var data = JSON.parse(raw);
                var blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'diagnostico-' + new Date().toISOString().slice(0, 10) + '.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch (e) {
                if (window.NotificadorHubble) {
                    window.NotificadorHubble.mostrar('Error al exportar diagnostico.', 'peligro', 4000);
                }
            }
        });
    }

    var repararBtns = document.querySelectorAll('[data-reparar-ajax]');
    repararBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var tipo = this.getAttribute('data-reparar-ajax');
            var form = this.closest('form');
            if (!form) { return; }
            var formData = new FormData(form);
            fetch(window.location.pathname, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function () { window.location.reload(); })
            .catch(function () {
                if (window.NotificadorHubble) {
                    window.NotificadorHubble.mostrar('Error de red al reparar.', 'peligro', 4000);
                }
            });
        });
    });
})();
