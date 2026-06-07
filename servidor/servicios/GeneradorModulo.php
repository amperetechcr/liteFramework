<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

use LiteFramework\Nucleo\Modelo;
use LiteFramework\Nucleo\Validador;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Controladores\ModuloControlador;
use LiteFramework\Middleware\AutenticacionInterceptor;
use LiteFramework\Middleware\ApiAuthInterceptor;
use Exception;

class GeneradorModulo
{
    private static $TABLA_ANZA = '    ';
    private static $DIRS;
    private static $RAIZ_FORZADA = null;

    private static function initDirs(): void
    {
        if (isset(self::$DIRS)) {
            return;
        }
        $raiz = self::$RAIZ_FORZADA ?? (defined('DIRECTORIO_RAIZ') ? DIRECTORIO_RAIZ : (__DIR__ . '/../..'));
        self::$DIRS = [
            'raiz' => $raiz,
            'migraciones' => $raiz . '/servidor/migraciones',
            'modelos' => $raiz . '/servidor/modelos',
            'api' => $raiz . '/servidor/api/controladores',
            'modulos' => $raiz . '/src/modulos',
            'js' => $raiz . '/src/js/modulos',
            'rutas' => $raiz . '/rutas/web.php',
            'autoload' => $raiz . '/servidor/autoload.php',
        ];
    }

