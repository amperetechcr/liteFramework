# API CRUD Genérico

Tipo: endpoint API
Namespace: LiteFramework\Api\Controladores
Ruta: servidor/api/controladores/CrudApiControlador.php

## Descripción

Endpoint POST /api que ejecuta operaciones CRUD sobre entidades permitidas. Todas las peticiones pasan por `procesarPeticionPost.php` que valida CSRF, autenticación, y enruta según `accion_crud`.

## Entidades permitidas (whitelist)

- `operador` — requiere permiso `operador.*`
- `rbac_rol` — requiere permiso `rbac_rol.*`
- `bitacora_sistema` — requiere permiso `bitacora_sistema.*`
- `estadistica` — requiere permiso `estadistica.*`

## Acciones

### leer
- Params: `{ "accion_crud": "leer", "tabla_destino": "operador", "limite": 50, "inicio": 0, "filtros": {...}, "ordenar_por": "...", "direccion_orden": "DESC" }`
- Response: `{ "estado_operacion": true, "datos": [...], "total": N, "pagina": N, "por_pagina": N, "total_paginas": N }`

### crear
- Params: `{ "accion_crud": "crear", "tabla_destino": "operador", "nombre_completo": "...", ... }`
- Response: `{ "estado_operacion": true, "datos": { "id_afectado": N } }`

### actualizar
- Params: `{ "accion_crud": "actualizar", "tabla_destino": "operador", "id_entidad": N, "nombre_completo": "...", ... }`
- o con `"datos": { "nombre_completo": "..." }` para auditoría detallada
- Response: `{ "estado_operacion": true, "datos": { "id_afectado": N } }`

### eliminar
- Params: `{ "accion_crud": "eliminar", "tabla_destino": "operador", "id_entidad": N }`
- Response: `{ "estado_operacion": true, "datos": { "id_afectado": N } }`

### buscar
- Params: `{ "accion_crud": "buscar", "tabla_destino": "operador", "termino_busqueda": "..." }`
- Busca en TODAS las columnas de la entidad (LIKE %termino%)
- Response: `{ "estado_operacion": true, "datos": [...] }`

## Reglas

- Toda respuesta incluye: `estado_operacion`, `mensaje_error`, `codigo_error`, `nuevo_token`, `datos`, `redireccion`
- Códigos de error: `no_autenticado`, `sin_permiso`, `token_invalido`, `datos_invalidos`
- Límite de registros: 1-200 (default 50)
- Las columnas se validan contra el esquema real de la tabla vía DESCRIBE
- Cada operación se audita en `bitacora_sistema`
- El payload DEBE tener `token_peticion` o header `X-CSRF-Token`
- Autenticación vía ApiAuthInterceptor para endpoints directos, o vía procesarPeticionPost.php para POST /api
