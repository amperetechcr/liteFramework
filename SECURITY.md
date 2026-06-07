# Politica de Seguridad

## Version actual

La version actual del framework es **1.3.0**.

Para una descripcion arquitectonica completa de las medidas de
seguridad del framework, ver `src/docs/AGENTS.md` seccion "Seguridad".

## Reportar vulnerabilidades

Si descubres una vulnerabilidad de seguridad en liteFramework, por favor:

1. **No** crees un issue publico en el repositorio.
2. Envia un correo electronico a `amperetechcr@gmail.com` con:
   - Descripcion detallada de la vulnerabilidad.
   - Pasos para reproducir el problema.
   - Version afectada.
   - Posible impacto (critico, alto, medio, bajo).
   - Sugerencias de mitigacion (opcional).
3. Espera confirmacion de recepcion en un plazo de 48 horas.
4. Una vez corregida la vulnerabilidad, se te creditara en el
   registro de cambios (`CHANGELOG.md`) si lo deseas.

## Medidas de seguridad implementadas

### Autenticacion

- Contrasenas hasheadas con `password_hash()` (bcrypt).
- Verificacion con `password_verify()`.
- Rate limiting con bloqueo por intentos fallidos
  (`APP_MAX_INTENTOS_ACCESO`, default 5).
- Regeneracion de ID de sesion tras login exitoso.
- Fingerprint de sesion: SHA-256(IP + User-Agent) que invalida
  la sesion si cambia.

### Proteccion CSRF

- Tokens de 64 caracteres generados con `random_bytes(32)`.
- Doble envio: header `X-CSRF-Token` o body `token_peticion`.
- Comparacion timing-attack safe con `hash_equals()`.
- Rotacion automatica tras cada validacion exitosa.
- Ventana de gracia de 60 segundos para peticiones concurrentes
  (soporta solapamiento de tabs).

### Control de acceso (RBAC)

- Tablas `permisos`, `permisos_rol`, `rbac_rol`.
- Matriz de permisos cacheada en `$_SESSION['matriz_permisos']`
  con TTL de 5 minutos.
- Claves de permiso canonicas: `<entidad>.<accion>`
  (p. ej. `operador.crear`, `archivo.eliminar`).
- Roles con `estado_rol` no son eliminables (sistema protegido).

### Sanitizacion

- `limpiarHtml()` neutraliza `<script>`, `javascript:` y atributos
  `on*`.
- Validacion de emails con `FILTER_VALIDATE_EMAIL`.
- Sanitizacion de identificadores SQL con whitelist
  (`preg_replace('/[^a-zA-Z0-9_]/', '', $id)`).
- Limpieza recursiva de arreglos en `SanitizadorEntrada`.

### Cabeceras HTTP

Definidas en `.htaccess` y aplicadas a todas las respuestas:

- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Content-Security-Policy` estricta (`default-src 'self'`,
  `frame-ancestors 'none'`)
- `Strict-Transport-Security` (HSTS condicional a HTTPS)
- `Permissions-Policy` deshabilitando geolocation, microfono,
  camara y payment
- `Header unset Server / X-Powered-By`
- `Cache-Control: no-store` en rutas autenticadas

### Base de datos

- Prepared statements exclusivamente (PDO).
- Codificacion `utf8mb4` con collation `unicode_ci`.
- Fetch mode `ASSOC`.
- Conexion con `ATTR_EMULATE_PREPARES = false` para evitar
  emulacion.

### Auditoria

- Doble persistencia: tabla `bitacora_sistema` + archivo
  `storage/logs/trazabilidad.log`.
- Niveles: `INFO`, `ADVERTENCIA`, `ERROR`, `SEGURIDAD`, `AUDITORIA`.
- Cada peticion lleva un `X-Trace-Id` (32 hex) para correlacionar
  eventos.
- Eventos de `SEGURIDAD` (CSRF invalido, login fallido,
  fingerprint invalido) se loggean con la huella del cliente.

### Configuracion regenerable

- `.user.ini` y el bloque marcado del `.htaccess` pueden
  regenerarse desde la UI (`/configuracion`, sección Archivos).
- Rango duro 1-2048 MB para `memory_limit` y `post_max_size`.
- Requiere confirmacion textual literal `"CONFIRMAR"`.
- Conflictos (p. ej. `post_max_size < upload_max_filesize`) se
  reportan y loggean.

## Requisitos de seguridad para produccion

Antes de desplegar en produccion:

1. `APP_ENTORNO=produccion` en `.env`.
2. `APP_DEPURACION=false` en `.env` (no exponer stack traces).
3. HTTPS obligatorio (HSTS solo se activa con HTTPS).
4. Cookies de sesion seguras: `cookie_secure=1` se activa
   automaticamente si hay HTTPS (el framework ya pone
   `HttpOnly` y `SameSite=Lax`). SameSite=Lax permite
   compatibilidad con túneles (ngrok, cloudflared) sin sacrificar
   seguridad CSRF en peticiones GET.
5. Verificar bloqueo de carpetas en `.htaccess`:
   `servidor/`, `rutas/`, `storage/`, `src/vistas/`, `.env`.
6. Mover `.env` fuera del document root si es posible.
7. Permisos de archivo: 644 para PHP, 755 para directorios,
   600 para `.env`.
8. Credenciales de BD fuertes y con privilegios minimos.
9. Desactivar `display_errors` en `php.ini` (`display_errors=Off`).
10. Configurar `log_errors=On` y revisar logs de Apache/PHP
    periodicamente.
11. Rotar logs (no hay rotacion automatica; ver
     `src/docs/AGENTS.md` seccion "Auditoria").
12. Auditar periodicamente el modo repositorio de uploads
    (MIME/ext=*) si esta activo.

## Historial de seguridad

| Version | Fecha | Cambios relevantes |
|---|---|---|
| 1.3.0 | 2026-06-07 | SSE optimizado con daemon auto-start, SameSite=Strict→Lax (túneles), WAF sin `scan`, compresión gzip Apache, migración a XAMPP Apache multi-thread, landing page rediseñada |
| 1.2.0 | 2026-06-06 | `declare(strict_types=1)` en 60 archivos, namespaces PSR-4, excepciones personalizadas, DialectoBaseDatos, GeneradorProyecto, PHPStan level 7, PHPCS 0 errores |
| 1.1.0 | 2026-06-02 | Refactor a partials, `ListaFiltrable`, split CSS, regenerador de `.user.ini` con confirmacion textual, navegacion por carpetas en archivos |
| 1.0.3 | 2026-06-02 | Explorador de archivos en arbol, filtro de huerfanos |
| 1.0.2 | 2026-06-02 | CSRF validado en `SubirArchivosControlador::eliminar()`, `ControlAccesoRBAC` en todos los metodos del controlador |
| 1.0.1 | 2026-05-31 | Correcciones de navegacion SPA en auditoria y configuracion |
| 1.0.0 | 2026-01-01 | Version inicial: CSRF, RBAC, fingerprint, sanitizacion, cabeceras, auditoria dual |

## Alcance

Esta politica cubre el nucleo del framework liteFramework y los
modulos distribuidos con el (`inicio`, `operadores`, `auditoria`,
`configuracion`, `subirArchivos`). Modulos personalizados creados
por terceros son responsabilidad de sus autores.

---

Para preguntas de seguridad: `amperetechcr@gmail.com`
