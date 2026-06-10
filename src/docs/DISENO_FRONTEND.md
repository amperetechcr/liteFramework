# Diseño Frontend — liteFramework

## Arquitectura CSS (15 archivos)

**Orden de carga (en `encabezado.php`):**
`tema → paletas → maquetacion → componentes → modales → subirArchivos → generadorPdf → estadisticas → documentacion → apariencia → estilos → utilidades → personalizacion`

| Archivo | Propósito |
|---------|-----------|
| `tema.css` | Valores iniciales, paleta-indigo, variables CSS |
| `paletas.css` | Todas las paletas: azul, rosa, esmeralda, ambar, violeta, cereza, cielo, pizarra |
| `maquetacion.css` | Grid, flexbox, rejillas, layout general |
| `componentes.css` | Tarjetas, botones, formularios, tablas, paginación |
| `modales.css` | Modales, notificaciones flotantes |
| `subirArchivos.css` | Explorador de archivos, upload con progreso |
| `generadorPdf.css` | Estilos para generación de PDFs |
| `estadisticas.css` | Estilos para gráficos y métricas |
| `documentacion.css` | Estilos para documentación del sistema |
| `apariencia.css` | Estilos para personalización visual |
| `estilos.css` | Estilos base del panel |
| `utilidades.css` | Clases helper (márgenes, paddings, alineación) |
| `personalizacion.css` | Fondos, texturas, fuentes, radios, sombras |

Nota: `generadorModulo.css` y `errores.css` son modulares (se cargan inline desde sus respectivos módulos).

## Sistema de Personalización UI

El usuario puede personalizar la UI via parámetros GET o desde el módulo `apariencia`:

- `paleta` — indigo, azul, esmeralda, rosa, ambar, violeta, pizarra, cereza, cielo
- `fondo` — blanco, lavanda, rosa, melon, cielo, menta, arena, lila, selva, medianoche, carmesi, bosque, marino, carbon, vino, azabache
- `estilo` — moderno, minimalista, elegante, redondeado, contraste
- `fuente` — sistema, serif, sans, mono, escritura
- `espaciado` — compacto, normal, amplio
- `tamano` — normal, pequeno, grande

Se aplican mediante clases en `<html>`: `paleta-azul fondo-medianoche fuente-sans espaciado-compacto tamano-grande`.

## Arquitectura JS (20+ archivos)

### Entry point: `src/js/principal.js`

Importa y orquesta todos los módulos al `DOMContentLoaded`:
- `ui/lite.js` — inicializa tema, paleta, estilo, fondo, textura, fuente, espaciado, tamano, radio, animacion, grosor, sombra
- `seguridad.js` — SeguridadSistema con protocolos de protección
- `api/utilidades.js` — `obtenerTokenCSRF()`, `obtenerBasePath()`, `notificar()`
- `api/ListaFiltrable.js` — Clase reusable para listas con filtros AJAX
- `ui/navegacion.js` — Navegación SPA (fetch + reemplazo de contenido)
- `ui/notificaciones.js` — NotificadorHubble (notificaciones flotantes)
- `api/formularioAutenticacion.js` — Validación login
- `api/inicioSesion.js` — Manejo formulario login
- `api/formularioCrud.js` — CRUD via API
- `api/manejoErrores.js` — Captura global de errores JS

### Módulos JS en `src/js/modulos/`:

| Archivo | Propósito |
|---------|-----------|
| `apariencia.js` | Personalización visual |
| `auditoria.js` | Bitácora de eventos |
| `configuracion.js` | Config de archivos y perfil |
| `documentacion.js` | Documentación del sistema |
| `estadisticas.js` | Métricas y gráficos |
| `generadorModulo.js` | Generador CRUD visual |
| `generadorPdf.js` | Generación de PDFs |
| `inicio.js` | Dashboard principal |
| `migraciones.js` | Gestión de migraciones |
| `operadores.js` | CRUD de operadores |
| `panelControl.js` | Panel de control |
| `subirArchivos.js` | Explorador de archivos |

### Clase ListaFiltrable

Patrón reusable para recargar listados con AJAX:
```javascript
new ListaFiltrable({
    baseUrl: '/ruta',
    containerId: 'id-contenedor',
    paginationSelector: '.paginacion',
    filtros: [{ id: 'filtro-buscar', paramName: 'buscar' }],
    busquedaId: 'filtro-buscar',
    afterRender: function() { /* callback post-AJAX */ }
});
```

## Patrón SPA (Single Page Application)

1. `navegacion.js` intercepta clicks en enlaces del menú
2. Hace `fetch(url + '?ajax=1')` al servidor
3. El servidor detecta `$_GET['ajax']` y devuelve SOLO el contenido del módulo
4. `navegacion.js` reemplaza `#contenido-principal` con el HTML recibido
5. El título de la página se actualiza desde `<div data-titulo-pagina="...">`

### En el servidor (PHP):

```php
$esAjax = isset($_GET['ajax']);
if ($esAjax) {
    echo '<div data-titulo-pagina="Título"></div>';
    // ... contenido sin layout
} else {
    require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php';
    // ... contenido con layout
    require DIRECTORIO_RAIZ . '/src/plantillas/pie.php';
}
```

## Buena Práctica: Fallback CSRF

Los módulos JS deben proveer fallback local para `obtenerTokenCSRF()` mientras carga el ES module:
```javascript
function csrfToken() {
    if (typeof window.obtenerTokenCSRF === 'function') return window.obtenerTokenCSRF();
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.value : '';
}
```
