# ORQUESTACIÓN lite_mcp — GUÍA RÁPIDA

## REGLA #1
Siempre llama ia(intent="inicializar") como PRIMER PASO.
Sin esto, todas las lite_* tools están bloqueadas.

## REGLA #2 — NO ADIVINES TOOLS
Si no estás 100% segura de qué tool usar:
   → llama ia(intent="describe tu necesidad en lenguaje natural")
   → ia() te devuelve la tool exacta y cómo usarla

## REGLA #3 — ENTRY POINT ÚNICO: ia(intent)
CUALQUIER operación DEBE empezar por ia(intent="describe en lenguaje natural").
El orquestador decide qué hacer:
  ├── Si devuelve resultado directo → listo (lite_* ejecutadas)
  ├── Si devuelve tool_suggestion → ejecutar tool + params exactos indicados
  │   └── Luego reportar feedback: ia(intent="feedback: {id} exito")
  └── Si devuelve "no_se" o ambigüedad → reformular con más detalle

NO usar herramientas directas como primera opción. Solo si ia() falla repetidamente:
- lite_read_file, lite_grep, lite_edit, lite_write_file
- lite_list_dir, lite_glob, lite_run, lite_equipo

## REGLA #4 — VERIFICACIÓN AUTOMÁTICA (N1)
Después de completar CUALQUIER implementación, la IA DEBE auto-verificar:
  1. bash(git diff --stat) para listar cambios
  2. bash(git diff) para ver diff
  3. lite_read_file(path) para cada archivo modificado
  4. Reportar y corregir cualquier alucinación/código inventado/inconsistencia