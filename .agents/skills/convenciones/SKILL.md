---
name: convenciones
description: Convenciones de codigo, commits, y estructura del proyecto liteFramework. Usar al crear o modificar cualquier archivo del proyecto.
license: Apache-2.0
---

# Convenciones de liteFramework

## Idioma

- Todo el codigo, comentarios, commits, variables, rutas y nombres estan en **espanol**
- Excepcion: interfaces PSR (LoggerInterface, LogLevel, etc.) en ingles
- Las cadenas de UI visibles para el usuario estan en espanol

## Naming

| Elemento | Convencion | Ejemplo |
|----------|------------|---------|
| Clases PHP | PascalCase en espanol | `ControladorBase`, `GestorSesiones` |
| Metodos PHP | camelCase en espanol | `obtenerInstancia()`, `validarEstructuraCorreo()` |
| Variables PHP | snake_case en espanol | `$id_operador`, `$nombre_completo` |
| Archivos PHP | PascalCase | `Enrutador.php`, `ConexionBaseDatos.php` |
| Archivos JS | camelCase | `navegacion.js`, `formularioCrud.js` |
| Archivos CSS | kebab-case | `maquetacion.css`, `generadorModulo.css` |
| Rutas URL | kebab-case | `/panel-control`, `/generador-modulo` |
| Tablas DB | snake_case | `operador`, `rbac_rol`, `bitacora_sistema` |
| Columnas DB | snake_case | `id_operador`, `nombre_completo`, `clave_acceso` |
| Clases CSS | kebab-case con prefijo BEM | `.tarjeta-seleccion-modulo`, `.grupo-campo` |
| IDs HTML | kebab-case | `#contenido-principal`, `#barra-lateral` |
| Constantes | UPPER_SNAKE_CASE | `DIRECTORIO_RAIZ`, `URL_BASE` |
| Namespaces PHP | PascalCase | `LiteFramework\Nucleo`, `LiteFramework\Seguridad` |
| Rutas nombradas | snake_case | `panel.control`, `generador.modulo` |

## PHP

- `declare(strict_types=1)` en TODOS los archivos
- Type hints en todas las funciones y metodos
- PSR-12 (validado con PHPCS)
- PHPStan nivel 7
- Sin dependencias externas (zero Composer packages en produccion)
- Prepared statements para todas las consultas SQL
- `htmlspecialchars()` con `ENT_QUOTES` para output
- `trim()` en todos los inputs de usuario
- Namespace `LiteFramework\` mapea a `servidor/`

## JavaScript

- ES modules (type="module")
- camelCase para funciones y variables
- fetch() para llamadas AJAX con `X-Requested-With: XMLHttpRequest`
- CSRF token via `<meta name="csrf-token">` o campo oculto `token_peticion`
- window.fetch monkey-patched en `utilidades.js` para offline queue
- `NotificadorHubble.mostrar()` para notificaciones al usuario
- `SeguridadSistema` para sanitizacion y validacion en cliente

## CSS

- CSS Custom Properties para todo (design tokens en `tema.css`)
- 13 paletas via clases `.paleta-*`, 8 estilos via `.estilo-*`
- Responsive: 4 breakpoints (>=1025, 769-1024, 601-768, <=600)
- `@media (prefers-color-scheme: dark)` para tema oscuro
- Sin colores hex hardcodeados (excepciones en generadorPdf.css, generadorProyecto.css, subirArchivos.css)
- `rem` para font-sizes y spacing
- Animaciones respetan `prefers-reduced-motion`

## Base de datos

- MySQL 5.7+ principal, SQLite fallback (auto-detect)
- SQLite in-memory para tests
- Migraciones versionadas en `servidor/migraciones/`
- Tablas con esquema definido en migraciones
- Foraneas: `id_operador`, `id_rol`, etc.
- Timestamps: `fecha_registro`, `fecha_actualizacion`, `fecha_creacion`

## Commits

- Formato: `tipo: descripcion en espanol`
- Tipos: feat, fix, refactor, style, docs, test, chore, security
- Ejemplo: `feat: agregar campo de telefono al formulario de registro`
- Commits en espanol, presente imperativo

## API

- Unico endpoint `POST /api`
- Payload JSON con `Content-Type: application/json`
- CSRF token en campo `token_peticion`
- Respuesta siempre incluye: `estado_operacion`, `mensaje_error`, `codigo_error`, `nuevo_token`, `datos`, `redireccion`
- Errores: `no_autenticado`, `sin_permiso`, `token_invalido`, `datos_invalidos`
- CRUD generico usa `accion_crud` + `tabla_destino` en whitelist
- `_cliente` en payload para datos de dispositivo

## Testing

- PHPUnit 11 con SQLite in-memory
- Constante `TESTS_RUNNING` para modo test
- `ConexionBaseDatos::resetearInstancia()` entre tests
- Tests en espanol (nombres de clases, metodos, aserciones)
- Archivos test: `*Test.php` en `tests/Casos/` o `tests/Integracion/`
- `tests/bootstrap.php` carga autoload, entorno, error handler
