---
name: arquitectura
description: Arquitectura completa de liteFramework. Usar cuando se necesite entender la estructura del proyecto, el flujo de una peticion, o como funcionan los componentes del framework.
license: Apache-2.0
---

# Arquitectura de liteFramework

Framework PHP MVC autónomo (zero dependencias externas) con SPA en vanilla JS.

## Estructura de directorios

```
liteFramework/
  index.php                 # Front controller (entrada unica)
  .htaccess                 # Rewrite rules + seguridad (CSP, HSTS)
  rutas/web.php             # Definicion de rutas
  servidor/                 # BACKEND (PHP)
    autoload.php            # Autoloader PSR-4 con class_alias
    config/                 # Configuracion
      conexion.php          # PDO (MySQL + SQLite fallback)
      configuracion_sistema.php  # Cache de config en DB
      ui.php                # Valores UI y listas validas
      entorno.php           # Carga de .env
    nucleo/                 # Core
      Enrutador.php         # Router con grupos, interceptores, params
      Modelo.php            # ORM Active Record
      ManejadorErrores.php  # Error/exception/fatal handler
      Validador.php         # Validacion de formularios
      Paginador.php         # Paginacion
      SubidaArchivos.php    # Upload handler
      DialectoBaseDatos.php # MySQL/SQLite dialect
    controladores/          # MVC Controllers
      ControladorBase.php
      AutenticacionControlador.php
      ModuloControlador.php
      SubirArchivosControlador.php
    modelos/                # Active Record models
      Operador.php, Rol.php, Archivo.php, DocumentoPdf.php, Estadistica.php
    seguridad/              # Seguridad
      SeguridadServidor.php # Headers, CSRF, WAF
      ControlAccesoRBAC.php # RBAC
      GestorSesiones.php    # Session fingerprint SHA-256
      SanitizadorEntrada.php
      ValidadorCSRF.php     # Tokens CSRF 64-char
      LimitadorPeticiones.php # Rate limiting
      RegistroAuditoria.php # Auditoria dual (DB + archivo)
      PoliticaContrasena.php
      TrazadorPeticiones.php
      SseGestor.php         # Server-Sent Events
    middleware/
      AutenticacionInterceptor.php
      ApiAuthInterceptor.php
      MantenimientoInterceptor.php
    api/                    # API layer
      procesarPeticionPost.php  # Dispatcher unico POST /api
      controladores/           # 8 API controllers
        AutenticacionApiControlador.php
        CrudApiControlador.php
        ConfiguracionApiControlador.php
        MigracionApiControlador.php
        OperadorApiControlador.php
        PersonalizacionApiControlador.php
        GeneradorModuloApiControlador.php
        GeneradorProyectoApiControlador.php
    servicios/              # Business logic
      GeneradorModulo.php, GeneradorProyecto.php, GeneradorPdf.php
      AdministradorArchivos.php, Correo.php
      AutenticacionOAuth.php  # Google + GitHub
      GeneradorEstadisticas.php
      DiagnosticoError.php, ContextoError.php, RemediadorError.php
    migraciones/            # SQL migrations
      GestorMigraciones.php
      migrar.php
      001_estructura_inicial.sql, ...
    cli/                    # CLI
      Consola.php
    consola/                # CLI scripts
      ejecutar_pruebas.php, crear_proyecto.php, generar_modulo.php
      sse_daemon.php, limpiar_auditoria.php
  src/                      # FRONTEND
    js/                     # 28 JS files (vanilla ES modules)
      principal.js          # Entry point (DOMContentLoaded)
      seguridad.js          # SecuritySistema singleton
      eventos.js            # SSE client (LiteSse)
      api/                  # API layer
        utilidades.js       # fetch wrapper, CSRF, offline queue
        formularioCrud.js   # CRUD form binding
        formularioAutenticacion.js
        inicioSesion.js
        ListaFiltrable.js   # Reusable AJAX list+filter
        manejoErrores.js
        recuperacionError.js
      ui/                   # UI layer
        lite.js             # Theme init
        navegacion.js       # SPA navigation
        notificaciones.js   # NotificadorHubble
        confirmaciones.js
      modulos/              # 12 module-specific JS files
        inicio.js, operadores.js, apariencia.js, auditoria.js,
        configuracion.js, documentacion.js, estadisticas.js,
        generadorModulo.js, generadorPdf.js, generadorProyecto.js,
        migraciones.js, subirArchivos.js
    css/                    # 17 CSS files
      tema.css              # Design tokens + reset + tipografia
      paletas.css           # 13 color palettes
      maquetacion.css       # Layout + sidebar responsive
      componentes.css       # Cards, buttons, forms, tables, tags
      estilos.css           # 8 style variants + modulos
      apariencia.css        # Theme picker UI
      utilidades.css        # Utility classes
      modales.css, subirArchivos.css, generadorPdf.css, estadisticas.css,
      documentacion.css, errores.css, oauth.css, personalizacion.css,
      generadorModulo.css, generadorProyecto.css
    modulos/                # SPA partial views (PHP)
      inicio/, operadores/, auditoria/, configuracion/, apariencia/,
      documentacion/, estadisticas/, generadorModulo/, generadorPdf/,
      generadorProyecto/, migraciones/, panelControl/, subirArchivos/
    plantillas/             # Layout templates
      encabezado.php, pie.php, modulo_cabecera.php
    vistas/                 # Standalone views
      inicio_sesion.php, mantenimiento.php
    docs/                   # Documentation
      API.md, DISENO_FRONTEND.md, AGENTS.md
  tests/                    # PHPUnit 11 tests (377 tests)
    Casos/                  # Unit tests (24 files)
    Integracion/            # Integration tests (10 files)
    bootstrap.php, phpunit.xml, phpunit.phar
  storage/                  # Runtime data
    archivos/, backups/, locks/, logs/, sse/
```

