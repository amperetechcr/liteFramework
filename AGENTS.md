# liteFramework — Contexto completo para IA

**Filosofía:** Framework PHP 8.2+ zero-dependency diseñado para que la IA escriba todo el código.
El humano solo da instrucciones en lenguaje natural. La IA conoce el 100% del código base.
No hay vendor/, no hay dependencias externas, no hay cajas negras.

**Regla #1 — Revertir si falla:** Todo cambio debe verificarse contra la bitácora del sistema (`bitacora_sistema`) antes de darlo por bueno. Si aparece cualquier entrada nueva de nivel ERROR, SEGURIDAD, o ADVERTENCIA no existente antes del cambio, o si el usuario declara el módulo como roto, el cambio debe revertirse inmediatamente. No acumular cambios sobre código roto. La regla aplica también ante error 500, test fallido, o cualquier notificación inesperada en la interfaz.

**Regla #2 — Calidad absoluta:** Todo código debe pasar PHPStan level 7 (0 errores), PHPCS PSR-12 (0 errores), y PHPUnit (0 failures, 0 skipped). No se aceptan baselines ni skips.

## Stack
- PHP 8.2+ con declare(strict_types=1). Sin Composer, sin npm.
- MySQL con fallback automático a SQLite in-memory
- MVC con ORM Active Record propio (Modelo.php)
- SPA con JS vanilla (ES modules, sin frameworks)
- CSS nativo con 13 paletas, 19 estilos visuales
- RBAC, CSRF con rotación + gracia 60s, rate limiting, WAF, auditoría dual (BD + archivo)
- SSE en tiempo real con daemon auto-start
- Sentry nativo (ReporteroSentry ~180 líneas, sin SDK)
- PHPUnit 11, PHPStan level 7, PHPCS PSR-12

## Flujo de petición (Arquitectura MVC)

```
index.php → autoload → .env → Sentry → ManejadorErrores → Config UI → SeguridadServidor → Enrutador → Interceptors → Controlador → Vista/JSON
```

### Sistema de Rutas (`rutas/web.php`)
API fluida con Enrutador + RutaBuilder:
```php
$enrutador->get('/ruta/{id}', [Clase::class, 'metodo'])
    ->interceptor(AutenticacionInterceptor::class)
    ->nombre('alias');
```
- Parámetros `{id}` se pasan como argumentos tipados al closure/controlador
- Cadena automática: RendimientoInterceptor → tus interceptors → acción
- URLs por nombre: `Enrutador::url('alias', ['id' => 1])`
- Grupo con prefijo e interceptores hereditarios: `$enrutador->grupo(['prefijo'=>'admin', 'interceptor'=>X::class], fn($r) => ...)`

### Middleware (Interceptor interface)
- **RendimientoInterceptor** — auto-inyectado en rutas protegidas, mide tiempo+memoria, headers X-Lite-*
- **AutenticacionInterceptor** — sesión + operador_id + huella → redirect si falla
- **ApiAuthInterceptor** — igual pero respuesta JSON 401
- **MantenimientoInterceptor** — 503 si MODO_MANTENIMIENTO=true, admins pasan

### Controladores
- **ControladorBase** — `verificarAutenticacion()`, `obtenerIdOperador()`, `obtenerPermisos()`
- **ModuloControlador** — `indice(modulo, vista?)` carga src/modulos/{mod}/{mod}.php
- **AutenticacionControlador** — mostrarInicioSesion(), cerrarSesion()
- **SubirArchivosControlador** — subir/eliminar/listar/descargar con CSRF + RBAC

### Vistas / Módulos (`src/modulos/{nombre}/{nombre}.php`)
Patrón dual SPA + full page:
```php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php'; // define $esAjax, $idOperador
if ($esAjax) echo '<div data-titulo-pagina="..."></div>';
if (!$esAjax) { $tituloPagina = '...'; $moduloActivo = '...'; require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php'; }
// HTML del módulo
<script src="<?= URL_BASE ?>/src/js/modulos/{nombre}.js"></script>
if (!$esAjax) require DIRECTORIO_RAIZ . '/src/plantillas/pie.php';
```
Los partials AJAX usan `?partial=lista` para actualizar solo la lista sin recargar.

