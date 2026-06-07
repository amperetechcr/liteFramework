<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

use PDOException;
use PDO;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Modelos\DocumentoPdf;
use Exception;

class GeneradorPdf
{
    private string $titulo = '';
    private string $contenido = '';
    private string $orientacion = 'vertical';
    private string $tamanoPagina = 'A4';
    private string $margen = 'normal';
    private string $encabezadoHtml = '';
    private string $pieHtml = '';
    private bool $conEstilos = true;

    private static array $tamanosValidos  = ['A4', 'Carta', 'Oficio', 'A3', 'A5'];
    private static array $orientacionesValidas = ['vertical', 'horizontal'];
    private static array $margenesValidos = ['estrecho', 'normal', 'amplio'];

    private static array $margenesMapa = [
        'estrecho' => '8mm 6mm',
        'normal'   => '15mm 12mm',
        'amplio'   => '25mm 20mm',
    ];

    public function __construct(string $orientacion = 'vertical', string $tamanoPagina = 'A4')
    {
        $this->establecerOrientacion($orientacion);
        $this->establecerTamanoPagina($tamanoPagina);
    }

    public function establecerTitulo(string $titulo): self
    {
        $this->titulo = $titulo;
        return $this;
    }

    public function establecerOrientacion(string $orientacion): self
    {
        if (in_array($orientacion, self::$orientacionesValidas, true)) {
            $this->orientacion = $orientacion;
        }
        return $this;
    }

    public function establecerTamanoPagina(string $tamano): self
    {
        if (in_array($tamano, self::$tamanosValidos, true)) {
            $this->tamanoPagina = $tamano;
        }
        return $this;
    }

    public function establecerMargen(string $margen): self
    {
        if (isset(self::$margenesMapa[$margen])) {
            $this->margen = $margen;
        }
        return $this;
    }

    public function establecerEncabezado(string $html): self
    {
        $this->encabezadoHtml = $html;
        return $this;
    }

    public function establecerPie(string $html): self
    {
        $this->pieHtml = $html;
        return $this;
    }

    public function sinEstilos(): self
    {
        $this->conEstilos = false;
        return $this;
    }

    public function agregarTitulo(string $texto, int $nivel = 1): self
    {
        $nivel = max(1, min(6, $nivel));
        $this->contenido .= "<h{$nivel}>" . h($texto) . "</h{$nivel}>\n";
        return $this;
    }

    public function agregarParrafo(string $texto): self
    {
        $this->contenido .= '<p>' . h($texto) . "</p>\n";
        return $this;
    }

    public function agregarHtml(string $html): self
    {
        $this->contenido .= $html . "\n";
        return $this;
    }

