# Modelo

Tipo: clase (ORM)
Namespace: LiteFramework\Nucleo
Ruta: servidor/nucleo/Modelo.php

## Descripción

ORM Active Record propio. Cada modelo extiende `Modelo` y define `$tabla`, `$idColumna`, `$rellenable`, `$tipos`, `$timestamps`. Soporta CRUD, consultas encadenadas (`donde()`, `ordenarPor()`, `limite()`, `saltar()`), eager loading (`con()`), paginación, y relaciones simples (`perteneceA()`, `tieneMuchos()`).

## Inputs/Parámetros

Propiedades estáticas a definir en cada subclase:
- `$tabla`: string — nombre de la tabla en BD
- `$idColumna`: string — columna primary key (default: 'id')
- `$rellenable`: array — columnas asignables en masa (empty = todas)
- `$tipos`: array — casteo automático: 'int', 'float', 'bool', 'json'
- `$timestamps`: bool — gestionar automáticamente fecha_creacion/fecha_actualizacion

## Outputs/Retorno

- `buscar($id)`: ?static — registro por PK
- `donde($col, $op, $val)`: static — query builder
- `obtener()`: array<static> — ejecuta consulta y retorna modelos hidratados
- `crear($datos)`: static — inserta y retorna modelo con ID asignado
- `guardar()`: bool — INSERT o UPDATE según estado
- `eliminar()`: bool — DELETE por PK
- `paginar($pagina, $porPagina, $where)`: array con 'datos', 'total', 'pagina', 'total_paginas'

## Reglas

- NUNCA usar chain con `limite()+saltar()` para paginación (Error 500). Usar `paginar()` o PDO directo
- `declare(strict_types=1)` obligatorio
- Prepared statements SIEMPRE (SQL injection prevention)
- SQLite en tests, MySQL en producción — `DialectoBaseDatos` abstrae diferencias
- Nombres de tabla y columnas en snake_case español
- `$timestamps` true agrega `fecha_creacion` y `fecha_actualizacion` automáticos
