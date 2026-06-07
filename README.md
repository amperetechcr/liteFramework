# liteFramework

[![CI](https://img.shields.io/github/actions/workflow/status/amperetechcr/liteFramework/ci.yml?branch=master&label=PHPStan%20PHPCS%20PHPUnit&logo=github)](https://github.com/amperetechcr/liteFramework/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4-777BB4?logo=php)](https://php.net)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%207-brightgreen)](https://phpstan.org)
[![PSR-12](https://img.shields.io/badge/PSR--12-%E2%9C%85-blueviolet)](phpcs.xml.dist)
[![Sentry](https://img.shields.io/badge/Sentry-monitoring-%23362D59)](https://sentry.io)
[![License](https://img.shields.io/badge/license-Apache%202.0-blue)](LICENSE.md)

**El framework PHP donde humanos e IA crean juntos.**  
Zero dependencias externas. Código 100% legible por IA. OpenCode + OpenClaw nativos.  
MVC, ORM, RBAC, SSE, Sentry — todo desde cero, sin vendor oculto, sin magia.

---

## Why liteFramework?

| | Laravel / Symfony | liteFramework |
|---|---|---|
| **Dependencias** | 50+ paquetes Composer — la IA no puede ver el código interno | Cero. La IA lee cada línea del framework |
| **Idioma** | Inglés técnico mezclado con config | 100% español. Semántica consistente para IA y humanos |
| **IA nativa** | No diseñado para IA colaborativa | AGENTS.md, skills, MCP, OpenCode + OpenClaw listos desde el día 1 |
| **Curva de IA** | La IA debe conocer las convenciones del framework de memoria | La IA aprende el framework leyéndolo directamente |
| **Código fuente** | 90% del código en vendor/ — opaco, inmodificable | 100% visible, tipado, documentado, modificable |

---

## Features

| Área | Capacidades |
|------|------------|
| **🤖 AI Synergy** | 8 skills para OpenClaw (arquitectura, testing, SEO, UI, API, rendimiento), 6 MCP servers (git, time, fetch, sentry, context7, gh_grep), AGENTS.md por capa del framework, código 100% en español con type hints explícitos en cada método |
| **🔍 Zero Dependencies** | Sin Composer, sin npm, sin vendor/. Cada línea del framework fue escrita para que una IA pueda leerla, entenderla y modificarla. Sin cajas negras, sin magia |
| **🧪 Quality Gates** | 458 tests / 1084 aserciones, PHPStan level 7, PHPCS 0 errores (PSR-12), CI en 3 versiones de PHP (8.2, 8.3, 8.4). La IA verifica que no rompe nada |
| **Arquitectura** | MVC con interceptors, ORM Active Record, enrutador con parámetros dinámicos, helpers, namespaces PSR-4 |
| **Seguridad** | CSRF rotativo con gracia 60s, RBAC granular, anti-secuestro (fingerprint), rate limiting DB, WAF, auditoría dual (BD + archivo), excepciones personalizadas, CSP/HTTPS/HSTS |
| **Base de Datos** | MySQL con fallback SQLite, migraciones versionadas con backup/restore, query builder, dialecto compatible MySQL/SQLite |
| **API** | Endpoint único `POST /api` con 15+ controladores, CRUD genérico, contrato uniforme |
| **Frontend** | 15 hojas CSS con 13 paletas, 8 estilos, 16 fondos; SPA nativo; ES modules sin bundler; adaptación táctil/ancho de banda |
| **SSE en Tiempo Real** | Daemon auto-start, `Last-Event-Id`, caché de posición en archivo (`fseek`), polling optimizado (500ms DB / 1s archivo), sesión con `session_write_close()` |
| **Rendimiento** | Apache multi-thread (XAMPP), compresión gzip (~71% reducción CSS), sin dependencias externas, mod_deflate |
| **Auditoría** | 50+ eventos auditados con contexto enriquecido: IP, User-Agent, sesión, datos de dispositivo, rendimiento |

---

## Arquitectura AI-First

### El código se explica a sí mismo

Cada capa del framework tiene documentación diseñada para que una IA la consuma:

| Quién lo lee | Qué ve | Dónde |
|---|---|---|
| **OpenCode** | Patrones prácticos: módulos, rutas, ORM, validación | `.opencode/skills/lite-framework/` |
| **OpenClaw** | 8 skills que describen cada capa del framework | `.agents/skills/` |
| **Context7 MCP** | Documentación de PHP, librerías y patrones vía context7 | `~/.config/opencode/opencode.json` |
| **gh_grep MCP** | Ejemplos de código real en GitHub | `~/.config/opencode/opencode.json` |

### Flujo de trabajo con IA

```
TAREA RECIBIDA
  │
  ▼
OpenCode analiza con skill arquitectura ──────────────────────┐
  │                                                            │
  ▼                                                            │
OpenCode escribe código (modelo, ruta, controlador, vista)     │
  │                                                            │
  ▼                                                            │
OpenClaw ejecuta tests, PHPStan, browser testing              │
  │                                                            │
  ▼                                                            │
Feedback → iteración ←────────────────────────────────────────┘
```

### Pipeline de verificación (OpenCode commands)

```bash
opencode run validate      # PHPStan + PHPCS + PHPUnit — todo en 1 comando
opencode run test          # Solo tests
opencode run stan          # Solo PHPStan level 7
opencode run lint          # Solo PSR-12
```

---

## Arquitectura del servidor

```
index.php
  ├── autoload.php (PSR-4 + class_alias)
  ├── GestorEntorno (.env → constantes)
  ├── ReporteroSentry (inicialización Sentry nativa)
  ├── ManejadorErrores (error/exception/fatal handler → ReporteroSentry::capturar)
  ├── rutas/web.php
  │     └── Enrutador::despachar(GET|POST|PUT|PATCH|DELETE, /ruta)
  │           ├── Interceptor (autenticación, permisos)
  │           └── Controlador → Modelo → Vista/JSON
  ├── src/sse.php (SSE endpoint con Last-Event-Id)
  └── src/error.php (códigos 400|401|403|404|500|503)

SSE Daemon (servidor/consola/sse_daemon.php):
  servidor/seguridad/SseGestor.php
    ├── iniciarDaemon() — auto-start via exec() si no corre
    ├── conectar(ultimoId) — timeout 30s, acepta Last-Event-Id
    ├── conectarModoArchivo() — polling 1s, caché de posición con fseek
    ├── conectarModoDB() — polling 500ms, cleanup cada 5 ciclos
    └── leerEventosDelArchivo(ultimoId, &posArchivo) — solo datos nuevos
```

### Namespaces

| Namespace | Directorio |
|-----------|-----------|
| `LiteFramework\Nucleo` | `servidor/nucleo/` |
| `LiteFramework\Seguridad` | `servidor/seguridad/` |
| `LiteFramework\Modelos` | `servidor/modelos/` |
| `LiteFramework\Controladores` | `servidor/controladores/` |
| `LiteFramework\Api\Controladores` | `servidor/api/controladores/` |
| `LiteFramework\Servicios` | `servidor/servicios/` |
| `LiteFramework\Middleware` | `servidor/middleware/` |
| `LiteFramework\Config` | `servidor/config/` |

---

## Requisitos

- PHP 8.2+ (extensiones: `pdo`, `pdo_mysql`, `pdo_sqlite`, `json`, `mbstring`, `fileinfo`, `exec()` para daemon SSE)
- Apache 2.4+ con `mod_rewrite`, `mod_headers`, `mod_deflate` + `mod_filter`
- MySQL 5.7+ / MariaDB 10.3+ (SQLite incluido como fallback)
- XAMPP recomendado en Windows; Apache nativo en Linux
- OpenCode + OpenClaw (opcional, para desarrollo asistido por IA)

## Instalación

```bash
# 1. Clonar
git clone https://github.com/amperetechcr/liteFramework.git
cd liteFramework

# 2. Configurar entorno
cp .env.example .env
# Editar DB_NOMBRE, DB_USUARIO, DB_CLAVE y OAUTH_REDIRECT_BASE en .env

# 3. Crear BD
mysql -u root -e "CREATE DATABASE lite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Ejecutar migraciones
php servidor/migrar.php

# 5. Servir con Apache (XAMPP recomendado en Windows)
# El daemon SSE se auto-inicia al primer request SSE.

# 6. Abrir en navegador
# https://localhost/liteFramework/
```

> Sin Composer, sin npm, sin `vendor/`. El autoloader es manual con soporte PSR-4.
> Si deseas usar Composer para el autoloading optimizado: `composer dump-autoload -o`

---

## Ejemplo rápido

```php
// rutas/web.php
$enrutador->get('/saludo/{nombre}', function(array $params) {
    echo "Hola, " . h($params['nombre']);
})->nombre('saludo');

// Generar URL
$url = Enrutador::url('saludo', ['nombre' => 'Mundo']);
// → /saludo/Mundo
```

```php
// modelo personalizado (generado automáticamente por GeneradorModulo)
class Producto extends Modelo {
    protected static string $tabla = 'producto';
    protected static array $rellenable = ['nombre', 'precio', 'stock'];

    public function categoria(): callable {
        return $this->perteneceA(Categoria::class, 'categoria_id');
    }
}

// Uso
$productos = Producto::donde('precio', '>', 100)->ordenarPor('nombre')->obtener();
$producto = Producto::buscar(42);
$producto->precio = 99.99;
$producto->guardar();
```

---

## Variables de entorno (`.env`)

| Variable | Default | Descripción |
|----------|---------|-------------|
| `APP_ENTORNO` | desarrollo | `desarrollo` o `produccion` |
| `APP_DEPURACION` | true | Mostrar trazas en errores |
| `APP_MAX_INTENTOS_ACCESO` | 5 | Intentos de login antes de bloqueo |
| `APP_BLOQUEO_MINUTOS` | 15 | Duración del bloqueo |
| `DB_ANFITRION` | localhost | Host MySQL |
| `DB_NOMBRE` | lite | Nombre BD |
| `DB_USUARIO` | root | Usuario MySQL |
| `DB_CLAVE` | (vacío) | Contraseña MySQL |
| `SESSION_INACTIVIDAD_MAXIMA` | 1800 | Timeout de inactividad (segundos) |
| `SESSION_TIEMPO_MAXIMO` | 28800 | Duración máxima de sesión |
| `SENTRY_DSN` | (vacío) | DSN de Sentry para reporte de errores (ej: `https://key@host/project`) |
| `APP_RELEASE` | (vacío) | Versión del release para tracking en Sentry (ej: `1.4.0`) |

---

## Seguridad

- **CSRF**: tokens de 64 hex, rotación automática por petición, gracia de 60s para peticiones concurrentes
- **Sesiones**: HttpOnly + SameSite=Lax + fingerprint SHA-256(subred + User-Agent) — Lax para compatibilidad con túneles (ngrok, cloudflared)
- **Contraseñas**: `password_hash(PASSWORD_DEFAULT)` con política de 8+ chars, mayúscula, número, símbolo
- **SQL**: 100% prepared statements, identificadores sanitizados contra whitelist
- **Headers**: CSP, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, HSTS, Referrer-Policy, Permissions-Policy
- **Auditoría**: dual (BD `bitacora_sistema` + `storage/logs/trazabilidad.log`) con Trace ID único por petición
- **WAF**: bloqueo automático de herramientas maliciosas (curl, python, wget, sqlmap, nmap, burp suite) — `scan` excluido para no bloquear navegadores legítimos
- **Rate limiting**: 5 intentos de login por IP/correo en ventana de 15 minutos (persistido en BD)
- **Excepciones**: jerarquía propia (`ErrorSeguridad`, `ErrorAutenticacion`, `ErrorValidacion`) con códigos HTTP

---

## SSE en Tiempo Real

`sse.php` expone eventos Server-Sent Events con daemon persistente:

| Componente | Característica |
|---|---|
| `SseGestor::iniciarDaemon()` | Auto-start via `exec()` del daemon PHP si no está corriendo |
| `SseGestor::conectar($ultimoId)` | Timeout 30s, acepta `HTTP_LAST_EVENT_ID` para reanudar |
| `conectarModoArchivo()` | Polling 1s, caché de posición con `fseek`, filtra por ID |
| `conectarModoDB()` | Polling 500ms, cleanup cada 5 ciclos |
| `leerEventosDelArchivo()` | `fseek($posArchivo)` — solo lee datos nuevos |
| Sesión | `session_write_close()` antes del loop para no bloquear |

```bash
# El daemon arranca automáticamente. Para verificar:
php servidor/consola/sse_daemon.php status
```

---

## Sentry — Zero-dependency monitoring

**Sin SDK, sin Composer, sin npm.** `ReporteroSentry` envía errores directo a la API Store de Sentry usando `file_get_contents` + `stream_context_create`. Una IA puede leer las ~170 líneas de `ReporteroSentry.php` y entender exactamente cómo funciona.

### Cómo funciona

1. `ReporteroSentry::iniciar(SENTRY_DSN)` se ejecuta en `index.php` tras cargar entorno
2. `ManejadorErrores::loggear()` llama a `ReporteroSentry::capturar()` automáticamente
3. Cubre: errores PHP (`E_USER_WARNING`), excepciones no capturadas y fatales (`E_ERROR`)
4. Cada evento incluye: stack trace completo, URL, método HTTP, cabeceras, entorno, sesión, release

### Configuración

```bash
# .env
SENTRY_DSN=https://clave_publica@oXXXXX.ingest.us.sentry.io/XXXXX
APP_RELEASE=1.4.0
```

### Clase

| Método | Descripción |
|--------|-------------|
| `iniciar(string $dsn)` | Inicializa con DSN de Sentry |
| `capturar(\Throwable $e, array $contexto)` | Envía error a Sentry (llamado automáticamente por ManejadorErrores) |
| `estaActivo(): bool` | Verifica si Sentry está configurado |

> Sin librerías externas, sin Composer, sin npm. La comunicación es HTTP directa a la API de Sentry. Timeout de 3s para no afectar la respuesta del servidor.

---

## Desarrollo con OpenCode + OpenClaw

El proyecto está configurado para desarrollo asistido con **OpenCode** (codificación) y **OpenClaw** (testing/automatización), compartiendo los mismos MCP servers.

### MCP Servers disponibles

| MCP | Transporte | OpenCode | OpenClaw |
|-----|-----------|----------|----------|
| `context7` | remote (streamable-http) | global ✅ | registrado ✅ |
| `gh_grep` | remote (streamable-http) | global ✅ | registrado ✅ |
| `git` | local (WSL + uvx) | global ✅ | registrado ✅ |
| `time` | local (WSL + uvx) | global ✅ | registrado ✅ |
| `fetch` | local (WSL + uvx) | global ✅ | registrado ✅ |
| `sentry` | local (WSL + sentry-mcp) | global ✅ | registrado ✅ |

### Comandos rápidos (OpenCode)

```bash
opencode run validate      # PHPStan + PHPCS + PHPUnit completo
opencode run test          # Ejecutar todos los tests
opencode run lint          # Validar PSR-12
opencode run stan          # PHPStan level 7
opencode run migrar        # Ejecutar migraciones
opencode run benchmark     # Benchmarks de rendimiento
```

### Skills OpenClaw

| Skill | Propósito |
|-------|-----------|
| `arquitectura` | Estructura completa del framework |
| `convenciones` | Naming, tipos, PHP, JS, CSS, DB |
| `ui-testing` | Pruebas de UI con browser tool |
| `api-testing` | Pruebas de API REST |
| `performance-testing` | Benchmark y perfil de memoria |
| `accessibility` | Auditoria WCAG 2.2 |
| `frontend-design` | Guía de diseño frontend |
| `seo` | Optimización SEO |

Ver `AGENTS.md` para el flujo de trabajo completo.

---

## Rendimiento y Servidor

| Aspecto | Detalle |
|---|---|
| **Servidor** | Apache 2.4 (XAMPP) multi-thread, reemplaza `php -S` single-thread |
| **Compresión** | gzip via `mod_deflate` + `mod_filter` — CSS 8.7KB→2.5KB (~71%) |
| **SSE Daemon** | Proceso PHP persistente, auto-arranque, consumo mínimo |
| **Base de Datos** | MySQL nativo, SSE prioriza archivo sobre DB polling |
| **Túneles** | Compatible ngrok (header `ngrok-skip-browser-warning`) y cloudflared |

---

## API

**POST /api** con `Content-Type: application/json`

```json
{
  "token_peticion": "csrf-token",
  "accion_crud": "iniciar_sesion",
  "correo": "admin@example.com",
  "clave": "MiClave123!"
}
```

**Respuesta uniforme:**

```json
{
  "estado_operacion": true,
  "mensaje_error": null,
  "codigo_error": null,
  "nuevo_token": "nuevo-csrf-token",
  "datos": { ... }
}
```

Ver [`src/docs/API.md`](src/docs/API.md) para documentación completa de endpoints.

---

## Testing

```bash
# Ejecutar todos los tests
php tests/phpunit.phar -c tests/phpunit.xml

# O via el CLI del framework
php servidor/consola/ejecutar_pruebas.php

# Tests HTTP externos (httpbin.org)
$env:TESTS_EXTERNAS_HTTP='true'; php tests/phpunit.phar -c tests/phpunit.xml --filter AyudanteHttp

# Resultado: 458 tests, 1084 aserciones, SQLite in-memory
```

El test suite usa SQLite in-memory (vía `TESTS_RUNNING`), sin necesidad de MySQL.
Reutiliza el autoloader, los helpers, y `ConexionBaseDatos::resetearInstancia()` del propio framework.

> 11 tests HTTP externos se saltan por defecto. Actívalos con `TESTS_EXTERNAS_HTTP=true`.

---

## Capturas

*(Agrega aquí capturas del panel admin, login, generador de módulos, etc.)*

---

## Comandos útiles

```bash
php servidor/migrar.php                       # Ejecutar migraciones
php servidor/migrar.php list                  # Listar migraciones pendientes
php servidor/consola/generar_modulo.php       # Generar módulo CRUD vía CLI
php servidor/consola/crear_proyecto.php       # Generar proyecto completo vía CLI
php servidor/consola/ejecutar_pruebas.php     # Ejecutar tests
php tests/phpunit.phar -c tests/phpunit.xml   # Ejecutar tests (directo)
php servidor/consola/sse_daemon.php status    # Verificar estado del daemon SSE

# PHPStan
php phpstan.phar analyse

# PHPCS
php phpcs.phar --standard=phpcs.xml.dist

# Validación completa
php phpstan.phar analyse && php phpcs.phar --standard=phpcs.xml.dist && php tests/phpunit.phar -c tests/phpunit.xml
```

---

## Licencia

Apache 2.0 — ver [LICENSE.md](LICENSE.md).

---

> **Hecho por humanos. Potenciado por IA. Construido desde cero.**
