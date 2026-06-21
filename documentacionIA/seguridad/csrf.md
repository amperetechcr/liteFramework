# CSRF — Protección contra falsificación

Tipo: servicio de seguridad
Namespace: LiteFramework\Seguridad
Ruta: servidor/seguridad/ValidadorCSRF.php

## Descripción

Protección contra Cross-Site Request Forgery. Cada petición POST requiere un token CSRF de 64 caracteres que se valida y rota post-uso. El token anterior tiene una gracia de 60 segundos.

## Inputs/Parámetros

- Token vía header `X-CSRF-Token` o campo body `token_peticion`
- El token se almacena en `$_SESSION['csrf_token']` con timestamp de expiración

## Outputs/Retorno

- `SeguridadServidor::validarTokenAntiFalsificacion($token)`: bool — true si el token es válido (actual o anterior con gracia)
- `SeguridadServidor::generarTokenAntiFalsificacion()`: string — nuevo token de 64 caracteres hex

## Reglas

- CSRF obligatorio en CADA POST (sin excepción)
- Token de 64 caracteres generado con `bin2hex(random_bytes(32))`
- Rotación post-validación: se genera nuevo token inmediatamente después de validar
- Gracia de 60s para el token anterior (`csrf_token_anterior` + `csrf_token_anterior_expira`)
- El meta tag `<meta name="csrf-token" content="...">` se actualiza en cada respuesta HTML
- En API: el `nuevo_token` se incluye en la respuesta JSON para que el cliente lo use en la siguiente petición
- Si el token expiró o es inválido → respuesta 403 con `codigo_error: "token_invalido"`
- CSRF se audita como nivel SEGURIDAD en bitácora
