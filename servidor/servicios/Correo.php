<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;

class Correo
{
    private string $anfitrion = 'localhost';
    private int $puerto = 25;
    private string $usuario = '';
    private string $clave = '';
    private string $remitente = '';
    private string $remitenteNombre = '';
    private bool $usarTLS = false;
    private int $tiempoEspera = 10;
    private LoggerInterface $logger;

    private string $destinatario = '';
    private string $destinatarioNombre = '';
    private string $asunto = '';
    private string $cuerpoHtml = '';
    private string $cuerpoTexto = '';
    private array $adjuntos = [];
    private array $destinatariosCopia = [];
    private array $destinatariosCopiaOculta = [];

    private $conexion = null;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public static function desdeEntorno(?LoggerInterface $logger = null): self
    {
        $correo = new self($logger);
        $correo->configurar(
            defined('MAIL_ANFITRION') ? MAIL_ANFITRION : 'localhost',
            defined('MAIL_PUERTO') ? (int)MAIL_PUERTO : 25,
            defined('MAIL_USUARIO') ? MAIL_USUARIO : '',
            defined('MAIL_CLAVE') ? MAIL_CLAVE : '',
            defined('MAIL_REMITENTE') ? MAIL_REMITENTE : '',
            null,
            defined('MAIL_TLS') ? (bool)MAIL_TLS : false,
        );
        return $correo;
    }

    public function configurar(string $anfitrion, int $puerto, string $usuario, string $clave, string $remitente, ?string $remitenteNombre = null, bool $usarTLS = true): static
    {
        $this->anfitrion = $anfitrion;
        $this->puerto = $puerto;
        $this->usuario = $usuario;
        $this->clave = $clave;
        $this->remitente = $remitente;
        $this->remitenteNombre = $remitenteNombre ?? $remitente;
        $this->usarTLS = $usarTLS;
        return $this;
    }

    public function para(string $correo, ?string $nombre = null): static
    {
        $this->destinatario = $correo;
        $this->destinatarioNombre = $nombre ?? $correo;
        return $this;
    }

    public function asunto(string $asunto): static
    {
        $this->asunto = $asunto;
        return $this;
    }

    public function cuerpo(string $html, ?string $texto = null): static
    {
        $this->cuerpoHtml = $html;
        $this->cuerpoTexto = $texto ?? strip_tags($html);
        return $this;
    }

    public function adjuntar(string $rutaArchivo, ?string $nombre = null): static
    {
        if (!file_exists($rutaArchivo)) {
            $this->logger->warning('Intento de adjuntar archivo inexistente', ['ruta' => $rutaArchivo]);
            return $this;
        }
        $this->adjuntos[] = ['ruta' => $rutaArchivo, 'nombre' => $nombre ?? basename($rutaArchivo)];
        return $this;
    }

    public function agregarCopia(string $correo): static
    {
        $this->destinatariosCopia[] = $correo;
        return $this;
    }

    public function enviar(): bool
    {
        try {
            $this->conectar();
            $this->dialogo('EHLO ' . ($this->anfitrion));

            if ($this->usarTLS && $this->puerto !== 587) {
                $this->iniciarTLS();
            }

            $this->autenticar();
            $this->dialogo('MAIL FROM:<' . $this->remitente . '>');
            $this->dialogo('RCPT TO:<' . $this->destinatario . '>');

            foreach ($this->destinatariosCopia as $cc) {
                $this->dialogo('RCPT TO:<' . $cc . '>');
            }

            $this->enviarContenido();
            $this->dialogo('QUIT');
            $this->cerrar();

            $this->logger->info('Correo enviado exitosamente', [
                'para' => $this->destinatario,
                'asunto' => $this->asunto,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Error al enviar correo', [
                'error' => $e->getMessage(),
                'para' => $this->destinatario,
                'asunto' => $this->asunto,
            ]);
            $this->cerrar();
            return false;
        }
    }

    private function conectar(): void
    {
        $this->logger->debug('Conectando a SMTP', [
            'anfitrion' => $this->anfitrion,
            'puerto' => $this->puerto,
        ]);

        $this->conexion = @fsockopen($this->anfitrion, $this->puerto, $codigoError, $mensajeError, $this->tiempoEspera);

        if (!$this->conexion) {
            throw new \RuntimeException("No se pudo conectar a SMTP {$this->anfitrion}:{$this->puerto} — {$mensajeError}");
        }

        $this->leer(220);
    }

