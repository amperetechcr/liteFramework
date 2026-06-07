class LiteSse {
    constructor(url)
    {
        this.url = url;
        this.suscriptores = {};
        this.intentosReconexion = 0;
        this.maxIntentos = 10;
        this.conectado = false;
        this.eventSource = null;
        this.conectar();
    }

    conectar()
    {
        if (this.eventSource) {
            this.eventSource.close();
        }

        this.eventSource = new EventSource(this.url, { withCredentials: true });

        this.eventSource.onopen = () => {
            this.conectado = true;
            this.intentosReconexion = 0;
            if (window.NotificadorHubble && this.suscriptores['sse.conectado']) {
                this.suscriptores['sse.conectado'].forEach(cb => cb({ conectado: true }));
            }
        };

        this.eventSource.onmessage = (e) => {
            try {
                const datos = JSON.parse(e.data);
                this.despachar('mensaje', datos);
            } catch {
                this.despachar('mensaje', { raw: e.data });
            }
        };

        this.eventSource.addEventListener('sse.conectado', (e) => {
            try {
                this.despachar('sse.conectado', JSON.parse(e.data)); } catch { this.despachar('sse.conectado', {}); }
        });

        this.eventSource.addEventListener('sse.error', (e) => {
            try {
                this.despachar('sse.error', JSON.parse(e.data)); } catch { this.despachar('sse.error', {}); }
        });

        this.eventSource.onerror = () => {
            this.conectado = false;
            this.eventSource.close();
            this.intentosReconexion++;
            if (this.intentosReconexion <= this.maxIntentos) {
                setTimeout(() => this.conectar(), 3000);
            }
        };
    }

    subscribir(tipo, callback) {
        if (!this.suscriptores[tipo]) {
            this.suscriptores[tipo] = [];

            this.eventSource.addEventListener(tipo, (e) => {
                try {
                    const datos = JSON.parse(e.data);
                    this.suscriptores[tipo].forEach(cb => cb(datos));
                } catch {
                    this.suscriptores[tipo].forEach(cb => cb({ raw: e.data }));
                }
            });
        }

        this.suscriptores[tipo].push(callback);

        return () => {
            this.suscriptores[tipo] = this.suscriptores[tipo].filter(cb => cb !== callback);
        };
    }

    despachar(tipo, datos) {
        if (this.suscriptores[tipo]) {
            this.suscriptores[tipo].forEach(cb => cb(datos));
        }
    }

    cancelar() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        this.conectado = false;
        this.suscriptores = {};
    }
    }

    export { LiteSse };