## ORM Active Record (`servidor/nucleo/Modelo.php`, 750 líneas)
Extender `Modelo` con propiedades estáticas:
- `$tabla`, `$idColumna`, `$rellenable`, `$tipos` (`int/float/bool/json`), `$timestamps`
- CRUD: `buscar()`, `crear()`, `guardar()` (INSERT/UPDATE automático), `eliminar()`
- Query builder: `donde()`, `oDonde()`, `dondeEn()`, `dondeNulo()`, `ordenarPor()`, `limite()`, `saltar()`, `seleccionar()` — todo termina con `->obtener()` o `->primero()`
- Agregaciones: `contar()`, `sumar()`, `promediar()`, `minimo()`, `maximo()`
- Paginación: **NUNCA usar ORM chain con limite()+saltar()** → usar PDO directo + Paginador (ver PAGINACION_PDO.md)
- Relaciones: `perteneceA(Clase, fk, pk)`, `tieneMuchos(Clase, fk, pk)`, eager: `con('operador')`
- Eventos: `creating()`, `created()`, `updating()`, `updated()`, `deleting()`, `deleted()`
- Magic getter/setter castea según `$tipos`
- `llenar(array)` filtra por `$rellenable`

### Validación (`servidor/nucleo/Validador.php`)
```php
$v = new Validador($_POST, ['correo' => 'requerido|correo|unico:operador,correo_electronico']);
if ($v->falla()) { $v->errores(); }
```
Reglas: requerido, correo, minimo:N, maximo:N, unico:tabla,columna[,excluirId], regex, confirmado, numero, entero, archivo, imagen, max_tamano:N, diferente:campo, en:val1,val2,...

## Seguridad (`servidor/seguridad/`)

| Archivo | Función |
|---|---|
| GestorSesiones | Cabeceras HTTP seguras, huella (subred IP+User-Agent), anti-secuestro, expiración, filtro bots |
| ValidadorCSRF | Token bin2hex(random_bytes(32)), rotación post-uso, gracia 60s al token anterior |
| SeguridadServidor | Facade que delega a todos los módulos |
| ControlAccesoRBAC | Matriz de permisos en sesión, modo IA (worker/manager con 10 permisos bloqueados a worker) |
| RegistroAuditoria | 5 niveles (INFO/ADVERTENCIA/ERROR/SEGURIDAD/AUDITORIA), BD + archivo, SSE en errores, exportación JSON/CSV |
| LimitadorPeticiones | Rate limiting por BD con ventanas fijas, MySQL/SQLite |
| SanitizadorEntrada | htmlspecialchars, bloqueo javascript:/data:/on*, strip_tags (modo plano), password_hash |
| TrazadorPeticiones | Trace ID único por request, header X-Trace-Id, alerta si >3s |
| PoliticaContrasena | 8+ chars, 1 mayúscula, 1 número, 1 símbolo (@$!%*?&) |
| SseGestor | SSE real-time, dual modo archivo (daemon) o DB, heartbeat 50ms |

Llamar siempre `SeguridadServidor::iniciarSesionEstricta()` antes de usar sesión.
CSRF en cada POST: `SeguridadServidor::validarTokenAntiFalsificacion($token)`.

## API Layer

### Endpoint POST /api (`servidor/api/procesarPeticionPost.php`)
Router unificado por `accion_crud`: 16 acciones mapeadas (login, logout, registrar, migraciones, config, generar módulo/proyecto). Fallback a CRUD genérico (whitelist: operador, rbac_rol, bitacora_sistema, estadistica).
- Payload JSON o POST, CSRF obligatorio, token rotado en respuesta
- Respuesta: `{ estado_operacion, mensaje_error, codigo_error, nuevo_token, datos }`