    public function agregarTabla(array $filas, array $encabezados = [], array $anchos = []): self
    {
        $html = '<div class="tabla-responsiva"><table><thead><tr>';
        if (!empty($encabezados)) {
            foreach ($encabezados as $i => $encabezado) {
                $estilo = isset($anchos[$i]) ? ' style="width:' . $anchos[$i] . '%"' : '';
                $html .= "<th{$estilo}>" . h($encabezado) . '</th>';
            }
        }
        $html .= '</tr></thead><tbody>';
        foreach ($filas as $fila) {
            $html .= '<tr>';
            foreach ((array)$fila as $celda) {
                $html .= '<td>' . h((string)$celda) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        $this->contenido .= $html . "\n";
        return $this;
    }

    public function agregarSaltoPagina(): self
    {
        $this->contenido .= '<div class="salto-pagina"></div>' . "\n";
        return $this;
    }

    public function agregarImagen(string $ruta, string $textoAlternativo = ''): self
    {
        $this->contenido .= '<img src="' . h($ruta) . '" alt="' . h($textoAlternativo) . '" loading="lazy">' . "\n";
        return $this;
    }

    public function agregarLineaSeparadora(): self
    {
        $this->contenido .= "<hr>\n";
        return $this;
    }

    public function agregarSeccion(string $titulo, string $contenido): self
    {
        $this->contenido .= '<section><h3>' . h($titulo) . '</h3>';
        $this->contenido .= '<div>' . $contenido . '</div></section>' . "\n";
        return $this;
    }

    public function agregarTarjeta(string $titulo, string $contenido): self
    {
        $this->contenido .= '<article class="evitar-salto"><h3>' . h($titulo) . '</h3>';
        $this->contenido .= '<div>' . $contenido . '</div></article>' . "\n";
        return $this;
    }

    public function agregarLista(array $elementos, bool $ordenada = false): self
    {
        $etiqueta = $ordenada ? 'ol' : 'ul';
        $html = "<{$etiqueta}>";
        foreach ($elementos as $elemento) {
            $html .= '<li>' . h($elemento) . '</li>';
        }
        $html .= "</{$etiqueta}>";
        $this->contenido .= $html . "\n";
        return $this;
    }

    public function desdePlantilla(int $idPlantilla, array $datos = []): self
    {
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $stmt = $conexion->prepare("SELECT titulo, contenido_html FROM documento_pdf WHERE id_documento = :id LIMIT 1");
            $stmt->execute([':id' => $idPlantilla]);
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($plantilla) {
                $this->establecerTitulo($plantilla['titulo']);
                $html = $plantilla['contenido_html'];
                foreach ($datos as $clave => $valor) {
                    $html = str_replace('{{' . $clave . '}}', (string)$valor, $html);
                }
                $this->agregarHtml($html);
            }
        } catch (PDOException $e) {
            RegistroAuditoria::error('GeneradorPdf', 'Error al cargar plantilla', [
                'id' => $idPlantilla,
                'error' => $e->getMessage(),
            ]);
        }
        return $this;
    }

    private function margenActual(): string
    {
        return self::$margenesMapa[$this->margen] ?? self::$margenesMapa['normal'];
    }

    private function opcionesSelect(array $opciones, string $valorActual): string
    {
        $html = '';
        foreach ($opciones as $valor) {
            $etiqueta = ucfirst($valor);
            $seleccionado = $valor === $valorActual ? ' selected' : '';
            $html .= '<option value="' . $valor . '"' . $seleccionado . '>' . $etiqueta . '</option>';
        }
        return $html;
    }

    private function clasesApariencia(): string
    {
        $cargar = function_exists('configUI');

        $paleta = $cargar ? (configUI('paleta') ?? 'indigo') : 'indigo';
        $estilo = $cargar ? (configUI('estilo') ?? 'moderno') : 'moderno';
        $fondo   = $cargar ? (configUI('fondo') ?? 'blanco') : 'blanco';
        $fuente = $cargar ? (configUI('fuente') ?? 'sistema') : 'sistema';
        $espaciado = $cargar ? (configUI('espaciado') ?? 'normal') : 'normal';
        $tamano = $cargar ? (configUI('tamano') ?? 'normal') : 'normal';

        $partes = [
            'paleta-' . $paleta,
            'estilo-' . $estilo,
            'fondo-' . $fondo,
            ($fuente !== 'sistema') ? 'fuente-' . $fuente : '',
            ($espaciado !== 'normal') ? 'espaciado-' . $espaciado : '',
            ($tamano !== 'normal') ? 'tamano-' . $tamano : '',
        ];

        return trim(implode(' ', array_filter($partes)));
    }

    public function generarHtml(): string
    {
        $orientacionInicial = $this->orientacion === 'horizontal' ? ' landscape' : '';
        $tamanoInicial = $this->tamanoPagina;
        $margenValor = $this->margenActual();
        $tieneEncabezado = $this->encabezadoHtml !== '';
        $tieneTitulo = $this->titulo !== '';

        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html lang="es-CR" class="' . $this->clasesApariencia() . '">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '<meta charset="UTF-8">' . "\n";
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $html .= '<title>' . h($this->titulo ?: 'Documento PDF') . '</title>' . "\n";

        if ($this->conEstilos) {
            $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/tema.css">' . "\n";
            $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/paletas.css">' . "\n";
            $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/maquetacion.css">' . "\n";
            $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/componentes.css">' . "\n";
            $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/estilos.css">' . "\n";
            $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/utilidades.css">' . "\n";
            $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/personalizacion.css">' . "\n";
            $html .= '<link rel="stylesheet" href="' . URL_BASE . '/src/css/generadorPdf.css">' . "\n";
        }

        $html .= '<style id="estiloPaginaPdf">@page{size:' . $tamanoInicial . $orientacionInicial . ';margin:' . $margenValor . ';}</style>' . "\n";
        $html .= '<script type="module" src="' . URL_BASE . '/src/js/principal.js"></script>' . "\n";
        $html .= '</head>' . "\n";

        $html .= '<body class="pagina-impresion-pdf">' . "\n";

        $html .= '<div class="panel-personalizacion-pdf oculto-impresion" id="panelPersonalizacion" role="toolbar" aria-label="Personalizacion del documento">' . "\n";
        $html .= '  <div class="panel-personalizacion-contenido">' . "\n";

        $html .= '    <div class="panel-personalizacion-grupo">' . "\n";
        $html .= '      <label for="selTamanoPagina" class="texto-pequeno texto-negrita">Tamano</label>' . "\n";
        $html .= '      <select id="selTamanoPagina" class="campo-entrada texto-sm">' . $this->opcionesSelect(self::$tamanosValidos, $this->tamanoPagina) . '</select>' . "\n";
        $html .= '    </div>' . "\n";

        $html .= '    <div class="panel-personalizacion-grupo">' . "\n";
        $html .= '      <label for="selOrientacion" class="texto-pequeno texto-negrita">Orientacion</label>' . "\n";
        $html .= '      <select id="selOrientacion" class="campo-entrada texto-sm">' . "\n";
        $html .= '        <option value="vertical"' . ($this->orientacion === 'vertical' ? ' selected' : '') . '>Vertical</option>' . "\n";
        $html .= '        <option value="horizontal"' . ($this->orientacion === 'horizontal' ? ' selected' : '') . '>Horizontal</option>' . "\n";
        $html .= '      </select>' . "\n";
        $html .= '    </div>' . "\n";

        $html .= '    <div class="panel-personalizacion-grupo">' . "\n";
        $html .= '      <label for="selMargen" class="texto-pequeno texto-negrita">Margenes</label>' . "\n";
        $html .= '      <select id="selMargen" class="campo-entrada texto-sm">' . "\n";
        foreach (['estrecho', 'normal', 'amplio'] as $mg) {
            $html .= '        <option value="' . $mg . '"' . ($this->margen === $mg ? ' selected' : '') . '>' . ucfirst($mg) . '</option>' . "\n";
        }
        $html .= '      </select>' . "\n";
        $html .= '    </div>' . "\n";

        $html .= '    <div class="panel-personalizacion-divisor"></div>' . "\n";

        $html .= '    <div class="panel-personalizacion-grupo panel-personalizacion-grupo-check">' . "\n";
        $html .= '      <input type="checkbox" id="chkMostrarTitulo" checked>' . "\n";
        $html .= '      <label for="chkMostrarTitulo" class="texto-pequeno">Titulo</label>' . "\n";
        $html .= '    </div>' . "\n";

        if ($tieneEncabezado) {
            $html .= '    <div class="panel-personalizacion-grupo panel-personalizacion-grupo-check">' . "\n";
            $html .= '      <input type="checkbox" id="chkMostrarEncabezado" checked>' . "\n";
            $html .= '      <label for="chkMostrarEncabezado" class="texto-pequeno">Encabezado</label>' . "\n";
            $html .= '    </div>' . "\n";
        }

        $html .= '    <div class="panel-personalizacion-divisor"></div>' . "\n";

        $html .= '    <div class="panel-personalizacion-acciones">' . "\n";
        $html .= '      <button type="button" id="btnImprimirPdf" class="accion-boton variante-solida">Descargar</button>' . "\n";
        $html .= '    </div>' . "\n";

        $html .= '  </div>' . "\n";
        $html .= '</div>' . "\n";

        if ($tieneEncabezado) {
            $html .= '<div id="encabezadoDocumento" class="encabezado-documento-pdf oculto-impresion">' . $this->encabezadoHtml . '</div>' . "\n";
        }

        if ($tieneTitulo) {
            $html .= '<h1 id="tituloDocumento" class="titulo-documento-pdf">' . h($this->titulo) . "</h1>\n";
        }

        $html .= '<main id="contenidoDocumento" class="contenido-impresion envoltura-contenido">' . "\n";
        $html .= $this->contenido;
        $html .= '</main>' . "\n";

        if ($this->pieHtml !== '') {
            $html .= '<footer class="oculto-impresion texto-centro texto-pequeno texto-suave relleno-normal">' . $this->pieHtml . '</footer>' . "\n";
        }

        $js = '';
        $js .= '(function(){' . "\n";
        $js .= '  var estiloPagina = document.getElementById("estiloPaginaPdf");' . "\n";
        $js .= '  var btnImprimir = document.getElementById("btnImprimirPdf");' . "\n";
        $js .= '  var chkMostrarTitulo = document.getElementById("chkMostrarTitulo");' . "\n";
        if ($tieneEncabezado) {
            $js .= '  var chkMostrarEncabezado = document.getElementById("chkMostrarEncabezado");' . "\n";
            $js .= '  var encabezadoEl = document.getElementById("encabezadoDocumento");' . "\n";
        }
        $js .= '  var tituloEl = document.getElementById("tituloDocumento");' . "\n";
        $js .= '  var panelEl = document.getElementById("panelPersonalizacion");' . "\n";
        $js .= "\n";
        $js .= '  var tamanos = {A4:"A4",Carta:"letter",Oficio:"legal",A3:"A3",A5:"A5"};' . "\n";
        $js .= '  var margenes = {estrecho:"8mm 6mm",normal:"15mm 12mm",amplio:"25mm 20mm"};' . "\n";
        $js .= "\n";
        $js .= '  function actualizarEstiloPagina() {' . "\n";
        $js .= '    var tam = document.getElementById("selTamanoPagina").value;' . "\n";
        $js .= '    var ori = document.getElementById("selOrientacion").value;' . "\n";
        $js .= '    var mar = document.getElementById("selMargen").value;' . "\n";
        $js .= '    var tamCss = tamanos[tam] || "A4";' . "\n";
        $js .= '    var oriCss = ori === "horizontal" ? " landscape" : "";' . "\n";
        $js .= '    var marCss = margenes[mar] || "15mm 12mm";' . "\n";
        $js .= '    estiloPagina.textContent = "@page{size:" + tamCss + oriCss + ";margin:" + marCss + ";}";' . "\n";
        $js .= '  }' . "\n";
        $js .= "\n";
        $js .= '  document.getElementById("selTamanoPagina").addEventListener("change",actualizarEstiloPagina);' . "\n";
        $js .= '  document.getElementById("selOrientacion").addEventListener("change",actualizarEstiloPagina);' . "\n";
        $js .= '  document.getElementById("selMargen").addEventListener("change",actualizarEstiloPagina);' . "\n";
        $js .= "\n";
        $js .= '  chkMostrarTitulo.addEventListener("change",function(){' . "\n";
        $js .= '    if(tituloEl) tituloEl.style.display = this.checked ? "" : "none";' . "\n";
        $js .= '  });' . "\n";
        if ($tieneEncabezado) {
            $js .= '  chkMostrarEncabezado.addEventListener("change",function(){' . "\n";
            $js .= '    if(encabezadoEl) encabezadoEl.style.display = this.checked ? "" : "none";' . "\n";
            $js .= '  });' . "\n";
        }
        $js .= "\n";
        $js .= '  btnImprimir.addEventListener("click",function(){' . "\n";
        $js .= '    window.print();' . "\n";
        $js .= '  });' . "\n";
        $js .= '})();' . "\n";

        $html .= '<script>' . "\n" . $js . '</script>' . "\n";
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
        return $this->contenido;
    }

    public static function listarPlantillas(): array
    {
        try {
            $conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
            $stmt = $conexion->query("SELECT id_documento, titulo, fecha_creacion FROM documento_pdf ORDER BY fecha_creacion DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public static function guardarPlantilla(string $titulo, string $contenidoHtml, ?int $idOperador = null): ?int
    {
        try {
            $modelo = DocumentoPdf::crear([
                'titulo' => $titulo,
                'contenido_html' => $contenidoHtml,
                'id_operador' => $idOperador,
            ]);
            return (int)$modelo->id_documento;
        } catch (Exception $e) {
            return null;
        }
    }
}
