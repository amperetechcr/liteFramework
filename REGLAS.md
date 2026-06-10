# REGLAS — liteFramework

> ⚠️ **INSTRUCCIÓN OBLIGATORIA — Llamar ia() PRIMERO**
>
> NO puedes usar ninguna herramienta del framework (`lite_read_file`, `lite_write_file`,
> `lite_run`, etc.) sin antes ejecutar `ia()`. El MCP server bloquea físicamente
> todas las tools `lite_*` hasta que el orquestador se inicialice.
>
> Pasos:
> 1. `ia(intent="inicializar")` — carga contexto del framework
> 2. O directamente: `ia(intent="<tu tarea en lenguaje natural>")`
> 3. Una vez ejecutado, todas las tools se desbloquean automáticamente
>
> Sin este paso cualquier tool `lite_*` devuelve error de bloqueo.

> Archivo único y centralizado con TODAS las reglas del framework.
> La IA debe llamar `ia(intent="inicializar")` al iniciar la sesión.
> Ninguna regla fuera de este archivo es válida.

---

## 1. Reglas Fundamentales

### 1.1 Filosofía
- Framework PHP 8.2+ zero-dependency diseñado para que la IA escriba todo el código.
- El humano solo da instrucciones en lenguaje natural. La IA conoce el 100% del código base.
- **No hay vendor/, no hay dependencias externas, no hay cajas negras.**
- **Sin Composer, sin npm.**
- Sentry, SMTP, Logger son implementaciones propias.

### 1.2 Regla #1 — Revertir si falla
- Todo cambio debe verificarse contra `bitacora_sistema` antes de darlo por bueno.
- Si aparece cualquier entrada nueva de nivel ERROR, SEGURIDAD, o ADVERTENCIA no existente antes del cambio, o si el usuario declara el módulo como roto → revertir inmediatamente.
- **No acumular cambios sobre código roto.**
- La regla aplica también ante error 500, test fallido, o notificación inesperada.

### 1.3 Regla #2 — Calidad absoluta
- Todo código debe pasar **PHPStan level 7** (0 errores), **PHPCS PSR-12** (0 errores), y **PHPUnit** (0 failures, 0 skipped).
- **No se aceptan baselines ni skips.**

### 1.4 Regla #3 — Trazar antes de tocar
- Antes de tocar cualquier archivo, asumir que NO se entiende cómo funciona.
- La respuesta por defecto a "¿Entiendo cómo funciona esto realmente o solo estoy asumiendo?" es **siempre** "estoy asumiendo".
- Antes de escribir código:
  1. Leer el archivo completo
  2. Identificar todos los imports, dependencias y consumidores
  3. Explicar por qué el diseño actual es como es
  4. Solo entonces proponer cambio
- **No asumir sin trazar.**

---

## 2. Reglas de Calidad (PHPStan + PHPCS + PHPUnit)

### 2.1 PHPStan Level 7 — Zero Tolerance
1. **Siempre validar `fopen()`**: `if ($salida === false)` después de llamarlo.
2. **Siempre validar `file_get_contents()`**: checkear que no sea `false` antes de usar.
3. **Siempre validar `json_encode()`**: `if ($payload !== false)` antes de `fwrite()`.
4. **Siempre validar `glob()`**: `if (is_array($archivos))` antes de iterar.
5. **Usar `->` no `?->`** cuando el tipo es no-nullable después de un null check.
6. **Validar tipos de `getopt()`**: `isset(...) && is_string(...)` antes de castear.
7. **No llamar métodos que no existen**: solo usar métodos existentes en cada clase.
8. **No `break` después de `throw`**: código muerto.
9. **BOM UTF-8 prohibido**: archivos sin BOM.

### 2.2 PHPCS PSR-12 — Zero Tolerance
1. No BOM UTF-8.
2. Llaves en nueva línea para funciones.
3. Llaves obligatorias en estructuras de control (sin one-liners).
4. camelCase para métodos (no SCREAMING_SNAKE).
5. Espacios en tipos unión: `true | string` no `true|string`.
6. Switch: cada `case` termina con `break` o `throw`.
7. Sin espacios/tabs finales en líneas.

