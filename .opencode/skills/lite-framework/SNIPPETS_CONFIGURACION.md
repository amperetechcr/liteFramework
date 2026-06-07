## Configuración Dinámica (.user.ini)

### Arquitectura

```
.env (defaults)
  ↓ fallback
Tabla `configuracion_sistema` (runtime, editable desde UI)
  ↓
ConfiguracionSistema::obtener('CLAVE', default) — cache 30s, optimistic locking
  ↓
GeneradorIniServidor::regenerar([...]) — regenera .user.ini atómicamente
  ↓
PHP lee .user.ini en cada request
```

### Clase `ConfiguracionSistema`

```php
$valor = ConfiguracionSistema::obtener('CLAVE', 'default');            // lectura con fallback
$res = ConfiguracionSistema::establecer('CLAVE', $valor, $version);     // optimistic locking
ConfiguracionSistema::forzarEstablecer('CLAVE', $valor);                // sin lock
ConfiguracionSistema::invalidarCache();                                  // forzar recarga
```
Retorna: `['estado'=>'ok'|'conflicto'|'error', 'version'=>N, 'valor_actual'=>...]`

### Clase `GeneradorIniServidor`

```php
$res = GeneradorIniServidor::regenerar(['memory_limit'=>512, 'post_max_size'=>50, 'upload_max_filesize'=>40, ...]);
$limites = GeneradorIniServidor::limitesActualesPHP();  // lee con ini_get()
GeneradorIniServidor::revertir();                       // restaura .user.ini.backup
```

Validación previa: `post_max_size >= upload_max_filesize`, `memory_limit >= post_max_size`, máx 2GB, máx 3600s. Escritura atómica: `.user.ini.tmp.{random}` → `rename()`. Backup automático.

### Endpoint

`accion_crud=actualizar_configuracion_archivos` — solo **rol 1 (Super Admin)**, requiere `confirmacion="CONFIRMAR"`.

### Nuevos parámetros configurables

1. Defaults en `GestorEntorno::definirValoresPorDefecto()` + `.env.example`
2. INSERT IGNORE en migración (ej: `006_nueva_config.sql`)
3. `ConfiguracionSistema::obtener('CLAVE', default)` en controladores
4. Si cambia php.ini directives: agregar a `GeneradorIniServidor::regenerar()`

### Frontend (subidas)

Usar `XMLHttpRequest` (NO fetch — no soporta `progress`). Mostrar: porcentaje, bytes, velocidad, tiempo restante. Cancelar: `xhr.abort()`. Notificaciones con `.notificacion-flotante` y `data-variante="exito|peligro|advertencia|informacion"`.
