<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo;

use Exception;
use PDO;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Modelos\Operador;
use ReflectionClass;
use RuntimeException;

class Modelo
{
    protected static string $tabla = '';
    protected static string $idColumna = 'id';
    protected static array $rellenable = [];
    protected static array $tipos = [];
    protected static bool $timestamps = false;

    private array $atributos = [];
    private array $cambios = [];
    private bool $existe = false;
    private array $dondePendiente = [];
    private bool $dondeResuelto = false;
    private array $seleccionColumnas = [];
    private array $eagerRelaciones = [];
    private static ?PDO $conexionGlobal = null;

    public function __construct(array $datos = [])
    {
        if (!empty($datos)) {
            $this->existe = true;
            $this->atributos = $datos;
        }
    }

    protected function creating(): void
    {
    }

    protected function created(): void
    {
    }

    protected function updating(): void
    {
    }

    protected function updated(): void
    {
    }

    protected function deleting(): void
    {
    }

    protected function deleted(): void
    {
    }

    private static function conectar(): PDO
    {
        if (self::$conexionGlobal === null) {
            self::$conexionGlobal = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        }
        return self::$conexionGlobal;
    }

    // --- CRUD basico ---

    public static function buscar(int|string $id): ?static
    {
        $bd = self::conectar();
        $tabla = self::sanitizarIdentificadorSql(static::$tabla);
        $idCol = self::sanitizarIdentificadorSql(static::$idColumna);
        $sql = $bd->prepare("SELECT * FROM {$tabla} WHERE {$idCol} = :id LIMIT 1");
        \assert($sql !== false);
        $sql->execute([':id' => $id]);
        $fila = $sql->fetch(PDO::FETCH_ASSOC);
        /** @phpstan-ignore new.static */
        return $fila ? new static($fila) : null;
    }

    public static function donde(string $columna, mixed $operador = null, mixed $valor = null): static
    {
        if ($valor === null && $operador !== null && !in_array((string)$operador, ['=', '<', '>', '<=', '>=', '<>', '!=', 'LIKE', 'NOT LIKE'], true)) {
            $valor = $operador;
            $operador = '=';
        }
        if ($valor === null && $operador === null) {
            $operador = '=';
        }
        if ($valor === null) {
            $valor = $operador;
            $operador = '=';
        }
        /** @phpstan-ignore new.static */
        $instancia = new static();
        $instancia->dondePendiente[] = [
            'tipo' => 'y',
            'columna' => self::sanitizarIdentificadorSql($columna),
            'operador' => self::sanitizarOperadorSql((string)$operador),
            'valor' => $valor,
        ];
        return $instancia;
    }

    public function oDonde(string $columna, mixed $operador = null, mixed $valor = null): static
    {
        if ($valor === null && $operador !== null && !in_array((string)$operador, ['=', '<', '>', '<=', '>=', '<>', '!=', 'LIKE', 'NOT LIKE'], true)) {
            $valor = $operador;
            $operador = '=';
        }
        if ($valor === null && $operador === null) {
            $operador = '=';
        }
        if ($valor === null) {
            $valor = $operador;
            $operador = '=';
        }
        $this->dondePendiente[] = [
            'tipo' => 'o',
            'columna' => self::sanitizarIdentificadorSql($columna),
            'operador' => self::sanitizarOperadorSql((string)$operador),
            'valor' => $valor,
        ];
        return $this;
    }

    public static function dondeEn(string $columna, array $valores): static
    {
        /** @phpstan-ignore new.static */
        $instancia = new static();
        if (empty($valores)) {
            return $instancia;
        }
        $col = self::sanitizarIdentificadorSql($columna);
        $marcadores = [];
        $inParams = [];
        foreach ($valores as $i => $v) {
            $alias = ':en_' . $col . '_' . $i;
            $marcadores[] = $alias;
            $inParams[$alias] = $v;
        }
        $instancia->dondePendiente[] = [
            'tipo' => 'y',
            'columna' => $col,
            'operador' => 'IN',
            'sql_raw' => $col . ' IN (' . implode(', ', $marcadores) . ')',
            'es_in' => true,
            'in_parametros' => $inParams,
        ];
        return $instancia;
    }