### 2.3 PHPUnit — Zero Tolerance
1. **Nunca skippear tests**: `markTestSkipped()` prohibido.
2. Callbacks con trabajo real: usar loops de 100k iteraciones, no `fn() => 42`.
3. No colores hex hardcodeados en CSS: usar `var(--color-*)`.
4. Test y código sincronizados: si hay test, el código debe existir.

### 2.4 Entrega
- Si cualquiera de los 3 falla, corregir y re-verificar.
- No se entrega código que falle PHPStan + PHPCS + PHPUnit.

---

## 3. Reglas de Arquitectura

### 3.1 Stack Tecnológico
- PHP 8.2+ con `declare(strict_types=1)`.
- MySQL con fallback automático a SQLite in-memory.
- MVC con ORM Active Record propio (`Modelo.php`).
- SPA con JS vanilla (ES modules, sin frameworks).
- CSS nativo con 13 paletas, 19 estilos visuales.
- PHPUnit 11, PHPStan level 7, PHPCS PSR-12.

### 3.2 Naming Conventions
| Elemento | Convención | Ejemplo |
|----------|------------|---------|
| Clases PHP | PascalCase español | `ControladorBase` |
| Métodos PHP | camelCase español | `obtenerInstancia()` |
| Variables PHP | snake_case español | `$id_operador` |
| Archivos PHP | PascalCase | `Enrutador.php` |
| Archivos JS | camelCase | `navegacion.js` |
| Archivos CSS | kebab-case | `maquetacion.css` |
| Rutas URL | kebab-case | `/panel-control` |
| Tablas DB | snake_case | `operador` |
| Columnas DB | snake_case | `id_operador` |
| Clases CSS | kebab-case con BEM | `.tarjeta-seleccion-modulo` |
| IDs HTML | kebab-case | `#contenido-principal` |
| Constantes | UPPER_SNAKE_CASE | `DIRECTORIO_RAIZ` |
| Namespaces | PascalCase | `LiteFramework\Nucleo` |
| Rutas nombradas | snake_case | `panel.control` |

