class ConfirmadorHubble {
    constructor()
    {
        this.elemento = null;
        this.resolucion = null;
    }

    mostrar(mensaje, textoAceptar, modoPeligro)
    {
        if (this.elemento) {
            this.cerrar();
        }

        textoAceptar = textoAceptar || 'Confirmar';
        modoPeligro = modoPeligro !== false;

        var plantilla = '<div class="modal-superposicion" id="confirmacion-hubble" role="dialog" aria-modal="true">'
            + '<div class="ventana-confirmacion">'
            + '<div class="modal-cabecera">'
            + '<h2>Confirmar accion</h2>'
            + '<button type="button" class="modal-cerrar" id="confirmacion-cerrar" aria-label="Cerrar">&times;</button>'
            + '</div>'
            + '<p class="margen-inferior-normal" id="confirmacion-mensaje">' + mensaje + '</p>'
            + '<div class="modal-acciones">'
            + '<button type="button" class="accion-boton variante-borde" id="confirmacion-cancelar">Cancelar</button>'
            + '<button type="button" class="accion-boton ' + (modoPeligro ? 'variante-peligro' : 'variante-solida') + '" id="confirmacion-aceptar">' + textoAceptar + '</button>'
            + '</div>'
            + '</div>'
            + '</div>';

        var contenedor = document.createElement('div');
        contenedor.innerHTML = plantilla;
        this.elemento = contenedor.firstElementChild;
        document.body.appendChild(this.elemento);

        var self = this;

        return new Promise(function (resolver) {
            self.resolucion = resolver;

            function resolverCon(valor)
            {
                self.cerrar();
                resolver(valor);
            }

            self.elemento.querySelector('#confirmacion-aceptar').addEventListener('click', function () {
                resolverCon(true);
            });

            self.elemento.querySelector('#confirmacion-cancelar').addEventListener('click', function () {
                resolverCon(false);
            });

            self.elemento.querySelector('#confirmacion-cerrar').addEventListener('click', function () {
                resolverCon(false);
            });

            self.elemento.addEventListener('click', function (e) {
                if (e.target === self.elemento) {
                    resolverCon(false);
                }
            });

            self.manejadorTecla = function (e) {
                if (e.key === 'Escape') {
                    resolverCon(false);
                }
            };
            document.addEventListener('keydown', self.manejadorTecla);
        });
    }

    cerrar()
    {
        if (this.elemento) {
            this.elemento.remove();
            this.elemento = null;
        }
        if (this.manejadorTecla) {
            document.removeEventListener('keydown', this.manejadorTecla);
            this.manejadorTecla = null;
        }
        this.resolucion = null;
    }
}

var confirmadorHubble = new ConfirmadorHubble();
window.ConfirmadorHubble = confirmadorHubble;

export { ConfirmadorHubble, confirmadorHubble };
