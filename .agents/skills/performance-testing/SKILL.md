---
name: performance-testing
description: Pruebas de rendimiento para liteFramework. Medir tiempos de carga, benchmark de API, perfil de memoria, consultas SQL y Core Web Vitals.
license: Apache-2.0
---

# Performance Testing para liteFramework

## Requisitos

- PHP 8.2+ CLI
- Apache Bench (ab) o siege para pruebas de carga
- Servidor PHP: `php -S localhost:8000 -t C:\xampp\htdocs\liteFramework`

## 1. Tiempos de carga de modulos SPA

Medir tiempo de carga de cada modulo desde el servidor PHP:

```bash
# Ejemplo con curl
curl -o /dev/null -s -w "Tiempo total: %{time_total}s\n" http://localhost:8000/panel-control
curl -o /dev/null -s -w "Tiempo total: %{time_total}s\n" "http://localhost:8000/operadores?ajax=1"
```

Modulos a medir (con y sin ajax=1):
- `/ingreso` - Pagina de login
- `/panel-control` - Dashboard
- `/operadores` - Lista de operadores
- `/operadores?ajax=1` - Lista parcial SPA
- `/auditoria` - Bitacora
- `/configuracion` - Configuracion
- `/apariencia` - Personalizacion
- `/documentacion` - Documentacion
- `/generador-modulo` - Generador de modulos
- `/generador-proyecto` - Generador de proyectos
- `/migraciones` - Migraciones
- `/archivos` - Explorador de archivos

Metricas: time_total, time_connect, time_starttransfer, size_download, http_code

## 2. Benchmark de API

```bash
# Apache Bench: 100 peticiones, 10 concurrentes
ab -n 100 -c 10 \
  -T "application/json" \
  -p payload_login.json \
  http://localhost:8000/api
```

Endpoints a benchmarkear:
- `iniciar_sesion` - Autenticacion
- `crud` (leer) - Listar operadores
- `crud` (crear) - Crear operador
- `crud` (actualizar) - Actualizar operador
- `crud` (eliminar) - Eliminar operador
- `personalizacion_guardar` - Guardar personalizacion
- `generar_modulo` - Generar modulo (operacion pesada)

Metricas: Requests per second, Time per request, Transfer rate, Failed requests

## 3. Perfil de consultas SQL

Activar logging de consultas para identificar cuellos de botella:

```php
// En conexion.php o bootstrap de pruebas
$conector->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

Consultas a perfilar:
- Login: SELECT en operador + intento_acceso
- Listar operadores: SELECT con JOIN a rbac_rol
- Busqueda con filtros: SELECT con WHERE y LIKE
- Paginacion: SELECT con LIMIT/OFFSET + COUNT
- CRUD generico: INSERT/UPDATE/DELETE
- Configuracion: SELECT de configuracion_sistema
- Personalizacion: SELECT/UPDATE de operador_personalizacion
- Migraciones: SELECT de _migraciones + ejecucion SQL
- Estadisticas: consulta SQL personalizada del usuario

Verificar:
- Indices faltantes (EXPLAIN en consultas lentas)
- N+1 queries en relaciones (perteneceA, tieneMuchos)
- Carga perezosa vs carga eager
- Tamaño de resultados (columnas innecesarias)

## 4. Pruebas de base de datos

Comparar rendimiento MySQL vs SQLite:

```bash
# MySQL
php -d "TESTS_RUNNING=0" servidor/consola/ejecutar_pruebas.php

# SQLite
php -d "TESTS_RUNNING=1" servidor/consola/ejecutar_pruebas.php
```

Medir tiempo de ejecucion de tests en ambos motores.

## 5. Perfil de memoria

Medir uso de memoria en operaciones criticas:

```php
$inicio = memory_get_usage(true);
// operacion
$pico = memory_get_peak_usage(true);
error_log("[Perfil] Operacion X: pico=" . ($pico / 1024 / 1024) . "MB");
```

Puntos de medicion:
- Carga del framework (autoload, entorno, config)
- Enrutamiento y dispatch
- Consultas ORM con relaciones
- Generacion de modulos (7 archivos)
- Generacion de proyectos (wizard completo)
- Subida y procesamiento de archivos
- Carga de pagina completa con CSS/JS

## 6. Core Web Vitals

Usar el Browser Tool de OpenClaw para medir metricas reales:

```bash
openclaw browser --browser-profile testing open "http://localhost:8000/ingreso"
openclaw browser --browser-profile testing screenshot
```

Metricas a verificar:
- First Contentful Paint (FCP)
- Largest Contentful Paint (LCP)
- Cumulative Layout Shift (CLS)
- Time to Interactive (TTI)
- Total Blocking Time (TBT)

## 7. Carga de assets

Verificar cantidad y peso de assets cargados:

| Tipo | Archivos | Peso estimado |
|------|----------|---------------|
| CSS | 17 archivos | ~100-150 KB |
| JS | 28 archivos | ~150-200 KB |
| Imagenes | SVG icons en sidebar | ~5-10 KB |

Verificar:
- Orden de carga correcto (tema -> paletas -> maquetacion -> componentes -> ...)
- Carga condicional de CSS especifico de modulo
- HTTP cache headers
- Compresion (si hay)

## 8. Prueba de estrés con PHPUnit

Ejecutar tests existentes y medir tiempo total:

```bash
# Tiempo total de tests
time php tests/phpunit.phar -c tests/phpunit.xml

# Solo tests unitarios
time php tests/phpunit.phar -c tests/phpunit.xml --testsuite liteframework-casos

# Solo tests de integracion
time php tests/phpunit.phar -c tests/phpunit.xml --testsuite liteframework-integracion
```

Benchmark actual: 377 tests, 926 assertions.