### 3.3 Reglas de Código PHP
- `declare(strict_types=1)` en TODOS los archivos.
- Type hints en TODOS los parámetros y retornos. Sin excepción.
- Sin `eval()`, sin `extract()`, sin `${$var}`.
- Sin `vendor/` ni dependencias externas.
- Namespace `LiteFramework\` mapea a `servidor/`.
- `h($var)` en toda salida HTML (XSS prevention).
- Prepared statements SIEMPRE (SQL injection prevention).
- Validar servidor aunque valide cliente.

### 3.4 Excepciones Personalizadas
- `ErrorSeguridad` → 403
- `ErrorAutenticacion` → 401
- `ErrorValidacion` → 422
- `ErrorHttp` → código HTTP variable
- `ErrorRed` → errores de red

### 3.5 Routing
- Single entry point: `index.php`.
- Toda ruta protegida pasa por RendimientoInterceptor → AutenticInterceptor/ApiAuthInterceptor.
- SSR dual: carga completa HTML y parcial AJAX (`?partial=lista`).
- Parámetros `{id}` se pasan como argumentos tipados al closure.
- URLs por nombre: `Enrutador::url('alias', ['id' => 1])`.
- Grupos con prefijo e interceptores hereditarios.
- Ruta nueva → `web.php` antes de `Enrutador::registrarInstancia()`.

### 3.6 API Contract
- Único endpoint `POST /api`.
- Payload JSON con `Content-Type: application/json`.
- CSRF token en campo `token_peticion`.
- Toda respuesta incluye: `estado_operacion`, `mensaje_error`, `codigo_error`, `nuevo_token`, `datos`, `redireccion`.
- Códigos de error: `no_autenticado`, `sin_permiso`, `token_invalido`, `datos_invalidos`.
- CRUD genérico usa `accion_crud` + `tabla_destino` en whitelist.
- Los controladores API son clases independientes (sin herencia).
- Los controladores web extienden `ControladorBase`.
- API POST retorna `[httpStatus, responseData]`.

### 3.7 Paginación — REGLA CRÍTICA
- **NUNCA** usar ORM chain con `limite()` + `saltar()` → Error 500 por estado estático compartido.
- **SIEMPRE** usar PDO directo para paginación.
- **NO** llamar `$pag->paginaActual()` como método → Error 500.
- Usar propiedades públicas: `$pag->paginaActual`, `$pag->porPagina`, `$pag->totalPaginas`, `$pag->totalRegistros`, `$pag->offset()`.
- Patrón obligatorio para paginación:
  1. `ConexionBaseDatos::obtenerInstancia()->obtenerConector()`
  2. `$con->query("SELECT COUNT(*) FROM tabla")->fetchColumn()`
  3. `Paginador::crear($total, 20)`
  4. `$con->prepare("SELECT * FROM tabla ORDER BY fecha DESC LIMIT :lim OFFSET :off")`
  5. Bind con `PDO::PARAM_INT`
  6. Execute, fetch con `PDO::FETCH_ASSOC`, hydratar con `new MiModelo($f)`

### 3.8 Configuración Dinámica
- Flujo: `.env` → tabla `configuracion_sistema` → `ConfiguracionSistema::obtener()` (cache 30s) → `GeneradorIniServidor::regenerar()` → `.user.ini`.
- Solo Super Admin (rol 1), requiere `CONFIRMAR`.
- Nuevo parámetro configurable: 1) default en `GestorEntorno`, 2) `INSERT IGNORE` en migración, 3) `ConfiguracionSistema::obtener()` en controladores.
- File uploads: usar `XMLHttpRequest` (no `fetch` — no progress). Mostrar porcentaje, bytes, velocidad, tiempo restante.

### 3.9 JavaScript y Frontend
- ES modules (`type="module"`).
- camelCase para funciones y variables.
- Entry point: `src/js/principal.js`.
- `fetch()` con `X-Requested-With: XMLHttpRequest`.
- CSRF token via `<meta name="csrf-token">` o campo `token_peticion`.
- `window.fetch` monkey-patched en `utilidades.js` para offline queue.
- `NotificadorHubble.mostrar()` para notificaciones.
- SPA: `navegacion.js` usa `history.pushState`/`popstate`; AJAX con `?ajax=1`.

### 3.10 CSS
- CSS Custom Properties para todo (design tokens en `tema.css`).
- 13 paletas via `.paleta-*`, 8 estilos via `.estilo-*`.
- 4 breakpoints responsive (>=1025, 769-1024, 601-768, <=600).
- `@media (prefers-color-scheme: dark)` para tema oscuro.
- Sin colores hex hardcodeados (excepciones: PDFs).
- `rem` para font-sizes y spacing.
- Animaciones respetan `prefers-reduced-motion`.
- Orden de carga: tema → paletas → maquetacion → componentes → modales → subirArchivos → generadorModulo → estilos → errores → utilidades → personalizacion.

### 3.11 Modelos (ORM)
- Extender `Modelo` con propiedades estáticas:
  - `$tabla`, `$idColumna`, `$rellenable`, `$tipos` (`int/float/bool/json`), `$timestamps`.
- SQLite en test.
- `DialectoBaseDatos` abstrae diferencias entre MySQL y SQLite.
- NUNCA ORM chain con `limite()+saltar()`.

---

## 4. Reglas de Seguridad

### 4.1 CSRF
- **CSRF obligatorio en CADA POST** (header `X-CSRF-Token` o body `token_peticion`).
- Token de 64 caracteres, rotación post-validación, gracia de 60s para el anterior.
- CSRF en cada POST, token rotado en respuesta.
- Payload JSON o POST → CSRF obligatorio.

### 4.2 Sesión
- Llamar siempre `SeguridadServidor::iniciarSesionEstricta()` antes de usar sesión.
- Cookie `HttpOnly` + `SameSite=Strict`.
- `session_regenerate_id(true)` post-login.
- Fingerprint: SHA-256(subred IP + User-Agent). Si cambia → sesión invalidada.

### 4.3 RBAC
- 4 roles: Super Admin (1), Admin (2), Operador Estandar (3), Consultor (4).
- 22 permisos.
- Control via `ControlAccesoRBAC::tienePermiso()`, `requerirPermisoEstricto()`.

### 4.4 Contraseñas
- `password_hash($clave, PASSWORD_DEFAULT)` / `password_verify()`.
- `PoliticaContrasena::validar($clave)`: 8+ chars, 1 mayúscula, 1 número, 1 símbolo.

### 4.5 Rate Limiting
- 5 intentos, 15 min bloqueo.
- Por `clave_hash` + time window.

### 4.6 WAF
- Bloquea User-Agents: curl, python, wget, sqlmap, nmap, burp, scan.

### 4.7 Headers HTTP de Seguridad
- CSP, X-Frame-Options: DENY, X-Content-Type-Options: nosniff, HSTS (si HTTPS), Referrer-Policy, Permissions-Policy.

### 4.8 Auditoría
- Dual: BD (`bitacora_sistema`) + archivo (`storage/logs/trazabilidad.log`).
- 5 niveles: INFO, ADVERTENCIA, ERROR, SEGURIDAD, AUDITORIA.
- Auditar en TODA operación de escritura.
- `RegistroAuditoria::info/advertencia/error/seguridad/auditoria()`.
- SSE en errores, exportación JSON/CSV.
- Lock de archivos para operaciones críticas (migraciones, SSE daemon).

### 4.9 Otras
- Cada petición tiene `X-Trace-Id` único.
- Sin secrets en código (`.env` + `GestorEntorno`).
- Nunca servir storage directo (bloqueado `.htaccess`). Usar endpoints con auth+RBAC.
- SubidaArchivos: tipos MIME + bytes. Guarda como `bin2hex(random_bytes(16))`.
- Validar MIME real server-side.

---

## 5. Reglas de Base de Datos

### 5.1 General
- MySQL 5.7+ principal, SQLite in-memory fallback (auto-detect).
- SQLite in-memory para tests.
- Prepared statements SIEMPRE.
- Migraciones versionadas en `servidor/migraciones/`.
- Tabla `_migraciones` rastrea archivo + hash SHA-256.
- Ejecuta en transacciones.
- Backup automático pre-ejecución (SHOW CREATE TABLE + SELECT * → SQL).
- Restauración con DROP + INSERT + FOREIGN_KEY_CHECKS=0.

### 5.2 Naming
- Tablas: snake_case español.
- Columnas: snake_case español.
- Foráneas: `id_operador`, `id_rol`, etc.
- Timestamps: `fecha_registro`, `fecha_actualizacion`, `fecha_creacion`.

### 5.3 ORM vs PDO
- Usar ORM chain para: `MiModelo::todos()`, `buscar($id)`, `donde('campo','valor')->obtener()`, create/update/delete.
- Usar PDO directo para: paginación con `limite()+saltar()`, queries complejos con múltiples condiciones, cualquier query que falle silenciosamente con 500.

---

## 6. Reglas de Frontend

### 6.1 Diseño
- Elegir una dirección conceptual clara y ejecutar con precisión. Maximalismo y minimalismo funcionan si son intencionales.
- **NUNCA** usar estéticas AI genéricas: Inter/Roboto/Arial, purple gradients, layouts predecibles.
- **NUNCA** converger a opciones comunes (Space Grotesk, etc.).
- Coincidir complejidad de implementación con visión estética.

### 6.2 Accesibilidad WCAG A/AA
- **1.1 Text alternatives**: todo `<img>` requiere `alt`. Icon buttons necesitan `aria-label`.
- **1.4.3 Color contrast**: texto normal <18px = 4.5:1 mínimo. Grande >=18px = 3:1 mínimo. UI components = 3:1 mínimo.
- **No usar solo color** para convey información — agregar icono + texto.
- **2.1 Keyboard**: toda funcionalidad accesible por teclado. Sin keyboard traps.
- **2.4.7 Focus visible**: nunca `outline: none` sin `:focus-visible` alternativo con 2px solid.
- **2.4.1 Skip links**: proveer `#salto-contenido`.
- **2.5.8 Target size**: elementos interactivos mínimo 24x24 CSS pixels.
- **2.5.7 Dragging**: drag debe tener alternativa single-pointer.
- **2.2 Timing**: permitir extender time limits.
- **2.3 Motion**: respetar `prefers-reduced-motion: reduce`.
- **3.1.1 Page language**: `<html lang="...">`.
- **3.2.3 Consistent navigation**: navegación consistente en todas las páginas.
- **3.3.2 Form labels**: todo `<input>` con `<label>` asociado.
- **3.3.1/3.3.3 Error handling**: `role="alert"`, `aria-invalid`, focus en primer error.
- **3.3.7 Redundant entry**: no forzar re-ingreso de datos ya provistos.
- **3.3.8 Accessible authentication**: login no debe depender solo de cognitive function tests. Ofrecer paste/autofill, passkey, SSO.
- **4.1.2 ARIA**: preferir HTML nativo sobre ARIA.
- **4.1.3 Live regions**: `aria-live` para cambios dinámicos.

