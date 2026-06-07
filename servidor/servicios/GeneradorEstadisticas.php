<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

use PDOException;
use PDO;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Modelos\Estadistica;
use Exception;

class GeneradorEstadisticas
{
    private string $consultaSql = '';
    private string $tipoVisualizacion = 'tarjetas';
    private string $titulo = '';
    private string $descripcion = '';
    private array $columnas = [];
    private array $resultados = [];
    private array $aliasColumnas = [];
    private array $configVisual = [];
    private string $errorSql = '';

    private static array $coloresDefecto = ['#4f46e5', '#059669', '#d97706', '#dc2626', '#2563eb', '#7c3aed', '#0891b2', '#be123c'];
    private static array $tiposValidos = ['tarjetas', 'barras', 'pastel', 'kpi'];

    public function __construct(string $consultaSql = '')
    {
        if ($consultaSql !== '') {
            $this->establecerConsulta($consultaSql);
        }
    }

    public function establecerConsulta(string $sql): self
    {
        $this->consultaSql = $sql;
        return $this;
    }

    public function establecerTitulo(string $titulo): self
    {
        $this->titulo = $titulo;
        return $this;
    }

    public function establecerDescripcion(string $desc): self
    {
        $this->descripcion = $desc;
        return $this;
    }

    public function comoTarjetas(): self
    {
        $this->tipoVisualizacion = 'tarjetas';
        return $this;
    }
    public function comoBarras(): self
    {
        $this->tipoVisualizacion = 'barras';
        return $this;
    }
    public function comoPastel(): self
    {
        $this->tipoVisualizacion = 'pastel';
        return $this;
    }
    public function comoKpi(): self
    {
        $this->tipoVisualizacion = 'kpi';
        return $this;
    }

    public function conAlias(array $alias): self
    {
        $this->aliasColumnas = $alias;
        return $this;
    }

    public function conColores(array $colores): self
    {
        $this->configVisual['colores'] = $colores;
        return $this;
    }

