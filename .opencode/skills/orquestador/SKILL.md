---
name: orquestador
description: "Sistema de orquestación y control de IA — tool OBLIGATORIA via ia()"
---

## Tool #1 Obligatoria

Siempre que trabajes con LiteFramework, DEBES llamar `ia()` como PRIMER PASO.

Sin el orquestador, las herramientas del sistema (`lite_read_file`, `lite_write_file`,
`lite_run`, etc.) están bloqueadas por el MCP server hasta que llames `ia()`.

Ejemplos:
- `ia(intent="inicializar")`
- `ia(intent="crear un modulo Producto con campos nombre:string, precio:decimal")`
- `ia(intent="validar este token csrf recibido en el POST")`
- `ia(intent="que helpers tengo disponibles para trabajar con fechas")`

Una vez que llames `ia()`, todas las tools quedan disponibles.
