(function () {
    "use strict";

    var MAPEO_ROLES = {
        tech_lead:        "Valeria",
        pm:               "Laura",
        "project manager":"Laura",
        orquestador:      "Valeria",
        "orquestador de flujo": "Valeria",
        arquitecto:       "Laura",
        frontend_dev:     "Camila",
        backend_dev:      "Santiago",
        fullstack_dev:    "Felipe",
        devops:           "Andres",
        qa_engineer:      "Mariana",
        "qa engineer":    "Mariana",
        security_auditor: "Sofia",
        code_reviewer:    "Mateo",
        data_analyst:     "Lucia",
        technical_writer: "Renata",
        ui_ux_designer:   "Gabriela",
        code_cleaner:     "Emilio",
        legal_advisor:    "Sebastian",
        infra_engineer:   "Diego",
        research_analyst: "Valentina",
        ml_engineer:      "Victoria",
        data_engineer:    "Javier",
        algorithm_engineer: "Nicolas",
        research_scientist: "Antonella",
        content_creator:  "Daniela",
        marketing_manager:"Florencia",
        creative_director:"Pablo",
        ceo:              "Isabella",
        cfo:              "Martina",
        owner:            "Valeria",
        pm_asistente:     "Benjamin",
        opencode_bridge:  "Agustin",
    };

    var DESTINOS_SIMILARES = {
        "escritorio":  ["escritorio1","escritorio2","escritorio3","escritorio4"],
        "reunion":     ["reunion"],
        "sala reunion":["reunion"],
        "reuniones":   ["reunion"],
        "servidor":    ["servidor"],
        "server":      ["servidor"],
        "juegos":      ["juegos"],
        "gaming":      ["juegos"],
        "descanso":    ["descanso"],
        "break":       ["descanso"],
        "cafe":        ["descanso"],
        "biblioteca":  ["biblioteca"],
        "libreria":    ["biblioteca"],
        "libros":      ["biblioteca"],
    };

    function normalizarDestino(destino) {
        if (!destino) return "escritorio1";
        var d = destino.toLowerCase().trim();
        var mapeo = DESTINOS_SIMILARES[d];
        if (mapeo) return mapeo[Math.floor(Math.random() * mapeo.length)];
        var estaciones = ["escritorio1","escritorio2","escritorio3","escritorio4","reunion","servidor","biblioteca","juegos","descanso"];
        if (estaciones.indexOf(destino) !== -1) return destino;
        return "escritorio1";
    }

    function encontrarAgentePorEvento(evt) {
        var role = (evt.agent_role || evt.rol || "").toLowerCase().trim();
        var nombre = evt.nombre || "";
        var nombreEsperado = MAPEO_ROLES[role] || MAPEO_ROLES[evt.agent_role] || MAPEO_ROLES[evt.rol] || "";
        console.log('[PixelAmpCrew] buscar agente: role="'+role+'" nombre="'+nombre+'" mapeado="'+nombreEsperado+'"');
        if (nombreEsperado) {
            var encontrado = activos.find(function(a){return a.nombre===nombreEsperado;});
            console.log('[PixelAmpCrew] por MAPEO_ROLES:', encontrado ? encontrado.nombre : 'NO ENCONTRADO');
            if (encontrado) return encontrado;
        }
        if (nombre) {
            var encontrado = activos.find(function(a){return a.nombre.toLowerCase()===nombre.toLowerCase();});
            console.log('[PixelAmpCrew] por nombre:', encontrado ? encontrado.nombre : 'NO ENCONTRADO');
            if (encontrado) return encontrado;
        }
        console.log('[PixelAmpCrew] No se pudo mapear el evento a ningun agente');
        return null;
    }

    var CONFIG = {
        ANCHO: 720, ALTO: 500,
        COLOR_SUELO: "#2d2d44",
        COLOR_PARED: "#1a1a2e",
        COLOR_PARED_FRANJA: "#16162a",
        COLOR_PASILLO: "#3a3a55",
        COLOR_HEADER: "#0f0f23",
        TEXTO_CLARO: "#e8e8f0",
        TEXTO_SUAVE: "#9090b0",
        DURACION_MOVER: 1600,
        TAM_NPC: 52,
    };

    var AGENTES = window.PIXELAMP_DATA.agentes;
    var canvas, ctx;
    var activos = [];
    var logEventos = [];
    var frame = 0;
    var ocupados = {};
    var reconnectTimer = null;
    var TONOS_PIEL = ["#f5cba7","#f0c09c","#e8b88c","#f0c8a8","#ecc0a0","#f8d0b0","#e0b090","#d4a87c","#ecc8a8","#f0ccac"];

    var ESTACIONES = {
        escritorio1: { x: 25,  y: 75,  w: 140, h: 65, emoji: "\uD83D\uDCBB", label: "Escritorio 1", color: "#2d2d55" },
        escritorio2: { x: 195, y: 75,  w: 140, h: 65, emoji: "\uD83D\uDCBB", label: "Escritorio 2", color: "#2d2d55" },
        escritorio3: { x: 365, y: 75,  w: 140, h: 65, emoji: "\uD83D\uDCBB", label: "Escritorio 3", color: "#2d2d55" },
        escritorio4: { x: 535, y: 75,  w: 140, h: 65, emoji: "\uD83D\uDCBB", label: "Escritorio 4", color: "#2d2d55" },
        reunion:      { x: 25,  y: 350, w: 320, h: 110, emoji: "\uD83D\uDCAC", label: "Sala Reuniones",  color: "#2d2d55" },
        servidor:     { x: 370, y: 350, w: 140, h: 110, emoji: "\uD83D\uDDBB", label: "Servidor",       color: "#2d2d55" },
        juegos:       { x: 25,  y: 195, w: 200, h: 85, emoji: "\uD83C\uDFAE", label: "Sala Juegos",    color: "#2d2d55" },
        descanso:     { x: 495, y: 195, w: 190, h: 85, emoji: "\uD83D\uDECC", label: "Zona Descanso",  color: "#2d2d55" },
        biblioteca:   { x: 535, y: 350, w: 140, h: 110, emoji: "\uD83D\uDCDA", label: "Biblioteca",     color: "#2d2d55" },
    };

    var SLOTS = {
        escritorio1: [[25+140/2, 75+65/2]],
        escritorio2: [[195+140/2, 75+65/2]],
        escritorio3: [[365+140/2, 75+65/2]],
        escritorio4: [[535+140/2, 75+65/2]],
        reunion: [[55,375],[130,375],[205,375],[55,425],[130,425],[205,425]],
        servidor: [[370+140/2-20,350+110/2],[370+140/2+20,350+110/2]],
        juegos: [[25+40,195+85/2],[25+100,195+85/2],[25+160,195+85/2],[25+40,195+85/2+20]],
        descanso: [[495+30,195+85/2-12],[495+90,195+85/2-12],[495+150,195+85/2-12],[495+30,195+85/2+12],[495+90,195+85/2+12],[495+150,195+85/2+12]],
        biblioteca: [[535+35,350+110/2],[535+70,350+110/2],[535+105,350+110/2]],
    };
    var ACCIONES = [
        { accion: "codificando",  emoji: "\u2328\uFE0F", destinos: ["escritorio1","escritorio2","escritorio3","escritorio4"], duracion: 7000 },
        { accion: "revisando",    emoji: "\uD83D\uDD0D",  destinos: ["escritorio1","escritorio2","escritorio3","escritorio4"], duracion: 5500 },
        { accion: "documentando", emoji: "\uD83D\uDCDD",  destinos: ["escritorio1","escritorio2","escritorio3","escritorio4"], duracion: 6000 },
        { accion: "leyendo",      emoji: "\uD83D\uDCD6",  destinos: ["biblioteca"], duracion: 8000 },
        { accion: "reunion",      emoji: "\uD83D\uDCAC",  destinos: ["reunion"], duracion: 10000 },
        { accion: "servidor",     emoji: "\uD83D\uDDBB",  destinos: ["servidor"], duracion: 5000 },
        { accion: "jugando",      emoji: "\uD83C\uDFAE",  destinos: ["juegos"], duracion: 6000 },
        { accion: "cafe",         emoji: "\u2615",        destinos: ["descanso"], duracion: 4000 },
    ];

    var DIALOGOS = {
        "Tech Lead":    ["Revisemos la arquitectura","El deadline es viernes","Necesito el reporte ASAP","Alguien vio mi commit?","Esto va a produccion hoy","Revise ese PR","Tenemos tech debt aqui","Prioridad: estabilidad"],
        "Backend":      ["La API responde en 12ms","Migracion lista","Cache invalidado","Optimice esa query","Unit tests passing","El endpoint ya esta","DTO validado","Error handling listo"],
        "Frontend":     ["El componente renderiza","Responsive listo","Animacion fluida","Los tests de UI pasan","El theme esta consistente","Accesibilidad OK","Lighthouse 95+","Build sin warnings"],
        "DevOps":       ["Pipeline verde","Deploy completado","Monitoreo activo","Escalando pods","Logs centralizados","SSL renovado","Backup verificado","Latencia estable"],
        "QA":           ["Casos de prueba OK","Regression testing","Bug en login","Cobertura al 85%","E2E passing","Edge case cubierto","Performance testing","Humo testing OK"],
        "Data":         ["Pipeline de datos OK","Dashboard actualizado","Metricas consistentes","ETL completado","Anomalia detectada","Reporte generado","Modelo entrenado","Accuracy 94%"],
        "UX":           ["Flujo de usuario OK","Prototipo validado","Heuristicas listas","Test de usabilidad","Wireframe aprobado","Accesibilidad OK","Jerarquia visual","Micro-interaccion"],
        "Seguridad":    ["Auditoria completada","Vulnerabilidad parcheada","Cifrado OK","OWASP top 10","Tokens rotados","Logs monitoreados","Firewall actualizado","Incidente mitigado"],
        "General":      ["Alguien pidio cafe?","Eso fue commit ciego","Listo, lo muevo a done","Me llevo esto al break","Quien revisa mi PR?","Necesito ayuda aca","Ya casi termino","Bug o feature?"],
        "codificando":  ["Commit en camino","Test verde","Push a develop","WIP limit respetado","Merge sin conflictos","Review pending","Feature completa","Bug squashed"],
        "revisando":    ["Code review en proceso","Posible NPE aqui","Esto se puede optimizar","LGTM","Necesito mas contexto","Variable mal nombrada","Buena practica","Solo un detalle"],
        "documentando": ["README actualizado","API docs generados","Swagger sincronizado","Changelog al dia","Wiki actualizada","Comentarios utiles","Guia de inicio","Arquitectura documentada"],
        "cafe":         ["Cafe pendiente","Alguien mas quiere?","Cafe recien hecho","Descanso mental","Recargando energia","Justo lo necesitaba","Se acabo el cafe","Segunda taza"],
    };

    function rand(m, M) { return Math.floor(Math.random() * (M - m + 1)) + m; }
    function elegir(a) { return a[rand(0, a.length - 1)]; }

    function oscurecer(hex, factor) {
        var r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
        return "rgb(" + Math.floor(r*(1-factor)) + "," + Math.floor(g*(1-factor)) + "," + Math.floor(b*(1-factor)) + ")";
    }

    function ocuparSlot(estId) {
        var slots = SLOTS[estId]; if (!slots) return null;
        if (!ocupados[estId]) ocupados[estId] = [];
        for (var i = 0; i < slots.length; i++) { if (!ocupados[estId][i]) { ocupados[estId][i] = true; return { slotIdx: i, x: slots[i][0], y: slots[i][1] }; } }
        return null;
    }

    function liberarSlot(ag) {
        if (ag.slotIdx === undefined || ag.estacionOrigen === undefined) return;
        var e = ag.estacionOrigen;
        if (ocupados[e] && ocupados[e][ag.slotIdx]) ocupados[e][ag.slotIdx] = false;
    }

    function seleccionarActivosIniciales() {
        var estacionesLibres = ["escritorio1","escritorio2","escritorio3","escritorio4","biblioteca","reunion","descanso","juegos","servidor"];
        activos = AGENTES.map(function(ag, i) {
            var estId = estacionesLibres[i % estacionesLibres.length];
            var slot = ocuparSlot(estId) || { x: 25 + (i % 5) * 140, y: 75 + Math.floor(i / 5) * 120 };
            var acc = ACCIONES[i % ACCIONES.length];
            return {
                id: ag.id, nombre: ag.nombre, iniciales: ag.iniciales,
                color: ag.color, rol: ag.rol, crewaiRole: null,
                x: slot.x, y: slot.y, ox: slot.x, oy: slot.y, dx: slot.x, dy: slot.y,
                progreso: 1, estacion: estId, estacionOrigen: estId,
                slotIdx: slot.slotIdx !== undefined ? slot.slotIdx : undefined,
                accion: "idle", emoji: "\uD83D\uDE34", duracion: 0,
                inicioViaje: 0, dialogox: i,
            };
        });
    }
    function moverA(ag, destinoId, acc) {
        console.log('[PixelAmpCrew] moverA:', ag.nombre, '→', destinoId, 'accion:', acc.accion);
        liberarSlot(ag);
        var slot = ocuparSlot(destinoId);
        if (!slot) {
            console.log('[PixelAmpCrew] NO HAY SLOT en', destinoId);
            return;
        }
        console.log('[PixelAmpCrew] Slot asignado:', destinoId, 'pos:', slot.x, slot.y);
        ag.ox = ag.x; ag.oy = ag.y; ag.dx = slot.x; ag.dy = slot.y;
        ag.progreso = 0; ag.estacionOrigen = ag.estacion; ag.estacion = destinoId;
        ag.slotIdx = slot.slotIdx; ag.accion = acc.accion; ag.emoji = acc.emoji;
        ag.duracion = acc.duracion || CONFIG.DURACION_MOVER; ag.inicioViaje = Date.now();
    }

    function elegirAccion(ag) {
        var destinosG = [];
        ACCIONES.forEach(function(acc) {
            acc.destinos.forEach(function(d) { if (destinosG.indexOf(d)===-1) destinosG.push(d); });
        });
        var destino = elegir(destinosG);
        var accs = ACCIONES.filter(function(a) { return a.destinos.indexOf(destino) !== -1; });
        var acc = elegir(accs);
        moverA(ag, destino, acc);
        agregarLog(ag.nombre, acc.accion, destino);
    }

    function actualizar() {
        var ahora = Date.now();
        activos.forEach(function(ag) {
            if (ag.progreso < 1) {
                var dur = ag.duracion || CONFIG.DURACION_MOVER;
                var t = Math.min(1, (ahora - ag.inicioViaje) / dur);
                t = t < 0.5 ? 2*t*t : -1+(4-2*t)*t;
                ag.x = ag.ox + (ag.dx - ag.ox)*t; ag.y = ag.oy + (ag.dy - ag.oy)*t;
                if (t >= 1) { ag.x = ag.dx; ag.y = ag.dy; ag.progreso = 1; }
            }
        });
    }

    // FONDO

    function dibujarSuelo() {
        ctx.fillStyle = CONFIG.COLOR_SUELO; ctx.fillRect(0,48,CONFIG.ANCHO,CONFIG.ALTO-48);
        ctx.fillStyle = "rgba(255,255,255,0.02)";
        for (var x=0; x<CONFIG.ANCHO; x+=40) { for (var y=60; y<CONFIG.ALTO; y+=40) { if ((Math.floor(x/40)+Math.floor(y/40))%2===0) ctx.fillRect(x,y,40,40); } }
        ctx.fillStyle = "#3d5a3d"; ctx.beginPath(); ctx.arc(18,CONFIG.ALTO-20,12,0,Math.PI*2); ctx.fill();
        ctx.fillStyle = "#4b7a4b"; ctx.fillRect(14,CONFIG.ALTO-40,8,20);
        ctx.fillStyle = "#3d5a3d"; ctx.beginPath(); ctx.arc(CONFIG.ANCHO-18,CONFIG.ALTO-20,12,0,Math.PI*2); ctx.fill();
        ctx.fillStyle = "#4b7a4b"; ctx.fillRect(CONFIG.ANCHO-22,CONFIG.ALTO-40,8,20);
    }

    function dibujarPasillos() {
        ctx.fillStyle = CONFIG.COLOR_PASILLO; ctx.fillRect(0,170,CONFIG.ANCHO,30); ctx.fillRect(0,310,CONFIG.ANCHO,20);
        ctx.fillRect(0,170,20,165); ctx.fillRect(CONFIG.ANCHO-20,170,20,165);
        ctx.strokeStyle = "rgba(255,255,255,0.04)"; ctx.lineWidth = 1; ctx.setLineDash([6,10]);
        ctx.beginPath(); ctx.moveTo(0,185); ctx.lineTo(CONFIG.ANCHO,185); ctx.moveTo(0,320); ctx.lineTo(CONFIG.ANCHO,320); ctx.stroke(); ctx.setLineDash([]);
    }

    function dibujarHeader() {
        ctx.fillStyle = CONFIG.COLOR_HEADER; ctx.fillRect(0,0,CONFIG.ANCHO,48);
        ctx.fillStyle = "rgba(255,255,255,0.06)"; ctx.fillRect(0,46,CONFIG.ANCHO,2);
        ctx.fillStyle = CONFIG.TEXTO_CLARO; ctx.font = "bold 14px sans-serif"; ctx.textAlign = "left"; ctx.textBaseline = "middle";
        ctx.fillText("\uD83C\uDFE2  PixelAmpCrew  \u2014  AmpereTech CR", 14, 24);

        var a = new Date(); var hh=String(a.getHours()).padStart(2,"0"); var mm=String(a.getMinutes()).padStart(2,"0");
        ctx.fillStyle = CONFIG.TEXTO_SUAVE; ctx.textAlign = "right"; ctx.font = "bold 13px monospace"; ctx.fillText(hh+":"+mm, CONFIG.ANCHO-14, 24);

        var rx = CONFIG.ANCHO-50; var ry = 24;
        ctx.save(); ctx.translate(rx, ry);
        ctx.shadowBlur = 0;
        ctx.strokeStyle = "rgba(255,255,255,0.15)"; ctx.lineWidth = 1.5; ctx.beginPath(); ctx.arc(0,0,10,0,Math.PI*2); ctx.stroke();
        var seg = a.getSeconds()/60*Math.PI*2 - Math.PI/2;
        var min = (a.getMinutes()+a.getSeconds()/60)/60*Math.PI*2 - Math.PI/2;
        ctx.strokeStyle = CONFIG.TEXTO_SUAVE; ctx.lineWidth = 1.5; ctx.beginPath(); ctx.moveTo(0,0); ctx.lineTo(Math.cos(min)*6, Math.sin(min)*6); ctx.stroke();
        ctx.strokeStyle = "rgba(255,255,255,0.3)"; ctx.lineWidth = 1; ctx.beginPath(); ctx.moveTo(0,0); ctx.lineTo(Math.cos(seg)*7, Math.sin(seg)*7); ctx.stroke();
        ctx.restore();
        ctx.textBaseline = "alphabetic";
    }
    // ESTACIONES

    function dibujarLabel(e, texto) {
        var tx = e.x+e.w/2; var ty = e.y-2;
        ctx.save(); ctx.shadowBlur = 0;
        ctx.fillStyle = "rgba(0,0,0,0.5)";
        ctx.beginPath(); ctx.roundRect(tx-70, ty-8, 140, 20, 4); ctx.fill();
        ctx.fillStyle = CONFIG.TEXTO_SUAVE; ctx.font = "bold 11px sans-serif"; ctx.textAlign = "center"; ctx.textBaseline = "middle";
        ctx.fillText(texto, tx, ty+2);
        ctx.restore();
    }

    function dibujarEstacion(k, e) {
        ctx.fillStyle = e.color; ctx.shadowColor = "rgba(0,0,0,0.3)"; ctx.shadowBlur = 6; ctx.shadowOffsetY = 2;
        ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.fill();
        ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;
        ctx.strokeStyle = "rgba(255,255,255,0.08)"; ctx.lineWidth = 1; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.stroke();
        dibujarLabel(e, e.emoji+"  "+e.label);

        ctx.fillStyle = "#1a3355"; ctx.beginPath(); ctx.roundRect(e.x+10,e.y+18,40,28,4); ctx.fill();
        ctx.fillStyle = "#3a6bab"; ctx.fillRect(e.x+13,e.y+21,34,18);
        ctx.fillStyle = "#2a2a44"; ctx.fillRect(e.x+22,e.y+39,16,6);
    }

    function dibujarSalaReuniones() {
        var e = ESTACIONES.reunion;
        var enR = activos.filter(function(a){return a.estacion==="reunion"&&a.progreso>=1;});
        var ocup = enR.length>=2;
        if (ocup) {
            var p = 0.15+Math.sin(frame*0.03)*0.08;
            ctx.save(); ctx.shadowColor="rgba(99,102,241,0.5)"; ctx.shadowBlur=20+Math.sin(frame*0.04)*10;
            ctx.fillStyle="rgba(99,102,241,"+p+")"; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.fill(); ctx.restore();
            ctx.fillStyle="rgba(99,102,241,0.25)"; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.fill();
            ctx.strokeStyle="rgba(99,102,241,0.5)"; ctx.lineWidth=1.5; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.stroke();
            var badge="\uD83D\uDCAC "+enR.length+" en reuni\u00F3n";
            ctx.save(); ctx.shadowColor="rgba(99,102,241,0.4)"; ctx.shadowBlur=8;
            ctx.fillStyle="#6366f1"; ctx.beginPath(); ctx.roundRect(e.x+e.w-95,e.y+6,85,20,6); ctx.fill();
            ctx.shadowBlur=0; ctx.fillStyle="#fff"; ctx.font="bold 10px sans-serif"; ctx.textAlign="center"; ctx.textBaseline="middle";
            ctx.fillText(badge, e.x+e.w-52, e.y+16); ctx.restore();
            if (enR.length>=2) {
                ctx.strokeStyle="rgba(99,102,241,0.15)"; ctx.lineWidth=1; ctx.setLineDash([3,4]);
                for(var i=0;i<enR.length;i++){for(var j=i+1;j<enR.length;j++){ctx.beginPath();ctx.moveTo(enR[i].x,enR[i].y+6);ctx.lineTo(enR[j].x,enR[j].y+6);ctx.stroke();}}
                ctx.setLineDash([]);
            }
        } else {
            ctx.fillStyle="rgba(99,102,241,0.12)"; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.fill();
            ctx.strokeStyle="rgba(99,102,241,0.25)"; ctx.lineWidth=1; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.stroke();
        }
        dibujarLabel(e, e.emoji+"  "+e.label);
        var mx=e.x+e.w/2, my=e.y+e.h/2+5;
        ctx.fillStyle="rgba(255,255,255,0.06)"; ctx.beginPath(); ctx.ellipse(mx,my,70,28,0,0,Math.PI*2); ctx.fill();
        ctx.strokeStyle="rgba(255,255,255,0.12)"; ctx.lineWidth=1; ctx.beginPath(); ctx.ellipse(mx,my,70,28,0,0,Math.PI*2); ctx.stroke();

        ctx.fillStyle="#1a3a2a"; ctx.beginPath(); ctx.roundRect(e.x+15,e.y+30,55,28,3); ctx.fill();
        ctx.strokeStyle="rgba(255,255,255,0.08)"; ctx.lineWidth=0.5; ctx.beginPath(); ctx.roundRect(e.x+15,e.y+30,55,28,3); ctx.stroke();
        ctx.fillStyle="rgba(255,255,255,0.1)"; ctx.fillRect(e.x+20,e.y+38,10,2); ctx.fillRect(e.x+35,e.y+38,10,2); ctx.fillRect(e.x+50,e.y+38,10,2);
        ctx.fillRect(e.x+20,e.y+44,10,2); ctx.fillRect(e.x+35,e.y+44,10,2); ctx.fillRect(e.x+50,e.y+44,10,2);

        ctx.fillStyle=CONFIG.TEXTO_SUAVE; ctx.font="10px sans-serif"; ctx.textAlign="center"; ctx.textBaseline="bottom";
        ctx.fillText("\uD83D\uDCC5 "+new Date().toLocaleDateString("es-CR",{weekday:"short",day:"numeric",month:"short"}), e.x+e.w/2, e.y+e.h-4);
    }
    function dibujarServidor() {
        var e=ESTACIONES.servidor;
        ctx.fillStyle="rgba(239,68,68,0.08)"; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.fill();
        ctx.strokeStyle="rgba(239,68,68,0.15)"; ctx.lineWidth=1; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.stroke();
        dibujarLabel(e, e.emoji+"  "+e.label);
        for(var i=0;i<3;i++){ctx.fillStyle=i%2===0?"rgba(0,255,0,0.4)":"rgba(0,255,0,0.15)";ctx.beginPath();ctx.arc(e.x+35+i*35,e.y+75,4,0,Math.PI*2);ctx.fill();}
    }

    function dibujarDescanso() {
        var e=ESTACIONES.descanso;
        ctx.fillStyle="rgba(251,191,36,0.06)"; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.fill();
        ctx.strokeStyle="rgba(251,191,36,0.12)"; ctx.lineWidth=1; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.stroke();
        dibujarLabel(e, e.emoji+"  "+e.label);

        ctx.fillStyle="#5c3a1e"; ctx.beginPath(); ctx.roundRect(e.x+e.w-60,e.y+20,30,40,4); ctx.fill();
        ctx.strokeStyle="rgba(255,255,255,0.1)"; ctx.lineWidth=1; ctx.beginPath(); ctx.roundRect(e.x+e.w-60,e.y+20,30,40,4); ctx.stroke();
        ctx.fillStyle="rgba(255,255,255,0.15)"; ctx.beginPath(); ctx.arc(e.x+e.w-50,e.y+35,4,0,Math.PI*2); ctx.fill();
        ctx.beginPath(); ctx.arc(e.x+e.w-40,e.y+35,4,0,Math.PI*2); ctx.fill();
        ctx.fillStyle=CONFIG.TEXTO_SUAVE; ctx.font="16px sans-serif"; ctx.textAlign="center"; ctx.textBaseline="middle";
        ctx.fillText("\u2615", e.x+e.w-45, e.y+28);

        var enDescanso = activos.filter(function(a){return a.estacion==="descanso" && a.progreso>=1;}).length;
        ctx.fillStyle=CONFIG.TEXTO_SUAVE; ctx.font="24px sans-serif"; ctx.textBaseline="middle";
        ctx.fillText("\u2615 "+enDescanso, e.x+e.w/2, e.y+e.h/2+6);
        ctx.font="10px sans-serif"; ctx.textBaseline="bottom"; ctx.fillText("en descanso", e.x+e.w/2, e.y+e.h-4);
    }

    function dibujarJuegos() {
        var e=ESTACIONES.juegos;
        ctx.fillStyle="rgba(34,197,94,0.06)"; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.fill();
        ctx.strokeStyle="rgba(34,197,94,0.12)"; ctx.lineWidth=1; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.stroke();
        dibujarLabel(e, e.emoji+"  "+e.label);
        var enJ=activos.filter(function(a){return a.estacion==="juegos";}).length;
        ctx.fillStyle=CONFIG.TEXTO_SUAVE; ctx.font="20px sans-serif"; ctx.textBaseline="middle";
        ctx.fillText("\uD83C\uDFAE "+enJ+" / 4", e.x+e.w/2, e.y+e.h/2+6);
        ctx.font="10px sans-serif"; ctx.textBaseline="bottom"; ctx.fillText("jugando ahora", e.x+e.w/2, e.y+e.h-4);
    }

    function dibujarBiblioteca() {
        var e=ESTACIONES.biblioteca;
        ctx.fillStyle="rgba(139,92,246,0.08)"; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.fill();
        ctx.strokeStyle="rgba(139,92,246,0.15)"; ctx.lineWidth=1; ctx.beginPath(); ctx.roundRect(e.x,e.y,e.w,e.h,8); ctx.stroke();
        dibujarLabel(e, e.emoji+"  "+e.label);
        for(var i=0;i<5;i++){ctx.fillStyle="rgba(255,255,255,0.06)";ctx.fillRect(e.x+15+i*24,e.y+45,12,40);ctx.fillStyle="rgba(139,92,246,0.2)";ctx.fillRect(e.x+17+i*24,e.y+47+(i%3)*8,8,4);}
    }
    // NPCS

    function dialogoPara(ag) {
        var lines = DIALOGOS[ag.rol] || DIALOGOS.General;
        if (DIALOGOS[ag.accion]) lines = DIALOGOS[ag.accion];
        if (!ag.dialogox) ag.dialogox = rand(0, lines.length-1);
        return lines[ag.dialogox];
    }

    function dibujarBurbuja(cx, cy, texto, color) {
        ctx.save(); ctx.font="bold 11px sans-serif";
        var ancho=ctx.measureText(texto).width+18, alto=22;
        ctx.shadowColor="rgba(0,0,0,0.3)"; ctx.shadowBlur=4;
        ctx.fillStyle=color; var bx=cx-ancho/2; var by=cy-CONFIG.TAM_NPC/2-alto-10;
        ctx.beginPath(); ctx.roundRect(bx,by,ancho,alto,6); ctx.fill();
        ctx.shadowBlur=0; ctx.strokeStyle="rgba(255,255,255,0.15)"; ctx.lineWidth=1; ctx.beginPath(); ctx.roundRect(bx,by,ancho,alto,6); ctx.stroke();
        ctx.fillStyle="#fff"; ctx.font="bold 11px sans-serif"; ctx.textAlign="center"; ctx.textBaseline="middle"; ctx.fillText(texto,cx,by+alto/2);
        ctx.fillStyle=color; ctx.beginPath(); ctx.moveTo(cx-5,by+alto); ctx.lineTo(cx,by+alto+6); ctx.lineTo(cx+5,by+alto); ctx.closePath(); ctx.fill();
        ctx.restore();
    }

    function dibujarNPC(ag) {
        var t = CONFIG.TAM_NPC;
        var cx = ag.x, cy = ag.y;
        var caminando = ag.progreso < 1;
        var bobY = caminando ? Math.sin(frame*0.12+ag.id)*2 : 0;
        var idle = ag.accion === "idle" || ag.accion === undefined;

        if (!idle && ag.progreso >= 1) {
            if (ag.estacion.indexOf("escritorio")===0 && ag.dialogox%2===0 && Math.sin(frame*0.02+ag.id)>0.3) {
                var txt = dialogoPara(ag);
                dibujarBurbuja(cx, cy+bobY, txt, ag.color);
            } else if (ag.estacion==="reunion") {
                var txt = dialogoPara(ag);
                var alpha = 0.6+Math.sin(frame*0.04+ag.id)*0.3;
                ctx.globalAlpha = Math.max(0.3, alpha);
                dibujarBurbuja(cx, cy+bobY, txt, ag.color);
                ctx.globalAlpha = 1;
            }
        }

        ctx.save();
        if (idle) { ctx.globalAlpha = 0.35; }
        ctx.shadowColor = ag.color+"44"; ctx.shadowBlur = 6; ctx.shadowOffsetY = 2;

        var tonoPiel = TONOS_PIEL[ag.id%TONOS_PIEL.length];
        var m = t/2;
        var esMujer = ag.nombre.slice(-1)==="a";
        var anchoHombro = esMujer ? m*0.75 : m*0.95;
        var anchoCintura = esMujer ? m*0.4 : m*0.55;
        var cabezaR = m*0.65;
        var cxC = cx, cyC = cy+bobY - m*0.3;

        ctx.strokeStyle = tonoPiel; ctx.lineWidth = 4; ctx.lineCap = "round";

        ctx.fillStyle = tonoPiel;
        ctx.beginPath(); ctx.arc(cxC, cyC, cabezaR, 0, Math.PI*2); ctx.fill();

        ctx.fillStyle = oscurecer(ag.color,0.5);
        ctx.beginPath(); ctx.arc(cxC, cyC-cabezaR*0.15, cabezaR*1.05, Math.PI, 0); ctx.fill();

        ctx.fillStyle = "#fff";
        ctx.beginPath(); ctx.arc(cxC-cabezaR*0.32, cyC-cabezaR*0.05, cabezaR*0.22, 0, Math.PI*2); ctx.fill();
        ctx.beginPath(); ctx.arc(cxC+cabezaR*0.32, cyC-cabezaR*0.05, cabezaR*0.22, 0, Math.PI*2); ctx.fill();
        ctx.fillStyle = "#333";
        ctx.beginPath(); ctx.arc(cxC-cabezaR*0.32, cyC-cabezaR*0.05, cabezaR*0.1, 0, Math.PI*2); ctx.fill();
        ctx.beginPath(); ctx.arc(cxC+cabezaR*0.32, cyC-cabezaR*0.05, cabezaR*0.1, 0, Math.PI*2); ctx.fill();
        ctx.fillStyle = "#fff";
        ctx.beginPath(); ctx.arc(cxC-cabezaR*0.28, cyC-cabezaR*0.1, cabezaR*0.04, 0, Math.PI*2); ctx.fill();
        ctx.beginPath(); ctx.arc(cxC+cabezaR*0.38, cyC-cabezaR*0.1, cabezaR*0.04, 0, Math.PI*2); ctx.fill();

        ctx.fillStyle = "rgba(255,150,150,0.25)";
        ctx.beginPath(); ctx.arc(cxC-cabezaR*0.4, cyC+cabezaR*0.2, cabezaR*0.12, 0, Math.PI*2); ctx.fill();
        ctx.beginPath(); ctx.arc(cxC+cabezaR*0.4, cyC+cabezaR*0.2, cabezaR*0.12, 0, Math.PI*2); ctx.fill();

        ctx.strokeStyle = "#555"; ctx.lineWidth = 1.2;
        ctx.beginPath(); ctx.arc(cxC, cyC+cabezaR*0.35, cabezaR*0.18, 0.1, Math.PI-0.1); ctx.stroke();

        ctx.fillStyle = tonoPiel; ctx.fillRect(cxC-3, cyC+cabezaR*0.85, 6, 5);
        var cuerpoY = cyC+cabezaR*0.95;
        var cuerpoH = m*0.95;
        ctx.fillStyle = ag.color;
        ctx.beginPath(); ctx.moveTo(cxC-anchoHombro, cuerpoY);
        ctx.quadraticCurveTo(cxC, cuerpoY+5, cxC+anchoHombro, cuerpoY);
        ctx.lineTo(cxC+anchoCintura, cuerpoY+cuerpoH);
        ctx.quadraticCurveTo(cxC, cuerpoY+cuerpoH+3, cxC-anchoCintura, cuerpoY+cuerpoH);
        ctx.closePath(); ctx.fill();

        if (ag.rol==="Tech Lead") {
            ctx.fillStyle = "#dc2626"; ctx.fillRect(cxC-2, cuerpoY+8, 4, cuerpoH-16);
            ctx.fillStyle = "#fff"; ctx.fillRect(cxC-3, cuerpoY+12, 6, 2);
        }
        if (ag.rol==="Backend") {
            ctx.strokeStyle = "#aaa"; ctx.lineWidth = 1.5;
            ctx.beginPath(); ctx.arc(cxC-cabezaR*0.15, cyC-cabezaR*0.05, cabezaR*0.18, 0, Math.PI*2); ctx.stroke();
            ctx.beginPath(); ctx.arc(cxC+cabezaR*0.15, cyC-cabezaR*0.05, cabezaR*0.18, 0, Math.PI*2); ctx.stroke();
            ctx.strokeStyle = "#aaa"; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(cxC-cabezaR*0.3, cyC-cabezaR*0.05); ctx.lineTo(cxC+cabezaR*0.3, cyC-cabezaR*0.05); ctx.stroke();
        }
        if (ag.rol==="DevOps") {
            ctx.fillStyle = oscurecer(ag.color,0.4); ctx.beginPath(); ctx.arc(cxC, cyC-cabezaR*0.4, cabezaR*0.7, Math.PI*1.2, Math.PI*1.8); ctx.fill();
        }

        ctx.lineCap = "round"; ctx.lineWidth = 4.5;
        var brazoLen = cuerpoH*0.45;
        ctx.strokeStyle = tonoPiel;
        ctx.beginPath(); ctx.moveTo(cxC-anchoHombro-2, cuerpoY+6);
        ctx.quadraticCurveTo(cxC-anchoHombro-8, cuerpoY+brazoLen*0.5, cxC-anchoHombro-5, cuerpoY+brazoLen); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(cxC+anchoHombro+2, cuerpoY+6);
        ctx.quadraticCurveTo(cxC+anchoHombro+8, cuerpoY+brazoLen*0.5, cxC+anchoHombro+5, cuerpoY+brazoLen); ctx.stroke();

        var piernaLen = cuerpoH*0.2;
        ctx.strokeStyle = "#444"; ctx.lineWidth = 4; ctx.lineCap = "square";
        ctx.beginPath(); ctx.moveTo(cxC-anchoCintura*0.3, cuerpoY+cuerpoH);
        ctx.lineTo(cxC-anchoCintura*0.3, cuerpoY+cuerpoH+piernaLen); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(cxC+anchoCintura*0.3, cuerpoY+cuerpoH);
        ctx.lineTo(cxC+anchoCintura*0.3, cuerpoY+cuerpoH+piernaLen); ctx.stroke();

        ctx.fillStyle = "#333";
        ctx.beginPath(); ctx.roundRect(cxC-anchoCintura*0.3-3, cuerpoY+cuerpoH+piernaLen-2, 6, 4, 2); ctx.fill();
        ctx.beginPath(); ctx.roundRect(cxC+anchoCintura*0.3-3, cuerpoY+cuerpoH+piernaLen-2, 6, 4, 2); ctx.fill();

        ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;
        if (!idle) {
            ctx.font = "16px sans-serif"; ctx.textAlign = "center"; ctx.textBaseline = "bottom";
            ctx.fillStyle = ag.color; ctx.globalAlpha = 0.9+Math.sin(frame*0.06+ag.id*2)*0.1;
            ctx.fillText(ag.emoji, cx, cy+bobY-t/2-2); ctx.globalAlpha = 1;
        }

        ctx.shadowColor = "rgba(0,0,0,0.4)"; ctx.shadowBlur = 3; ctx.shadowOffsetY = 1;
        ctx.font = "10px sans-serif"; ctx.fillStyle = CONFIG.TEXTO_CLARO; ctx.textBaseline = "top";
        ctx.fillText(ag.nombre, cx, cy+bobY+t/2+2);
        ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;
        ctx.restore();
    }
    // DIBUJAR

    function dibujar() {
        frame++;
        ctx.clearRect(0,0,CONFIG.ANCHO,CONFIG.ALTO);
        dibujarHeader();
        dibujarSuelo();
        dibujarPasillos();

        Object.keys(ESTACIONES).forEach(function(k) {
            var e=ESTACIONES[k];
            if (k==="reunion") dibujarSalaReuniones();
            else if (k==="servidor") dibujarServidor();
            else if (k==="descanso") dibujarDescanso();
            else if (k==="juegos") dibujarJuegos();
            else if (k==="biblioteca") dibujarBiblioteca();
            else dibujarEstacion(k,e);
        });

        var grad = ctx.createLinearGradient(0,48,0,CONFIG.ALTO);
        grad.addColorStop(0, "rgba(99,102,241,0.04)"); grad.addColorStop(1, "rgba(0,0,0,0)");
        ctx.fillStyle = grad; ctx.fillRect(0,48,CONFIG.ANCHO,CONFIG.ALTO-48);

        activos.forEach(dibujarNPC);
    }

    // CICLO

    function procesarEventoCrewAI(evt) {
        console.log('[PixelAmpCrew] procesarEventoCrewAI:', JSON.stringify(evt));
        var ag = encontrarAgentePorEvento(evt);
        if (!ag) {
            console.log('[PixelAmpCrew] No se encontro agente para evento:', JSON.stringify(evt));
            return;
        }
        console.log('[PixelAmpCrew] Agente encontrado:', ag.nombre, 'accion actual:', ag.accion);

        ag.crewaiRole = evt.agent_role || evt.rol || ag.crewaiRole;
        ag.accionAnterior = ag.accion;

        var accNombre = evt.accion || "";
        var acc = ACCIONES.find(function(a) { return a.accion === accNombre; });
        if (!acc) {
            if (accNombre) {
                acc = {accion:accNombre, emoji:"\u2699\uFE0F", destinos:["escritorio1","escritorio2","escritorio3","escritorio4"], duracion:5000};
                ACCIONES.push(acc);
            } else {
                acc = ACCIONES[0];
            }
        }
        var destino = normalizarDestino(evt.destino);
        console.log('[PixelAmpCrew] Mover a:', ag.nombre, '→', destino, 'accion:', acc.accion);
        moverA(ag, destino, acc);

        if (evt.mensaje) {
            ag.dialogox = DIALOGOS.General.indexOf(evt.mensaje);
            if (ag.dialogox === -1) {
                DIALOGOS.General.push(evt.mensaje);
                ag.dialogox = DIALOGOS.General.length - 1;
            }
        }

        agregarLog(ag.nombre, acc.accion, destino);
        actualizarPanel();
    }

    function agregarLog(nombre,accion,lugar) { logEventos.unshift({nombre:nombre,accion:accion,lugar:lugar,tiempo:new Date()}); if (logEventos.length>8) logEventos.pop(); }

    function actualizarPanel() {
        var dA=document.getElementById("panel-agentes");
        if (dA) {
            var activosOrden = activos.slice().sort(function(a,b){return (a.accion==="idle"?1:0) - (b.accion==="idle"?1:0) || a.nombre.localeCompare(b.nombre);});
            dA.innerHTML = activosOrden.map(function(a){
                var idle = a.accion === "idle" || a.accion === undefined;
                var emoji = idle ? "\uD83D\uDE34" : (a.progreso<1 ? "\uD83D\uDEB6" : a.emoji);
                var estilo = idle ? "opacity:0.4;" : "";
                return "<div class=\"panel-agente\" style=\""+estilo+"\"><span class=\"iniciales\" style=\"background:"+a.color+"\">"+a.iniciales+"</span><span class=\"nombre\"><strong>"+a.nombre+"</strong><br><span class=\"texto-xs texto-suave\">"+(a.crewaiRole||a.rol)+"</span></span><span class=\"accion\" title=\""+a.accion+"\">"+emoji+"</span></div>";
            }).join("");
        }
        var dC=document.getElementById("panel-contadores");
        if (dC) {
            var enAccion=activos.filter(function(a){return a.accion!=="idle" && a.accion!==undefined;}).length;
            var r=activos.filter(function(a){return a.estacion==="reunion" && a.accion!=="idle";}).length;
            dC.innerHTML = "<div class=\"contador\"><span class=\"numero\" style=\"color:#22c55e\">"+enAccion+"</span><span class=\"etiqueta\">Trabajando</span></div><div class=\"contador\"><span class=\"numero\" style=\"color:#6366f1\">"+r+"</span><span class=\"etiqueta\">Reuni\u00F3n</span></div>";
        }
        var dL=document.getElementById("log-lista");
        if (dL) { dL.innerHTML = logEventos.slice(0,5).map(function(e){var hh=String(e.tiempo.getHours()).padStart(2,"0");var mm=String(e.tiempo.getMinutes()).padStart(2,"0");return "<div class=\"log-item\">"+hh+":"+mm+" \u00B7 <strong>"+e.nombre+"</strong> \u2192 "+e.accion+"</div>";}).join(""); }
    }

    function loop() { actualizar(); dibujar(); requestAnimationFrame(loop); }

    var EVENTOS_VISTOS = {};

    function iniciarPolling() {
        var barra = document.getElementById("sse-status");
        if (!barra) {
            barra = document.createElement("div");
            barra.id = "sse-status";
            barra.style.cssText = "padding:4px 8px;margin-bottom:6px;border-radius:4px;font-size:10px;text-align:center;background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.2);color:#818cf8;";
            var panel = document.getElementById("panel-lateral-oficina");
            if (panel) panel.prepend(barra);
        }
        barra.textContent = "\u21BB Polling cada 3s";

        var apiBase = (document.querySelector('meta[name="api-base"]')?.content || '').replace(/\/+$/, '');
        var pollingUrl = apiBase ? apiBase + '/crewai/eventos' : (window.PIXELAMP_DATA?.URL_BASE || '') + '/api/crewai/eventos';
        console.log('[PixelAmpCrew] Polling URL:', pollingUrl);

        setInterval(function() {
            var controller = new AbortController();
            var timeoutId = setTimeout(function() { controller.abort(); }, 5000);
            fetch(pollingUrl, { headers: { 'Accept': 'application/json' }, signal: controller.signal })
                .then(function(r) { clearTimeout(timeoutId); return r.json(); })
                .then(function(data) {
                    if (!data.eventos || !data.eventos.length) return;
                    data.eventos.forEach(function(evt) {
                        var eid = evt._id || '';
                        if (EVENTOS_VISTOS[eid]) return;
                        EVENTOS_VISTOS[eid] = true;
                        console.log('[PixelAmpCrew] EVENTO: crewai', JSON.stringify(evt));
                        procesarEventoCrewAI(evt);
                    });
                    barra.textContent = "\u2705 Conectado";
                })
                .catch(function(err) {
                    clearTimeout(timeoutId);
                    console.log('[PixelAmpCrew] Polling error:', err);
                    barra.textContent = "\u26A0 Error de conexion";
                });
        }, 3000);
    }

    function init() {
        console.log('[PixelAmpCrew] init() llamado');
        canvas=document.getElementById("canvas-oficina"); if (!canvas) return;
        console.log('[PixelAmpCrew] Canvas encontrado');
        ctx=canvas.getContext("2d");
        seleccionarActivosIniciales(); actualizarPanel(); loop();
        iniciarPolling();
    }

    if (document.readyState==="loading") document.addEventListener("DOMContentLoaded", init);
    else init();
})();
