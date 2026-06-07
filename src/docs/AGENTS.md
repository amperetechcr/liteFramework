# liteFramework — Guía para IA

> Patrones prácticos → `.opencode/skills/lite-framework/instructions.md` | Endpoints → `API.md` | Frontend → `DISENO_FRONTEND.md`

## Stack + Filosofía

PHP 8.1+ **sin Composer** (autoloader manual + PSR-4 opcional). MySQL con fallback SQLite in-memory. Sin npm, JS vanilla. **Todo en español + camelCase**.

**Namespaces:** `LiteFramework\*` con PSR-4 (`composer.json`). `declare(strict_types=1)` en todos los archivos. Excepciones personalizadas: `ErrorSeguridad` (403), `ErrorAutenticacion` (401), `ErrorValidacion` (422).

## Arquitectura (6 capas)

| Capa | Ubicación | Propósito |
|------|-----------|-----------|
| **Enrutamiento** | `rutas/web.php` + `nucleo/Enrutador.php` | GET/POST/PUT/PATCH/DELETE con interceptors |
| **Controladores Web** | `controladores/` | Vistas PHP, extienden `ControladorBase` |
| **Controladores API** | `api/controladores/` | POST/JSON, **sin** herencia (clases independientes) |
| **ORM** | `nucleo/Modelo.php` | Active Record, PDO, MySQL/SQLite |
| **Seguridad** | `seguridad/` | RBAC, CSRF, sesión, WAF, auditoría, sanitización |
| **Servicios** | `servicios/` | GeneradorModulo, PDF, Estadísticas |
| **Frontend** | `src/modulos/`, `src/js/`, `src/css/` | Vistas + JS + CSS por módulo |

## Convenciones

| Elemento | Regla | Ejemplo |
|----------|-------|---------|
| Clases/Modelos | PascalCase español | `ControladorBase`, `Operador` |
| Aliases | PascalCase inglés | `Fecha`, `ArchivoH`, `Seguridad` |
| Métodos/Funciones | camelCase español | `obtenerInstancia()`, `tienePermiso()` |
| Variables/JS | camelCase español | `$correoElectronico`, `subirArchivos.js` |
| Tablas/Columnas BD | snake_case español | `operador`, `correo_electronico` |
| Constantes | UPPER_SNAKE | `DIRECTORIO_RAIZ`, `DB_ANFITRION` |
| Rutas URL | kebab-case | `/panel-control` |
| Nombres ruta | dot.notation | `api.operadores.listar` |
| Directorios | lower_snake_case | `servidor/nucleo/` |
| **Type hints** | **Siempre en parámetros y retorno** | **`function foo(int $id, string $name): array`** |

## Tipado Obligatorio

**TODO método/función debe tener type hints en parámetros y retorno.** Sin excepción.

| Ubicación | Parámetros | Retorno |
|-----------|------------|---------|
| Métodos públicos | `string`, `int`, `array`, `?type`, `mixed` | `: void`, `: bool`, `: array`, `: string`, `: static`, `: ?Modelo` |
| Métodos privados/protegidos | Igual que públicos | Igual que públicos |
| Métodos estáticos | Igual que instancia | Igual que instancia |
| Funciones globales | `?string $x = null` | `: string`, `: void`, `: mixed` |
| Métodos mágicos | `__get(string $clave): mixed`, `__set(string $clave, mixed $valor): void` | Según contrato PHP |
| Constructores | Tipos en parámetros | `: void` |

## Archivos Clave

`index.php` (entry + `DIRECTORIO_RAIZ` + `h()`) · `autoload.php` (PSR-4 + aliases) · `composer.json` (PSR-4 opcional) · `rutas/web.php` · `nucleo/Enrutador.php` · `nucleo/Modelo.php` · `nucleo/Validador.php` · `nucleo/Paginador.php` · `nucleo/SubidaArchivos.php` · `nucleo/ManejadorErrores.php` · `nucleo/DialectoBaseDatos.php` · `nucleo/Excepciones/*` · `nucleo/Helpers/*` · `api/procesarPeticionPost.php` · `api/controladores/*` · `config/conexion.php` · `config/entorno.php` · `config/ui.php` · `seguridad/*` · `servicios/*` · `consola/generar_modulo.php` · `consola/crear_proyecto.php` · `consola/ejecutar_pruebas.php` · `middleware/*`