    public static function generar(string $claseNombre, array $campos, ?string $tabla = null): array
    {
        self::initDirs();
        $D = self::$DIRS;

        $claseNombre = ucfirst($claseNombre);
        $nombreModulo = lcfirst($claseNombre);
        if ($tabla === null) {
            $tabla = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $claseNombre));
        }
        $idColumna = 'id_' . $tabla;

        $camposParseados = [];
        foreach ($campos as $c) {
            $nombre = $c['nombre'] ?? '';
            $tipo = $c['tipo'] ?? 'string';
            $reglas = $c['reglas'] ?? '';
            $nombreCol = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $nombre));
            $camposParseados[] = [
                'nombre' => $nombre,
                'nombre_columna' => $nombreCol,
                'tipo' => $tipo,
                'reglas' => $reglas,
                'requerido' => str_contains($reglas, 'required'),
                'unico' => str_contains($reglas, 'unique'),
                'es_fk' => str_ends_with($nombreCol, '_id'),
            ];
        }

        $archivos = [];
        $errores = [];

        $pasos = [
            'migracion' => fn() => self::generarMigracion($D, $claseNombre, $tabla, $idColumna, $camposParseados),
            'modelo' => fn() => self::generarModelo($D, $claseNombre, $tabla, $idColumna, $camposParseados),
            'controlador' => fn() => self::generarControlador($D, $claseNombre, $tabla, $idColumna, $camposParseados),
            'vista' => fn() => self::generarVista($D, $claseNombre, $nombreModulo, $tabla, $idColumna, $camposParseados),
            'js' => fn() => self::generarJS($D, $claseNombre, $nombreModulo, $tabla, $idColumna, $camposParseados),
            'rutas' => fn() => self::agregarRutas($D, $claseNombre, $nombreModulo, $tabla),
            'autoload' => fn() => self::agregarAutoload($D, $claseNombre),
        ];

        foreach ($pasos as $tipo => $fn) {
            try {
                $res = $fn();
                $archivos[] = $res;
                if (!$res['exito']) {
                    $errores[] = $res['mensaje'];
                }
            } catch (Exception $e) {
                $archivos[] = ['tipo' => $tipo, 'ruta' => '', 'exito' => false, 'mensaje' => $e->getMessage()];
                $errores[] = $tipo . ': ' . $e->getMessage();
            }
        }

        return [
            'exito' => empty($errores),
            'archivos' => $archivos,
            'errores' => $errores,
            'pasos_siguientes' => [
                'Revisa la migracion generada en el modulo Migraciones',
                'Ejecuta las migraciones pendientes desde el modulo Migraciones',
                'Accede al nuevo modulo en /' . $nombreModulo,
            ],
        ];
    }

    public static function generarEn(string $raiz, string $claseNombre, array $campos, ?string $tabla = null): array
    {
        self::$RAIZ_FORZADA = $raiz;
        self::$DIRS = null;
        try {
            return self::generar($claseNombre, $campos, $tabla);
        } finally {
            self::$RAIZ_FORZADA = null;
            self::$DIRS = null;
        }
    }

    public static function parsearCamposDesdeArgs(array $camposRaw): array
    {
        $campos = [];
        foreach ($camposRaw as $campo) {
            $partes = explode(':', $campo);
            $campos[] = [
                'nombre' => $partes[0],
                'tipo' => $partes[1] ?? 'string',
                'reglas' => $partes[2] ?? '',
            ];
        }
        return $campos;
    }

    private static function proximoNumeroMigracion(string $dir): string
    {
        $nextNum = 1;
        $existing = glob($dir . '/[0-9][0-9][0-9]_*.sql');
        if (!empty($existing)) {
            rsort($existing);
            preg_match('/^(\d+)/', basename($existing[0]), $m);
            $nextNum = (int)$m[1] + 1;
        }
        return str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
    }

    // ─── 1. MIGRACION ──────────────────────────────────────────────────────────

    private static function generarMigracion(array $D, string $clase, string $tabla, string $idCol, array $campos): array
    {
        $num = self::proximoNumeroMigracion($D['migraciones']);
        $file = $D['migraciones'] . "/{$num}_crear_{$tabla}.sql";

        $mapa = [
            'string' => 'VARCHAR(255)', 'text' => 'TEXT', 'int' => 'INTEGER',
            'decimal' => 'DECIMAL(12,2)', 'bool' => 'INTEGER DEFAULT 0',
            'email' => 'VARCHAR(255)', 'date' => 'DATE', 'datetime' => 'DATETIME',
        ];

        $cols = ["{$idCol} INTEGER PRIMARY KEY AUTOINCREMENT"];
        $indices = [];

        foreach ($campos as $c) {
            $tipo = $mapa[$c['tipo']] ?? 'VARCHAR(255)';
            $extra = $c['requerido'] ? ' NOT NULL' : '';
            if ($c['unico']) {
                $extra .= ' UNIQUE';
                $indices[] = "CREATE UNIQUE INDEX IF NOT EXISTS idx_{$tabla}_{$c['nombre_columna']} ON {$tabla}({$c['nombre_columna']});";
            }
            if ($c['es_fk']) {
                $relTable = $c['nombre_columna'];
                $relTable = str_replace('_id', '', $relTable);
                $extra .= " REFERENCES {$relTable}(id_{$relTable}) ON DELETE CASCADE";
            }
            $cols[] = "{$c['nombre_columna']} {$tipo}{$extra}";
        }

        $cols[] = "fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP";
        $cols[] = "fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP";

        $colsStr = implode(",\n    ", $cols);
        $indicesStr = !empty($indices) ? "\n\n" . implode("\n", $indices) : '';

        $contenido = <<<SQL
-- Migracion {$num}: Crear tabla {$tabla}
-- Generado automaticamente por generar_modulo.php

CREATE TABLE IF NOT EXISTS {$tabla} (
    {$colsStr}
);{$indicesStr}

-- Permisos RBAC
INSERT OR IGNORE INTO permisos (clave_permiso, descripcion) VALUES
    ('{$tabla}.crear', 'Crear registros de {$tabla}'),
    ('{$tabla}.leer', 'Consultar registros de {$tabla}'),
    ('{$tabla}.actualizar', 'Modificar registros de {$tabla}'),
    ('{$tabla}.eliminar', 'Eliminar registros de {$tabla}');

INSERT OR IGNORE INTO permisos_rol (id_rol, id_permiso)
SELECT 1, id_permiso FROM permisos WHERE clave_permiso LIKE '{$tabla}.%';

SQL;
        file_put_contents($file, $contenido);
        return ['tipo' => 'Migracion', 'ruta' => 'servidor/migraciones/' . basename($file), 'exito' => true, 'mensaje' => null];
    }

    // ─── 2. MODELO ─────────────────────────────────────────────────────────────

    private static function generarModelo(array $D, string $clase, string $tabla, string $idCol, array $campos): array
    {
        $file = $D['modelos'] . "/{$clase}.php";
        $T = self::$TABLA_ANZA;

        $rellenable = array_map(fn($c) => "'" . $c['nombre_columna'] . "'", $campos);
        $rellenableStr = $T . implode(",\n{$T}{$T}", $rellenable);

        $tipos = [];
        $casts = [];
        $validaciones = [];
        foreach ($campos as $c) {
            $mapa = ['int' => "'int'", 'decimal' => "'float'", 'bool' => "'bool'"];
            $t = $mapa[$c['tipo']] ?? '';
            if ($t) {
                $tipos[] = "'" . $c['nombre_columna'] . "' => " . $t;
                $jsonT = trim($t, "'");
                $casts[] = "'" . $c['nombre_columna'] . "' => '" . $jsonT . "'";
            }
            $reglasArr = [];
            if ($c['requerido']) {
                $reglasArr[] = 'requerido';
            }
            if ($c['tipo'] === 'email') {
                $reglasArr[] = 'correo';
            }
            if ($c['tipo'] === 'int') {
                $reglasArr[] = 'entero';
            }
            if ($c['tipo'] === 'decimal') {
                $reglasArr[] = 'numero';
            }
            if ($c['unico']) {
                $reglasArr[] = "unico:{$tabla},{$c['nombre_columna']}";
            }
            if (!empty($reglasArr)) {
                $validaciones[] = "'" . $c['nombre_columna'] . "' => '" . implode('|', $reglasArr) . "'";
            }
        }

        $tiposStr = '';
        if (!empty($tipos)) {
            $tiposStr = "\n{$T}protected static \$tipos = [\n{$T}{$T}" . implode(",\n{$T}{$T}", $tipos) . ",\n{$T}];";
        }

        $castsStr = '';
        if (!empty($casts)) {
            $castsStr = "\n{$T}protected static \$casts = [\n{$T}{$T}" . implode(",\n{$T}{$T}", $casts) . ",\n{$T}];";
        }

        $validStr = '';
        if (!empty($validaciones)) {
            $validStr = "\n{$T}protected static \$reglasValidacion = [\n{$T}{$T}" . implode(",\n{$T}{$T}", $validaciones) . ",\n{$T}];";
        }

        $contenido = <<<PHP
<?php

class {$clase} extends Modelo {
    protected static \$tabla = '{$tabla}';
    protected static \$idColumna = '{$idCol}';
    protected static \$rellenable = [
{$T}{$rellenableStr},
    ];{$tiposStr}{$castsStr}{$validStr}
}

PHP;
        file_put_contents($file, $contenido);
        return ['tipo' => 'Modelo', 'ruta' => 'servidor/modelos/' . basename($file), 'exito' => true, 'mensaje' => null];
    }

    // ─── 3. CONTROLADOR API ────────────────────────────────────────────────────

    private static function generarControlador(array $D, string $clase, string $tabla, string $idCol, array $campos): array
    {
        $file = $D['api'] . "/{$clase}Controlador.php";
        $T = self::$TABLA_ANZA;
        if (!is_dir($D['api'])) {
            mkdir($D['api'], 0755, true);
        }

        $validaciones = [];
        foreach ($campos as $c) {
            $reglas = [];
            if ($c['requerido']) {
                $reglas[] = 'requerido';
            }
            if ($c['tipo'] === 'email') {
                $reglas[] = 'correo';
            }
            if ($c['tipo'] === 'int') {
                $reglas[] = 'entero';
            }
            if ($c['tipo'] === 'decimal') {
                $reglas[] = 'numero';
            }
            if ($c['unico']) {
                $reglas[] = "unico:{$tabla},{$c['nombre_columna']}";
            }
            if (!empty($reglas)) {
                $validaciones[] = "{$T}{$T}{$T}{$T}'{$c['nombre_columna']}' => '" . implode('|', $reglas) . "'";
            }
        }
        $validStr = '';
        if (!empty($validaciones)) {
            $validStr = "\n{$T}\$validador = new Validador(\$datos, [\n" . implode(",\n", $validaciones) . ",\n{$T}{$T}]);\n{$T}{$T}if (\$validador->falla()) {\n{$T}{$T}{$T}return ['estado_operacion' => false, 'mensaje_error' => 'Datos invalidos', 'codigo_error' => 'datos_invalidos', 'errores' => \$validador->errores()];\n{$T}{$T}}";
        }

        $contenido = <<<PHP
<?php

class {$clase}Controlador {

    public function listar(array \$params = []): array {
        \$pagina = (int)(\$params['pagina'] ?? 1);
        \$porPagina = (int)(\$params['por_pagina'] ?? 20);
        \$offset = (\$pagina - 1) * \$porPagina;

        try {
            \$total = {$clase}::contar();
            \$items = {$clase}::todos();
            \$items = array_slice(\$items, \$offset, \$porPagina);

            return [
                'estado_operacion' => true,
                'datos' => array_map(fn(\$i) => \$i->aArreglo(), \$items),
                'total' => \$total,
                'pagina' => \$pagina,
                'por_pagina' => \$porPagina,
                'total_paginas' => (int)ceil(\$total / \$porPagina),
            ];
        } catch (Exception \$e) {
            return ['estado_operacion' => false, 'mensaje_error' => 'Error al listar', 'codigo_error' => 'error_interno'];
        }
    }

    public function obtener(int \$id): array {
        \$item = {$clase}::buscar(\$id);
        if (!\$item) {
            return ['estado_operacion' => false, 'mensaje_error' => 'Registro no encontrado', 'codigo_error' => 'no_encontrado'];
        }
        return ['estado_operacion' => true, 'datos' => \$item->aArreglo()];
    }

    public function crear(array \$datos): array {{$validStr}

        try {
            \$item = {$clase}::crear(\$datos);
            RegistroAuditoria::auditoria('{$clase}', 'Crear', ['{$idCol}' => \$item->{\$item::\$idColumna ?? '{$idCol}'}]);
            return ['estado_operacion' => true, 'datos' => \$item->aArreglo()];
        } catch (Exception \$e) {
            return ['estado_operacion' => false, 'mensaje_error' => 'Error al crear: ' . \$e->getMessage(), 'codigo_error' => 'error_interno'];
        }
    }

    public function actualizar(int \$id, array \$datos): array {
        \$item = {$clase}::buscar(\$id);
        if (!\$item) {
            return ['estado_operacion' => false, 'mensaje_error' => 'Registro no encontrado', 'codigo_error' => 'no_encontrado'];
        }

        try {
            \$datos['fecha_actualizacion'] = date('Y-m-d H:i:s');
            \$item->llenar(\$datos)->guardar();
            RegistroAuditoria::auditoria('{$clase}', 'Actualizar', ['{$idCol}' => \$id, 'datos' => \$datos]);
            return ['estado_operacion' => true, 'datos' => \$item->aArreglo()];
        } catch (Exception \$e) {
            return ['estado_operacion' => false, 'mensaje_error' => 'Error al actualizar', 'codigo_error' => 'error_interno'];
        }
    }

    public function eliminar(int \$id): array {
        \$item = {$clase}::buscar(\$id);
        if (!\$item) {
            return ['estado_operacion' => false, 'mensaje_error' => 'Registro no encontrado', 'codigo_error' => 'no_encontrado'];
        }

        try {
            \$item->eliminar();
            RegistroAuditoria::auditoria('{$clase}', 'Eliminar', ['{$idCol}' => \$id]);
            return ['estado_operacion' => true, 'datos' => ['{$idCol}' => \$id]];
        } catch (Exception \$e) {
            return ['estado_operacion' => false, 'mensaje_error' => 'Error al eliminar', 'codigo_error' => 'error_interno'];
        }
    }
}

PHP;
        file_put_contents($file, $contenido);
        return ['tipo' => 'Controlador API', 'ruta' => 'servidor/api/controladores/' . basename($file), 'exito' => true, 'mensaje' => null];
    }

    // ─── 4. VISTA ──────────────────────────────────────────────────────────────

    private static function generarVista(array $D, string $clase, string $modulo, string $tabla, string $idCol, array $campos): array
    {
        $dir = $D['modulos'] . "/{$modulo}";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file = $dir . "/{$modulo}.php";

        $camposPhp = var_export($campos, true);

        $contenido = <<<PHP
<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

\$moduloTitulo = '{$clase}';
\$nombreModulo = '{$modulo}';
\$idColumna = '{$idCol}';
\$tabla = '{$tabla}';
\$campos = {$camposPhp};

\$permisos = \$_SESSION['matriz_permisos'] ?? [];
\$puedeCrear = in_array('{$tabla}.crear', \$permisos);
\$puedeEditar = in_array('{$tabla}.actualizar', \$permisos);
\$puedeEliminar = in_array('{$tabla}.eliminar', \$permisos);

if (\$esAjax) {
    echo '<div data-titulo-pagina="' . \$moduloTitulo . '"></div>';
}
?>
<?php if (!\$esAjax): \$tituloPagina = \$moduloTitulo; \$moduloActivo = \$nombreModulo; require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php'; endif; ?>

<div class="cabecera-panel margen-inferior-normal">
    <h1><?= h(\$moduloTitulo) ?></h1>
    <?php if (\$puedeCrear): ?>
    <button type="button" class="accion-boton variante-solida" id="btnNuevo<?= \$clase ?>">+ Nuevo</button>
    <?php endif; ?>
</div>

<table id="tabla-<?= \$tabla ?>" class="tabla-datos">
    <thead>
        <tr>
            <th>ID</th>
            <?php foreach (\$campos as \$c): ?>
            <th><?= h(ucfirst(str_replace('_', ' ', \$c['nombre']))) ?></th>
            <?php endforeach; ?>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody id="cuerpo-tabla-<?= \$tabla ?>">
        <tr><td colspan="<?= count(\$campos) + 2 ?>" class="texto-centro">Cargando...</td></tr>
    </tbody>
</table>

<div id="modal-<?= \$tabla ?>" class="modal-superposicion" role="dialog" aria-modal="true" hidden>
    <div class="modal-contenido">
        <div class="modal-cabecera">
            <h2 id="titulo-modal-<?= \$tabla ?>">Nuevo <?= \$clase ?></h2>
            <button type="button" class="modal-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <form id="form-<?= \$tabla ?>" class="formulario-modal">
            <input type="hidden" name="id" id="campo-id">
            <?php foreach (\$campos as \$c): ?>
            <div class="grupo-campo">
                <label for="campo-<?= \$c['nombre_columna'] ?>"><?= h(ucfirst(str_replace('_', ' ', \$c['nombre']))) ?></label>
                <?php if (\$c['tipo'] === 'text'): ?>
                <textarea name="<?= \$c['nombre_columna'] ?>" id="campo-<?= \$c['nombre_columna'] ?>" class="campo-entrada" <?= \$c['requerido'] ? 'required' : '' ?>></textarea>
                <?php else: ?>
                <input type="<?= \$c['tipo'] === 'int' ? 'number' : (\$c['tipo'] === 'decimal' ? 'number' : (\$c['tipo'] === 'email' ? 'email' : 'text')) ?>" name="<?= \$c['nombre_columna'] ?>" id="campo-<?= \$c['nombre_columna'] ?>" class="campo-entrada" step="<?= \$c['tipo'] === 'decimal' ? '0.01' : '' ?>" <?= \$c['requerido'] ? 'required' : '' ?>>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="agrupador-flexible-filas brecha-normal margen-superior-normal">
                <button type="submit" class="accion-boton variante-solida">Guardar</button>
                <button type="button" class="accion-boton variante-borde modal-cerrar">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script type="module" src="<?= URL_BASE ?>/src/js/modulos/<?= \$nombreModulo ?>.js"></script>

<?php if (!\$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; ?>

PHP;
        file_put_contents($file, $contenido);
        return ['tipo' => 'Vista modulo', 'ruta' => 'src/modulos/' . $modulo . '/' . $modulo . '.php', 'exito' => true, 'mensaje' => null];
    }

    // ─── 5. JS ─────────────────────────────────────────────────────────────────

    private static function generarJS(array $D, string $clase, string $modulo, string $tabla, string $idCol, array $campos): array
    {
        $file = $D['js'] . "/{$modulo}.js";
        $columnas = "'" . implode("', '", array_map(fn($c) => $c['nombre_columna'], $campos)) . "'";

        $contenido = <<<JS
import { obtenerTokenCSRF } from '/src/js/api/utilidades.js';

const TABLA = '{$tabla}';
const ID_COL = '{$idCol}';
const URL_BASE = window.location.origin + (window.__basePath || '');

function cargarLista() {
    const tbody = document.getElementById('cuerpo-tabla-{$tabla}');
    const apiUrl = URL_BASE + '/api/controlador/{$clase}/listar';

    fetch(apiUrl, {
        headers: { 'X-CSRF-Token': obtenerTokenCSRF(), 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.estado_operacion) {
            tbody.innerHTML = '<tr><td colspan="10" class="texto-centro">Error al cargar</td></tr>';
            return;
        }
        if (!res.datos || res.datos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="texto-centro">No hay registros</td></tr>';
            return;
        }
        tbody.innerHTML = res.datos.map(item => {
            const id = item[ID_COL];
            const cols = [{$columnas}].map(c => {
                const val = item[c] ?? '';
                return '<td>' + String(val).substring(0, 100) + '</td>';
            }).join('');
            return '<tr><td>' + id + '</td>' + cols + '<td class="acciones-tabla">' +
                '<button class="accion-boton variante-texto editar" data-id="' + id + '">Editar</button>' +
                '<button class="accion-boton variante-texto color-peligro eliminar" data-id="' + id + '">Eliminar</button>' +
                '</td></tr>';
        }).join('');
    })
    .catch(() => {
        tbody.innerHTML = '<tr><td colspan="10" class="texto-centro">Error de conexion</td></tr>';
    });
}

function guardar(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    const id = data.get('id');
    const payload = {};
    for (const [k, v] of data.entries()) payload[k] = v;
    delete payload.id;

    const url = id
        ? URL_BASE + '/api/controlador/{$clase}/actualizar/' + id
        : URL_BASE + '/api/controlador/{$clase}/crear';

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': obtenerTokenCSRF(), 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (res.estado_operacion) {
            document.getElementById('modal-{$tabla}').hidden = true;
            cargarLista();
            if (window.NotificadorHubble) NotificadorHubble.mostrar(id ? 'Actualizado' : 'Creado', 'exito');
        } else {
            if (window.NotificadorHubble) NotificadorHubble.mostrar(res.mensaje_error || 'Error', 'peligro');
        }
    })
    .catch(() => { if (window.NotificadorHubble) NotificadorHubble.mostrar('Error de conexion', 'peligro'); });
}