---

## 7. Reglas de Testing

### 7.1 PHPUnit
- PHPUnit 11 con SQLite in-memory.
- Constante `TESTS_RUNNING` para modo test.
- `ConexionBaseDatos::resetearInstancia()` entre tests.
- Tests en español (nombres, métodos, aserciones).
- Archivos: `*Test.php` en `tests/Casos/` o `tests/Integracion/`.
- `tests/bootstrap.php` carga autoload, entorno, error handler.
- En Windows: usar `cmd /c` para ejecutar tests (PowerShell 5.1 no maneja `\r` de PHPUnit 11).

### 7.2 API Testing
- Response contract MUST: `estado_operacion`, `mensaje_error`, `codigo_error`, `nuevo_token` (64 chars), `datos`, `redireccion`.
- Login: success → `nuevo_token` presente + datos operador + session cookie.
- Login: wrong password → `credenciales_invalidas`; suspended → `cuenta_suspendida`; rate limited → `muchos_intentos`.
- CSRF: sin token → `token_invalido`; wrong token → `token_invalido`; expired → `token_invalido`; `nuevo_token` rotado.
- RBAC: testear cada acción con cada rol contra matriz de permisos.
- CRUD whitelist: solo `operador`, `documento_pdf`, `estadistica`, `archivo`.
- Rate limiting: N requests rápidos → `muchos_intentos`.
- SQL injection: testear caracteres especiales.
- XSS: testear `<script>` tags.
- Session fixation: session ID debe cambiar en login.
- Errores no deben exponer información sensible.

