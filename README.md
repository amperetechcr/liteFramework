# liteFramework

[![CI](https://github.com/amperetechcr/liteFramework/actions/workflows/ci.yml/badge.svg)](https://github.com/amperetechcr/liteFramework/actions/workflows/ci.yml)
[![Tests](https://img.shields.io/badge/tests-364%20passing-brightgreen)](tests/)
[![PHP](https://img.shields.io/badge/PHP-8.1%20|%208.2%20|%208.3-777BB4?logo=php)](https://php.net)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%200-brightgreen)](https://phpstan.org)
[![PSR-12](https://img.shields.io/badge/PSR--12-%E2%9C%85-blueviolet)](phpcs.xml.dist)
[![License](https://img.shields.io/badge/license-Apache%202.0-blue)](LICENSE.md)

**Framework PHP MVC** con autenticación RBAC, API unificada, ORM Active Record, migraciones versionadas, personalización de UI y generación de proyectos.  
Cero dependencias externas — sin Composer, sin npm, sin Node.

---

## Features

| Área | Capacidades |
|------|------------|
| **Arquitectura** | MVC con interceptors, ORM Active Record, enrutador con parámetros dinámicos, helpers, namespaces PSR-4 |
| **Seguridad** | CSRF rotativo con gracia 60s, RBAC granular, anti-secuestro (fingerprint), rate limiting DB, WAF, auditoría dual (BD + archivo), excepciones personalizadas, CSP/HTTPS/HSTS |
| **Base de Datos** | MySQL con fallback SQLite, migraciones versionadas con backup/restore, query builder, dialecto compatible MySQL/SQLite |
| **API** | Endpoint único `POST /api` con 15+ controladores, CRUD genérico, contrato uniforme |
| **Frontend** | 15 hojas CSS con 13 paletas, 8 estilos, 16 fondos; SPA nativo; ES modules sin bundler; adaptación táctil/ancho de banda |
| **Código** | PHP 8.1+, `declare(strict_types=1)`, namespaces PSR-4, tipado fuerte, PSR-12 |
| **Testing** | 364 tests / 768 aserciones, PHPUnit phar (sin Composer), SQLite in-memory, CI integrado |
| **Generación** | Generador de módulos CRUD (7 archivos por entidad), generador de proyectos completos (wizard web + CLI + API) |
| **Auditoría** | 50+ eventos auditados con contexto enriquecido: IP, User-Agent, sesión, datos de dispositivo, rendimiento |

---

## Arquitectura

```
index.php
  ├── autoload.php (PSR-4 + class_alias)
  ├── GestorEntorno (.env → constantes)
  ├── ManejadorErrores (error/exception/fatal handler)
  ├── rutas/web.php
  │     └── Enrutador::despachar(GET|POST|PUT|PATCH|DELETE, /ruta)
  │           ├── Interceptor (autenticación, permisos)
  │           └── Controlador → Modelo → Vista/JSON
  └── src/error.php (códigos 400|401|403|404|500|503)
```

### Namespaces

| Namespace | Directorio |
|-----------|-----------|
| `LiteFramework\Nucleo` | `servidor/nucleo/` |
| `LiteFramework\Seguridad` | `servidor/seguridad/` |
| `LiteFramework\Modelos` | `servidor/modelos/` |
| `LiteFramework\Controladores` | `servidor/controladores/` |
| `LiteFramework\Api\Controladores` | `servidor/api/controladores/` |
| `LiteFramework\Servicios` | `servidor/servicios/` |
| `LiteFramework\Middleware` | `servidor/middleware/` |
| `LiteFramework\Config` | `servidor/config/` |

---

## Requisitos

- PHP 8.1+ (extensiones: `pdo`, `pdo_mysql`, `pdo_sqlite`, `json`, `mbstring`, `fileinfo`)
- Apache 2.4+ con `mod_rewrite`, `mod_headers`
- MySQL 5.7+ / MariaDB 10.3+ (SQLite incluido como fallback)

## Instalación

```bash
# 1. Clonar
git clone https://github.com/amperetechcr/liteFramework.git
cd liteFramework

# 2. Configurar entorno
cp .env.example .env
# Editar DB_NOMBRE, DB_USUARIO, DB_CLAVE en .env

# 3. Crear BD
mysql -u root -e "CREATE DATABASE lite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Ejecutar migraciones
php servidor/migrar.php

# 5. Abrir en navegador
# http://localhost/liteFramework/
```

> Sin Composer, sin npm, sin `vendor/`. El autoloader es manual con soporte PSR-4.
> Si deseas usar Composer para el autoloading optimizado: `composer dump-autoload -o`

---

## Ejemplo rápido

```php
// rutas/web.php
$enrutador->get('/saludo/{nombre}', function(array $params) {
    echo "Hola, " . h($params['nombre']);
})->nombre('saludo');

// Generar URL
$url = Enrutador::url('saludo', ['nombre' => 'Mundo']);
// → /saludo/Mundo
```

```php
// modelo personalizado (generado automáticamente por GeneradorModulo)
class Producto extends Modelo {
    protected static string $tabla = 'producto';
    protected static array $rellenable = ['nombre', 'precio', 'stock'];

    public function categoria(): callable {
        return $this->perteneceA(Categoria::class, 'categoria_id');
    }
}

// Uso
$productos = Producto::donde('precio', '>', 100)->ordenarPor('nombre')->obtener();
$producto = Producto::buscar(42);
$producto->precio = 99.99;
$producto->guardar();
```

---

## Variables de entorno (`.env`)

| Variable | Default | Descripción |
|----------|---------|-------------|
| `APP_ENTORNO` | desarrollo | `desarrollo` o `produccion` |
| `APP_DEPURACION` | true | Mostrar trazas en errores |
| `APP_MAX_INTENTOS_ACCESO` | 5 | Intentos de login antes de bloqueo |
| `APP_BLOQUEO_MINUTOS` | 15 | Duración del bloqueo |
| `DB_ANFITRION` | localhost | Host MySQL |
| `DB_NOMBRE` | lite | Nombre BD |
| `DB_USUARIO` | root | Usuario MySQL |
| `DB_CLAVE` | (vacío) | Contraseña MySQL |
| `SESSION_INACTIVIDAD_MAXIMA` | 1800 | Timeout de inactividad (segundos) |
| `SESSION_TIEMPO_MAXIMO` | 28800 | Duración máxima de sesión |

---

## Seguridad

- **CSRF**: tokens de 64 hex, rotación automática por petición, gracia de 60s para peticiones concurrentes
- **Sesiones**: HttpOnly + SameSite=Strict + fingerprint SHA-256(subred + User-Agent)
- **Contraseñas**: `password_hash(PASSWORD_DEFAULT)` con política de 8+ chars, mayúscula, número, símbolo
- **SQL**: 100% prepared statements, identificadores sanitizados contra whitelist
- **Headers**: CSP, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, HSTS, Referrer-Policy, Permissions-Policy
- **Auditoría**: dual (BD `bitacora_sistema` + `storage/logs/trazabilidad.log`) con Trace ID único por petición
- **WAF**: bloqueo automático de herramientas maliciosas (curl, python, wget, sqlmap, nmap, burp suite, escáneres)
- **Rate limiting**: 5 intentos de login por IP/correo en ventana de 15 minutos (persistido en BD)
- **Excepciones**: jerarquía propia (`ErrorSeguridad`, `ErrorAutenticacion`, `ErrorValidacion`) con códigos HTTP

---

## API

**POST /api** con `Content-Type: application/json`

```json
{
  "token_peticion": "csrf-token",
  "accion_crud": "iniciar_sesion",
  "correo": "admin@example.com",
  "clave": "MiClave123!"
}
```

**Respuesta uniforme:**

```json
{
  "estado_operacion": true,
  "mensaje_error": null,
  "codigo_error": null,
  "nuevo_token": "nuevo-csrf-token",
  "datos": { ... }
}
```

Ver [`src/docs/API.md`](src/docs/API.md) para documentación completa de endpoints.

---

## Testing

```bash
# Ejecutar todos los tests
php tests/phpunit.phar -c tests/phpunit.xml

# O via el CLI del framework
php servidor/consola/ejecutar_pruebas.php

# Resultado: 364 tests, 768 aserciones, SQLite in-memory
```

El test suite usa SQLite in-memory (vía `TESTS_RUNNING`), sin necesidad de MySQL.
Reutiliza el autoloader, los helpers, y `ConexionBaseDatos::resetearInstancia()` del propio framework.

---

## Capturas

*(Agrega aquí capturas del panel admin, login, generador de módulos, etc.)*

---

## Comandos útiles

```bash
php servidor/migrar.php                       # Ejecutar migraciones
php servidor/migrar.php list                  # Listar migraciones pendientes
php servidor/consola/generar_modulo.php       # Generar módulo CRUD vía CLI
php servidor/consola/crear_proyecto.php       # Generar proyecto completo vía CLI
php servidor/consola/ejecutar_pruebas.php     # Ejecutar tests
php tests/phpunit.phar -c tests/phpunit.xml   # Ejecutar tests (directo)
```

---

## Licencia

Apache 2.0 — ver [LICENSE.md](LICENSE.md).
