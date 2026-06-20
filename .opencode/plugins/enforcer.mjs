const BLOCKED = {
  write:       { suggest: "lite_write_file", reason: "usa el MCP que crea directorios automáticamente" },
  edit:        { suggest: "lite_edit",       reason: "el MCP detecta archivos congelados y respeta el perímetro" },
  bash:        { suggest: "lite_run",        reason: "el MCP auto-agrega --json y token de autenticación" },
  read:        { suggest: "lite_read_file",  reason: "el MCP resuelve rutas relativas a la raíz del proyecto" },
  grep:        { suggest: "lite_grep",       reason: "el MCP respeta PROJECT_ROOT y el encoding UTF-8" },
  glob:        { suggest: "lite_glob",       reason: "el MCP cambia al directorio correcto antes de buscar" },
  apply_patch: { suggest: "lite_edit",       reason: "los parches pueden ignorar archivos congelados" },
};

const LOG_FILE = require("path").join(require("os").homedir(), ".crewai", "enforcer.log");

let primerMensaje = true;

export const Enforcer = async ({ project, directory }) => {
  return {
    "tool.execute.before": async (input, output) => {
      if (!BLOCKED[input.tool]) return;

      const info = BLOCKED[input.tool];
      const argsStr = input.args ? JSON.stringify(input.args).slice(0, 200) : "(sin argumentos)";

      try {
        const fs = require("fs");
        const entry = JSON.stringify({
          t: Date.now(),
          tool: input.tool,
          args: argsStr,
          proyecto: project || "?",
        }) + "\n";
        const dir = require("path").dirname(LOG_FILE);
        if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
        fs.appendFileSync(LOG_FILE, entry, "utf-8");
      } catch (_) {}

      if (primerMensaje) {
        primerMensaje = false;
        throw new Error(
          `ACCION DENEGADA: '${input.tool}' está bloqueada permanentemente.\n\n` +
          `En su lugar, use '${info.suggest}' — ${info.reason}.\n\n` +
          `Tools nativas BLOQUEADAS (use las lite_* en su lugar):\n` +
          Object.entries(BLOCKED).map(([t, i]) => `  ${t.padEnd(12)} → ${i.suggest}`).join("\n") + "\n\n" +
          `Flujo correcto:\n` +
          `  1. ia(intent="inicializar")  ← desbloquea MCP y da contexto del framework\n` +
          `  2. lite_read_file / lite_write_file / lite_edit / lite_grep / lite_glob / lite_run  ← tools mecánicas sin IA\n\n` +
          `Detalle: '${input.tool}' bloqueado porque ${info.reason}.`
        );
      }

      throw new Error(
        `ACCION DENEGADA: '${input.tool}' bloqueada. Use '${info.suggest}' en su lugar.`
      );
    }
  };
};