### 7.3 UI Testing (vía OpenClaw Browser)
- Todos los módulos deben cargar vía SPA sin recarga completa.
- `#contenido-principal` actualiza via AJAX; URL cambia via `history.pushState`; back/forward funciona.
- Sidebar responsive: >=1025 fixed, 769-1024 collapsable, 601-768 off-canvas overlay, <=600 off-canvas fullscreen.
- Testear 13 paletas x 8 estilos x light/dark x 7 fuentes x 5 spacings.
- Notificaciones: máx 5 visibles. Auto-dismiss: success=3s, danger=5s, warning=6s. Swipe-to-dismiss. Pausa en hover.
- Offline: banner visible, retry queue (`COLA_REINTENTOS`), `.offline` CSS class aplicado/removido.
- Error pages: 404, 403, 500, 503 con mensajes amigables.

### 7.4 Performance Testing
- Medir tiempo de carga de cada módulo SPA (con y sin `ajax=1`).
- API Benchmark: `ab -n 100 -c 10` en endpoints críticos.
- SQL profiling: verificar índices faltantes, N+1 queries, lazy vs eager loading.
- Memory profiling: autoload, routing, ORM, module generation, full page load.
- Core Web Vitals: FCP, LCP, CLS, TTI, TBT.
- Asset loading: orden correcto, cache headers, compression.

