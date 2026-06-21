import os, sys, json, requests, logging, time, threading
print('[PixelAmp] CWD:', os.getcwd(), file=sys.stderr)
from crewai.events import (
    BaseEventListener,
    AgentExecutionStartedEvent,
    AgentExecutionCompletedEvent,
    ToolUsageStartedEvent,
    ToolUsageFinishedEvent,
    CrewKickoffStartedEvent,
)

_BRIDGE_DIR = os.path.dirname(os.path.abspath(__file__))
_LITE_ROOT = os.path.abspath(os.path.join(_BRIDGE_DIR, '..', '..'))

CREWAI_SSE_URL = os.getenv("CREWAI_SSE_URL", "https://localhost/liteFramework/api/crewai")
CREWAI_SSE_TOKEN = os.getenv("CREWAI_SSE_TOKEN", "")
METRICAS_PATH = os.getenv("CREWAI_METRICAS_PATH", os.path.join(_LITE_ROOT, "storage", "logs", "crewai_metricas.log"))
logger = logging.getLogger("pixelamp")
logging.basicConfig(level=logging.DEBUG, format="[PixelAmp] %(levelname)s %(message)s")


def _cargar_role_map():
    path = os.path.join(_BRIDGE_DIR, "role_map.json")
    try:
        with open(path, "r", encoding="utf-8") as f:
            return json.load(f)
    except (FileNotFoundError, json.JSONDecodeError) as e:
        logger.warning("No se pudo cargar role_map.json: %s", e)
        return {}


_ROLE_MAP_RAW = _cargar_role_map()
ROLE_TO_NAME = {k.lower(): v for k, v in _ROLE_MAP_RAW.items()}

_cola_eventos = []
_cola_lock = threading.Lock()
_INTERVALO_BATCH = 0.3
_thread_vaciado_iniciado = False


def _flush_cola():
    with _cola_lock:
        if not _cola_eventos:
            return
        batch = _cola_eventos.copy()
        _cola_eventos.clear()
    for evento in batch:
        try:
            r = requests.post(CREWAI_SSE_URL, json=evento,
                timeout=2.0, headers={"Content-Type": "application/json"}, verify=False)
            logger.debug("emit %s/%s -> %s", evento.get("agent_role","?"), evento.get("accion","?"), r.status_code)
        except requests.RequestException as e:
            logger.warning("SSE batch emit failed: %s", e)


def _iniciar_thread_vaciado():
    global _thread_vaciado_iniciado
    if _thread_vaciado_iniciado:
        return
    _thread_vaciado_iniciado = True
    def _loop():
        while True:
            time.sleep(_INTERVALO_BATCH)
            _flush_cola()
    t = threading.Thread(target=_loop, daemon=True)
    t.start()


def _registrar_metrica(rol, accion, duracion_ms):
    try:
        with open(METRICAS_PATH, "a", encoding="utf-8") as f:
            f.write(json.dumps({
                "t": time.time(), "r": rol, "a": accion, "d_ms": round(duracion_ms, 1)
            }) + "\n")
    except OSError:
        pass


def _cargar_event_map():
    path = os.path.join(_BRIDGE_DIR, "visualizador_config.json")
    try:
        with open(path, "r", encoding="utf-8") as f:
            cfg = json.load(f)
        return cfg.get("event_map", {})
    except Exception as e:
        logger.warning("No se pudo cargar event_map: %s", e)
        return {}

EVENT_MAP = _cargar_event_map()

class PixelAmpBridge(BaseEventListener):
    def __init__(self):
        super().__init__()
        self._tiempos = {}

    def setup_listeners(self, crewai_event_bus):
        _iniciar_thread_vaciado()
        @crewai_event_bus.on(AgentExecutionStartedEvent)
        def on_agent_start(source, event):
            rol = event.agent.role
            self._tiempos[rol] = time.time()
            self._emit(rol, "codificando")

        @crewai_event_bus.on(AgentExecutionCompletedEvent)
        def on_agent_complete(source, event):
            rol = event.agent.role
            inicio = self._tiempos.pop(rol, None)
            if inicio:
                _registrar_metrica(rol, "completado", (time.time() - inicio) * 1000)
            self._emit(rol, "descanso")

        @crewai_event_bus.on(ToolUsageStartedEvent)
        def on_tool_start(source, event):
            role = event.agent_role or ""
            if role:
                self._emit(role, "servidor")

        @crewai_event_bus.on(ToolUsageFinishedEvent)
        def on_tool_finish(source, event):
            role = event.agent_role or ""
            if role:
                self._emit(role, "codificando")

        @crewai_event_bus.on(CrewKickoffStartedEvent)
        def on_crew_start(source, event):
            self._emit("Orquestador de Flujo", "reunion")

    def _emit(self, role, action):
        mapping = EVENT_MAP.get(action, {"emoji": "\u2328\uFE0F", "destino": "escritorio"})
        payload = {
            "token": CREWAI_SSE_TOKEN,
            "agent_role": role,
            "accion": action,
            "emoji": mapping["emoji"],
            "destino": mapping["destino"],
            "nombre": ROLE_TO_NAME.get(role.lower(), ""),
        }
        with _cola_lock:
            _cola_eventos.append(payload)

pixelamp = PixelAmpBridge()

