# lite_mcp — Guia de uso para la IA

## Regla #1: `ia()` siempre primero

Ninguna tool `lite_*` funciona sin llamar `ia()` antes. El servidor MCP bloquea fisicamente todas las tools hasta que se ejecute `ia()`.

```
IA: ia(intent="inicializar")
MCP: tools desbloqueadas, contexto cargado
```

## Tools nativas bloqueadas (enforcer)

OpenCode tiene tools nativas (`write`, `edit`, `bash`, `read`, `grep`, `glob`, `apply_patch`) pero estan **bloqueadas permanentemente** por `enforcer.mjs`. Usa siempre las equivalentes `lite_*`:

| Nativa bloqueada | Usa en su lugar | Motivo |
|-----------------|-----------------|--------|
| `write` | `lite_write_file` | Crea directorios automaticamente |
| `edit` | `lite_edit` | Detecta archivos congelados + respeta perimetro |
| `bash` | `lite_run` | Auto-agrega `--json` y token de autenticacion |
| `read` | `lite_read_file` | Resuelve rutas relativas a la raiz del proyecto |
| `grep` | `lite_grep` | Respeta PROJECT_ROOT y encoding UTF-8 |
| `glob` | `lite_glob` | Cambia al directorio correcto antes de buscar |
| `apply_patch` | `lite_edit` | Los parches pueden ignorar archivos congelados |

## Tools mecanicas (N1, 0 LLM, no usan inteligencia artificial)

Son operaciones directas de sistema operativo (archivos, procesos). No hay inferencia, no hay modelo, no hay llamada HTTP a LM Studio.

| Tool | Que hace |
|------|----------|
| `lite_read_file(path)` | Lee archivo del proyecto (path relativo) |
| `lite_write_file(path, content)` | Escribe archivo. Crea directorios si no existen |
| `lite_list_dir(path)` | Lista contenido del directorio |
| `lite_read_dir_tree(path)` | Arbol completo de directorios |
| `lite_run(command, args)` | Ejecuta comando CLI (consola del framework) |
| `lite_ping()` | Health check del servidor MCP |
| `lite_filtrar(consulta, archivo)` | Filtra contenido irrelevante (heuristica, reduce tokens) |
| `lite_grep(pattern, path, include)` | Busca con regex en archivos |
| `lite_glob(pattern, path)` | Busca archivos por patron glob |
| `lite_edit(filePath, oldString, newString, replaceAll)` | Reemplaza texto exacto en archivo |

## Tools de control de sesion (NUEVAS)

### `lite_modo` — Control de modo

Define que operaciones puede hacer la IA en la sesion actual.

```
lite_modo(accion="ver")
  → {"modo": "normal", "descripcion": "Acceso completo", ...}

lite_modo(accion="definir", modo="solo-lectura")
  → bloquea escritura y ejecucion de comandos
```

| Modo | Leer | Escribir | Ejecutar | Confirmar |
|------|------|----------|----------|-----------|
| `normal` | Si | Si | Si | No |
| `solo-lectura` | Si | **No** | **No** | No |
| `solo-plan` | Si | **No** | Si | No |
| `confirmar` | Si | Si | Si | **Si** (cola) |

Uso tipico:
```
lite_modo(accion="definir", modo="solo-lectura")  # investigar sin riesgo
lite_modo(accion="definir", modo="normal")          # volver a acceso completo
```

### `lite_perimetro` — Perimetro de archivos

Define que rutas del proyecto puede leer/escribir la IA.

```
lite_perimetro(accion="definir", permitir=["servidor/", "src/"], denegar=[".env", "storage/"])
lite_perimetro(accion="ver")
lite_perimetro(accion="reset")
```

- `permitir`: si se define, SOLO esas rutas son accesibles
- `denegar`: rutas explicitamente bloqueadas (aunque esten en permitir)
- `reset`: vuelve a acceso completo a todo el proyecto

### `lite_bitacora` — Bitacora de operaciones

Cada tool call queda registrada automaticamente en `~/.crewai/bitacora.log`. Se puede consultar en vivo.

```
lite_bitacora(accion="ver", limite=20)       # ultimas 20 operaciones
lite_bitacora(accion="exportar")              # todo el log como texto
```

## Freeze — `lite_freeze` — Archivos congelados

Sistema que protege archivos de modificaciones accidentales.
Implementado en la capa Python del servidor MCP, NO en PHP.

### Regla #1: Usar BACKSLASHES en Windows

**CRITICO**: `lite_freeze` es SENSIBLE al separador de path en Windows.
Usa backslashes `\`, NO forward slashes `/`.

```
✅ lite_freeze(accion="descongelar", archivo="src\archivo.php")
❌ lite_freeze(accion="descongelar", archivo="src/archivo.php")  → falla
```

### Flujo correcto

```
lite_freeze(accion="descongelar", archivo="ruta\\con\\backslashes.php")
   → si ok
lite_edit(filePath="ruta/con/forward/slashes.php", oldString="...", newString="...")
   → funciona (lite_edit normaliza internamente)
```

### Verificar estado

```
lite_freeze(accion="listar")   → ver que archivos estan congelados
lite_freeze(accion="check", archivo="ruta\especifica.php")  → estado de uno
```

Acciones disponibles: `listar`, `verificar`, `congelar`, `descongelar`, `check`, `analizar`.
Niveles: `total` (ningun cambio), `menor` (solo typos/logging), `plan` (solo con plan aprobado).

### `lite_autorizar` — Cola de aprobacion

Solo activa cuando el modo es `confirmar`. Las operaciones de escritura se encolan en vez de ejecutarse.

```
lite_autorizar(accion="listar")              # operaciones pendientes
lite_autorizar(accion="aprobar", id=1)       # ejecutar operacion
lite_autorizar(accion="rechazar", id=1)      # cancelar operacion
lite_autorizar(accion="limpiar")             # vaciar cola
```

## Flujo de trabajo recomendado

```
1. ia(intent="inicializar")              → desbloquea MCP
2. lite_modo(accion="definir", modo="solo-lectura")  → prevenir escrituras accidentales
3. lite_read_file / lite_grep / ...      → investigar
4. lite_modo(accion="definir", modo="normal")  → habilitar escritura
5. lite_write_file / lite_edit / ...     → modificar
6. lite_bitacora(accion="ver")           → verificar que se hizo
```

## Comandos CLI disponibles (via `lite_run`)

```
lite_run(command="inicio")                         # info del sistema
lite_run(command="list")                           # todos los comandos
lite_run(command="migrar")                         # ejecutar migraciones
lite_run(command="pruebas")                        # ejecutar tests
lite_run(command="auditoria:resumen")              # resumen de auditoria
lite_run(command="crud", args="--tabla=operador --accion=listar")
lite_run(command="ia:orquestar", args="auto diagnosticar sistema")
```

Nota: `lite_run` siempre agrega `--json --token=...` automaticamente. No es necesario pasarlos.