    public static function dondeNulo(string $columna): static
    {
        /** @phpstan-ignore new.static */
        $instancia = new static();
        $instancia->dondePendiente[] = [
            'tipo' => 'y',
            'sql_raw' => self::sanitizarIdentificadorSql($columna) . ' IS NULL',
            'es_raw' => true,
        ];
        return $instancia;
    }

    public static function dondeNoNulo(string $columna): static
    {
        /** @phpstan-ignore new.static */
        $instancia = new static();
        $instancia->dondePendiente[] = [
            'tipo' => 'y',
            'sql_raw' => self::sanitizarIdentificadorSql($columna) . ' IS NOT NULL',
            'es_raw' => true,
        ];
        return $instancia;
    }

    public static function seleccionar(array $columnas): static
    {
        /** @phpstan-ignore new.static */
        $instancia = new static();
        foreach ($columnas as $col) {
            $instancia->seleccionColumnas[] = self::sanitizarIdentificadorSql($col);
        }
        return $instancia;
    }

    public function ordenarPor(string $columna, string $direccion = 'ASC'): static
    {
        $this->dondePendiente['_ordenar'] = [
            self::sanitizarIdentificadorSql($columna),
            self::sanitizarDireccionOrden($direccion),
        ];
        return $this;
    }

    public function limite(int $limite): static
    {
        $this->dondePendiente['_limite'] = $limite;
        return $this;
    }

    public function saltar(int $saltar): static
    {
        $this->dondePendiente['_saltar'] = $saltar;
        return $this;
    }

    public function con(string ...$relaciones): static
    {
        $this->eagerRelaciones = array_merge($this->eagerRelaciones, $relaciones);
        return $this;
    }

    public function obtener(): array
    {
        if ($this->dondeResuelto) {
            return [];
        }

        $bd = self::conectar();
        $tabla = self::sanitizarIdentificadorSql(static::$tabla);
        $donde = $this->dondePendiente;
        $this->dondePendiente = [];
        $this->dondeResuelto = true;

        $select = $this->seleccionColumnas;
        $this->seleccionColumnas = [];
        $selectSql = empty($select) ? '*' : implode(', ', $select);

        $ordenar = $donde['_ordenar'] ?? null;
        $limite = $donde['_limite'] ?? null;
        $saltar = $donde['_saltar'] ?? null;
        unset($donde['_ordenar'], $donde['_limite'], $donde['_saltar']);

        $sql = "SELECT {$selectSql} FROM {$tabla}";
        $parametros = [];
        $condiciones = [];

        foreach ($donde as $item) {
            if (!empty($item['es_raw'])) {
                $condiciones[] = ($item['tipo'] === 'o' ? ' OR ' : ' AND ') . $item['sql_raw'];
                if (!empty($item['in_parametros'])) {
                    foreach ($item['in_parametros'] as $alias => $valor) {
                        $parametros[$alias] = $valor;
                    }
                }
                continue;
            }
            if (!empty($item['es_in'])) {
                $condiciones[] = ($item['tipo'] === 'o' ? ' OR ' : ' AND ') . $item['sql_raw'];
                foreach (($item['in_parametros'] ?? []) as $alias => $valor) {
                    $parametros[$alias] = $valor;
                }
                continue;
            }
            $col = $item['columna'];
            $op = $item['operador'];
            $alias = ':d_' . $col . '_' . count($parametros);
            $condiciones[] = ($item['tipo'] === 'o' ? ' OR ' : ' AND ') . $col . ' ' . $op . ' ' . $alias;
            $parametros[$alias] = $item['valor'];
        }

        if ($condiciones) {
            $sql .= ' WHERE 1=1 ' . implode('', $condiciones);
        }

        if ($ordenar !== null) {
            $sql .= ' ORDER BY ' . self::sanitizarIdentificadorSql($ordenar[0]) . ' ' . self::sanitizarDireccionOrden($ordenar[1]);
        }

        if ($limite !== null) {
            $sql .= ' LIMIT ' . $limite;
            if ($saltar !== null) {
                $sql .= ' OFFSET ' . $saltar;
            }
        }

        $consulta = $bd->prepare($sql);
        \assert($consulta !== false);
        $consulta->execute($parametros);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($filas as $fila) {
            /** @phpstan-ignore new.static */
            $resultado[] = new static($fila);
        }

        if ($this->eagerRelaciones) {
            $this->cargarRelaciones($resultado);
            $this->eagerRelaciones = [];
        }

        return $resultado;
    }

