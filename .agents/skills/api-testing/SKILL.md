---
name: api-testing
description: Pruebas automatizadas de la API REST de liteFramework. Verificar contratos, autenticacion, CSRF, rate limiting, CRUD y endpoints especiales.
license: Apache-2.0
---

# API Testing para liteFramework

## Endpoint unico

`POST /api` con `Content-Type: application/json`

## Contrato de respuesta

Toda respuesta debe incluir:

```json
{
  "estado_operacion": true,
  "mensaje_error": null,
  "codigo_error": null,
  "nuevo_token": "token_csrf_64_caracteres",  // Siempre presente
  "datos": { ... },
  "redireccion": null
}
```

## Pruebas de autenticacion

### Login exitoso
```bash
curl -X POST http://localhost:8000/api \
  -H "Content-Type: application/json" \
  -d '{"accion_crud":"iniciar_sesion","correo":"admin@example.com","clave":"..."}'
```
- Verificar `estado_operacion: true`
- Verificar `nuevo_token` presente (64 chars)
- Verificar `datos.operador` con datos del usuario
- Verificar cookie de sesion

### Login fallido
- Contrasena incorrecta -> `codigo_error: "credenciales_invalidas"`
- Correo inexistente -> `codigo_error: "credenciales_invalidas"`
- Cuenta suspendida -> `codigo_error: "cuenta_suspendida"`
- Rate limiting despues de N intentos -> `codigo_error: "muchos_intentos"`

### Registro de operador
- Verificar validacion: correo duplicado, contrasena debil
- Verificar campos requeridos
- Verificar que se asigna rol por defecto

## Pruebas CSRF

- Peticion sin `token_peticion` -> `codigo_error: "token_invalido"`
- Peticion con token incorrecto -> `codigo_error: "token_invalido"`
- Peticion con token expirado -> `codigo_error: "token_invalido"`
- Verificar que `nuevo_token` es diferente al anterior (rotacion)
- Verificar periodo de gracia permite token anterior

## Pruebas de autorizacion (RBAC)

Para cada accion, probar con cada rol:

| Recurso | Super Admin | Admin | Operador | Consultor |
|---------|-------------|-------|----------|-----------|
| Crear operador | OK | OK | DENY | DENY |
| Listar operadores | OK | OK | OK | OK |
| Editar operador | OK | OK | OK (self) | DENY |
| Eliminar operador | OK | DENY | DENY | DENY |
| Gestionar config | OK | DENY | DENY | DENY |
| Ver bitacora | OK | OK | DENY | OK |
| Subir archivos | OK | OK | OK | DENY |
| Gestionar PDFs | OK | OK | OK | DENY |

## Pruebas CRUD generico

Tablas permitidas en whitelist:
- `operador`, `documento_pdf`, `estadistica`, `archivo`

### Crear (accion_crud = "crear")
- Enviar datos validos -> `estado_operacion: true` con id creado
- Enviar datos invalidos -> `codigo_error: "datos_invalidos"`
- Enviar datos duplicados -> mensaje de error
- Verificar campos `_cliente` enriquecen el payload

### Leer (accion_crud = "leer")
- Listar registros (con/sin filtros)
- Obtener por ID
- Paginar resultados

### Actualizar (accion_crud = "actualizar")
- Modificar registro existente
- Verificar `fecha_actualizacion` se actualiza
- Verificar conflictos de version (optimistic locking)

### Eliminar (accion_crud = "eliminar")
- Eliminar registro existente
- Intentar eliminar inexistente
- Verificar restricciones de integridad

## Pruebas de endpoints especificos

### Personalizacion UI
- `personalizacion_obtener`: sin datos -> array vacio
- `personalizacion_guardar`: guardar cada propiedad (paleta, estilo, fondo, etc.)
- `personalizacion_obtener`: verificar datos guardados
- Validar contra whitelist (valores invalidos rechazados)
- Sanitizar `<script>` tags en valores

### Migraciones
- `ejecutar_migracion`: ejecutar una migracion especifica
- `ejecutar_migracion`: ejecutar todas pendientes
- Verificar estado de migraciones
- Verificar backup antes de migrar

### Configuracion del sistema
- `obtener_configuracion_archivos`: verificar valores por defecto
- `guardar_configuracion_archivos`: solo Super Admin
- `guardar_configuracion_archivos`: requiere CONFIRMAR en el nombre
- Verificar versionado (optimistic locking)

### Estadisticas
- CRUD completo
- Verificar consulta SQL personalizada
- Verificar tipos de visualizacion

### Generador de modulos
- Verificar que genera 7 archivos
- Verificar nombres de archivos generados
- Verificar que usa las tablas correctas

### Proyectos
- Verificar wizard de 6 pasos
- Verificar progreso via SSE

## Pruebas de rate limiting

- Enviar N peticiones rapidas a `/api`
- Verificar que despues del limite responde con `codigo_error: "muchos_intentos"`
- Verificar que el contador se resetea despues de la ventana de tiempo
- Verificar que rate_limit esta en DB (tabla `rate_limit`)

## Pruebas de OAuth

- Google OAuth: flujo completo de autenticacion
- GitHub OAuth: flujo completo de autenticacion
- Vincular cuenta existente con OAuth
- Desvincular cuenta OAuth

## Pruebas de seguridad

- Inyeccion SQL: probar caracteres especiales en campos
- XSS: probar `<script>` tags en campos de texto
- CSRF: verificar que peticiones sin token son rechazadas
- Path traversal: probar `../` en campos de archivo
- Session fixation: verificar que session ID cambia al login
- Verificar que errores no exponen informacion sensible