---

## 8. Reglas de SEO

### 8.1 Técnico
- `robots.txt`: Allow `/`, Disallow `/admin/`, `/api/`, `/private/`.
- Canonical URLs self-referencing. Paginación con `rel="prev"`/`rel="next"`.
- XML Sitemap: max 50,000 URLs, solo indexables.
- URL structure: guiones, lowercase, <75 chars, sin parámetros cuando sea posible.
- HTTPS siempre. Headers: HSTS, X-Content-Type-Options, X-Frame-Options.

### 8.2 On-Page
- Title tags: 50-60 chars, keyword al inicio, único por página.
- Meta descriptions: 150-160 chars, keyword natural, call-to-action.
- Single `<h1>` por página, jerarquía lógica, keywords naturales.
- Image SEO: filenames descriptivos, alt text, WebP/AVIF con fallbacks, lazy loading.
- Internal linking: anchor text descriptivo, enlaces relevantes, breadcrumbs.

### 8.3 Structured Data
- JSON-LD para Organization, Article, Product, FAQ, Breadcrumbs.
- Validar con Google Rich Results Test.

### 8.4 Mobile
- `meta name="viewport" content="width=device-width, initial-scale=1"`.
- Tap targets: mínimo 48px. Font sizes: mínimo 16px.
- `rel="alternate" hreflang="..."` para multi-idioma.

---

## 9. Reglas del Orquestador y CrewAI

### 9.1 Arquitectura de 3 Niveles
- **N3: ORQUESTADOR** — 1 LLM, planea y decide.
- **N2: TACTICO** — Manager PM + 1-3 sub-agentes, hierarchical.
- **N1: MECANICO** — 0 LLM, Python/PHP directo, instantáneo.
- No mezclar niveles. Cada tool pertenece a UN nivel.

### 9.2 Orquestador
- `kickoff()` crea 1 Crew con 1 solo agente: `orquestador`.
- El orquestador decide qué tools tácticas llamar.
- Ningún otro agente ve el prompt completo del usuario.
- Pasos del orquestador:
  1. Analizar prompt y decidir equipo necesario.
  2. Empezar con `lite_equipo(tipo='analisis', ...)`.
  3. Equipos dinámicos: `lite_equipo(tipo='custom', agentes='a,b,c', ...)`.
  4. Instruir a agentes a usar `lite_filtrar` antes de leer archivos para reducir tokens.
  5. Armar equipo perfecto para CADA tarea.
  6. Si es compleja, ejecutar múltiples equipos en secuencia.
  7. PM puede re-invocar `lite_equipo` para especialistas adicionales.
  8. Después de CADA tool call: `lite_sse_enviar(...)` para actualizar visualizador.
  9. Consolidar todo en respuesta estructurada.

### 9.3 Agentes (29 disponibles)
- Solo `pm` y `tech_lead` tienen `allow_delegation=True`.
- `max_iter`: pm=8, orquestador=3, resto=5.
- PM recibe siempre `max_iter = subagent_iter + 2`.
- MCPs mínimos por agente (cada agente solo los que necesita).
- `lite_call` disponible para agents con tools: tech_lead, arquitecto, frontend_dev, backend_dev, fullstack_dev, owner, pm_asistente, opencode_bridge, orquestador.

