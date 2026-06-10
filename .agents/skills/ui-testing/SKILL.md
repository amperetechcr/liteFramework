---
name: ui-testing
description: Pruebas automatizadas de UI/UX para liteFramework usando el Browser Tool de OpenClaw. Verificar navegacion SPA, personalizacion visual, responsive design, flujos de usuario y accesibilidad.
license: Apache-2.0
---

# UI/UX Testing para liteFramework

## Requisitos

1. PHP dev server corriendo: `php -S localhost:8000 -t C:\xampp\htdocs\liteFramework`
2. OpenClaw Browser Tool habilitado en config
3. Base de datos MySQL o SQLite configurada

## Configuracion de OpenClaw

```json5
{
  "browser": { "enabled": true, "headless": true },
  "tools": { "profile": "coding", "alsoAllow": ["browser"] }
}
```

## 1. Pruebas de navegacion SPA

Verificar que todos los modulos carguen correctamente via SPA:

```bash
openclaw browser --browser-profile testing start
openclaw browser --browser-profile testing open "http://localhost:8000/ingreso"
openclaw browser --browser-profile testing screenshot
```

Flujo completo de login + navegacion:

1. Abrir `/ingreso`, verificar formulario de login visible
2. Completar credenciales y enviar
3. Verificar redireccion a `/inicio` o `/panel-control`
4. Navegar a cada modulo via clicks en la barra lateral
5. Verificar que `#contenido-principal` se actualiza sin recarga completa
6. Verificar que la URL cambia via history.pushState
7. Probar navegacion hacia atras/adelante (popstate)
8. Probar sidebar responsive (abrir/cerrar en mobile)

Modulos a verificar:
- `/panel-control` - Dashboard principal
- `/operadores` - CRUD operadores con filtros
- `/auditoria` - Bitacora con filtros
- `/configuracion` - Configuracion del sistema
- `/apariencia` - Personalizacion UI
- `/documentacion` - Documentacion con busqueda
- `/generador-modulo` - Generador de modulos CRUD
- `/generador-proyecto` - Generador de proyectos (wizard 6 pasos)
- `/generador-pdf` - Documentos PDF
- `/estadisticas` - Estadisticas
- `/migraciones` - Gestor de migraciones
- `/archivos` - Explorador de archivos

## 2. Pruebas de personalizacion visual

Probar combinaciones de paletas, estilos, fondos y fuentes:

```bash
# Probar paleta via GET param
openclaw browser --browser-profile testing open "http://localhost:8000/ingreso?paleta=esmeralda"
openclaw browser --browser-profile testing screenshot --format ai
```

Combinaciones criticas a probar:
- Cada una de las 13 paletas con cada uno de los 8 estilos
- Fondos claro y oscuro
- Fuentes: sistema, serif, mono, humanista, geometrica, decorativa, redondeada
- Espaciados: compacto, normal, amplio, holgado, comodo
- Verificar que `--color-marca`, `--color-marca-hover`, `--color-marca-claro` se actualizan
- Verificar que `@media (prefers-color-scheme: dark)` funciona
- Verificar que `.forzar-iluminacion-oscura` y `.forzar-iluminacion-clara` funcionan

## 3. Pruebas responsive

Probar en los 4 breakpoints del sidebar:

| Breakpoint | Comportamiento sidebar |
|------------|----------------------|
| >=1025px | Sidebar fijo 240px |
| 769-1024px | Collapsado 56px con hover expand |
| 601-768px | Off-canvas overlay (toggle JS) |
| <=600px | Off-canvas fullscreen |

Para cada breakpoint:
1. Verificar que el sidebar se comporta correctamente
2. Verificar que las tablas son responsivas (overflow-x)
3. Verificar que los formularios no se rompen
4. Verificar que las tarjetas se reordenan (grid auto-fit)
5. Verificar que el header movil se muestra correctamente

## 4. Pruebas de flujos de usuario

### Flujo de autenticacion
1. Acceder a ruta protegida sin sesion -> redireccion a `/ingreso`
2. Login con credenciales validas -> redireccion a modulo inicio
3. Login con contrasena incorrecta -> mensaje de error
4. Verificar rotacion de CSRF token en respuesta
5. Cerrar sesion -> redireccion a `/ingreso`
6. Verificar que session fingerprint bloquea acceso desde otro User-Agent

### Flujo CRUD operadores
1. Navegar a `/operadores`, verificar lista cargada
2. Usar filtros (busqueda, rol, estado)
3. Abrir modal de nuevo operador, completar formulario
4. Enviar, verificar operador creado
5. Editar operador existente
6. Suspender/activar operador
7. Verificar paginacion

### Flujo de modulo generator
1. Navegar a `/generador-modulo`
2. Configurar campos: texto, numero, fecha, correo, archivo, etc.
3. Verificar preview de tipos de dato inferidos
4. Generar modulo, verificar archivos creados

### Flujo de project generator (wizard)
1. Navegar a `/generador-proyecto`
2. Paso 1: configurar nombre del proyecto, descripcion
3. Paso 2: configurar base de datos
4. Paso 3: seleccionar modulos
5. Paso 4: disenar entidades
6. Paso 5: personalizar apariencia
7. Paso 6: configurar admin
8. Generar proyecto

### Flujo de archivos
1. Navegar a `/archivos`
2. Subir archivo (drag-drop y click)
3. Verificar barra de progreso con velocidad y ETA
4. Navegar carpetas
5. Eliminar archivo con confirmacion
6. Verificar cuota de almacenamiento

## 5. Pruebas de accesibilidad

- Verificar `:focus-visible` en todos los elementos interactivos
- Verificar `aria-label` en iconos y botones
- Verificar `role="alert"` en notificaciones y errores
- Verificar skip link (`#salto-contenido`)
- Verificar contraste de color en todas las paletas (WCAG AA 4.5:1)
- Verificar `prefers-reduced-motion` desactiva animaciones
- Verificar `aria-live="polite"` en el contenedor de notificaciones
- Verificar navegacion por teclado (Tab, Enter, Escape)

## 6. Pruebas de notificaciones

- Verificar que `NotificadorHubble` muestra notificaciones
- Verificar maximo 5 notificaciones visibles
- Verificar auto-dismiss por tipo (exito=3s, peligro=5s, advertencia=6s)
- Verificar swipe-to-dismiss
- Verificar pausa al hacer hover
- Verificar notificaciones con sugerencias

## 7. Pruebas de offline

1. Desconectar red (simular offline)
2. Verificar banner offline visible
3. Verificar cola de reintentos (`COLA_REINTENTOS`)
4. Reconectar, verificar reintentos enviados
5. Verificar que `.offline` clase CSS se aplica/remueve

## 8. Pruebas de error

- 404: navegar a ruta inexistente
- 403: acceder a modulo sin permisos
- 500: forzar error interno
- 503: activar modo mantenimiento
- Verificar mensajes de error amigables
- Verificar diagnostico de errores en `DiagnosticoError`
