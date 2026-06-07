<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo;

use Exception;
use PDO;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Seguridad\RegistroAuditoria;

class Modelo
{
    protected static string $tabla = '';
    protected static string $idColumna = 'id';
    protected static array $rellenable = [];
    protected static array $tipos = [];

    private array $atributos = [];
    private array $cambios = [];
    private bool $existe = false;

    private static ?PDO $conexionGlobal = null;
    private static array $dondePendiente = [];

    public function __construct(array $datos = [])
    {
        if (!empty($datos)) {
            $this->existe = true;
            $this->atributos = $datos;
        }
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
        $sql->execute([':id' => $id]);
        $fila = $sql->fetch(PDO::FETCH_ASSOC);
        return $fila ? new static($fila) : null;
    }

    public static function donde(string $columna, ?string $operador = null, mixed $valor = null): static
    {
        if ($valor === null) {
            $valor = $operador;
            $operador = '=';
        }
        $instancia = new static();
        self::$dondePendiente[] = [
            'tipo' => 'y',
            'columna' => self::sanitizarIdentificadorSql($columna),
            'operador' => self::sanitizarOperadorSql($operador),
            'valor' => $valor,
        ];
        return $instancia;
    }

    public function oDonde(string $columna, ?string $operador = null, mixed $valor = null): static
    {
        if ($valor === null) {
            $valor = $operador;
            $operador = '=';
        }
        self::$dondePendiente[] = [
            'tipo' => 'o',
            'columna' => self::sanitizarIdentificadorSql($columna),
            'operador' => self::sanitizarOperadorSql($operador),
            'valor' => $valor,
        ];
        return $this;
    }

    public function ordenarPor(string $columna, string $direccion = 'ASC'): static
    {
        self::$dondePendiente['_ordenar'] = [
            self::sanitizarIdentificadorSql($columna),
            self::sanitizarDireccionOrden($direccion)
        ];
        return $this;
    }

    public function limite(int $limite): static
    {
        self::$dondePendiente['_limite'] = $limite;
        return $this;
    }

    public function saltar(int $saltar): static
    {
        self::$dondePendiente['_saltar'] = $saltar;
        return $this;
    }

    public function obtener(): array
    {
        $bd = self::conectar();
        $tabla = self::sanitizarIdentificadorSql(static::$tabla);
        $donde = self::$dondePendiente;
        self::$dondePendiente = [];

        $sql = "SELECT * FROM {$tabla}";
        $parametros = [];
        $condiciones = [];

        if (!empty($donde)) {
            foreach ($donde as $clave => $item) {
                if ($clave === '_ordenar' || $clave === '_limite' || $clave === '_saltar') {
                    continue;
                }
                $col = self::sanitizarIdentificadorSql($item['columna']);
                $op = self::sanitizarOperadorSql($item['operador']);
                $alias = ':donde_' . $col . '_' . count($parametros);
                $conector = $item['tipo'] === 'o' ? ' OR ' : ' AND ';
                $condiciones[] = $conector . $col . ' ' . $op . ' ' . $alias;
                $parametros[$alias] = $item['valor'];
            }
        }

        if (!empty($condiciones)) {
            $sql .= ' WHERE 1=1 ' . implode(' ', $condiciones);
        }

        if (isset($donde['_ordenar'])) {
            $colOrden = self::sanitizarIdentificadorSql($donde['_ordenar'][0]);
            $dirOrden = self::sanitizarDireccionOrden($donde['_ordenar'][1]);
            $sql .= ' ORDER BY ' . $colOrden . ' ' . $dirOrden;
        }

        if (isset($donde['_limite'])) {
            $sql .= ' LIMIT ' . $donde['_limite'];
            if (isset($donde['_saltar'])) {
                $sql .= ' OFFSET ' . $donde['_saltar'];
            }
        }

        $consulta = $bd->prepare($sql);
        $consulta->execute($parametros);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($filas as $fila) {
            $resultado[] = new static($fila);
        }
        return $resultado;
    }

    public function primero(): ?static
    {
        $this->limite(1);
        $resultados = $this->obtener();
        return !empty($resultados) ? $resultados[0] : null;
    }

    public static function todos(): array
    {
        $instancia = new static();
        return $instancia->obtener();
    }

    public static function contar(): int
    {
        $bd = self::conectar();
        $tabla = self::sanitizarIdentificadorSql(static::$tabla);
        $sql = $bd->query("SELECT COUNT(*) FROM {$tabla}");
        return (int)$sql->fetchColumn();
    }

    public static function crear(array $datos): static
    {
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
            $idValor = $this->atributos[$idCol] ?? null;
            if (in_array('fecha_actualizacion', $this->columnasTabla())) {
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
            $bd->prepare($sql)->execute($parametros);
        } else {
            if (in_array('fecha_creacion', $this->columnasTabla())) {
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
            $bd->prepare($sql)->execute($parametros);
            $this->atributos[$idCol] = (int)$bd->lastInsertId();
            $this->existe = true;
        }

        $this->cambios = [];
        return true;
    }

    public function eliminar(): bool
    {
        if (!$this->existe) {
            return false;
        }
        $bd = self::conectar();
        $tabla = self::sanitizarIdentificadorSql(static::$tabla);
        $idCol = self::sanitizarIdentificadorSql(static::$idColumna);
        $idValor = $this->atributos[$idCol];
        $sql = $bd->prepare("DELETE FROM {$tabla} WHERE {$idCol} = :id");
        return $sql->execute([':id' => $idValor]);
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

    private function columnasTabla(): array
    {
        try {
            $bd = self::conectar();
            $tabla = self::sanitizarIdentificadorSql(static::$tabla);
            $desc = $bd->query("DESCRIBE {$tabla}");
            return $desc->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (Exception $e) {
            return [];
        }
    }

    // --- Relaciones simples ---

    public function perteneceA(string $claseRelacionada, ?string $claveForanea = null, ?string $claveLocal = null): ?Modelo
    {
        $relacion = new $claseRelacionada();
        $fk = $claveForanea ?: strtolower((new ReflectionClass($relacion))->getShortName()) . '_id';
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
