import os, json, requests, logging
from crewai.events import (
    BaseEventListener,
    AgentExecutionStartedEvent,
    AgentExecutionCompletedEvent,
    ToolUsageStartedEvent,
    ToolUsageFinishedEvent,
    CrewKickoffStartedEvent,
)

CREWAI_SSE_URL = os.getenv("CREWAI_SSE_URL", "http://localhost/liteFramework/api/crewai")
CREWAI_SSE_TOKEN = os.getenv("CREWAI_SSE_TOKEN", "")
logger = logging.getLogger("pixelamp")

EVENT_MAP = {
    "codificando": {"emoji": "\u2328\uFE0F", "destino": "escritorio"},
    "reunion":     {"emoji": "\U0001F4AC", "destino": "reunion"},
    "servidor":    {"emoji": "\U0001F5BB", "destino": "servidor"},
    "biblioteca":  {"emoji": "\U0001F4D6", "destino": "biblioteca"},
    "descanso":    {"emoji": "\u2615",     "destino": "descanso"},
}

class PixelAmpBridge(BaseEventListener):
    def __init__(self):
        super().__init__()

    def setup_listeners(self, crewai_event_bus):
        @crewai_event_bus.on(AgentExecutionStartedEvent)
        def on_agent_start(source, event):
            self._emit(event.agent.role, "codificando")

        @crewai_event_bus.on(AgentExecutionCompletedEvent)
        def on_agent_complete(source, event):
            self._emit(event.agent.role, "descanso")

        @crewai_event_bus.on(ToolUsageStartedEvent)
        def on_tool_start(source, event):
            role = event.agent_role or "desconocido"
            self._emit(role, "servidor")

        @crewai_event_bus.on(ToolUsageFinishedEvent)
        def on_tool_finish(source, event):
            role = event.agent_role or "desconocido"
            self._emit(role, "codificando")

        @crewai_event_bus.on(CrewKickoffStartedEvent)
        def on_crew_start(source, event):
            self._emit("crew", "reunion")

    def _emit(self, role, action):
        mapping = EVENT_MAP.get(action, {"emoji": "\u2328\uFE0F", "destino": "escritorio"})
        try:
            requests.post(CREWAI_SSE_URL, json={
                "token": CREWAI_SSE_TOKEN,
                "agent_role": role,
                "accion": action,
                "emoji": mapping["emoji"],
                "destino": mapping["destino"],
            }, timeout=1, headers={"Content-Type": "application/json"})
        except requests.RequestException as e:
            logger.warning("SSE emit failed: %s", e)

pixelamp = PixelAmpBridge()
