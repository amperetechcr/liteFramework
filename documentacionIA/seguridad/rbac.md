# RBAC — Control de Acceso

Tipo: servicio de seguridad
Namespace: LiteFramework\Seguridad
Ruta: servidor/seguridad/ControlAccesoRBAC.php

## Descripción

Sistema de control de acceso basado en roles (RBAC) con 4 roles y 22 permisos. Cada operación de escritura/lectura se valida contra la matriz rol×permiso.

## Roles

| ID | Nombre | Descripción |
|----|--------|-------------|
| 1 | Super Administrador | Acceso total al sistema (todos los permisos) |
| 2 | Administrador | Acceso administrativo (operadores, archivos, documentos, estadísticas) |
| 3 | Operador Estandar | Acceso operativo básico (leer/actualizar operador, leer archivos/documentos/estadísticas) |
| 4 | Consultor | Acceso solo lectura (leer operador, roles, bitácora) |

## Permisos (22)

| Clave | Descripción |
|-------|-------------|
| `operador.crear` | Crear operadores |
| `operador.leer` | Consultar operadores |
| `operador.actualizar` | Modificar operadores |
| `operador.eliminar` | Eliminar operadores |
| `rbac_rol.leer` | Consultar roles |
| `rbac_rol.crear` | Crear roles |
| `rbac_rol.actualizar` | Modificar roles |
| `rbac_rol.eliminar` | Eliminar roles |
| `bitacora_sistema.leer` | Consultar bitácora |
| `bitacora_sistema.crear` | Registrar eventos |
| `archivo.subir` | Subir archivos |
| `archivo.leer` | Consultar archivos |
| `archivo.eliminar` | Eliminar archivos |
| `configuracion.gestionar` | Modificar configuración global |
| `documentoPdf.crear/leer/actualizar/eliminar` | Gestión de PDFs |
| `estadistica.crear/leer/actualizar/eliminar` | Gestión de estadísticas |

## API

- `ControlAccesoRBAC::tienePermiso($clave)`: bool — verifica permiso del operador en sesión
- `ControlAccesoRBAC::requerirPermisoEstricto($clave)`: void — lanza excepción si no tiene permiso

## Reglas

- Los permisos se asignan vía tabla `permisos_rol` (FK a `rbac_rol` e `id_permiso`)
- Los roles se definen en `rbac_rol` (BD)
- Super Admin (rol 1) tiene TODOS los permisos automáticamente
- Las validaciones se hacen server-side SIEMPRE, aunque el cliente también valide