document.addEventListener('DOMContentLoaded', () => {
    cargarLista();

    document.getElementById('btnNuevo{$clase}')?.addEventListener('click', () => {
        document.getElementById('form-{$tabla}').reset();
        document.getElementById('campo-id').value = '';
        document.getElementById('titulo-modal-{$tabla}').textContent = 'Nuevo {$clase}';
        document.getElementById('modal-{$tabla}').hidden = false;
    });

    document.getElementById('form-{$tabla}').addEventListener('submit', guardar);

    document.getElementById('cuerpo-tabla-{$tabla}').addEventListener('click', e => {
        const btn = e.target.closest('button');
        if (!btn) return;

        const id = btn.dataset.id;
        if (btn.classList.contains('eliminar')) {
            window.ConfirmadorHubble.mostrar('Eliminar registro #' + id + '?').then(function(confirmado) {
                if (!confirmado) return;
                fetch(URL_BASE + '/api/controlador/{$clase}/eliminar/' + id, {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': obtenerTokenCSRF(), 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(res => {
                    if (res.estado_operacion) {
                        cargarLista();
                        if (window.NotificadorHubble) NotificadorHubble.mostrar('Eliminado', 'exito');
                    } else {
                        if (window.NotificadorHubble) NotificadorHubble.mostrar(res.mensaje_error || 'Error', 'peligro');
                    }
                });
            });
        }

        if (btn.classList.contains('editar')) {
            fetch(URL_BASE + '/api/controlador/{$clase}/obtener/' + id, {
                headers: { 'X-CSRF-Token': obtenerTokenCSRF(), 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(res => {
                if (res.estado_operacion && res.datos) {
                    document.getElementById('campo-id').value = res.datos[ID_COL];
                    [{$columnas}].forEach(c => {
                        const el = document.getElementById('campo-' + c);
                        if (el) el.value = res.datos[c] ?? '';
                    });
                    document.getElementById('titulo-modal-{$tabla}').textContent = 'Editar {$clase}';
                    document.getElementById('modal-{$tabla}').hidden = false;
                }
            });
        }
    });
});
JS;
        file_put_contents($file, $contenido);
        return ['tipo' => 'JS modulo', 'ruta' => 'src/js/modulos/' . $modulo . '.js', 'exito' => true, 'mensaje' => null];
    }

    // ─── 6. RUTAS ──────────────────────────────────────────────────────────────

    private static function agregarRutas(array $D, string $clase, string $modulo, string $tabla): array
    {
        $controlador = "{$clase}Controlador";
        $ruta = <<<RUTAS

// === Rutas para {$clase} (generado automaticamente) ===
\$enrutador->get('/{$modulo}', function() {
    (new ModuloControlador())->indice('{$modulo}');
})->interceptor(AutenticacionInterceptor::class)->nombre('{$modulo}');

\$enrutador->get('/api/controlador/{$clase}/listar', function() {
    \$ctrl = new {$controlador}();
    echo json_encode(\$ctrl->listar(\$_GET));
})->interceptor(ApiAuthInterceptor::class)->nombre('api.{$tabla}.listar');

\$enrutador->get('/api/controlador/{$clase}/obtener/{id}', function(\$id) {
    \$ctrl = new {$controlador}();
    echo json_encode(\$ctrl->obtener((int)\$id));
})->interceptor(ApiAuthInterceptor::class)->nombre('api.{$tabla}.obtener');

\$enrutador->post('/api/controlador/{$clase}/crear', function() {
    \$ctrl = new {$controlador}();
    \$datos = json_decode(file_get_contents('php://input'), true) ?: [];
    echo json_encode(\$ctrl->crear(\$datos));
})->interceptor(ApiAuthInterceptor::class)->nombre('api.{$tabla}.crear');

\$enrutador->post('/api/controlador/{$clase}/actualizar/{id}', function(\$id) {
    \$ctrl = new {$controlador}();
    \$datos = json_decode(file_get_contents('php://input'), true) ?: [];
    echo json_encode(\$ctrl->actualizar((int)\$id, \$datos));
})->interceptor(ApiAuthInterceptor::class)->nombre('api.{$tabla}.actualizar');

\$enrutador->post('/api/controlador/{$clase}/eliminar/{id}', function(\$id) {
    \$ctrl = new {$controlador}();
    echo json_encode(\$ctrl->eliminar((int)\$id));
})->interceptor(ApiAuthInterceptor::class)->nombre('api.{$tabla}.eliminar');

RUTAS;
        file_put_contents($D['rutas'], $ruta, FILE_APPEND);
        return ['tipo' => 'Rutas', 'ruta' => 'rutas/web.php', 'exito' => true, 'mensaje' => null];
    }

    // ─── 7. AUTOLOAD ───────────────────────────────────────────────────────────

    private static function agregarAutoload(array $D, string $clase): array
    {
        $controlador = "{$clase}Controlador";
        $linea = "        '{$clase}'               => __DIR__ . '/modelos/{$clase}.php',\n";
        $linea .= "        '{$controlador}'      => __DIR__ . '/api/controladores/{$controlador}.php',\n";

        $contenido = file_get_contents($D['autoload']);
        $contenido = str_replace("    ];", $linea . "    ];", $contenido);
        file_put_contents($D['autoload'], $contenido);
        return ['tipo' => 'Autoload', 'ruta' => 'servidor/autoload.php', 'exito' => true, 'mensaje' => null];
    }
}
