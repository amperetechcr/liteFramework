# Enrutador

Tipo: clase
Namespace: LiteFramework\Nucleo
Ruta: servidor/nucleo/Enrutador.php

## Descripción

Sistema de enrutamiento HTTP que mapea métodos y patrones URI a acciones (closures o controladores). Soporta interceptors, named routes, grupos con prefijo hereditario, y parámetros dinámicos `{id}`.

## Inputs/Parámetros

- `$metodo`: string — GET, POST, PUT, PATCH, DELETE
- `$uri`: string — ruta solicitada, ej: `/operadores`
- `$patron`: string — patrón con placeholders, ej: `/operador/{id}`

## Outputs/Retorno

- `despachar()`: mixed — resultado del controlador/interceptor, o false si 404
- `url()`: string — URL generada desde nombre de ruta + parámetros

## Reglas

- Single entry point: `index.php` parsea la URI y llama a `$enrutador->despachar()`
- Las rutas se definen en `rutas/web.php`
- `Enrutador::registrarInstancia($enrutador)` debe llamarse después de definir todas las rutas
- Los parámetros `{id}` se pasan como argumentos tipados al closure
- Toda ruta protegida pasa por RendimientoInterceptor primero, luego AutenticacionInterceptor/ApiAuthInterceptor
- URI base se resuelve desde `dirname($_SERVER['SCRIPT_NAME'])`
