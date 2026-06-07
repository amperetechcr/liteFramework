document.querySelector('[data-accion="volver-atras"]')?.addEventListener('click', function (e) {
    e.preventDefault();
    history.back();
});
