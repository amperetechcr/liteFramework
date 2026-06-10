# liteFramework — Patrones Prácticos

> Para contexto completo (arquitectura, ORM, validación, seguridad, helpers): ver `src/docs/AGENTS.md`
> Para documentación de endpoints API: ver `src/docs/API.md`
> Para problema conocido de paginación ORM: ver `PAGINACION_PDO.md`

## ⚠️ Regla Absoluta: Código que pase PHPStan + PHPCS + PHPUnit

**No existe enviar código que falle. No existe skippear tests. No existe baseline para PHPStan.**
El código debe pasar las 3 validaciones desde el primer intento. Estas son las reglas:

### Reglas PHPStan nivel 7 (tolerancia cero)

| # | Regla | Incumplimiento común | Cómo hacerlo bien |
|---|-------|----------------------|-------------------|
| 1 | **Siempre validar `fopen()`** | `fwrite(fopen(...), $data)` sin validar que `fopen` no retorne `false` | `$salida = fopen(...); if ($salida === false) { ... }` |
| 2 | **Siempre validar `file_get_contents()`** | `json_decode(file_get_contents($f), true)` sin validar que retorne `string` | `$c = file_get_contents($f); if ($c === false) { ... }` después `json_decode($c, true)` |
| 3 | **Siempre validar `json_encode()`** | `fwrite($h, json_encode($data))` sin validar retorno `false` | `$payload = json_encode($data); if ($payload !== false) { fwrite($h, $payload); }` |
| 4 | **Siempre validar `glob()`** | `foreach (glob(...) as $f)` sin verificar que sea iterable | `$archivos = glob(...); if (is_array($archivos)) { foreach ($archivos as $f) { ... } }` |
| 5 | **Usar `->` no `?->` cuando el tipo es no-nullable** | `$consola?->metodo()` donde `$consola` ya se verificó no-null | Usar `$consola->metodo()` después de la verificación |
| 6 | **Validar tipos de `getopt()`** | `(string)$args['clave']` donde `getopt()` retorna `list\|string\|false` | `isset($args['clave']) && is_string($args['clave']) ? $args['clave'] : ''` |
| 7 | **No llamar métodos que no existen** | `RegistroAuditoria::exito()` no existe | Usar `RegistroAuditoria::info()`, `advertencia()`, `error()`, `seguridad()`, o `auditoria()` |
| 8 | **No dejar `break` después de `throw`** | `throw ...; break;` — código inalcanzable | Eliminar el `break` |
| 9 | **BOM UTF-8 prohibido** | `index.php` empieza con `\xEF\xBB\xBF` | Guardar siempre UTF-8 sin BOM |

### Reglas PHPCS PSR-12 (tolerancia cero)

| # | Regla | Incumplimiento común | Cómo hacerlo bien |
|---|-------|----------------------|-------------------|
| 1 | **Sin BOM UTF-8** | Archivos con byte order mark | Guardar UTF-8 sin BOM |
| 2 | **Braces en línea nueva para funciones** | `function h() { return ...; }` | `function h() {\n    return ...;\n}` |
| 3 | **Braces necesarios para controles** | `if ($x) return;` | `if ($x) {\n    return;\n}` |
| 4 | **CamelCase en métodos** | `function PERMISOS_BLOQUEADOS()` | `function permisosBloqueados()` |
| 5 | **Espacios en union types** | `true\|string` | `true \| string` |
| 6 | **Switch: todo `case` termina en `break`/`throw`** | Case sin break y sin throw | Agregar `break` o `throw` |
| 7 | **Sin trailing whitespace** | Espacios al final de línea | Limpiar siempre |

### Reglas PHPUnit (tolerancia cero)

| # | Regla | Incumplimiento común | Cómo hacerlo bien |
|---|-------|----------------------|-------------------|
| 1 | **No skippear tests jamás** | `$this->markTestSkipped()` — prohibido | Si un test necesita un servicio externo, que esté disponible |
| 2 | **Callbacks con trabajo real** | `medir(fn() => 42)` da 0.0ms al hacer assert > 0 | Usar loop de 100k iteraciones |
| 3 | **No hardcodear colores en CSS** | `color: #22c55e` | Usar `var(--color-exito)`, `var(--color-peligro)` |
| 4 | **Test y código sincronizados** | Test busca valor que no existe | Si se agrega un test, el código debe existir |