    private function cargarRelaciones(array $resultado): void
    {
        foreach ($this->eagerRelaciones as $relacion) {
            $metodo = 'cargar' . ucfirst($relacion);
            if (method_exists($this, $metodo)) {
                $this->$metodo($resultado);
            }
        }
    }

    protected function cargarOperador(array $resultado): void
    {
        $ids = array_filter(array_map(fn($m) => $m->id_operador ?? null, $resultado));
        if (empty($ids)) {
            return;
        }
        $ids = array_unique(array_map('intval', $ids));
        $operadores = Operador::dondeEn('id_operador', $ids)->obtener();
        $mapa = [];
        foreach ($operadores as $op) {
            $mapa[$op->id_operador] = $op;
        }
        foreach ($resultado as $modelo) {
            $modelo->atributos['_eager_operador'] = $mapa[$modelo->id_operador] ?? null;
        }
    }

    public function eagerOperador(): mixed
    {
        return $this->atributos['_eager_operador'] ?? null;
    }

    public function primero(): ?static
    {
        $this->limite(1);
        $resultados = $this->obtener();
        return !empty($resultados) ? $resultados[0] : null;
    }

    public function primeroOExcepcion(): static
    {
        $this->limite(1);
        $resultados = $this->obtener();
        if (empty($resultados)) {
            throw new RuntimeException('No se encontro el registro solicitado en ' . static::$tabla);
        }
        return $resultados[0];
    }

    public static function primeroOCrear(string $columna, mixed $valor, array $datos = []): static
    {
        $existente = self::donde($columna, (string)$valor)->primero();
        if ($existente !== null) {
            return $existente;
        }
        $datos[$columna] = $valor;
        return self::crear($datos);
    }

    public static function crearOActualizar(array $condiciones, array $datos = []): static
    {
        /** @phpstan-ignore new.static */
        $consulta = new static();
        foreach ($condiciones as $col => $val) {
            $consulta = $consulta->donde($col, (string)$val);
        }
        $existente = $consulta->primero();
        if ($existente !== null) {
            foreach ($datos as $col => $val) {
                $existente->{$col} = $val;
            }
            $existente->guardar();
            return $existente;
        }
        $mezcla = array_merge($condiciones, $datos);
        return self::crear($mezcla);
    }

    public static function todos(): array
    {
        /** @phpstan-ignore new.static */
        $instancia = new static();
        return $instancia->obtener();
    }

    public static function contar(): int
    {
        $bd = self::conectar();
        $tabla = self::sanitizarIdentificadorSql(static::$tabla);
        $sql = $bd->query("SELECT COUNT(*) FROM {$tabla}");
        \assert($sql !== false);
        return (int)$sql->fetchColumn();
    }

