function mostrarErrorCampo(campo, mensaje)
{
    campo.style.borderColor = 'var(--color-peligro)';

    let elementoError = campo.parentNode.querySelector('.campo-error');
    if (!elementoError) {
        elementoError = document.createElement('span');
        elementoError.className = 'campo-error';
        campo.parentNode.appendChild(elementoError);
    }
    elementoError.textContent = mensaje;
}

function limpiarErrorCampo(campo)
{
    campo.style.borderColor = '';
    const elementoError = campo.parentNode.querySelector('.campo-error');
    if (elementoError) {
        elementoError.textContent = '';
    }
}

function limpiarTodosErrores(formulario)
{
    formulario.querySelectorAll('.campo-error').forEach(el => el.remove());
    formulario.querySelectorAll('input, textarea, select').forEach(el => el.style.borderColor = '');
}

const SeguridadSistema = {
    mapaCaracteresPeligrosos: {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#x27;',
        "/": '&#x2F;'
    },

    sanitizarTexto: function (textoEntrada) {
        if (!textoEntrada) {
            return '';
        }
        const expresionRegularGlobal = /[&<>"'/] / ig;
        return textoEntrada.replace(expresionRegularGlobal, (caracter) => this.mapaCaracteresPeligrosos[caracter]);
    },

    validarEstructuraCorreo: function (correo) {
        const patronCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return patronCorreo.test(correo);
    },

    validarFuerzaContrasena: function (contrasena) {
        const longitudOk = contrasena.length >= 8;
        const tieneMayuscula = /[A-Z]/.test(contrasena);
        const tieneDigito = /\d/.test(contrasena);
        const tieneSimbolo = /[@$!%*?&]/.test(contrasena);
        return longitudOk && tieneMayuscula && tieneDigito && tieneSimbolo;
    },

    protegerFormulariosGlobales: function () {
        const todosLosFormularios = document.querySelectorAll('form');

        todosLosFormularios.forEach(formulario => {
            formulario.addEventListener('submit', (evento) => {
                const camposEntrada = formulario.querySelectorAll('input, textarea, select');
                let validacionAprobada = true;

                limpiarTodosErrores(formulario);

                camposEntrada.forEach(campo => {
                    const valorRecortado = campo.value.trim();

                    campo.style.borderColor = '';

                    if (campo.hasAttribute('required') && valorRecortado === '') {
                        validacionAprobada = false;
                        mostrarErrorCampo(campo, 'Este campo es obligatorio.');
                        return;
                    }

                    if (campo.type === 'email' && campo.value && !this.validarEstructuraCorreo(campo.value)) {
                        validacionAprobada = false;
                        mostrarErrorCampo(campo, 'El correo ingresado no tiene un formato v\u00E1lido.');
                        return;
                    }

                    if (campo.type === 'password' && campo.name === 'clave_registro' && campo.value) {
                        if (!this.validarFuerzaContrasena(campo.value)) {
                            validacionAprobada = false;
                            mostrarErrorCampo(campo, 'Debe tener 8+ caracteres, 1 may\u00FAscula, 1 n\u00FAmero y 1 s\u00EDmbolo (@$!%*?&).');
                            return;
                        }
                    }
                });

            if (!validacionAprobada) {
                evento.preventDefault();
                if (window.NotificadorHubble) {
                    window.NotificadorHubble.mostrar('Corrija los errores marcados en cada campo.', 'peligro', 3000);
                }
            }
            });
        });
    },

    inicializarProtocolos: function () {
        this.protegerFormulariosGlobales();
    }
};

export { SeguridadSistema, mostrarErrorCampo, limpiarErrorCampo, limpiarTodosErrores };