### API Controladores REST (9 archivos)
- AutenticacionApiControlador — login con rate limiting (5 intentos/15min → 429), password_verify, huella
- OperadorApiControlador — registrar/actualizarPerfil con validación de política de clave
- MigracionApiControlador — ejecutar/resetear/respaldo con lock exclusivo y backup automático
- ConfiguracionApiControlador — actualizar límites + regenerar .user.ini/.htaccess
- CrudApiControlador — SQL dinámico vía DESCRIBE + whitelist + RBAC
- CrewaiApiControlador — endpoint SSE para agentes CrewAI
- PersonalizacionApiControlador — guardar/obtener preferencias UI por operador
- GeneradorModuloApiControlador/GeneradorProyectoApiControlador — scaffolders

## Servicios Clave

- **GeneradorPdf** — HTML+CSS → print CSS, panel de personalización JS, plantillas desde BD con placeholders {{var}}
- **GeneradorEstadisticas** — 4 tipos: tarjetas, barras, pastel (conic-gradient), kpi. Cache archivo + TTL
- **GeneradorModulo** — Scaffolder 7 archivos: migración SQL + modelo + controlador API + vista + JS + rutas + autoload
- **GeneradorProyecto** — Scaffolder de proyectos desde JSON: copia árbol (excluye .git, node_modules, md), renderiza templates con placeholders, genera entidades, migración semilla con admin
- **AdministradorArchivos** — Upload con cuotas, 8 categorías MIME (~70 tipos), ZIP con PharData, modo repositorio
- **AutenticacionOAuth** — Google + GitHub, state CSRF, auto-registro, vinculación
- **Correo** — SMTP nativo (fsockopen + TLS), AUTH LOGIN, MIME multipart con adjuntos, sin dependencias
- **Diagnóstico de errores**: ContextoError → 4 Verificadores (BD, Archivos, Seguridad, Sistema) → sugerencias + remedios automáticos (crear directorio, regenerar CSRF)
- **ReporteroSentry** — Cliente Sentry nativo sin SDK, protocolo v7, timeout 3s

## Helpers (12)

| Helper | Alias corto | Métodos clave |
|---|---|---|
| `AyudanteArchivo` | `ArchivoH` | tamanoLegible, esImagen, categoriaMime, sanitizarNombre, esNombreSeguro |
| `AyudanteOperador` | `OperadorH` | idActual, nombreActual, rolActual, tienePermiso, permisoRequerido |
| `AyudanteSeguridad` | `Seguridad` | sesionActiva, autenticacionRequerida, tokenCSRF, csrfMeta, validarCSRF, tienePermiso |
| `AyudanteFecha` | `Fecha` | ahora, formatear, relativo, diferencia, sumarDias, esHoy, esPasado |
| `AyudanteCadena` | `Cadena` | limitar, slug, aleatorio, enmascarar, normalizar, esEmail, capitalizar |
| `AyudanteArreglo` | `Arreglo` | pluck, agrupar, ordenar, aplanar, primero, ultimo, sumar, promedio |
| `AyudanteGeneral` | `General` | generarToken, moneda, bytesLegibles, dd, aBooleano, desdeJson, unaVez |
| `AyudanteHttp` | `HttpCliente` | obtener, post, postJson, enviar, paralelo, codigoComoTexto |
| `AyudanteCache` | `Cache` | recordar, recordarJson, obtener, guardar, olvidar, limpiar, info |
| `AyudanteRendimiento` | `Rendimiento` | iniciar, detener, medir, comparar, reporte, loggear, cabeceras |
| `AyudanteMonitor` | — | obtenerEstadisticas, obtenerUltimos, logPath |

## Frontend JS (ES Modules, sin frameworks)

Entry: `src/js/principal.js`
- **lite.js** — tema oscuro/claro (localStorage + prefers-color-scheme), personalización UI (13 paletas, 19 estilos, 16 fondos, 6 fuentes, 5 espaciados), datos de cliente enviados en payload
- **navegacion.js** — SPA con History API, captura clics, fetch + reemplazo, recarga scripts
- **notificaciones.js** — NotificadorHubble: toasts con cola, swipe-to-dismiss, máx 5 visibles
- **confirmaciones.js** — ConfirmadorHubble: modal Promise, Escape cierra
- **utilidades.js** — CSRF token, fetch override con cola offline
- **manejoErrores.js** — unhandledrejection global + interceptor HTTP con traducción español
- **recuperacionError.js** — regenerar_token, redirigir_login, mostrar sugerencias del backend
- **graficos.js** — Canvas barras/pastel nativo, DPR, responsive
- 15 módulos JS en src/js/modulos/ — controller de cada módulo panel