    public function contarDonde(): int
    {
        if ($this->dondeResuelto) {
            return 0;
        }

        $bd = self::conectar();
        $tabla = self::sanitizarIdentificadorSql(static::$tabla);
        $donde = $this->dondePendiente;
        $this->dondePendiente = [];
        $this->dondeResuelto = true;

        unset($donde['_ordenar'], $donde['_limite'], $donde['_saltar']);

        if (empty($donde)) {
            $sql = $bd->query("SELECT COUNT(*) FROM {$tabla}");
            \assert($sql !== false);
            return (int)$sql->fetchColumn();
        }

        $parametros = [];
        $condiciones = [];
        foreach ($donde as $item) {
            if (!empty($item['es_raw'])) {
                $condiciones[] = ($item['tipo'] === 'o' ? ' OR ' : ' AND ') . $item['sql_raw'];
                continue;
            }
            if (!empty($item['es_in'])) {
                $condiciones[] = ($item['tipo'] === 'o' ? ' OR ' : ' AND ') . $item['sql_raw'];
                foreach (($item['in_parametros'] ?? []) as $alias => $valor) {
                    $parametros[$alias] = $valor;
                }
                continue;
            }
            $col = $item['columna'];
            $op = $item['operador'];
            $alias = ':c_' . $col . '_' . count($parametros);
            $condiciones[] = ($item['tipo'] === 'o' ? ' OR ' : ' AND ') . $col . ' ' . $op . ' ' . $alias;
            $parametros[$alias] = $item['valor'];
        }

        $sql = "SELECT COUNT(*) FROM {$tabla} WHERE 1=1 " . implode('', $condiciones);
        $stmt = $bd->prepare($sql);
        \assert($stmt !== false);
        $stmt->execute($parametros);
        return (int)$stmt->fetchColumn();
    }

    public static function sumar(string $columna): int
    {
        $bd = self::conectar();
        $col = self::sanitizarIdentificadorSql($columna);
        $sql = $bd->query("SELECT COALESCE(SUM({$col}), 0) FROM " . self::sanitizarIdentificadorSql(static::$tabla));
        \assert($sql !== false);
        return (int)$sql->fetchColumn();
    }

    public static function promediar(string $columna): float
    {
        $bd = self::conectar();
        $col = self::sanitizarIdentificadorSql($columna);
        $sql = $bd->query("SELECT COALESCE(AVG({$col}), 0) FROM " . self::sanitizarIdentificadorSql(static::$tabla));
        \assert($sql !== false);
        $val = $sql->fetchColumn();
        return (float)$val;
    }

    public static function minimo(string $columna): int
    {
        $bd = self::conectar();
        $col = self::sanitizarIdentificadorSql($columna);
        $sql = $bd->query("SELECT MIN({$col}) FROM " . self::sanitizarIdentificadorSql(static::$tabla));
        \assert($sql !== false);
        return (int)($sql->fetchColumn() ?? 0);
    }

    public static function maximo(string $columna): int
    {
        $bd = self::conectar();
        $col = self::sanitizarIdentificadorSql($columna);
        $sql = $bd->query("SELECT MAX({$col}) FROM " . self::sanitizarIdentificadorSql(static::$tabla));
        \assert($sql !== false);
        return (int)($sql->fetchColumn() ?? 0);
    }

    public static function crear(array $datos): static
    {
        /** @phpstan-ignore new.static */
        $modelo = new static();
        $modelo->llenar($datos);
        $modelo->guardar();
        return $modelo;
    }

