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

## REGLA #4 — VERIFICACIÓN AUTOMÁTICA (N1)
Después de completar CUALQUIER implementación, la IA DEBE auto-verificar:
  1. lite_run(git diff --stat) para listar cambios
  2. lite_run(git diff) para ver diff
  3. lite_read_file(path) para cada archivo modificado
  4. Reportar y corregir cualquier alucinación/código inventado/inconsistencia