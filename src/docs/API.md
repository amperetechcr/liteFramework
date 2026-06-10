# API — liteFramework v1.2.0

Unico endpoint: **POST /api** con `accion_crud`. El dispatcher valida CSRF y delega al controlador. Ver `procesarPeticionPost.php` para mapa completo `accion_crud` → controlador.

## Contrato de respuesta

```json
{"estado_operacion":true|false, "mensaje_error":null|"texto", "codigo_error":null|"CODIGO",
 "nuevo_token":"csrf-64-hex", "datos":{...}|null, "redireccion":null|"/ruta"}
```

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `estado_operacion` | bool | `true` si éxito |
| `mensaje_error` | string\|null | Texto legible para el usuario |
| `codigo_error` | string\|null | Código máquina para lógica cliente |
| `datos` | object\|null | Payload específico de la acción |
| `nuevo_token` | string | CSRF rotado (cliente debe actualizarlo) |
| `redireccion` | string\|null | Ruta a navegar tras éxito |

**Campos adicionales recibidos automáticamente:**
`_cliente` (objeto, opcional): Datos del dispositivo recolectados por JS (screen, viewport, conexion, memoria, CPU, timezone, idiomas, touch, plataforma). Se almacenan en `$_SESSION['_datos_cliente']` y se incluyen en el contexto de cada evento de auditoría.

## Autenticación

### Iniciar sesión

**Body:** `accion_crud=iniciar_sesion` + `correo` + `clave`
**Éxito (200):** `{"datos":{"operador_nombre":"Juan Perez"},"redireccion":"/inicio"}`
**Errores:** `datos_invalidos` | `token_invalido` | `bloqueo_temporal` (5 intentos, 15 min) | `cuenta_suspendida` | `acceso_denegado`

### Registrar operador

Requiere permiso `operador.crear`.
**Body:** `accion_crud=registrar_operador` + `nombre_completo` (min 3) + `correo_electronico` (único BD) + `clave_registro` (8+ chars, 1 mayúscula, 1 dígito, 1 símbolo `@$!%*?&`) + `id_rol` (existente en `rbac_rol`)

### Cerrar sesión

**GET /salir** (NO es parte de la API unificada). Destruye sesión y redirige a `/ingreso`.

## Generación de proyectos

### Generar proyecto

**Body:** `accion_crud=generar_proyecto` + `definicion_proyecto` (objeto JSON con estructura: proyecto.nombre, proyecto.codigo, directorio_salida, base_datos, apariencia, modulos_activados, entidades, operador_inicial)
**Requiere:** Sesión activa + CSRF válido
**Éxito (200):** `{"estado_operacion":true, "datos":{"directorio":"...","archivos_procesados":N,"entidades":[],"modulos_activados":[],"pasos_siguientes":[]}}`

## Gestión de operadores (Admin)

### Suspender operador

Requiere rol 1 (Admin).
**Body:** `accion_crud=operador_suspender` + `id_entidad`

### Activar operador

Requiere rol 1 (Admin).
**Body:** `accion_crud=operador_activar` + `id_entidad`

## Perfil

### Actualizar mi perfil

**Body:** `accion_crud=actualizar_mi_perfil` + `nombre_completo` + `correo_electronico` + `clave_actual` (opcional) + `clave_nueva` (opcional). Solo modifica perfil del operador logueado. Si se envían claves, valida la actual antes de cambiarla.

### Actualizar perfil (cualquier operador)

Requiere permiso `operador.actualizar`.
**Body:** `accion_crud=actualizar_perfil` + `id_entidad` + `nombre_completo` + `correo_electronico` + `id_rol` + `estado_cuenta`

## Personalización UI

### Obtener

**Body:** `accion_crud=obtener_personalizacion_ui`
**Respuesta:** `{"datos":{"paleta":"azul","estilo":"moderno","fondo":"blanco","textura":"ninguna","fuente":"sistema","espaciado":"normal","tamano":"normal","radio":"normal","animacion":"normal","grosor":"normal","sombra":"normal","tema":"oscuro"}}`

### Guardar

**Body:** `accion_crud=guardar_personalizacion_ui` + `paleta` + `estilo` + `fondo` + `textura` + `fuente` + `espaciado` + `tamano` + `radio` + `animacion` + `grosor` + `sombra` + `tema`
Cada campo validado contra lista blanca (ver `servidor/config/ui.php`).

## Configuración de archivos

Requiere **rol 1 (Super Admin)** y campo `confirmacion="CONFIRMAR"`.
**Body:** `accion_crud=actualizar_configuracion_archivos` + `confirmacion` + `valores:{ARCHIVO_TAMANO_MAXIMO_MB, ARCHIVO_TIPOS_MIME_PERMITIDOS, ARCHIVO_CUOTA_USUARIO_MB, ARCHIVO_EXTENSIONES_PERMITIDAS, ARCHIVO_MEMORIA_PHP_MB, ARCHIVO_TIEMPO_EJECUCION_SEG, ARCHIVO_MAXIMO_SUBIDAS_SIMULTANEAS, ARCHIVO_POST_MAX_SIZE_MB}`

`ARCHIVO_TIPOS_MIME_PERMITIDOS`: categorías separadas por comas (`imagenes,documentos,videos,audio,comprimidos,ejecutables`) o `*` (repositorio). Regenera `.user.ini` atómicamente. Conflictos reportados en `datos.hubo_conflictos`.

## CRUD genérico

`accion_crud=crud` + `operacion` (listar|obtener|crear|actualizar|eliminar) + `tabla_destino`. Whitelist: `operador`, `rbac_rol`, `bitacora_sistema`, `estadistica`. Permisos: `{entidad}.{crear|leer|actualizar|eliminar}`.

### Listar
`operacion=listar` + `filtros:{}` + `buscar` + `pagina` + `por_pagina` + `ordenar_por` + `orden`. Respuesta incluye `total`, `pagina`, `por_pagina`, `total_paginas`.

### Obtener
`operacion=obtener` + `id_entidad`. Retorna el registro único.

### Crear
`operacion=crear` + `datos:{...}`. Retorna `id_afectado`.

### Actualizar
`operacion=actualizar` + `id_entidad` + `datos:{...}`.

### Eliminar
`operacion=eliminar` + `id_entidad`. Roles protegidos del sistema no se eliminan.

## Seguridad de peticiones

1. **Sesión activa**: validada por `ApiAuthInterceptor` (cookie `lite_sid`, HttpOnly + SameSite=Strict)
2. **CSRF**: header `X-CSRF-Token` o body `token_peticion`, comparación con `hash_equals`, gracia 60s
3. **Fingerprint**: SHA-256(IP + User-Agent). Si cambia → sesión invalidada

**Headers requeridos:**
```
POST /api | Content-Type: application/json | X-CSRF-Token: <token> | X-Requested-With: XMLHttpRequest | Cookie: lite_sid=<sid>
```

```js
// Ejemplo fetch
import { obtenerTokenCSRF } from '/src/js/api/utilidades.js';
const r = await fetch('/api', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':obtenerTokenCSRF()},
  body: JSON.stringify({accion_crud:'iniciar_sesion', correo:'...', clave:'...'}) });
const datos = await r.json(); // datos.nuevo_token = siguiente CSRF
```
