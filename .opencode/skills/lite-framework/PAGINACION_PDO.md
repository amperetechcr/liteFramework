## Paginación con PDO Directo

### Problema

El query builder del ORM (`::todos()->ordenarPor()->limite()->saltar()->obtener()`) puede fallar con **error 500** por estado estático compartido (`self::$dondePendiente`) que acumula condiciones entre operaciones.

### Solución

Siempre usar consultas PDO directas para paginación.

### Patrón correcto

```php
$con = ConexionBaseDatos::obtenerInstancia()->obtenerConector();

$total = (int)$con->query("SELECT COUNT(*) FROM tabla")->fetchColumn();
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 20;
$offset = ($pagina - 1) * $porPagina;

$paginador = Paginador::crear($total, $porPagina);

$stmt = $con->prepare("SELECT * FROM tabla ORDER BY fecha DESC LIMIT :lim OFFSET :off");
$stmt->bindValue(':lim', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultados = [];
foreach ($filas as $f) $resultados[] = new MiModelo($f);
```

### Cuándo SÍ usar ORM chain

- Consultas simples: `MiModelo::todos()`, `MiModelo::buscar($id)`, `MiModelo::donde('campo','valor')->obtener()`
- Crear/actualizar/eliminar registros

### Cuándo NO usar ORM chain (usar PDO directo)

- Paginación con `limite()` + `saltar()`
- Queries complejos con múltiples condiciones
- Cualquier consulta que falle silenciosamente con 500

**Síntoma:** Consola muestra `Failed to load resource: 500`, pero `trazabilidad.log` no registra el error.