## Flujo de una peticion

### Peticion web (GET /panelControl)
1. `.htaccess` redirige a `index.php`
2. `index.php` carga autoload, entorno, error handler, config UI
3. Fusiona personalizacion UI desde GET params y sesion
4. Establece headers de seguridad (CSP, HSTS, X-Frame-Options)
5. Parsea URI, carga rutas de `rutas/web.php`
6. `$enrutador->despachar()` encuentra ruta, ejecuta interceptores, llama al controlador
7. Controlador carga modelo, renderiza vista PHP en `src/modulos/` o `src/vistas/`

### Peticion API (POST /api)
1. `.htaccess` redirige a `index.php`
2. `index.php` detecta POST a `/api` (definido en rutas)
3. `ApiAuthInterceptor` verifica: sesion activa, CSRF token, rate limiting
4. `procesarPeticionPost.php` recibe JSON, parsea `accion_crud`
5. Dispara al API controller correspondiente
6. Toda respuesta incluye: `estado_operacion`, `mensaje_error`, `codigo_error`, `nuevo_token`, `datos`, `redireccion`

### Peticion SPA (GET /modulo?ajax=1)
1. `navegacion.js` hace fetch a `url + '?ajax=1'` con header `X-Requested-With: XMLHttpRequest`
2. Backend detecta `$_GET['ajax']` y solo renderiza el contenido del modulo (sin header/footer)
3. JS reemplaza `#contenido-principal` innerHTML y hace pushState

## Sistema de personalizacion UI

13 paletas de colores: indigo, azul, esmeralda, rosa, ambar, violeta, pizarra, cereza, cielo, teal, lima, naranja, fucsia

8 estilos: moderno, minimalista, elegante, redondeado, contraste, jugueton, corporativo, 3d-moderno

16 fondos: blanco, gris-claro, gris, gris-oscuro, carbon, oscuro, nieve, papel, arena, crema, lavanda, polvo, tinta, pizarra-fondo, bosque, lateral

7 fuentes: sistema, serif, mono, humanista, geometrica, decorativa, redondeada

Cada combinacion se aplica via clases CSS: `paleta-indigo`, `estilo-moderno`, `fondo-blanco`, etc.

La personalizacion se guarda en `operador_personalizacion` (tabla DB) via API y en localStorage.

## Seguridad

- CSRF: token de 64 caracteres, rotacion por peticion, periodo de gracia
- Sesion: fingerprint SHA-256 (User-Agent + IP + token semilla)
- RBAC: 4 roles (Super Admin, Admin, Operador Estandar, Consultor) con 22 permisos
- Rate limiting: por clave_hash + ventana de tiempo
- WAF: bloquea User-Agent conocidos (curl, python, sqlmap, etc.)
- Headers: CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- Input: htmlspecialchars con ENT_QUOTES, prepared statements, validacion por whitelist

## API REST

Endpoint unico: `POST /api`

Contrato de respuesta:
```json
{
  "estado_operacion": true,
  "mensaje_error": null,
  "codigo_error": null,
  "nuevo_token": "csrf_token_64_chars",
  "datos": { ... },
  "redireccion": null
}
```

Acciones disponibles: iniciar_sesion, registrar_operador, cerrar_sesion, crud (generico con whitelist), obtener_configuracion_archivos, guardar_configuracion_archivos, ejecutar_migracion, obtener_estadisticas, CRUD documentos PDF, CRUD estadisticas, personalizacion_obtener, personalizacion_guardar, generar_modulo, generar_proyecto, verificar_progreso_proyecto

## Frontend SPA

- Entry point: `principal.js` (cargado como ES module)
- Navegacion: `navegacion.js` con history.pushState/popstate
- Notificaciones: `NotificadorHubble` (5 visibles max, swipe-to-dismiss)
- CRUD forms: `formularioCrud.js` (vincularFormularioCrud)
- Listas: `ListaFiltrable.js` (AJAX list + filter + pagination)
- Offline: cola de reintentos en `COLA_REINTENTOS`, banner offline, clases `.offline` en CSS
- SSE: `eventos.js` conecta a `src/sse.php` para notificaciones en tiempo real

## Tests

- PHPUnit 11 (bundled PHAR en `tests/phpunit.phar`)
- 377 tests, 2 suites: `Casos/` (unit) y `Integracion/` (integration)
- Base de datos: SQLite in-memory para tests
- Ejecucion: `composer test` o `php tests/phpunit.phar -c tests/phpunit.xml`
- CI: GitHub Actions (PHP 8.2/8.3/8.4, syntax check, PHPCS PSR-12, PHPStan level 7)
- Sin tests de frontend (JS) ni E2E