### 9.4 Equipos (Crews)
- `process="hierarchical"` con <=2 sub-agentes. Más de 3 = lento.
- `process="sequential"` para <=3 agentes sin manager.
- `manager_agent=pm` NO incluirlo en `agents[]`.
- `planning=False`, `max_rpm=15`, `cache=True`.
- `max_iter` por tipo: kickoff=5, desarrollo=6, analisis=5, revision_diseno=4, seguridad=4, frontend=5, backend=5, completo=6, datos=5, investigacion=6, legal=3, negocio=4, contenido=4, calidad=4, despliegue=4, limpieza=4, ui=5, infraestructura=4, documentacion=4.

### 9.5 SSE
- Después de CADA tool call: `lite_sse_enviar(operador_id=0, tipo='crewai', datos=json.dumps({...}))`.
- `agent_role`: nombre exacto de variable Python (snake_case), NO nombre humano.
- `accion`: codificando, revisando, leyendo, reunion, servidor, cafe, jugando, documentando.

---

## 10. Reglas de MCP y Tools

### 10.1 Protocolo MCP
- Formato: newline-JSON (NO Content-Length).
- `sys.stdout.write(json.dumps(msg) + "\n")` + `sys.stdout.flush()`.
- `sys.stdin.readline()` → `json.loads()`.
- NO usar FastMCP para servidores stdio.
- NO usar `mcp.run()`.
- `PYTHONIOENCODING=utf-8` en 3 lugares: opencode.json, mcp_server.py, cada script.

### 10.2 Tools MCP (34 disponibles)
- **N1 Mecánicas (6):** lite_read_file, lite_write_file, lite_list_dir, lite_read_dir_tree, lite_run, lite_ping. 0 LLM, ~0ms.
- **N2 CLI (24):** migrar, modulo:generar, proyecto:crear, pruebas, auditoria, mantenimiento, lock, correo, operador, crud, sse, freeze, diagnostico, iteraciones, ia:orquestar. ~50-80ms.
- **N2 Tácticas (3):** lite_equipo (kickoff, design_review, develop). ~30-60s.
- **Tool #1 `ia`:** orquestador universal. USAR SIEMPRE PRIMERO.

### 10.3 lite_call
- NO inventar nombres de tools. Usar SIEMPRE los parámetros exactos.
- `tool_name`: string exacto.
- `args_json`: JSON con nombres de parámetros exactos.

### 10.4 lite_run
- Auto-agrega `--json --token=AI_CREW_DEFAULT_TOKEN_2024`.

### 10.5 lite_filtrar
- Usar SIEMPRE antes de `lite_read_file` para reducir tokens.
- `lite_filtrar(consulta='...', archivo='...')` o `lite_filtrar(consulta='...', texto='...')`.

### 10.6 lite_freeze — Niveles de Protección
- `total`: ningún cambio sin autorización explícita del usuario.
- `menor`: solo cambios que NO alteren estructura/lógica (typos, logging, comentarios).
- `plan`: requiere presentar plan de refactor al usuario primero.
- Checklist pre-modificación:
  - Ejecutar `lite_freeze(accion="check", archivo="X")` — ¿está congelado?
  - Si SÍ: respetar nivel.
  - Si NO: investigar por qué. Ejecutar `lite_freeze(accion="analizar")`.
  - Después de modificar: `lite_freeze(accion="verificar")`.
  - Si el cambio es estable: `lite_freeze(accion="congelar", ...)`.

### 10.7 NO usar WSL para MCP servers
- WSL-based MCPs son lentos (~1s+) y pueden colgarse.
- Usar: HTTP MCPs, npx MCPs, o Python directo.

---

## 11. Archivos Congelados

- **Total (no changes):** 493 archivos — todo `servidor/`, `src/`, `tests/`, `plantillas/`, raíz del proyecto.
- **Menor (typos/logging):** 40 archivos — docs, skills, configs markdown.
- **Plan (requiere aprobación):** 1 archivo — `tools.py`.
- Regla general: si un archivo no está en frozen.json, investigar antes de modificar.

---

## 12. Recordatorios IA

