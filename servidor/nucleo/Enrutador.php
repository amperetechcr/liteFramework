<?php

declare(strict_types=1);

namespace LiteFramework\Nucleo;

use ReflectionMethod;
use LiteFramework\Seguridad\RegistroAuditoria;
use LiteFramework\Config\GestorEntorno;
use ReflectionFunction;

class Enrutador
{
    private array $rutas = [];
    private string $grupoPrefijo = '';
    private array $grupoInterceptores = [];
    private static ?Enrutador $instancia = null;

    public static function registrarInstancia(Enrutador $enrutador): void
    {
        self::$instancia = $enrutador;
    }

    public static function obtenerInstancia(): ?Enrutador
    {
        return self::$instancia;
    }

    public function get(string $patron, callable|array $accion): object
    {
        return $this->registrarRuta('GET', $patron, $accion);
    }

    public function post(string $patron, callable|array $accion): object
    {
        return $this->registrarRuta('POST', $patron, $accion);
    }

    public function put(string $patron, callable|array $accion): object
    {
        return $this->registrarRuta('PUT', $patron, $accion);
    }

    public function patch(string $patron, callable|array $accion): object
    {
        return $this->registrarRuta('PATCH', $patron, $accion);
    }

    public function delete(string $patron, callable|array $accion): object
    {
        return $this->registrarRuta('DELETE', $patron, $accion);
    }

    public function grupo(array $atributos, callable $callback): void
    {
        $prefijoAnterior = $this->grupoPrefijo;
        $interceptorAnterior = $this->grupoInterceptores;

        if (isset($atributos['prefijo'])) {
            $this->grupoPrefijo .= '/' . trim($atributos['prefijo'], '/');
        }
        if (isset($atributos['interceptor'])) {
            $this->grupoInterceptores = array_merge(
                $this->grupoInterceptores,
                (array)$atributos['interceptor']
            );
        }

        $callback($this);

        $this->grupoPrefijo = $prefijoAnterior;
        $this->grupoInterceptores = $interceptorAnterior;
    }

    private function registrarRuta(string $metodo, string $patron, callable|array $accion): object
    {
        $patronCompleto = $this->grupoPrefijo . '/' . trim($patron, '/');
        $patronCompleto = '/' . trim($patronCompleto, '/');
        if ($patronCompleto === '//') {
            $patronCompleto = '/';
        }

        $ruta = [
            'metodo' => $metodo,
            'patron' => $patronCompleto,
            'accion' => $accion,
            'nombre' => null,
            'interceptor' => $this->grupoInterceptores,
        ];

        $indice = count($this->rutas);
        $this->rutas[] = $ruta;
        return new class ($this, $indice) {
            private Enrutador $enrutador;
            private int $indice;
            public function __construct(Enrutador $enrutador, int $indice)
            {
                $this->enrutador = $enrutador;
                $this->indice = $indice;
            }
            public function nombre(string $nombre): self
            {
                $this->enrutador->asignarNombre($this->indice, $nombre);
                return $this;
            }
            public function interceptor(string|array $interceptor): self
            {
                $this->enrutador->asignarInterceptor($this->indice, (array)$interceptor);
                return $this;
            }
        };
    }

    public function asignarNombre(int $indice, string $nombre): void
    {
        $this->rutas[$indice]['nombre'] = $nombre;
    }

    public function asignarInterceptor(int $indice, array $interceptor): void
    {
        $this->rutas[$indice]['interceptor'] = array_merge(
            $this->rutas[$indice]['interceptor'],
            $interceptor
        );
    }

    public function despachar(string $metodo, string $uri): mixed
    {
        $uri = '/' . trim(parse_url($uri, PHP_URL_PATH), '/');
        if ($uri === '//') {
            $uri = '/';
        }
        $metodo = strtoupper($metodo);

        foreach ($this->rutas as $ruta) {
            if ($ruta['metodo'] !== $metodo) {
                continue;
            }

            $regex = $this->patronARegex($ruta['patron']);
            if (preg_match($regex, $uri, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                $cadena = array_merge($ruta['interceptor'], [$ruta['accion']]);
                return $this->ejecutarCadena($cadena, $params);
            }
        }

        return false;
    }

    private function patronARegex(string $patron): string
    {
        $regex = preg_replace(
            '/\{([a-zA-Z_]+)\}/',
            '(?P<$1>[^/]+)',
            $patron
        );
        return '#^' . $regex . '$#';
    }

    private function ejecutarCadena(array $cadena, array $params): mixed
    {
        if (empty($cadena)) {
            return '';
        }
        $accion = array_shift($cadena);

        if (is_string($accion) && class_exists($accion)) {
            $instancia = new $accion();
            return $instancia->manejar($params, function ($p) use ($cadena) {
                return $this->ejecutarCadena($cadena, $p);
            });
        }

        if (is_string($accion) && strpos($accion, '@') !== false) {
            $partes = explode('@', $accion);
            $clase = $partes[0];
            $metodo = $partes[1];
            if (!class_exists($clase)) {
                http_response_code(500);
                RegistroAuditoria::error('Enrutador', 'Controlador no encontrado', [
                    'clase' => $clase,
                    'metodo' => $metodo,
                ]);
                $this->mostrarError(500, 'Controlador no encontrado: ' . $clase);
                return '';
            }
            $controlador = new $clase();
            $rf = new ReflectionMethod($clase, $metodo);
            return $rf->invokeArgs($controlador, $params);
        }

        if (is_array($accion) && count($accion) === 2) {
            $clase = $accion[0];
            $metodo = $accion[1];
            if (!class_exists($clase)) {
                http_response_code(500);
                $this->mostrarError(500, 'Controlador no encontrado: ' . $clase);
                return '';
            }
            $controlador = new $clase();
            $rf = new ReflectionMethod($clase, $metodo);
            return $rf->invokeArgs($controlador, $params);
        }

        if (is_callable($accion)) {
            $rf = new ReflectionFunction($accion);
            return $rf->invoke($params);
        }

        http_response_code(500);
        $this->mostrarError(500, 'Accion de ruta invalida');
        return '';
    }

    public static function url(string $nombre, array $parametros = []): string
    {
        $instancia = self::$instancia;
        if (!$instancia) {
            return '#';
        }
        foreach ($instancia->rutas as $ruta) {
            if ($ruta['nombre'] === $nombre) {
                $url = $ruta['patron'];
                foreach ($parametros as $clave => $valor) {
                    $url = str_replace('{' . $clave . '}', (string)$valor, $url);
                }
                return URL_BASE . $url;
            }
        }
        return '#';
    }

    private function mostrarError(int $codigo, string $mensaje): void
    {
        if (GestorEntorno::esDepuracion()) {
            echo '<h1>Error ' . $codigo . '</h1>';
            echo '<p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
        } else {
            echo '<h1>Error ' . $codigo . '</h1>';
            echo '<p>Pagina no disponible.</p>';
        }
    }
}
