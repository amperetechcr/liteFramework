document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.vincularFormularioAutenticacion === 'function') {
        window.vincularFormularioAutenticacion('formularioInicioSesion', 'iniciar_sesion');
        window.vincularFormularioAutenticacion('formularioRegistro', 'registrar_operador');
    }
});