1. **Type hints SIEMPRE**: `function foo(string $param): array` — parámetros y retorno. Sin excepción.
2. **Español siempre**: variables, comentarios, docs, commits.
3. **Sin dependencias externas**: no Composer, no npm.
4. **Clase nueva → `autoload.php`**: agregar clase + alias.
5. **Ruta nueva → `web.php`**: antes de `Enrutador::registrarInstancia()`.
6. **Prepared statements SIEMPRE**: nunca concatenar SQL.
7. **`h($var)` en toda salida HTML**: XSS prevention.
8. **Validar servidor aunque valide cliente**: doble validación.
9. **Auditar ops importantes**: `RegistroAuditoria`.
10. **Sin secrets en código**: `.env` + `GestorEntorno`.
11. **`session_regenerate_id(true)` post-login**: session fixation prevention.
12. **Paginación: NUNCA ORM chain con `limite()/saltar()`**: PDO directo.
13. **CSRF en toda petición POST**: header `X-CSRF-Token` o body `token_peticion`.
14. **`SENTRY_DSN` en `.env`**: habilita reportero automático de errores.

### Protocolo de Depuración (NO SALTAR PASOS)
1. Tests (`cmd /c`) — el código debe pasar.
2. Bitácora (`lite_crud`) — leer error REAL de BD.
3. Log archivo (`storage/logs/trazabilidad.log`) — trazas y trace_id.
4. Consola navegador (F12) — errores JS, 403/404, fetch fails.
5. MCP tools — datos vivos, no adivinar.
6. `console.log` — solo si 1-5 no dieron respuesta.
7. Fix más simple (1 línea) — probar, si falla revertir.
8. Si 3 intentos fallan — revertir TODO, volver al paso 1.

### Prohibido
- Desactivar seguridad completa.
- Modificar archivos no relacionados.
- Hacer 10 cambios a la vez.
- Asumir sin ver logs.

---

## 13. Configuración del Sistema

### 13.1 Modelo
- LM Studio local: `http://127.0.0.1:1234/v1`.
- Modelo: `cerebras_Qwen3-Coder-REAP-25B-A3B-Q4_K_S`.
- Temperature: 0.2 (determinístico).

### 13.2 MCP Servers (16)
- context7, gh_grep, time, fetch, sentry, git, firecrawl, memory, puppeteer, openclaw, crewai, lite_mcp, playwright, deepwiki, sequential_thinking, mcp_image.

### 13.3 Roles
- `AI_AGENT_ROLE=manager` → escritura total.
- `AI_AGENT_ROLE=worker` → solo lectura.
- Token: `AI_CREW_DEFAULT_TOKEN_2024` (no cambiar sin actualizar hash).
- Hash: `ced1f72a8c7bec9b034b525b03865225647ed8b5296875316b101e4a87c06fd1`.

### 13.4 Base de Datos
- Host: localhost, BD: lite, User: root, Pass: (vacía).

### 13.5 Admin
- Email: `desarrolloia@gmail.com`, Rol: Super Admin (id_rol=1), ID: 482.
- Este usuario SIEMPRE debe existir.

### 13.6 Memoria Persistente
- Usar `memory` MCP para almacenar decisiones, preferencias, decisiones de arquitectura.
- Al iniciar sesión: buscar entidades relevantes.
- Durante sesión: almacenar decisiones con file paths.
- Al final: consolidar resumen de lo logrado.
- Una observación por hecho. Relacionar entidades. Buscar antes de crear.

---

> Fin de REGLAS.md — Este archivo reemplaza: AGENTS.md, src/docs/AGENTS.md, src/docs/API.md, src/docs/DISENO_FRONTEND.md, src/docs/CODE_OF_CONDUCT.md, src/docs/CONTRIBUTING.md, SECURITY.md, CHANGELOG.md, README.md, .env (reglas AI), .opencode/skills/lite-framework/*, .agents/skills/*, GROUND_RULES.md, TOOLS.md, frozen.json (metadata de reglas), memory/SKILL.md.
