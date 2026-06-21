# API Autenticación

Tipo: endpoint API
Namespace: LiteFramework\Api\Controladores
Ruta: servidor/api/controladores/AutenticacionApiControlador.php

## Descripción

Endpoints de autenticación vía POST /api. Maneja inicio y cierre de sesión con validación CSRF, rate limiting, y fingerprint de sesión.

## Acciones

### iniciar_sesion
- Payload: `{ "token_peticion": "...", "accion_crud": "iniciar_sesion", "correo": "...", "clave": "..." }`
- Response éxito: `{ "estado_operacion": true, "datos": { "id_operador": N, "nombre_completo": "...", "correo_electronico": "...", "id_rol": N, "nombre_rol": "...", "redireccion": "/inicio" }, "nuevo_token": "..." }`
- Response error: `{ "estado_operacion": false, "codigo_error": "credenciales_invalidas" | "cuenta_suspendida" | "muchos_intentos" | "token_invalido" }`

### cerrar_sesion
- Payload: `{ "token_peticion": "...", "accion_crud": "cerrar_sesion" }`
- Response: `{ "estado_operacion": true, "datos": { "redireccion": "/" }, "nuevo_token": "..." }`

## Reglas

- CSRF obligatorio en toda petición (header `X-CSRF-Token` o body `token_peticion`)
- Rate limiting: 5 intentos, 15 min bloqueo por `clave_hash + time window`
- Fingerprint: SHA-256(subred IP + User-Agent). Si cambia → sesión invalidada
- Token rotado post-validación con gracia de 60s para el anterior
- Sesión: cookie HttpOnly + SameSite=Strict + session_regenerate_id post-login
- Super Admin por defecto: `desarrolloia@gmail.com`, rol 1, ID 482