    public function guardar(): bool
    {
        $bd = self::conectar();
        $tabla = self::sanitizarIdentificadorSql(static::$tabla);
        $idCol = self::sanitizarIdentificadorSql(static::$idColumna);

        $datos = $this->cambios;
        if (empty($datos)) {
            $datos = $this->atributos;
        }

        if ($this->existe) {
            $this->updating();
            $idValor = $this->atributos[$idCol] ?? null;
            if (static::$timestamps) {
                $datos['fecha_actualizacion'] = date('Y-m-d H:i:s');
            }
            unset($datos[$idCol]);

            $partes = [];
            $parametros = [];
            foreach ($datos as $col => $val) {
                $colLimpia = self::sanitizarIdentificadorSql($col);
                $alias = ':act_' . $colLimpia;
                $partes[] = "{$colLimpia} = {$alias}";
                $parametros[$alias] = $val;
            }
            $parametros[':id_valor'] = $idValor;

            $sql = "UPDATE {$tabla} SET " . implode(', ', $partes) . " WHERE {$idCol} = :id_valor";
            $stmt = $bd->prepare($sql);
            \assert($stmt !== false);
            $stmt->execute($parametros);
            $this->cambios = [];
            $this->updated();
        } else {
            $this->creating();
            if (static::$timestamps) {
                $datos['fecha_creacion'] = date('Y-m-d H:i:s');
            }
            $columnas = [];
            $aliases = [];
            $parametros = [];
            foreach ($datos as $col => $val) {
                $colLimpia = self::sanitizarIdentificadorSql($col);
                $alias = ':' . $colLimpia;
                $columnas[] = $colLimpia;
                $aliases[] = $alias;
                $parametros[$alias] = $val;
            }
            $sql = "INSERT INTO {$tabla} (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $aliases) . ")";
            $stmt = $bd->prepare($sql);
            \assert($stmt !== false);
            $stmt->execute($parametros);
            $this->atributos[$idCol] = (int)$bd->lastInsertId();
            $this->existe = true;
            $this->cambios = [];
            $this->created();
        }