    private function iniciarTLS(): void
    {
        $this->dialogo('STARTTLS');
        $tlsOk = @stream_socket_enable_crypto($this->conexion, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        if (!$tlsOk) {
            throw new \RuntimeException('No se pudo iniciar TLS');
        }

        $this->dialogo('EHLO ' . ($this->anfitrion));
    }

    private function autenticar(): void
    {
        if (empty($this->usuario)) {
            return;
        }

        $this->dialogo('AUTH LOGIN');
        $this->dialogo(base64_encode($this->usuario));
        $this->dialogo(base64_encode($this->clave));
    }

    private function enviarContenido(): void
    {
        $this->dialogo('DATA');

        $separador = md5(uniqid((string)microtime(true), true));
        $tieneAdjuntos = !empty($this->adjuntos);

        $cabeceras = [];
        $cabeceras[] = 'From: ' . $this->codificarCabecera($this->remitenteNombre) . ' <' . $this->remitente . '>';
        $cabeceras[] = 'To: ' . $this->codificarCabecera($this->destinatarioNombre) . ' <' . $this->destinatario . '>';

        if (!empty($this->destinatariosCopia)) {
            $cabeceras[] = 'Cc: ' . implode(', ', $this->destinatariosCopia);
        }

        $cabeceras[] = 'Subject: ' . $this->codificarCabecera($this->asunto);
        $cabeceras[] = 'Date: ' . date('r');
        $cabeceras[] = 'MIME-Version: 1.0';

        if ($tieneAdjuntos) {
            $cabeceras[] = 'Content-Type: multipart/mixed; boundary="' . $separador . '"';
            $cabeceras[] = '';
            $cabeceras[] = '--' . $separador;
            $cabeceras[] = 'Content-Type: multipart/alternative; boundary="alt-' . $separador . '"';
            $cabeceras[] = '';
            $cabeceras[] = '--alt-' . $separador;
            $cabeceras[] = 'Content-Type: text/plain; charset=UTF-8';
            $cabeceras[] = 'Content-Transfer-Encoding: quoted-printable';
            $cabeceras[] = '';
            $cabeceras[] = quoted_printable_encode($this->cuerpoTexto);
            $cabeceras[] = '';
            $cabeceras[] = '--alt-' . $separador;
            $cabeceras[] = 'Content-Type: text/html; charset=UTF-8';
            $cabeceras[] = 'Content-Transfer-Encoding: quoted-printable';
            $cabeceras[] = '';
            $cabeceras[] = quoted_printable_encode($this->cuerpoHtml);
            $cabeceras[] = '';
            $cabeceras[] = '--alt-' . $separador . '--';

            foreach ($this->adjuntos as $adjunto) {
                $contenido = file_get_contents($adjunto['ruta']);
                if ($contenido === false) continue;
                $cabeceras[] = '';
                $cabeceras[] = '--' . $separador;
                $cabeceras[] = 'Content-Type: application/octet-stream; name="' . $adjunto['nombre'] . '"';
                $cabeceras[] = 'Content-Transfer-Encoding: base64';
                $cabeceras[] = 'Content-Disposition: attachment; filename="' . $adjunto['nombre'] . '"';
                $cabeceras[] = '';
                $cabeceras[] = chunk_split(base64_encode($contenido));
            }

            $cabeceras[] = '';
            $cabeceras[] = '--' . $separador . '--';
        } else {
            $cabeceras[] = 'Content-Type: multipart/alternative; boundary="' . $separador . '"';
            $cabeceras[] = '';
            $cabeceras[] = '--' . $separador;
            $cabeceras[] = 'Content-Type: text/plain; charset=UTF-8';
            $cabeceras[] = 'Content-Transfer-Encoding: quoted-printable';
            $cabeceras[] = '';
            $cabeceras[] = quoted_printable_encode($this->cuerpoTexto);
            $cabeceras[] = '';
            $cabeceras[] = '--' . $separador;
            $cabeceras[] = 'Content-Type: text/html; charset=UTF-8';
            $cabeceras[] = 'Content-Transfer-Encoding: quoted-printable';
            $cabeceras[] = '';
            $cabeceras[] = quoted_printable_encode($this->cuerpoHtml);
            $cabeceras[] = '';
            $cabeceras[] = '--' . $separador . '--';
        }

        $cabeceras[] = '.';
        $this->enviarDatos(implode("\r\n", $cabeceras));
        $this->leer(250);
    }

    private function dialogo(string $comando): void
    {
        $this->enviarDatos($comando);

        if (str_starts_with($comando, 'EHLO')) {
            $this->leer(250, true);
        } elseif (str_starts_with($comando, 'AUTH LOGIN')) {
            $this->leer(334);
        } elseif (str_starts_with($comando, 'STARTTLS')) {
            $this->leer(220);
        } elseif (str_starts_with($comando, 'QUIT')) {
            return;
        } else {
            $this->leer(250, true);
        }
    }

    private function enviarDatos(string $datos): void
    {
        if (!$this->conexion) {
            throw new \RuntimeException('Conexion SMTP no establecida');
        }

        $this->logger->debug('SMTP > ' . $this->ofuscar($datos));
        @fwrite($this->conexion, $datos . "\r\n");
    }

    private function leer(int $codigoEsperado, bool $multiLinea = false): void
    {
        if (!$this->conexion) {
            throw new \RuntimeException('Conexion SMTP no establecida');
        }

        $respuesta = '';

        do {
            $linea = @fgets($this->conexion, 512);
            if ($linea === false) {
                throw new \RuntimeException('SMTP: conexion cerrada inesperadamente');
            }
            $respuesta .= $linea;
        } while ($multiLinea && strlen($linea) > 3 && $linea[3] === '-');

        $codigo = (int)substr($respuesta, 0, 3);
        $this->logger->debug('SMTP < ' . trim($respuesta));

        if ($codigo !== $codigoEsperado) {
            throw new \RuntimeException("SMTP error: se esperaba {$codigoEsperado}, se recibio {$codigo} — " . trim($respuesta));
        }
    }

    private function cerrar(): void
    {
        if ($this->conexion) {
            @fclose($this->conexion);
            $this->conexion = null;
        }
    }

    private function codificarCabecera(string $texto): string
    {
        if (preg_match('/[^\x20-\x7E]/', $texto)) {
            return '=?' . 'UTF-8' . '?B?' . base64_encode($texto) . '?=';
        }
        return $texto;
    }

    private function ofuscar(string $datos): string
    {
        if (str_starts_with($datos, base64_encode($this->usuario))) {
            return 'AUTH LOGIN (usuario)';
        }
        if (str_starts_with($datos, base64_encode($this->clave))) {
            return 'AUTH LOGIN (clave)';
        }
        return $datos;
    }
}
