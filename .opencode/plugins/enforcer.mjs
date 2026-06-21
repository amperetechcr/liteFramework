const NATIVE = {
  write:       { suggest: "lite_write_file", reason: "usa el MCP que crea directorios automáticamente" },
  edit:        { suggest: "lite_edit",       reason: "el MCP detecta archivos congelados y respeta el perímetro" },
  bash:        { suggest: "lite_run",        reason: "el MCP auto-agrega --json y token de autenticación" },
  read:        { suggest: "lite_read_file",  reason: "el MCP resuelve rutas relativas a la raíz del proyecto" },
  grep:        { suggest: "lite_grep",       reason: "el MCP respeta PROJECT_ROOT y el encoding UTF-8" },
  glob:        { suggest: "lite_glob",       reason: "el MCP cambia al directorio correcto antes de buscar" },
  apply_patch: { suggest: "lite_edit",       reason: "los parches pueden ignorar archivos congelados" },
};

const EXTERNAL_PREFIXES = [
  "firecrawl_firecrawl_",
  "git_git_",
  "playwright_browser_",
  "mcp_image_",
  "sentry_",
  "gh_grep_",
  "deepwiki_",
  "fetch_fetch_",
  "time_get_",
  "context7_",
];

const path = require("path");
const os = require("os");
const fs = require("fs");

const TOKEN_FILE = path.join(os.homedir(), ".crewai", "ia_token.json");
const LOG_FILE = path.join(os.homedir(), ".crewai", "enforcer.log");

function leerToken() {
  try {
    if (!fs.existsSync(TOKEN_FILE)) return null;
    const raw = fs.readFileSync(TOKEN_FILE, "utf-8").trim();
    if (!raw) return null;
    return JSON.parse(raw);
  } catch (_) {
    return null;
  }
}

function borrarToken() {
  try {
    if (fs.existsSync(TOKEN_FILE)) fs.unlinkSync(TOKEN_FILE);
  } catch (_) {}
}

function logTool(tool, args, proyecto) {
  try {
    const entry = JSON.stringify({
      t: Date.now(), tool, args: (args ? JSON.stringify(args).slice(0, 200) : ""), proyecto: proyecto || "?",
    }) + "\n";
    const dir = path.dirname(LOG_FILE);
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    fs.appendFileSync(LOG_FILE, entry, "utf-8");
  } catch (_) {}
}

export const Enforcer = async ({ project, directory }) => {
  return {
    "tool.execute.before": async (input, output) => {
      const tool = input.tool;
      const args = input.args;

      // ia() siempre permitida (el token lo escribe lite_mcp.py si corresponde)
      if (tool === "ia" || tool.endsWith("_ia")) {
        return;
      }

      // Bloqueo de nativas (write, edit, bash, etc.)
      if (NATIVE[tool]) {
        const info = NATIVE[tool];
        logTool(tool, args, project);
        throw new Error(
          `ACCION DENEGADA: '${tool}' bloqueada.\nUse '${info.suggest}' — ${info.reason}.`
        );
      }

      // Bloqueo dinámico de tools externas: solo la tool que ia() ordenó
      const esExterna = EXTERNAL_PREFIXES.some(p => tool.startsWith(p));
      if (esExterna) {
        const token = leerToken();
        if (!token) {
          logTool(tool, args, project);
          throw new Error(
            `ACCION DENEGADA: '${tool}' requiere ia() primero.\n\n` +
            `1. ia(intent="describe tu tarea")\n` +
            `2. El orquestador ordena la tool exacta\n` +
            `3. Solo esa tool queda desbloqueada`
          );
        }

        // Verificar que la tool coincide con la ordenada
        const toolEsperada = token.tool;
        let coincide = tool.endsWith(toolEsperada);
        if (!coincide) {
          const prefijo = EXTERNAL_PREFIXES.find(p => p.startsWith(toolEsperada + '_'));
          if (prefijo) coincide = tool.startsWith(prefijo);
        }
        if (!coincide) {
          borrarToken();
          logTool(tool, args, project);
          throw new Error(
            `ACCION DENEGADA: El orquestador ordenó '${toolEsperada}', pero intentaste '${tool}'.\n\n` +
            `Debes ejecutar EXACTAMENTE la herramienta que el orquestador indicó en tool_suggestion.\n` +
            `Si necesitas otra operación, llama ia() nuevamente.`
          );
        }

        // Coincide → permitir y consumir token
        borrarToken();
      }
    }
  };
};