## Flujo Petición

```
index.php → autoload → .env → ManejadorErrores → configUI → Seguridad::cabecerasSeguras()
→ rutas/web.php → Enrutador::despachar() → Interceptors → Closure/[Ctrl@metodo] → Vista/JSON
```

Las rutas API POST pasan por `procesarPeticionPost.php` que valida CSRF, parsea JSON, y delega al controlador según `accion_crud`.

## Enrutador

```php
$enrutador->get('/ruta/{id}', fn($id) => ...)->interceptor(MiInterceptor::class)->nombre('ruta');
$enrutador->post('/api', fn() => require 'procesarPeticionPost.php');
$enrutador->grupo(['prefijo'=>'admin','interceptor'=>AuthInterceptor::class], fn($r) => $r->get(...));
Enrutador::registrarInstancia($enrutador);
return $enrutador;
```
**Verbos:** `get`, `post`, `put`, `patch`, `delete`. **URL por nombre:** `Enrutador::url('ruta', ['id'=>5])`.

## Interceptors

```php
class MiInterceptor {
    public function manejar($params, $siguiente) { /* pre-procesar */ return $siguiente($params); }
}
```
**Existentes:** `AutenticacionInterceptor` (session + huella, redirect si no), `ApiAuthInterceptor` (JSON 401 si no).

## ORM (Active Record)

```php
// Estáticas
$p = Producto::buscar(1); Producto::todos(); Producto::crear([...]); Producto::contar(); Producto::existe('c', $v);
// Query Builder
Producto::donde('activo','=',1)->oDonde('stock','>',0)->ordenarPor('fecha','DESC')->limite(10)->saltar(0)->obtener();
Producto::donde('slug','=','x')->primero(); // uno o null
// Instancia
$p->nombre = 'X'; $p->guardar(); $p->eliminar(); $p->aArreglo(); $p->llenar($_POST);
// Relaciones
$categoria = $producto->perteneceA(Categoria::class, 'categoria_id', 'id_categoria');
$productos = $categoria->tieneMuchos(Producto::class, 'categoria_id', 'id_categoria');
// Tipos automáticos: 'int','float','bool','json' en static::$tipos
```

## Validación

```php
$v = new Validador($datos, ['nombre'=>'requerido|min:3|max:255', 'email'=>'requerido|email']);
if ($v->falla()) $errores = $v->errores(); $limpios = $v->datos();
```
**Reglas:** `requerido`, `correo`, `minimo:n`, `maximo:n`, `numero`, `entero`, `unico:tabla,col[,id_excluir]`, `regex:pat`, `confirmado`, `archivo`, `imagen`, `max_tamano:n`, `diferente:campo`, `en:val1,val2`

## Seguridad

| Componente | Cómo |
|------------|------|
| **CSRF** | `SeguridadServidor::generarTokenAntiFalsificacion()` / `validarTokenAntiFalsificacion($token)`. Token 64 hex, rotación automática, gracia 60s. Helper: `Seguridad::tokenCSRF()` |
| **Sesión** | `GestorSesiones::iniciarSesionEstricta()` — cookie `HttpOnly`+`SameSite=Strict`, regeneración post-login |
| **Fingerprint** | SHA-256(subred IP + User-Agent). Si cambia → sesión invalidada |
| **RBAC** | `ControlAccesoRBAC::cargarPermisosEnMemoria($con, $idRol)` → luego `tienePermiso('entidad.accion')` / `requerirPermisoEstricto('clave')` (403) |
| **WAF** | `GestorSesiones::filtrarAgentesMaliciosos()` — bloquea curl, python, wget, sqlmap, nmap, burp, scan |
| **Rate Limit** | `SeguridadServidor::verificarBloqueoAcceso()` — 5 intentos, 15 min bloqueo |
| **Sanitización** | `SanitizadorEntrada::sanitizarTextoBase()` (htmlspecialchars), `sanitizarTextoPlano()` (strip_tags), `procesarCorreoElectronico()` |
| **Contraseñas** | `password_hash($clave, PASSWORD_DEFAULT)` / `password_verify()`. Helper: `PoliticaContrasena::validar($clave)` — 8+ chars, 1 mayúscula, 1 número, 1 símbolo |
| **Headers HTTP** | CSP, X-Frame-Options:DENY, X-Content-Type-Options:nosniff, HSTS (si HTTPS), Referrer-Policy |