```bash
# 1. Auto-fix PSR-12
php phpcbf.phar --standard=phpcs.xml.dist --extensions=php --ignore=vendor,plantillas,tests .

# 2. PHPStan nivel 7 — 0 errores
php phpstan.phar analyse --configuration=phpstan.neon.dist --no-progress

# 3. PHPCS — 0 errores
php phpcs.phar --standard=phpcs.xml.dist --extensions=php --ignore=vendor,plantillas,tests --runtime-set ignore_warnings_on_exit 1 .

# 4. Tests — 0 failures, 0 skipped
php tests/phpunit.phar -c tests/phpunit.xml

# 5. Si algo falla, corregir y repetir. No entregar código que falle.
```

**No entregues código si alguna de estas falla. No hay excepciones. No hay baselines. No hay skips.**

---

## 1. Crear Módulo (5 pasos)

**Paso 1:** `src/modulos/miModulo/miModulo.php` (orquestador + partials `listado.php`, `formulario.php`)
**Paso 2:** Ruta: `$enrutador->get('/mi-modulo', fn() => (new ModuloControlador())->indice('miModulo'))->interceptor(AutenticacionInterceptor::class)->nombre('miModulo')`
**Paso 3:** Sidebar: `src/plantillas/encabezado.php` → array `$enlacesNav`
**Paso 4:** Dashboard: `src/modulos/inicio.php` → array `$modulos`
**Paso 5:** Modelo en `servidor/modelos/` si usa BD (extender `Modelo`)

## 2. Generador CRUD Automático (CLI)

```bash
php servidor/consola/generar_modulo.php Producto \
  --campos="nombre:string:required,precio:decimal:required,stock:int,categoria_id:int"
```
**Tipos:** `string|text|int|decimal|bool|email|date|datetime`
**Reglas:** `required|unique`
**Genera 7 archivos:** Modelo, Migración, API Controlador, Vista, JS, Rutas, Autoload

## 3. Paginación (REGLA CRÍTICA)

**NUNCA** `Producto::todos()->ordenarPor(...)->limite(10)->saltar(0)->obtener()` → **Error 500**
**SIEMPRE PDO directo:**
```php
$con = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
$total = (int)$con->query("SELECT COUNT(*) FROM tabla")->fetchColumn();
$paginador = Paginador::crear($total, 20);
$stmt = $con->prepare("SELECT * FROM tabla ORDER BY fecha DESC LIMIT :lim OFFSET :off");
$stmt->bindValue(':lim', 20, PDO::PARAM_INT);
$stmt->bindValue(':off', $paginador->offset(), PDO::PARAM_INT);
$stmt->execute();
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($filas as $f) $resultados[] = new MiModelo($f);
```
Ver `PAGINACION_PDO.md` para detalle completo.

## 4. Patrón AJAX (SPA)

`navegacion.js` hace `fetch(url+'?ajax=1')` → reemplaza `#contenido-principal`
**En el módulo PHP:**
1. `$esAjax = isset($_GET['ajax'])` (lo inyecta `modulo_cabecera.php`)
2. Si `$esAjax && !$partial`: imprimir `<div data-titulo-pagina="...">`
3. Si `$partial==='lista'`: require partial + `return`
4. Si `!$esAjax`: require `encabezado.php`
5. Al final si `!$esAjax`: require `pie.php`

## 5. Configuración Dinámica (.user.ini)

`.env` → `configuracion_sistema` (BD) → `ConfiguracionSistema::obtener()` (cache 30s) → `GeneradorIniServidor::regenerar()` → PHP lee `.user.ini`
**Solo Super Admin (rol 1)**, requiere escribir `CONFIRMAR`.

## 6. Subida Archivos

