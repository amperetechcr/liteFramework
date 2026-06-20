# ORQUESTACIÓN lite_mcp — GUÍA RÁPIDA

## REGLA #1
Siempre llama ia(intent="inicializar") como PRIMER PASO.
Sin esto, todas las lite_* tools están bloqueadas.

## REGLA #2 — NO ADIVINES TOOLS
Si no estás 100% segura de qué tool usar:
   → llama ia(intent="describe tu necesidad en lenguaje natural")
   → ia() te devuelve la tool exacta y cómo usarla

## REGLA #3 — MAPPING RÁPIDO
- Leer archivo...          → lite_read_file(path)
- Buscar código...         → lite_grep(pattern)
- Editar texto...          → lite_edit(filePath, oldString, newString)
- Crear archivo...         → lite_write_file(path, content)
- Listar directorio...     → lite_list_dir(path) o lite_glob(pattern)
- Ejecutar comando...      → lite_run(command)
- Tarea compleja...        → lite_equipo(tipo, tarea)
- No sabes...              → ia(intent="...")

## REGLA #4 — VERIFICACIÓN AUTOMÁTICA
Después de completar CUALQUIER implementación (crear/editar/eliminar archivos),
la IA DEBE invocar automáticamente task(subagent_type="verificador") para revisar
los cambios. No esperar a que el usuario lo pida.