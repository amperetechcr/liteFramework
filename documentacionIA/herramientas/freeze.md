# Freeze — Sistema de congelamiento de archivos

Tipo: capa de proteccion (Python MCP server)
Ruta: implementado en el wrapper Python del servidor MCP (CrewAI), no en PHP

## Descripcion

Sistema que impide que la IA modifique archivos protegidos accidentalmente.
Cuando un archivo esta "congelado", `lite_edit` lo rechaza con el error
"archivo congelado". La IA debe descongelarlo explicitamente antes de editarlo.

## Inputs (lite_freeze)

| Parametro | Descripcion |
|-----------|-------------|
| `accion`  | `listar` | `verificar` | `congelar` | `descongelar` | `check` | `analizar` |
| `archivo` | Ruta del archivo (relativa al proyecto) |
| `nivel`   | `total` (default) | `menor` | `plan` |
| `razon`   | Requerido para `congelar` |

## Reglas CRITICAS (Windows)

### 1. Path SEPARATOR: usar backslashes `\`

En Windows, el servidor MCP guarda los paths usando **backslashes**. Si usas
forward slashes `/` en `lite_freeze`, se guarda como una entrada diferente
a la que `lite_edit` verifica, y la descongelacion falla silenciosamente.

**CORRECTO:**
```
lite_freeze(accion="descongelar", archivo="src\modulos\inicio\inicio.php")
```

**INCORRECTO (falla silenciosamente):**
```
lite_freeze(accion="descongelar", archivo="src/modulos/inicio/inicio.php")
```

### 2. Siempre verificar antes de editar

Si `lite_edit` falla con "archivo congelado":
1. Corre `lite_freeze(accion="listar")` para ver el formato exacto del path
2. Usa EXACTAMENTE ese mismo formato en `lite_freeze(accion="descongelar", archivo=...)`
3. Reintenta `lite_edit`

### 3. Orden correcto

```
lite_freeze(accion="descongelar", archivo="ruta\\con\\backslashes.php")
   → si ok
lite_edit(filePath="ruta/con/backslashes.php", ...)
   → funciona
```

`lite_edit` acepta ambos separadores (normaliza internamente), pero
`lite_freeze` es sensible al separador en Windows.

## Niveles de congelamiento

| Nivel   | Permite |
|---------|---------|
| `total` | Ningun cambio |
| `menor` | Typos y logging, sin alterar estructura |
| `plan`  | Solo cambios con plan aprobado |

## Outputs

- `lite_freeze(accion="listar")`: lista de archivos congelados con ruta y nivel
- `lite_freeze(accion="verificar")`: OK o lista de archivos con hash modificado
- `lite_freeze(accion="check", archivo=...)`: estado de un archivo especifico
- `lite_freeze(accion="analizar")`: diagnostico del sistema de freeze

## Notas

- El freeze NO esta en PHP (OrquestadorIA.php no tiene logica de freeze)
- Esta en el wrapper Python del servidor MCP (CrewAI)
- `lite_edit` en PHP es solo `file_get_contents + str_replace + file_put_contents`
- La capa Python intercepta `lite_edit` y verifica el freeze antes de delegar a PHP
- No existe `lite_delete` ni herramienta de borrado de archivos en el framework