    public function ejecutar(): self
    {
        $this->resultados = [];
        $this->columnas = [];
        $this->errorSql = '';

        if ($this->consultaSql === '') {
            $this->errorSql = 'No se ha definido una consulta SQL.';
            return $this;
        }

        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $consulta = $conexion->prepare($this->consultaSql);
            \assert($consulta !== false);
            $consulta->execute();
            $this->resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($this->resultados)) {
                $this->columnas = array_keys($this->resultados[0]);
            }
        } catch (PDOException $e) {
            $this->errorSql = $e->getMessage();
            RegistroAuditoria::error('GeneradorEstadisticas', 'Error al ejecutar consulta', [
                'error' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    public function desdePlantilla(int $id): self
    {
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $stmt = $conexion->prepare("SELECT titulo, descripcion, consulta_sql, tipo_visualizacion, columnas_mostrar, configuracion_visual FROM estadistica WHERE id_estadistica = :id LIMIT 1");
            \assert($stmt !== false);
            $stmt->execute([':id' => $id]);
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($plantilla) {
                $this->establecerTitulo($plantilla['titulo']);
                $this->establecerDescripcion($plantilla['descripcion'] ?? '');
                $this->establecerConsulta($plantilla['consulta_sql']);
                $this->tipoVisualizacion = in_array($plantilla['tipo_visualizacion'], self::$tiposValidos, true)
                    ? $plantilla['tipo_visualizacion'] : 'tarjetas';

                if (!empty($plantilla['columnas_mostrar'])) {
                    $decodificado = json_decode($plantilla['columnas_mostrar'], true);
                    if (is_array($decodificado)) {
                        $this->aliasColumnas = $decodificado;
                    }
                }
                if (!empty($plantilla['configuracion_visual'])) {
                    $decodificado = json_decode($plantilla['configuracion_visual'], true);
                    if (is_array($decodificado)) {
                        $this->configVisual = $decodificado;
                    }
                }

                $this->ejecutar();
            }
        } catch (PDOException $e) {
            $this->errorSql = $e->getMessage();
        }
        return $this;
    }

    public function obtenerResultados(): array
    {
        return $this->resultados;
    }

    public function obtenerColumnas(): array
    {
        return $this->columnas;
    }

    public function tieneError(): bool
    {
        return $this->errorSql !== '';
    }

    public function obtenerError(): string
    {
        return $this->errorSql;
    }

    private function colores(): array
    {
        return $this->configVisual['colores'] ?? self::$coloresDefecto;
    }

    private function generarContenido(): string
    {
        if ($this->errorSql !== '') {
            $html = '<article class="alerta alerta-peligro margen-inferior-normal">';
            $html .= '<p><strong>Error en la consulta SQL:</strong></p>';
            $html .= '<code>' . h($this->errorSql) . '</code>';
            $html .= '</article>';
            return $html;
        }

        if (empty($this->resultados)) {
            return '<article class="alineacion-centrada"><p class="texto-suave">La consulta no devolvio resultados.</p></article>';
        }

        $colores = $this->colores();

        switch ($this->tipoVisualizacion) {
            case 'barras':
                return $this->generarBarras($colores);
            case 'pastel':
                return $this->generarPastel($colores);
            case 'kpi':
                return $this->generarKpi($colores);
            default:
                return $this->generarTarjetas();
        }
    }

    private function generarTarjetas(): string
    {
        $html = '<div class="resultado-grid">';
        foreach ($this->resultados as $fila) {
            $html .= '<article><div class="agrupador-flexible-columnas brecha-pequena">';
            foreach ($this->columnas as $col) {
                $etiqueta = $this->aliasColumnas[$col] ?? $col;
                $valor = $fila[$col] ?? '';
                $html .= '<div>';
                $html .= '<span class="texto-xs texto-suave texto-mayuscula">' . h($etiqueta) . '</span>';
                $html .= '<p class="texto-base texto-negrita">' . h((string)$valor) . '</p>';
                $html .= '</div>';
            }
            $html .= '</div></article>';
        }
        $html .= '</div>';
        return $html;
    }

    private function generarBarras(array $colores): string
    {
        $colEtiqueta = $this->columnas[0] ?? '';
        $colValor = $this->columnas[1] ?? '';
        $valores = array_column($this->resultados, $colValor);
        $maxValor = !empty($valores) ? max($valores) : 1;

        $html = '<article>';
        foreach ($this->resultados as $indice => $fila) {
            $etiqueta = $fila[$colEtiqueta] ?? '';
            $valor = (float)($fila[$colValor] ?? 0);
            $porcentaje = $maxValor > 0 ? ($valor / $maxValor) * 100 : 0;
            $color = $colores[$indice % count($colores)];
            $formateado = $valor == (int)$valor ? number_format($valor, 0) : number_format($valor, 2);

            $html .= '<div class="barra-estadistica">';
            $html .= '<span class="barra-etiqueta">' . h($etiqueta) . '</span>';
            $html .= '<div class="barra-contenedor">';
            $html .= '<div class="barra-relleno" style="width:' . $porcentaje . '%;background:' . $color . '"></div>';
            $html .= '<span class="barra-valor">' . $formateado . '</span>';
            $html .= '</div></div>';
        }
        $html .= '</article>';
        return $html;
    }

    private function generarPastel(array $colores): string
    {
        $colEtiqueta = $this->columnas[0] ?? '';
        $colValor = $this->columnas[1] ?? '';
        $valores = array_column($this->resultados, $colValor);
        $total = array_sum($valores);

        $gradiente = '';
        $acumulado = 0;
        foreach ($this->resultados as $indice => $fila) {
            $valor = (float)($fila[$colValor] ?? 0);
            $porcentaje = $total > 0 ? ($valor / $total) * 100 : 0;
            $color = $colores[$indice % count($colores)];
            $gradiente .= $color . ' ' . $acumulado . '% ' . $acumulado . '%, ';
            $acumulado += $porcentaje;
            $gradiente .= $color . ' ' . $acumulado . '%';
            if ($indice < count($this->resultados) - 1) {
                $gradiente .= ', ';
            }
        }

        $html = '<div class="grafico-pastel" style="background: conic-gradient(' . $gradiente . ')"></div>';
        $html .= '<div class="leyenda-pastel">';
        foreach ($this->resultados as $indice => $fila) {
            $etiqueta = $fila[$colEtiqueta] ?? '';
            $valor = (float)($fila[$colValor] ?? 0);
            $porcentaje = $total > 0 ? round(($valor / $total) * 100, 1) : 0;
            $color = $colores[$indice % count($colores)];
            $html .= '<span class="item-leyenda">';
            $html .= '<span class="vineta-pastel" style="background:' . $color . '"></span>';
            $html .= h($etiqueta) . ' (' . $porcentaje . '%)';
            $html .= '</span>';
        }
        $html .= '</div>';
        return $html;
    }

    private function generarKpi(array $colores): string
    {
        $colEtiqueta = $this->columnas[0] ?? '';
        $colValor = $this->columnas[1] ?? '';

        $html = '<div class="kpi-grid">';
        foreach ($this->resultados as $indice => $fila) {
            $etiqueta = $fila[$colEtiqueta] ?? '';
            $valor = $fila[$colValor] ?? '';
            $color = $colores[$indice % count($colores)];
            $html .= '<article class="alineacion-centrada">';
            $html .= '<p class="texto-3xl texto-negrita" style="color:' . $color . '">' . h($valor) . '</p>';
            $html .= '<p class="texto-pequeno texto-suave texto-mayuscula">' . h($etiqueta) . '</p>';
            $html .= '</article>';
        }
        $html .= '</div>';
        return $html;
    }

    private function clasesApariencia(): string
    {
        if (!function_exists('configUI')) {
            return 'paleta-indigo estilo-moderno';
        }
        $paleta = configUI('paleta') ?? 'indigo';
        $estilo = configUI('estilo') ?? 'moderno';
        return trim('paleta-' . $paleta . ' estilo-' . $estilo);
    }

    public function generarHtml(bool $paginaCompleta = true): string
    {
        if (!$paginaCompleta) {
            return $this->generarContenido();
        }

        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html lang="es-CR" class="' . $this->clasesApariencia() . '">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '<meta charset="UTF-8">' . "\n";
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $html .= '<title>' . h($this->titulo ?: 'Estadistica') . '</title>' . "\n";
        $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/tema.css">' . "\n";
        $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/paletas.css">' . "\n";
        $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/componentes.css">' . "\n";
        $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/estilos.css">' . "\n";
        $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/utilidades.css">' . "\n";
        $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/estadisticas.css">' . "\n";
        $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/personalizacion.css">' . "\n";
        $html .= '<script type="module" src="' . URL_BASE . '/src/js/principal.js"></script>' . "\n";
        $html .= '</head>' . "\n";
        $html .= '<body>' . "\n";
        $html .= '<main class="marco-del-sitio relleno-normal">' . "\n";

        if ($this->titulo !== '') {
            $html .= '<h1 class="margen-inferior-normal">' . h($this->titulo) . '</h1>' . "\n";
        }
        if ($this->descripcion !== '') {
            $html .= '<p class="texto-suave margen-inferior-normal">' . h($this->descripcion) . '</p>' . "\n";
        }
        $html .= $this->generarContenido();
        $html .= '</main>' . "\n";
        $html .= '</body></html>';

        return $html;
    }

    public function renderizar(): void
    {
        echo $this->generarHtml();
        exit;
    }

    public function guardar(string $rutaArchivo): bool
    {
        $html = $this->generarHtml();
        $directorio = dirname($rutaArchivo);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        return file_put_contents($rutaArchivo, $html) !== false;
    }

    public function obtenerContenido(): string
    {
        return $this->generarContenido();
    }

    public static function listarPlantillas(): array
    {
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $stmt = $conexion->query("SELECT id_estadistica, titulo, tipo_visualizacion, fecha_creacion FROM estadistica ORDER BY fecha_creacion DESC");
            \assert($stmt !== false);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public static function guardarPlantilla(string $titulo, string $consultaSql, string $tipoVisualizacion = 'tarjetas', ?string $descripcion = null, ?array $columnasMostrar = null, ?array $configuracionVisual = null, ?int $idOperador = null): ?int
    {
        try {
            $modelo = Estadistica::crear([
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'consulta_sql' => $consultaSql,
                'tipo_visualizacion' => $tipoVisualizacion,
                'columnas_mostrar' => $columnasMostrar ? json_encode($columnasMostrar) : null,
                'configuracion_visual' => $configuracionVisual ? json_encode($configuracionVisual) : null,
                'id_operador' => $idOperador,
            ]);
            return (int)$modelo->id_estadistica;
        } catch (Exception $e) {
            return null;
        }
    }
}