```php
$subida = new SubidaArchivos('campo_input');
$subida->validar(['image/jpeg','image/png'], 5*1024*1024);
$ruta = $subida->guardar(DIRECTORIO_RAIZ.'/storage/archivos');
```
**Reglas:** Nunca servir storage directo. Endpoints con auth+RBAC. Validar MIME real.

## 7. UI Personalización

**Orden CSS:** `tema → paletas → maquetacion → componentes → modales → subirArchivos → generadorModulo → estilos → errores → utilidades → personalizacion`
**Params GET:** `paleta`, `estilo`, `personalizacion`, `fuente`, `espaciado`, `tamano`


## 8. Buenas Prácticas

1. **Type hints SIEMPRE**: `function foo(string $param): array` — en parámetros y retorno
2. `h($var)` **siempre** en salida HTML
3. Prepared statements via ORM/PDO
4. Validar servidor aunque valide cliente
5. `session_regenerate_id(true)` post-login
6. Sin secrets en código (usar `.env`)
7. Auditar ops importantes (`RegistroAuditoria`)
8. **Validar antes de entregar**: PHPStan + PHPCS + Tests

## 9. Estructura Módulos

```
src/modulos/
├── apariencia/  ├── auditoria/  ├── configuracion/  ├── documentacion/
├── estadisticas/  ├── generadorModulo/  ├── generadorPdf/  ├── inicio/
├── migraciones/  ├── operadores/  ├── panelControl/  └── subirArchivos/
```

## 10. Puente OpenCode ↔ OpenClaw

### Probar un modulo con OpenClaw

```bash
openclaw agent --agent main --message "Abre http://localhost/liteFramework/{ruta} y verificame que cargue sin errores"
```

### Ejecutar tests completos con OpenClaw

```bash
openclaw agent --agent main --message "Ejecuta los tests de liteFramework con PHPUnit y dime el resultado"
```

### Prueba de interfaz de todos los módulos

```bash
openclaw agent --agent main --message "Prueba estos módulos de liteFramework: 1) /liteFramework/panelControl 2) /liteFramework/operadores 3) /liteFramework/configuracion 4) /liteFramework/apariencia 5) /liteFramework/generador-modulo 6) /liteFramework/generador-proyecto 7) /liteFramework/migraciones. Reporta si hay errores."
```

### Prueba de rendimiento

```bash
openclaw agent --agent main --message "Mide tiempos de carga de /liteFramework/ingreso, /liteFramework/panelControl, /liteFramework/operadores usando el navegador"
```

### Flujo típico

1. **Desarrollo**: `opencode run "Agregar campo teléfono al formulario de operadores"`
2. **Verificación**: `opencode run "Ejecuta los tests unitarios"`
3. **Pruebas E2E**: Ejecutar comando OpenClaw desde acá
4. **Verificación visual**: Probar módulo con el navegador

## ⚠️ Windows PowerShell - Tests se pegan
PHPUnit 11 usa `\r` (carriage return) en la barra de progreso. PowerShell 5.1 se cuelga al pipear eso.
**Siempre usar cmd /c + 2>nul para ejecutar tests (sino PowerShell mezcla stderr con stdout y se cuelga):**
```cmd
cmd /c ""C:\xampp\php\php.exe" -f tests\phpunit.phar -- -c tests\phpunit.xml 2>nul"
```

## ⚠️ Protocolo de depuración (NO SALTAR PASOS)
```
1. Tests (cmd /c)        → el código debe pasar, es la línea base
2. Bitácora (crud listar)→ leer error REAL de BD, no adivinar
3. Log archivo           → trazas y trace_id en storage/logs/
4. Consola navegador F12 → JS errors, 403/404, fetch fails
5. MCP tools             → datos vivos con lite_mcp_lite_crud
6. console.log           → solo si 1-5 no dieron respuesta
7. Fix más simple primero→ 1 línea, probar, si no funciona revertir
8. 3 intentos fallidos   → revertir TODO y volver al paso 1
```

**Prohibido:** desactivar seguridad completa, modificar archivos no relacionados, 10 cambios simultáneos, asumir sin ver logs.
```
