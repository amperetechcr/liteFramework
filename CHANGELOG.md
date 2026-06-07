# Changelog

Basado en [Keep a Changelog](https://keepachangelog.com/).

## [1.3.0] - 2026-06-07

**Agregado:** SSE optimizado con daemon auto-start (`SseGestor::iniciarDaemon()`, `exec()`) · soporte `Last-Event-Id` (`HTTP_LAST_EVENT_ID`) · caché de posición en archivo (`$posArchivo`) para leer solo datos nuevos con `fseek` · landing page profesional (`inicio_sesion.php`) con hero, features grid, stats cards, OAuth SVGs, login/register integrado · ruta POST `/configuracion` para fallback sin JS · compresión gzip via `mod_deflate` + `mod_filter` (CSS: 8.7KB→2.5KB, ~71%) · `ngrok-skip-browser-warning` header en wrapper fetch · header `ngrok-skip-browser-warning` en `utilidades.js:37` · compressible types en `httpd.conf`

**Cambiado:** Migración a Apache (XAMPP) — de `php -S` single-thread a multi-threaded en puerto 80 · SameSite cookie `Strict`→`Lax` en `GestorSesiones.php:35` para compatibilidad con túneles · SSE timeout 120→30s, polling DB 2s→500ms, polling archivo 3s→1s · `session_write_close()` antes del loop SSE · módulo Inicio removido del panel y sidebar

**Corregido:** WAF falso positivo — `scan` removido de `$botsNegros` en `GestorSesiones.php:127` (bloqueaba navegadores legítimos) · Route config POST `/configuracion` añadida con validación CSRF y redirect · JS syntax errors en 8 archivos (`?.` con espacios, regex con espacios, `return {` faltante, `}` faltante en `if`) · CSS variables: `--fondo-principal`→`--fondo-pantalla`, `--fondo-secundario`→`--fondo-alterno`, `--color-borde`→`--trazo-suave`

**Optimizado:** SSE daemon auto-start verificado (PID activo) · compresión gzip con `mod_deflate` + `mod_filter` · sin caché agresiva en desarrollo (solo gzip, sin `max-age` ni version query strings) · MySQL a carga mínima (SSE usa archivo como fuente principal)

**Testing:** 377 tests, 926 aserciones · PHPStan level 7 limpio (0 ignores excepto `missingType.iterableValue`) · PHPCS 0 errores (0 restantes) · CI pasa con `ignore_warnings_on_exit 1`

## [1.2.0] - 2026-06-06

**Agregado:** `declare(strict_types=1)` en 60 archivos · namespaces PSR-4 `LiteFramework\*` en 55 archivos · `composer.json` con autoload PSR-4 · excepciones personalizadas (`ErrorSeguridad`, `ErrorAutenticacion`, `ErrorValidacion`) · `DialectoBaseDatos` (MySQL/SQLite cross-compat) · `GeneradorProyecto` + CLI + API + wizard web · 10 templates de proyecto (`plantillas/proyecto/`) · módulo `generadorProyecto` en panel admin · `CODE_OF_CONDUCT.md`

**Cambiado:** `exit()` reemplazado por `throw` en seguridad (GestorSesiones, ControlAccesoRBAC, SeguridadServidor) · autoloader con PSR-4 + class_alias() para retrocompatibilidad · `index.php` usa `$configUI[...]` sin listas hardcodeadas · `apariencia.php` botones generados desde `ui.php` · `lite.js` sin 11 listas hardcodeadas (regex-based) · `restablecerPersonalizacion()` resetea textura · server whitelist ampliada (teal, fucsia, 3d-moderno, etc.) · `estilos.css` keyframe 3d sin transform (fix modal position:fixed) · README profesional con badges, arquitectura, ejemplos

**Mejorado:** Contexto de auditoría enriquecido con `session_id`, `http_referer`, `duracion_ms`, datos del cliente (screen, conexion, memoria, CPU, timezone) · auditoría de descarga de archivos, cambio de contraseña, payload inválido · `h()` en vez de `htmlspecialchars()` en auditoría · clases CSS adaptativas `.touch-device`, `.slow-conexion`, `.offline` · banner offline + cola de reintentos · loading adaptativo según conexión en SPA · timezone en fechas vía `AyudanteFecha::formatear()`

**Testing:** Nuevo suite completo — 311 tests, 757 aserciones. Tests para: helpers (114), lógica pura (37), integración DB (18), CSRF+RBAC (13), sesiones (7), GeneradorModulo (4), Enrutador (13), errores+archivos (9), apariencia+consistencia (12), autoloader (5), auditoría (15), datos cliente (6). PHPUnit phar standalone (sin Composer).

## [1.1.0] - 2026-06-02

**Agregado:** `utilidades.js` (ES module + window globals) · `ListaFiltrable.js` (reusable list+filters+pagination) · `modales.css` + `subirArchivos.css` (extraídos de componentes.css) · `h()` helper en `index.php` · navegación por carpetas en explorador de archivos · estado vacío en listados

**Cambiado:** `configuracion/` refactorizado en 4 partials · `subir_archivos/` → `subirArchivos/` (camelCase) · `operadores.js` 277→175 líneas · `auditoria.js` 150→31 líneas (ambos con ListaFiltrable) · todos los módulos JS usan `window.obtenerTokenCSRF()` con fallback · componentes.css 1807→862 líneas

**Eliminado:** Paginación de subirArchivos (reemplazada por carpetas) · árbol jerárquico con `<details>` · funciones de árbol obsoletas

## [1.0.3] - 2026-06-02

**Agregado:** Vista de explorador en árbol jerárquico · `SubidaArchivos::guardar($dir, $preservarNombre)` · filtro de archivos huérfanos con `file_exists()` · estado vacío en explorador

**Corregido:** JS `agregarTarjetaAlListado` inserta en árbol por `data-ruta` · JS `renderizarTarjetaArchivo` alineado con PHP · `actualizarContador` detecta vacío con `.nodo-archivo`

## [1.0.2] - 2026-06-02

**Corregido:** Error 500 en archivos (ORM chain → PDO directo) · `deltaB` undefined en subida JS · `Archivo::aArreglo()` faltaban campos · validación ID en eliminar · paginación ineficiente con array_slice

**Seguridad:** CSRF validado en `eliminar()` · RBAC verificado en todos los métodos

## [1.0.1] - 2026-05-31

**Corregido:** Auditoría no cargaba vía SPA · filtros de auditoría rotos tras SPA · URLs sin basePath (404) · personalización UI no respondía tras SPA · `window.ConfiguracionGestor` sobrescrito

**Mejorado:** `configuracion.js` auto-inicializable · eliminado script inline de polling · página de inicio rediseñada

## [1.0.0] - 2026-01-01

**Agregado:** MVC completo · ORM Active Record · autenticación · RBAC · CSRF con rotación · rate limiting · fingerprint SHA-256 · auditoría dual (BD + archivo) · X-Trace-Id · personalización UI · migraciones · API REST · CRUD genérico · Validador · SubidaArchivos · interceptors

**Seguridad:** Sanitización completa · headers HTTP seguros · password_hash() · bloqueo por intentos · huella de cliente

**Vistas:** Login/registro · panel con estadísticas · gestión operadores · auditoría/bitácora · configuración perfil + UI
