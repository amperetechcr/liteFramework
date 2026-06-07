class NotificadorHubble {
    constructor() {
        this.contenedor = this.crearContenedor();
        this.cola = [];
        this.temporizadorLote = null;
        this.maxVisibles = 5;
        this.retrasoEntreNotificaciones = 180;
        this.duracionMinima = 1500;
        this.duracionPorTipo = {
            exito: 3000,
            peligro: 5000,
            informacion: 4000,
            advertencia: 6000
        };
    }

    crearContenedor() {
        let contenedor = document.getElementById('contenedor-notificaciones-hubble');
        if (!contenedor) {
            contenedor = document.createElement('div');
            contenedor.id = 'contenedor-notificaciones-hubble';
            contenedor.setAttribute('aria-live', 'polite');
            contenedor.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:1rem;max-width:450px;max-height:80vh;overflow-y:auto;pointer-events:none;';
            document.body.appendChild(contenedor);
        }
        return contenedor;
    }

    factorDuracion(total) {
        if (total <= 2) return 1;
        if (total <= 4) return 0.7;
        if (total <= 6) return 0.5;
        return 0.35;
    }

    mostrarConSugerencias(mensaje, sugerencias, tipo, duracion) {
        var html = mensaje ? '<p>' + mensaje + '</p>' : '';
        if (sugerencias && sugerencias.length) {
            html += '<ul style="margin:8px 0 0 0;padding-left:16px;font-size:12px;text-align:left">';
            sugerencias.forEach(function(s) {
                html += '<li style="margin-bottom:4px">' + s + '</li>';
            });
            html += '</ul>';
        }
        this.mostrar(html, tipo, duracion);
    }

    mostrar(mensaje, tipo, duracion) {
        tipo = tipo || 'informacion';
        const base = duracion || this.duracionPorTipo[tipo] || 4000;
        this.cola.push({ mensaje, tipo, duracionBase: base });

        if (this.temporizadorLote) {
            clearTimeout(this.temporizadorLote);
        }
        this.temporizadorLote = setTimeout(() => this.vaciarCola(), 50);
    }

    vaciarCola() {
        this.temporizadorLote = null;
        const lote = [...this.cola];
        this.cola = [];

        if (!lote.length) return;

        const factor = this.factorDuracion(lote.length);

        lote.forEach((item, indice) => {
            const duracionFinal = Math.max(
                Math.round(item.duracionBase * factor),
                this.duracionMinima
            );

            setTimeout(() => {
                this.limitarVisibles();
                this.mostrarNotificacion(item.mensaje, item.tipo, duracionFinal);
            }, indice * this.retrasoEntreNotificaciones);
        });
    }

    limitarVisibles() {
        while (this.contenedor.children.length >= this.maxVisibles) {
            this.cerrarElemento(this.contenedor.children[0]);
        }
    }

    mostrarNotificacion(mensaje, tipo, duracion) {
        const elemento = document.createElement('div');
        elemento.setAttribute('role', 'alert');
        elemento.className = 'notificacion-flotante';
        elemento.setAttribute('data-variante', tipo);

        const texto = document.createElement('span');
        texto.textContent = mensaje;
        texto.style.cssText = 'flex:1;line-height:1.4;';
        elemento.appendChild(texto);

        const botonCerrar = document.createElement('button');
        botonCerrar.className = 'notificacion-cerrar';
        botonCerrar.innerHTML = '&times;';
        botonCerrar.setAttribute('aria-label', 'Cerrar notificaci\u00F3n');
        botonCerrar.addEventListener('click', (e) => {
            e.stopPropagation();
            this.cerrarElemento(elemento);
        });
        elemento.appendChild(botonCerrar);

        const cerrar = () => this.cerrarElemento(elemento);

        let temporizador = setTimeout(cerrar, duracion);

        elemento.addEventListener('mouseenter', () => clearTimeout(temporizador));
        elemento.addEventListener('mouseleave', () => {
            temporizador = setTimeout(cerrar, duracion);
        });

        this.iniciarDeslizamiento(elemento, cerrar);

        this.contenedor.appendChild(elemento);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                elemento.classList.add('estado-visible');
            });
        });
    }

    cerrarElemento(elemento) {
        if (elemento._cerrando) return;
        elemento._cerrando = true;
        elemento.classList.remove('estado-visible');
        elemento.style.transition = '';
        elemento.style.transform = '';
        elemento.style.opacity = '';
        setTimeout(() => {
            if (elemento.parentNode) {
                elemento.remove();
            }
        }, 400);
    }

    iniciarDeslizamiento(elemento, alCerrar) {
        let inicioX = 0, desplazamientoX = 0, arrastrando = false;

        const alInicio = (x) => {
            inicioX = x;
            arrastrando = true;
            elemento.style.transition = 'none';
        };

        const alMovimiento = (x) => {
            if (!arrastrando) return;
            desplazamientoX = x - inicioX;
            if (desplazamientoX > 0) desplazamientoX = 0;
            elemento.style.transform = `translateX(${desplazamientoX}px)`;
            elemento.style.opacity = 1 - Math.min(Math.abs(desplazamientoX) / 200, 0.8);
        };

        const alFinal = () => {
            if (!arrastrando) return;
            arrastrando = false;
            elemento.style.transition = 'opacity 0.3s ease, transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            if (Math.abs(desplazamientoX) > 80) {
                alCerrar();
            } else {
                elemento.style.transform = '';
                elemento.style.opacity = '';
            }
        };

        elemento.addEventListener('mousedown', (e) => alInicio(e.clientX));
        document.addEventListener('mousemove', (e) => alMovimiento(e.clientX));
        document.addEventListener('mouseup', alFinal);

        elemento.addEventListener('touchstart', (e) => {
            const toque = e.touches[0];
            alInicio(toque.clientX);
        }, { passive: true });
        elemento.addEventListener('touchmove', (e) => {
            const toque = e.touches[0];
            alMovimiento(toque.clientX);
        }, { passive: true });
        elemento.addEventListener('touchend', alFinal, { passive: true });
    }
}

const notificadorHubble = new NotificadorHubble();
window.NotificadorHubble = notificadorHubble;

function MostrarNotificacionSistema(mensaje, tipo) {
    if (window.NotificadorHubble) {
        window.NotificadorHubble.mostrar(mensaje, tipo);
    }
}

export { NotificadorHubble, notificadorHubble, MostrarNotificacionSistema };
