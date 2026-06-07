# liteFramework — Patrones Prácticos

> Para contexto completo (arquitectura, ORM, validación, seguridad, helpers): ver `src/docs/AGENTS.md`
> Para documentación de endpoints API: ver `src/docs/API.md`
> Para problema conocido de paginación ORM: ver `PAGINACION_PDO.md`

## ⚠️ Regla de Validación Obligatoria

**Antes de entregar cualquier código nuevo o modificado, DEBES ejecutar:**

```bash
# 1. PHPStan nivel 7
php phpstan.phar analyse --configuration=phpstan.neon.dist --no-progress

# 2. PHPCS PSR-12
php phpcs.phar --standard=phpcs.xml.dist --extensions=php --ignore=vendor,plantillas,tests --runtime-set ignore_warnings_on_exit 1 .

# 3. Tests
php tests/phpunit.phar -c tests/phpunit.xml
```

**No entregues código si alguna de estas 3 falla.**

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
