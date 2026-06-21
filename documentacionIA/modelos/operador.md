# Operador

Tipo: modelo (ORM)
Namespace: LiteFramework\Modelos
Ruta: servidor/modelos/Operador.php

## Descripción

Modelo de la tabla `operador`. Gestiona usuarios del sistema: autenticación, roles, permisos, estado de cuenta. Relaciona con `Rol` vía `id_rol`.

## Inputs/Parámetros (propiedades)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id_operador` | int (PK, auto) | Identificador único |
| `id_rol` | int (FK → rbac_rol) | Rol del operador |
| `nombre_completo` | string | Nombre del usuario |
| `correo_electronico` | string (unique) | Email de acceso |
| `clave_acceso` | string | Password hash |
| `estado_cuenta` | int (0/1) | 1=activo, 0=suspendido |
| `fecha_registro` | timestamp | Creación del registro |

Rellenable: `nombre_completo`, `correo_electronico`, `clave_acceso`, `id_rol`, `estado_cuenta`

## Outputs/Retorno

- `rol()`: ?Rol — relación belongsTo con rbac_rol
- `tienePermiso($clave)`: bool — verifica permiso específico vía RBAC
- `estaActivo()`: bool — estado_cuenta === 1
- `activar()`: bool — cambia estado a activo
- `suspender()`: bool — cambia estado a suspendido
- `contarActivos()`: int — total operadores activos
- `contarSuspendidos()`: int — total operadores suspendidos
- `obtenerPerfil($id)`: ?array — perfil JOIN con rol
- `listarConFiltros(...)`: array — paginación con búsqueda por nombre/correo + filtro rol + filtro estado