        return true;
    }

    public function eliminar(): bool
    {
        if (!$this->existe) {
            return false;
        }
        $this->deleting();
        $bd = self::conectar();
        $tabla = self::sanitizarIdentificadorSql(static::$tabla);
        $idCol = self::sanitizarIdentificadorSql(static::$idColumna);
        $idValor = $this->atributos[$idCol];
        $sql = $bd->prepare("DELETE FROM {$tabla} WHERE {$idCol} = :id");
        \assert($sql !== false);
        $resultado = $sql->execute([':id' => $idValor]);
        if ($resultado) {
            $this->deleted();
        }
        return $resultado;
    }

    // --- Paginacion ---

    public static function paginar(
        int $pagina = 1,
        int $porPagina = 10,
        array $where = [],
        string $select = '*',
        string $joins = '',
        string $groupBy = '',
        array $parametrosExtra = []
    ): array {
        $bd = self::conectar();
        $tabla = self::sanitizarIdentificadorSql(static::$tabla);

        $condiciones = [];
        $parametros = $parametrosExtra;

        foreach ($where as $cond => $val) {
            if (is_int($cond)) {
                $condiciones[] = $val;
            } elseif (str_ends_with($cond, ' LIKE') || str_ends_with($cond, ' NOT LIKE')) {
                $colLimpia = self::sanitizarIdentificadorSql(substr($cond, 0, -5));
                $operador = str_contains($cond, 'NOT LIKE') ? 'NOT LIKE' : 'LIKE';
                $alias = ':w_like_' . count($parametros);
                $condiciones[] = $colLimpia . ' ' . $operador . ' ' . $alias;
                $parametros[$alias] = $val;
            } elseif ($val === null) {
                $condiciones[] = self::sanitizarIdentificadorSql($cond) . ' IS NULL';
            } elseif (is_array($val)) {
                $colLimpia = self::sanitizarIdentificadorSql($cond);
                $parts = [];
                foreach ($val as $i => $v) {
                    $alias = ':w_' . $colLimpia . '_' . $i;
                    $parts[] = $alias;
                    $parametros[$alias] = $v;
                }
                $condiciones[] = $colLimpia . ' IN (' . implode(',', $parts) . ')';
            } else {
                $colLimpia = self::sanitizarIdentificadorSql($cond);
                $alias = ':w_' . $colLimpia;
                $condiciones[] = $colLimpia . ' = ' . $alias;
                $parametros[$alias] = $val;
            }
        }

        $clausulaWhere = !empty($condiciones) ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $sqlTotal = "SELECT COUNT(*) FROM {$tabla} {$joins} {$clausulaWhere}";
        $stmtTotal = $bd->prepare($sqlTotal);
        \assert($stmtTotal !== false);
        $stmtTotal->execute($parametros);
        $total = (int)$stmtTotal->fetchColumn();

        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }
        $inicio = ($pagina - 1) * $porPagina;

        $sql = "SELECT {$select} FROM {$tabla} {$joins} {$clausulaWhere} {$groupBy} ORDER BY " . self::sanitizarIdentificadorSql(static::$idColumna) . " DESC LIMIT :limite OFFSET :inicio";
        $consulta = $bd->prepare($sql);
        \assert($consulta !== false);
        foreach ($parametros as $clave => $valor) {
            $consulta->bindValue($clave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $consulta->bindValue(':inicio', $inicio, PDO::PARAM_INT);
        $consulta->execute();

        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $resultado = [];
        foreach ($filas as $fila) {
            /** @phpstan-ignore new.static */
            $resultado[] = new static($fila);
        }

        return [
            'datos' => $resultado,
            'total' => $total,
            'pagina' => $pagina,
            'total_paginas' => $totalPaginas,
            'por_pagina' => $porPagina,
            'inicio' => $inicio + 1,
            'fin' => min($inicio + $porPagina, $total),
        ];
    }

    // --- Accesores ---

    public function llenar(array $datos): static
    {
        foreach ($datos as $col => $val) {
            if (in_array($col, static::$rellenable, true) || empty(static::$rellenable)) {
                $this->{$col} = $val;
            }
        }
        return $this;
    }

    public function __get(string $clave): mixed
    {
        if (array_key_exists($clave, $this->atributos)) {
            $valor = $this->atributos[$clave];
            if (isset(static::$tipos[$clave])) {
                return match (static::$tipos[$clave]) {
                    'int' => (int)$valor,
                    'float' => (float)$valor,
                    'bool' => (bool)$valor,
                    'json' => is_string($valor) ? json_decode($valor, true) : $valor,
                    default => $valor,
                };
            }
            return $valor;
        }
        return null;
    }

    public function __set(string $clave, mixed $valor): void
    {
        $this->atributos[$clave] = $valor;
        $this->cambios[$clave] = $valor;
    }

    public function __isset(string $clave): bool
    {
        return isset($this->atributos[$clave]);
    }

    public function aArreglo(): array
    {
        return $this->atributos;
    }

    // --- Relaciones simples ---

    public function perteneceA(string $claseRelacionada, ?string $claveForanea = null, ?string $claveLocal = null): ?Modelo
    {
        $reflection = new ReflectionClass($claseRelacionada); /** @phpstan-ignore argument.type */
        $fk = $claveForanea ?: strtolower($reflection->getShortName()) . '_id';
        $pk = $claveLocal ?: static::$idColumna;
        $valorFk = $this->{$fk};
        return $claseRelacionada::donde($pk, $valorFk)->primero();
    }

    public function tieneMuchos(string $claseRelacionada, ?string $claveForanea = null, ?string $claveLocal = null): array
    {
        $fk = $claveForanea ?: (new ReflectionClass(static::class))->getShortName() . '_id';
        $pk = $claveLocal ?: static::$idColumna;
        $valorPk = $this->{$pk};
        return $claseRelacionada::donde($fk, $valorPk)->obtener();
    }

    // --- Sanitizacion ---

    private static function sanitizarIdentificadorSql(string $identificador): string
    {
        $limpio = preg_replace('/[^a-zA-Z0-9_]/', '', $identificador);
        if ($limpio === '' || is_numeric(substr($limpio, 0, 1))) {
            return 'id';
        }
        return $limpio;
    }

    private static function sanitizarOperadorSql(string $operador): string
    {
        $permitidos = ['=', '<', '>', '<=', '>=', '<>', '!=', 'LIKE', 'NOT LIKE'];
        $op = trim(strtoupper($operador));
        return in_array($op, $permitidos, true) ? $op : '=';
    }

    private static function sanitizarDireccionOrden(string $direccion): string
    {
        $d = strtoupper(trim($direccion));
        return in_array($d, ['ASC', 'DESC'], true) ? $d : 'ASC';
    }
}
