var Graficos = (function () {
    'use strict';

    var coloresDefecto = ['#4f46e5', '#059669', '#d97706', '#dc2626', '#2563eb', '#7c3aed', '#0891b2', '#be123c'];

    function color(indice, personalizados) {
        var paleta = personalizados && personalizados.length > 0 ? personalizados : coloresDefecto;
        return paleta[indice % paleta.length];
    }

    function formatoValor(valor) {
        if (valor >= 1000000) return (valor / 1000000).toFixed(1) + 'M';
        if (valor >= 1000) return (valor / 1000).toFixed(1) + 'K';
        if (valor === Math.floor(valor)) return valor.toString();
        return valor.toFixed(1);
    }

    function dibujarBarras(canvas, datos, opciones) {
        opciones = opciones || {};
        var ctx = canvas.getContext('2d');
        var dpr = window.devicePixelRatio || 1;
        var ancho = canvas.clientWidth;
        var alto = canvas.clientHeight;
        canvas.width = ancho * dpr;
        canvas.height = alto * dpr;
        ctx.scale(dpr, dpr);

        var padding = { top: 16, right: 16, bottom: 40, left: 80 };
        if (ancho < 300) padding.left = 50;

        var w = ancho - padding.left - padding.right;
        var h = alto - padding.top - padding.bottom;
        var valores = datos.map(function (d) { return d.valor; });
        var maxValor = Math.max.apply(null, valores) || 1;
        var barras = datos.length;
        var anchoBarra = Math.min(40, (w / barras) * 0.6);
        var separacion = (w - anchoBarra * barras) / (barras + 1);
        if (separacion < 4) {
            anchoBarra = (w - 4 * (barras + 1)) / barras;
            separacion = 4;
        }

        ctx.clearRect(0, 0, ancho, alto);

        var personalizados = opciones.colores || null;

        datos.forEach(function (d, i) {
            var x = padding.left + separacion + i * (anchoBarra + separacion);
            var altura = (d.valor / maxValor) * h;
            var y = padding.top + h - altura;

            var c = color(i, personalizados);
            ctx.fillStyle = c;
            ctx.beginPath();
            ctx.roundRect(x, y, anchoBarra, altura, [4, 4, 0, 0]);
            ctx.fill();

            ctx.fillStyle = getComputedStyle(canvas).getPropertyValue('--texto-base').trim() || '#334155';
            ctx.font = '11px system-ui, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(d.etiqueta, x + anchoBarra / 2, padding.top + h + 16);
        });

        var mejorValor = Math.max.apply(null, valores);
        ctx.fillStyle = getComputedStyle(canvas).getPropertyValue('--texto-muy-suave').trim() || '#94a3b8';
        ctx.font = '10px system-ui, sans-serif';
        ctx.textAlign = 'right';
        for (var i = 0; i <= 4; i++) {
            var v = (maxValor / 4) * i;
            var yPos = padding.top + h - (v / maxValor) * h;
            ctx.fillText(formatoValor(v), padding.left - 8, yPos + 4);
            ctx.strokeStyle = getComputedStyle(canvas).getPropertyValue('--trazo-muy-suave').trim() || '#f1f5f9';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padding.left, yPos);
            ctx.lineTo(ancho - padding.right, yPos);
            ctx.stroke();
        }
    }

    function dibujarPastel(canvas, datos, opciones) {
        opciones = opciones || {};
        var ctx = canvas.getContext('2d');
        var dpr = window.devicePixelRatio || 1;
        var ancho = canvas.clientWidth;
        var alto = canvas.clientHeight;
        canvas.width = ancho * dpr;
        canvas.height = alto * dpr;
        ctx.scale(dpr, dpr);

        var centroX = ancho / 2;
        var centroY = alto / 2;
        var radio = Math.min(ancho, alto) / 2 - 20;
        if (radio < 10) radio = 10;

        ctx.clearRect(0, 0, ancho, alto);

        var total = datos.reduce(function (s, d) { return s + d.valor; }, 0);
        if (total === 0) {
            ctx.fillStyle = getComputedStyle(canvas).getPropertyValue('--texto-muy-suave').trim() || '#94a3b8';
            ctx.font = '14px system-ui, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('Sin datos', centroX, centroY);
            return;
        }

        var personalizados = opciones.colores || null;
        var anguloInicio = -Math.PI / 2;

        datos.forEach(function (d, i) {
            var porcion = (d.valor / total) * Math.PI * 2;
            var anguloFin = anguloInicio + porcion;

            var c = color(i, personalizados);
            ctx.fillStyle = c;
            ctx.beginPath();
            ctx.moveTo(centroX, centroY);
            ctx.arc(centroX, centroY, radio, anguloInicio, anguloFin);
            ctx.closePath();
            ctx.fill();

            var anguloLabel = anguloInicio + porcion / 2;
            var distancia = radio * 0.65;
            var lx = centroX + Math.cos(anguloLabel) * distancia;
            var ly = centroY + Math.sin(anguloLabel) * distancia;

            var pct = ((d.valor / total) * 100).toFixed(1);
            if (parseFloat(pct) > 4) {
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 12px system-ui, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(pct + '%', lx, ly);
            }

            anguloInicio = anguloFin;
        });
    }

    function dibujarLinea(canvas, datos, opciones) {
        opciones = opciones || {};
        var ctx = canvas.getContext('2d');
        var dpr = window.devicePixelRatio || 1;
        var ancho = canvas.clientWidth;
        var alto = canvas.clientHeight;
        canvas.width = ancho * dpr;
        canvas.height = alto * dpr;
        ctx.scale(dpr, dpr);

        var padding = { top: 16, right: 16, bottom: 40, left: 50 };
        var w = ancho - padding.left - padding.right;
        var h = alto - padding.top - padding.bottom;
        var valores = datos.map(function (d) { return d.valor; });
        var maxValor = Math.max.apply(null, valores) || 1;
        var minValor = Math.min.apply(null, valores) || 0;
        var rango = maxValor - minValor || 1;

        ctx.clearRect(0, 0, ancho, alto);

        var c = (opciones.colores && opciones.colores[0]) || coloresDefecto[0];
        var puntos = datos.map(function (d, i) {
            var x = padding.left + (i / Math.max(datos.length - 1, 1)) * w;
            var y = padding.top + h - ((d.valor - minValor) / rango) * h;
            return { x: x, y: y };
        });

        ctx.strokeStyle = c;
        ctx.lineWidth = 2;
        ctx.lineJoin = 'round';
        ctx.beginPath();
        puntos.forEach(function (p, i) {
            if (i === 0) ctx.moveTo(p.x, p.y);
            else ctx.lineTo(p.x, p.y);
        });
        ctx.stroke();

        ctx.fillStyle = c + '20';
        ctx.beginPath();
        ctx.moveTo(puntos[0].x, padding.top + h);
        puntos.forEach(function (p) { ctx.lineTo(p.x, p.y); });
        ctx.lineTo(puntos[puntos.length - 1].x, padding.top + h);
        ctx.closePath();
        ctx.fill();

        puntos.forEach(function (p, i) {
            ctx.fillStyle = getComputedStyle(canvas).getPropertyValue('--texto-base').trim() || '#334155';
            ctx.font = '10px system-ui, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(datos[i].etiqueta, p.x, padding.top + h + 16);
        });
    }

    if (!CanvasRenderingContext2D.prototype.roundRect) {
        CanvasRenderingContext2D.prototype.roundRect = function (x, y, w, h, radii) {
            var r = Array.isArray(radii) ? radii : [radii, radii, radii, radii];
            this.moveTo(x + r[0], y);
            this.lineTo(x + w - r[1], y);
            this.quadraticCurveTo(x + w, y, x + w, y + r[1]);
            this.lineTo(x + w, y + h - r[2]);
            this.quadraticCurveTo(x + w, y + h, x + w - r[2], y + h);
            this.lineTo(x + r[3], y + h);
            this.quadraticCurveTo(x, y + h, x, y + h - r[3]);
            this.lineTo(x, y + r[0]);
            this.quadraticCurveTo(x, y, x + r[0], y);
            this.closePath();
        };
    }

    function responsable(canvas, dibujar) {
        var redibujar = function () { dibujar(); };
        window.addEventListener('resize', redibujar);
        var observer = new ResizeObserver(redibujar);
        observer.observe(canvas);
        return function () {
            window.removeEventListener('resize', redibujar);
            observer.disconnect();
        };
    }

    return {
        barras: function (canvas, datos, opciones) {
            if (!canvas || !datos || datos.length === 0) return;
            dibujarBarras(canvas, datos, opciones);
            return responsable(canvas, function () { dibujarBarras(canvas, datos, opciones); });
        },
        pastel: function (canvas, datos, opciones) {
            if (!canvas || !datos || datos.length === 0) return;
            dibujarPastel(canvas, datos, opciones);
            return responsable(canvas, function () { dibujarPastel(canvas, datos, opciones); });
        },
        linea: function (canvas, datos, opciones) {
            if (!canvas || !datos || datos.length === 0) return;
            dibujarLinea(canvas, datos, opciones);
            return responsable(canvas, function () { dibujarLinea(canvas, datos, opciones); });
        }
    };
})();