## CSS (20 archivos, design system nativo)

- **tema.css** — ~70 CSS custom properties (colores, espaciado 7 niveles, tamaños 12 escalas, sombras 3 niveles), dark mode, reset, tipografía
- **paletas.css** — 13 paletas con light/dark/forced: indigo, azul, esmeralda, rosa, ámbar, violeta, pizarra, cereza, cielo, teal, lima, naranja, fucsia
- **estilos.css** — 19 estilos visuales (moderno, glass, neo, neon, cyber, brutal, vapor, cosmic, organic, material, liquid, pixel, mesh, clay, academia, minimal, 3d-moderno, jugueton, corporativo)
- **maquetacion.css** — Layout: sidebar fijo + contenido, grid, flex, responsive
- **componentes.css** — 741 líneas: 8 variantes botón, formularios, tablas, modales, notificaciones, paginación, badges, tooltips
- **utilidades.css** — Clases helper texto/espaciado/visibilidad/responsive

## Migraciones (`servidor/migraciones/`)
Tabla `_migraciones` rastrea archivo + hash SHA-256. Ejecuta en transacciones. Backup automático pre-ejecución (SHOW CREATE TABLE + SELECT * → SQL). Restauración con DROP + INSERT + FOREIGN_KEY_CHECKS=0.
- 001: 14 tablas + 4 roles + 22 permisos + 8 configs
- 007: rate_limit, 008: oauth_vinculo

## Base de datos
MySQL primario (`DB_ANFITRION`, `DB_NOMBRE`, `DB_USUARIO`, `DB_CLAVE` en .env). Fallback SQLite in-memory si MySQL falla. SQLite en test. 14 tablas. `DialectoBaseDatos` abstrae diferencias (NOW() vs datetime('now'), etc.).

## CLI (`consola` en raíz)
Script con modo dual texto/JSON y modo IA (--ai --token=...):
18 comandos: migrar, modulo:generar, proyecto:crear, pruebas, auditoria:* (exportar, limpiar, resumen), lock:* (adquirir, liberar, estado, limpiar), operador:* (list, crear, estado), crud, diagnostico:*, sse:enviar, correo:test, mantenimiento:*, inicio
Flags: --json, --ai, --token=HASH

## Excepciones
ErrorSeguridad (403), ErrorAutenticacion (401), ErrorValidacion (422), ErrorHttp (500), ErrorRed (0)

## Conexión MCP y lite_call
- `lite_call` en los agents CrewAI funciona por **import directo de lite_mcp.py** (mismo proceso Python), NO necesita HTTP. La advertencia `lite_mcp_http (puerto 5003) no respondio` es un falso positivo: el health check de socket/sesión falla pero `lite_call` opera igual.
- `lite_mcp_http.py` (puerto 5003) es solo para clientes MCP externos vía StreamableHTTP. Los agents CrewAI nunca lo usan.
- Si matas procesos `lite_mcp.py` o `mcp_server.py`, debes reiniciar opencode para que el cliente MCP los respawnee desde cero.

## Patrones clave
- Toda ruta protegida pasa por RendimientoInterceptor → AutenticInterceptor/ApiAuthInterceptor
- CSRF en cada POST, token rotado post-validación con 60s de gracia para el anterior
- Auditoría (RegistroAuditoria::info/advertencia/error/seguridad/auditoria) en toda operación de escritura
- SSR dual: carga completa HTML y parcial AJAX (?partial=lista)
- Auto-diagnóstico en errores → sugerencias + remedios automáticos (crear tmp_dir, regenerar CSRF, redirigir login)
- Zero dependencias: Sentry, SMTP, Logger son implementaciones propias en ~180, ~335, ~123 líneas respectivamente
- Lock de archivos para operaciones críticas (migraciones, SSE daemon)
- SSE con daemon auto-start para eventos en tiempo real
