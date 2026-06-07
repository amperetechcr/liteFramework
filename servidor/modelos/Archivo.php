<?php

declare(strict_types=1);

namespace LiteFramework\Modelos;

use PDO;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Nucleo\Modelo;

/**
 * @property int $id_archivo
 * @property string $nombre_original
 * @property string $nombre_generado
 * @property string $ruta_archivo
 * @property string $tipo_mime
 * @property int $tamano_bytes
 * @property string $extension
 * @property string $categoria
 * @property int $id_operador
 * @property int|null $id_carpeta
 */
class Archivo extends Modelo
{
    protected static string $tabla = 'archivo';
    protected static string $idColumna = 'id_archivo';
    protected static array $rellenable = ['nombre_original', 'nombre_generado', 'ruta_archivo',
        'tipo_mime', 'tamano_bytes', 'extension', 'categoria', 'id_operador', 'id_carpeta'];

    public function operador(): ?Operador
    {
        $idOp = $this->id_operador;
        return $idOp ? Operador::buscar($idOp) : null;
    }

    public function tamanoFormateado(): string
    {
        $bytes = (int)$this->tamano_bytes;
        if ($bytes <= 0) {
            return '0 B';
        }
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = min(floor(log($bytes) / log(1024)), count($unidades) - 1);
        return round($bytes / pow(1024, $i), 2) . ' ' . $unidades[$i];
    }

    public function esImagen(): bool
    {
        $imgMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp', 'image/tiff'];
        return in_array($this->tipo_mime, $imgMimes, true);
    }

    public function esDocumento(): bool
    {
        $docMimes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'text/csv'];
        return in_array($this->tipo_mime, $docMimes, true);
    }

    public function enlaceDescarga(): string
    {
        return URL_BASE . '/archivos/descargar/' . $this->id_archivo;
    }

    public function rutaMostrar(): string
    {
        $base = DIRECTORIO_RAIZ . '/storage/archivos/';
        $ruta = $this->ruta_archivo ?? '';
        if (str_starts_with($ruta, $base)) {
            $relativa = substr($ruta, strlen($base));
            $relativa = str_replace('\\', '/', $relativa);
            $dir = dirname($relativa);
            return $dir === '.' ? '' : $relativa;
        }
        return $ruta;
    }

    public static function sumaTamanoBytes(): int
    {
        $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $stmt = $bd->query("SELECT COALESCE(SUM(tamano_bytes), 0) FROM archivo");
        \assert($stmt !== false);
        return (int)$stmt->fetchColumn();
    }

    public static function sumaTamanoPorOperador(int $idOperador): int
    {
        $bd = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $stmt = $bd->prepare("SELECT ruta_archivo, tamano_bytes FROM archivo WHERE id_operador = :id");
        \assert($stmt !== false);
        $stmt->execute([':id' => $idOperador]);
        $total = 0;
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (file_exists($fila['ruta_archivo'])) {
                $total += (int)$fila['tamano_bytes'];
            }
        }
        return $total;
    }

    public function aArreglo(): array
    {
        $arr = parent::aArreglo();
        $arr['tamano_formateado'] = $this->tamanoFormateado();
        $arr['es_imagen'] = $this->esImagen();
        $arr['es_documento'] = $this->esDocumento();
        $arr['enlace_descarga'] = $this->enlaceDescarga();
        $arr['ruta_mostrar'] = $this->rutaMostrar();
        return $arr;
    }
}
