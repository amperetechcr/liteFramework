# liteFramework — Guía de arquitectura y flujo de trabajo

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

## Flujo de trabajo con IA

```
TAREA → OpenCode analiza (skill arquitectura) →
  → OpenCode escribe código (modelo, ruta, controlador, vista, JS, CSS) →
    → OpenClaw valida (tests, PHPStan, browser testing, accesibilidad) →
      → Feedback → iteración
```

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
