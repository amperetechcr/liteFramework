# liteFramework — Integracion OpenCode + OpenClaw

Este proyecto usa **OpenCode** para desarrollo y **OpenClaw** para testing/automatizacion.
Ambos leen este archivo como contexto de sistema.

## Flujo de trabajo

```
┌─────────────────────────────────────────────────────────────┐
│                    TAREA RECIBIDA                            │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
              ┌───────────────────────────────┐
              │   Analisis inicial             │
              │   Skills: arquitectura          │
              │   Herramienta: OpenCode         │
              └───────────────┬───────────────┘
                              │
                ┌─────────────┴─────────────┐
                ▼                           ▼
  ┌─────────────────────────┐  ┌─────────────────────────┐
  │  CODIFICACION            │  │  TESTING / VALIDACION    │
  │  OpenCode + agent        │  │  OpenClaw + agent        │
  │                          │  │                          │
  │  - Crear modulos         │  │  - UI tests (browser)    │
  │  - Escribir modelos      │  │  - API tests (curl)      │
  │  - Agregar rutas         │  │  - Performance tests     │
  │  - Migraciones           │  │  - Visual regression     │
  │  - Controladores         │  │  - Accesibilidad         │
  │  - JS/CSS                │  │  - Responsive testing    │
  └────────────┬────────────┘  └────────────┬────────────┘
               │                             │
               └─────────────┬───────────────┘
                             ▼
              ┌───────────────────────────────┐
              │  VERIFICACION                 │
              │  - Ejecutar tests PHPUnit      │
              │  - opencode run test-filter    │
              │  - OpenClaw: browser test      │
              └───────────────────────────────┘
```

## Cuando usar OpenCode

Usar OpenCode para **escribir y modificar codigo**:

- `opencode` → abrir TUI en el proyecto
- `opencode run "mensaje"` → ejecutar tarea desde terminal
- Skills en `.opencode/skills/lite-framework/`
- Comandos rapidos: `/test`, `/lint`, `/stan`, `/migrar`, `/sse`

```bash
# Ejemplos
opencode run "Crear modulo Producto con campos nombre, precio, stock"
opencode run "Agregar ruta GET /productos con interceptor de autenticacion"
```

## Cuando usar OpenClaw

Usar OpenClaw para **automatizar pruebas y validacion**:

- `openclaw agent --agent main --message "..."` → ejecutar tarea
- Skills en `.agents/skills/`
- Browser tool para UI testing
- Exec tool para PHPUnit, curl, benchmarks

```bash
# Ejemplos
openclaw agent --agent main --message "Prueba el modulo de operadores"
openclaw agent --agent main --message "Ejecuta los tests y reporta resultados"
```

## Skills disponibles

### OpenCode (`.opencode/skills/`)
| Skill | Proposito |
|-------|-----------|
| `lite-framework` | Patrones practicos: modulos, rutas, ORM, validacion, seguridad |

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

## Stack

- PHP 8.2+ con declare(strict_types=1)
- Sin dependencias externas (no Composer, no npm)
- MySQL con SQLite fallback
- MVC con ORM Active Record propio
- SPA con JS vanilla (ES modules)
- CSS nativo con 13 paletas, 8 estilos, 16 fondos
- RBAC, CSRF, WAF, rate limiting, auditoria dual
- SSE en tiempo real con daemon auto-start
- Apache multi-thread (XAMPP) con compresión gzip
- PHPUnit 11 (404 tests, 970 aserciones)
- PHPStan level 7, PHPCS 0 errores