## Auditoría

```php
RegistroAuditoria::auditoria('Producto', 'Crear', ['id'=>5]); // BD bitacora_sistema
RegistroAuditoria::info(...) | advertencia(...) | error(...) | seguridad('RBAC', 'Denegado', [...])
```
**Dual:** BD (`bitacora_sistema`) + archivo (`storage/logs/trazabilidad.log`). Cada petición tiene `X-Trace-Id` único. Consulta: `RegistroAuditoria::consultarEventos($idOp, $modulo, $limite, $offset)`.

## Paginador — ⚠️ REGLA CRÍTICA

**Propiedades PÚBLICAS** (NO métodos):
```php
$pag->paginaActual; $pag->porPagina; $pag->totalPaginas; $pag->totalRegistros; $pag->offset();  // ✅
$pag->paginaActual();  // ❌ Error 500
```
**NUNCA usar ORM chain con `limite()+saltar()`** → error 500 por estado estático. **SIEMPRE PDO directo.**
Ver `PAGINACION_PDO.md` para el patrón obligatorio.

## Helpers (alias corto disponible → mismo archivo)

| Helper | Alias | Métodos clave |
|--------|-------|---------------|
| `AyudanteArchivo` | `ArchivoH` | `tamanoLegible(bytes)`, `esImagen()`, `categoriaMime()`, `iconoExtension()`, `sanitizarNombre()`, `esNombreSeguro()` |
| `AyudanteOperador` | `OperadorH` | `idActual()`, `nombreActual()`, `rolActual()`, `tienePermiso()`, `permisoRequerido()`, `nombreRol()`, `estadoEtiqueta()` |
| `AyudanteSeguridad` | `Seguridad` | `sesionActiva()`, `autenticacionRequerida()`, `tokenCSRF()`, `csrfMeta()`, `validarCSRF()`, `tienePermiso()`, `ipCliente()` |
| `AyudanteFecha` | `Fecha` | `ahora()`, `formatear()`, `relativo()`, `diferencia()`, `sumarDias()`, `restarDias()`, `esHoy()`, `esPasado()`, `esFuturo()` |
| `AyudanteCadena` | `Cadena` | `limitar()`, `slug()`, `aleatorio()`, `enmascarar()`, `normalizar()`, `esEmail()`, `capitalizar()` |
| `AyudanteArreglo` | `Arreglo` | `pluck()`, `agrupar()`, `ordenar()`, `aplanar()`, `primero()`, `ultimo()`, `sumar()`, `promedio()` |
| `AyudanteGeneral` | `General` | `generarToken()`, `moneda()`, `bytesLegibles()`, `dd()`, `aBooleano()`, `desdeJson()`, `unaVez()` |

## API — POST /api (unificado)

El dispatcher `procesarPeticionPost.php` recibe `accion_crud`, valida CSRF, y delega:

| accion_crud | Controlador | Método |
|-------------|-------------|--------|
| `iniciar_sesion`, `cerrar_sesion` | `AutenticacionApiControlador` | `iniciarSesion`, `cerrarSesion` |
| `registrar_operador`, `actualizar_mi_perfil` | `OperadorApiControlador` | `registrar`, `actualizarPerfil` |
| `guardar_personalizacion_ui`, `obtener_personalizacion_ui` | `PersonalizacionApiControlador` | `guardar`, `obtener` |
| `migraciones_*` (5) | `MigracionApiControlador` | `ejecutar`, `ejecutarIndividual`, `resetear`, `verSql`, `respaldo` |
| `actualizar_configuracion_archivos` | `ConfiguracionApiControlador` | `actualizarConfiguracionArchivos` |
| `generar_modulo` | `GeneradorModuloApiControlador` | `generarModulo` |
| cualquier otra | `CrudApiControlador` | `procesar` (CRUD genérico, whitelist: operador, rbac_rol, bitacora_sistema, estadistica) |

**Patrón:** cada método recibe `$payload` (array), retorna `[httpStatus, responseData]` con `estado_operacion`, `mensaje_error`, `codigo_error`, `datos`.

