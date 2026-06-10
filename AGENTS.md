# liteFramework — Guía de arquitectura y flujo de trabajo

**Filosofía:** Este es uno de los únicos frameworks PHP hechos para que la IA lo use, no para que el humano toque código.
El humano da instrucciones en lenguaje natural → la IA escribe el código → el framework ejecuta.
No hay vendor oculto. No hay dependencias mágicas. La IA conoce cada línea. Por eso no alucina.

**Regla de calidad absoluta:** Todo código generado debe pasar PHPStan nivel 7 (0 errores), PHPCS PSR-12 (0 errores), y PHPUnit (0 failures, 0 skipped). No se aceptan baselines, skips, ni excepciones. Ver `.opencode/skills/lite-framework/instructions.md` para las tablas detalladas de reglas.

PHP 8.2+ con declare(strict_types=1). Sin dependencias externas (no Composer, no npm).
Código 100% en español con type hints en cada método.
OpenCode escribe. OpenClaw verifica.

## Stack

- PHP 8.2+ con declare(strict_types=1)
- Sin dependencias externas (no Composer, no npm)
- MySQL con SQLite fallback
- MVC con ORM Active Record propio
- SPA con JS vanilla (ES modules)
- CSS nativo con 13 paletas, 8 estilos, 16 fondos
- RBAC, CSRF, WAF, rate limiting, auditoria dual
- SSE en tiempo real con daemon auto-start
- Sentry nativo (ReporteroSentry, sin dependencias)
- Apache multi-thread (XAMPP) con compresión gzip
- PHPUnit 11 (458 tests, 1084 aserciones)
- PHPStan level 7, PHPCS 0 errores
- 6 MCP servers compartidos (git, time, fetch, sentry, context7, gh_grep)

## Flujo de trabajo: humano → IA → framework

```
HUMANO da instrucción en lenguaje natural →
  → IA analiza con skill arquitectura →
    → IA escribe código (modelo, ruta, controlador, vista, JS, CSS) →
      → IA valida (tests, PHPStan, browser testing, accesibilidad) →
        → Humano verifica resultado o itera
```

**El humano nunca escribe código directamente. La IA escribe, la IA verifica, el humano solo supervisa.**

## Cuando usar OpenCode

Usar OpenCode para **escribir y modificar codigo**:

- `opencode` → abrir TUI en el proyecto
- `opencode run "mensaje"` → ejecutar tarea desde terminal
- Skills en `.opencode/skills/lite-framework/`
- Comandos rapidos: `/test`, `/lint`, `/stan`, `/validate`, `/migrar`

```bash
opencode run "Crear modulo Producto con campos nombre, precio, stock"
opencode run "Agregar ruta GET /productos con interceptor de autenticacion"
opencode run validate    # PHPStan + PHPCS + PHPUnit — 1 comando
```

## Cuando usar OpenClaw

Usar OpenClaw para **automatizar pruebas y validacion**:

- `openclaw agent --agent main --message "..."` → ejecutar tarea
- Skills en `.agents/skills/` (8 skills disponibles)
- Browser tool para UI testing
- Exec tool para PHPUnit, curl, benchmarks

```bash
openclaw agent --agent main --message "Prueba el modulo de operadores"
openclaw agent --agent main --message "Ejecuta los tests y reporta resultados"
```

## MCP Servers (compartidos)

| MCP | Que hace |
|-----|----------|
| `context7` | Documentación de librerías y frameworks |
| `gh_grep` | Búsqueda de código en GitHub |
| `git` | Operaciones Git (status, diff, log, commit) |
| `time` | Conversión y consulta de zonas horarias |
| `fetch` | Fetch de URLs y contenido web |
| `sentry` | Gestión de proyectos Sentry (issues, DSNs) |

Configuración en `~/.config/opencode/opencode.json` (OpenCode) y `~/.openclaw/openclaw.json` (OpenClaw).

## Skills

### OpenCode (`.opencode/skills/`)
| Skill | Proposito |
|-------|-----------|
| `lite-framework` | Patrones practicos: modulos, rutas, ORM, validacion, seguridad |
| `lite-framework-config` | Configuracion del proyecto |

### OpenClaw (`.agents/skills/`)
| Skill | Proposito |
|-------|-----------|
| `arquitectura` | Estructura completa del framework |
| `convenciones` | Naming, tipos, PHP, JS, CSS, DB |
| `ui-testing` | Pruebas de UI con browser tool |
| `api-testing` | Pruebas de API REST |
| `performance-testing` | Benchmark y perfil de memoria |
| `accessibility` | Auditoria WCAG 2.2 |
| `frontend-design` | Guia de diseno frontend |
| `seo` | Optimizacion SEO |