**Endpoints GET JSON adicionales (protegidos con ApiAuthInterceptor):** `/api/operadores`, `/api/operadores/{id}`, `/api/roles`, `/api/archivos`, `/api/archivos/configuracion`

## JS Frontend

**Entry point:** `src/js/principal.js` — importa `ui/lite.js` (tema), `seguridad.js`, `api/utilidades.js`, `api/ListaFiltrable.js`, `ui/navegacion.js`, `ui/notificaciones.js`, `api/formularioAutenticacion.js`, `api/inicioSesion.js`, `api/formularioCrud.js`, `api/manejoErrores.js`.

**12 módulos JS:** `apariencia`, `auditoria`, `configuracion`, `documentacion`, `estadisticas`, `generadorModulo`, `generadorPdf`, `inicio`, `migraciones`, `operadores`, `panelControl`, `subirArchivos`.

**CSRF en JS:** `obtenerTokenCSRF()` (de `<meta name="csrf-token">`). Fallback: `document.querySelector('input[name="csrf_token"]')`.

**ListaFiltrable:** clase reusable para listas con filtros + paginación AJAX. Usa `obtenerBasePath()` para rutas relativas.

**ManejoErrores:** captura global de errores JS no manejados.

Ver `DISENO_FRONTEND.md` para arquitectura completa.

## CSS (15 archivos)

**Orden:** `tema → paletas → maquetacion → componentes → modales → subirArchivos → generadorModulo → estilos → errores → utilidades → personalizacion`
**Personalización params GET:** `paleta` (9 colores), `fondo` (16 fondos), `estilo` (5 estilos), `fuente` (5), `espaciado` (3), `tamano` (3).

## Servicios

- **`GeneradorModulo`** — genera 7 archivos: modelo, migración, API controller, vista, JS, rutas, autoload. CLI: `php servidor/consola/generar_modulo.php Producto --campos="nombre:string:required,..."`
- **`GeneradorPdf`** — PDF builder con HTML+CSS. Métodos: `establecerTitulo()`, `agregarParrafo()`, `agregarTabla()`, `agregarLista()`, `agregarHtml()`, `renderizar()`
- **`GeneradorEstadisticas`** — tipos: tarjetas, barras, pastel, kpi. `establecerConsulta($sql)->comoKpi()->ejecutar()->renderizar()`

## Configuración Dinámica

`.env` → BD `configuracion_sistema` (runtime) → `ConfiguracionSistema::obtener('CLAVE', default)` (cache 30s, optimistic locking con columna `version`) → `GeneradorIniServidor::regenerar()` escribe `.user.ini` atómicamente. **Solo Super Admin (rol 1)**, requiere confirmación `CONFIRMAR`.

## Subida Archivos

```php
$s = new SubidaArchivos('campo'); $s->validar(['image/jpeg'], 5*1024*1024); // tipos MIME + bytes
$ruta = $s->guardar(DIRECTORIO_RAIZ.'/storage/archivos'); // nombre: bin2hex(random_bytes(16))
```
**Nunca** servir storage directo (bloqueado .htaccess). Usar endpoints con auth+RBAC.

## Migraciones

```bash
php servidor/migrar.php list   # lista pendientes
php servidor/migrar.php ejecutar  # ejecuta todas
```
Archivos en `servidor/migraciones/00X_*.sql` con `GestorMigraciones.php`.

## Recordatorios IA

1. **Type hints SIEMPRE**: `function foo(string $param): array` — en parámetros y retorno. Sin excepción.
2. Español siempre (vars, comentarios, docs)
3. Sin dependencias externas (no Composer, no npm)
4. Clase nueva → `autoload.php` (clase + alias)
5. Ruta nueva → `web.php` antes de `Enrutador::registrarInstancia()`
6. Prepared statements SIEMPRE
7. `h($var)` en toda salida HTML
8. Validar servidor aunque valide cliente
9. Auditar ops importantes (`RegistroAuditoria`)
10. Sin secrets en código (`.env` + `GestorEntorno`)
11. `session_regenerate_id(true)` post-login
12. Paginación: **NUNCA** ORM chain con `limite()/saltar()` → PDO directo
13. CSRF en toda petición POST (header `X-CSRF-Token` o body `token_peticion`)
