<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use LiteFramework\Nucleo\Helpers\AyudanteHttp as Http;

class HttpTest extends \TestBase
{
    public function testCodigoComoTexto(): void
    {
        $this->assertSame('OK', Http::codigoComoTexto(200));
        $this->assertSame('No encontrado', Http::codigoComoTexto(404));
        $this->assertSame('Error interno del servidor', Http::codigoComoTexto(500));
    }

    public function testCodigoComoTextoConCodigoDesconocido(): void
    {
        $this->assertSame('Codigo desconocido', Http::codigoComoTexto(999));
    }

    public function testCodigoComoTextoConCero(): void
    {
        $this->assertSame('Codigo desconocido', Http::codigoComoTexto(0));
    }

    public function testCodigoComoTextoTodosLosCodigos(): void
    {
        $this->assertSame('Creado', Http::codigoComoTexto(201));
        $this->assertSame('Sin contenido', Http::codigoComoTexto(204));
        $this->assertSame('Movido permanentemente', Http::codigoComoTexto(301));
        $this->assertSame('Encontrado', Http::codigoComoTexto(302));
        $this->assertSame('No modificado', Http::codigoComoTexto(304));
        $this->assertSame('Solicitud incorrecta', Http::codigoComoTexto(400));
        $this->assertSame('No autorizado', Http::codigoComoTexto(401));
        $this->assertSame('Prohibido', Http::codigoComoTexto(403));
        $this->assertSame('Metodo no permitido', Http::codigoComoTexto(405));
        $this->assertSame('Conflicto', Http::codigoComoTexto(409));
        $this->assertSame('Entidad no procesable', Http::codigoComoTexto(422));
        $this->assertSame('Demasiadas solicitudes', Http::codigoComoTexto(429));
        $this->assertSame('Puerta de enlace incorrecta', Http::codigoComoTexto(502));
        $this->assertSame('Servicio no disponible', Http::codigoComoTexto(503));
        $this->assertSame('Tiempo de espera agotado', Http::codigoComoTexto(504));
    }

    public function testVerificarDisponible(): void
    {
        $this->assertIsBool(Http::verificarDisponible());
    }

    public function testObtenerConUrlInvalidaRetornaError(): void
    {
        $resultado = Http::obtener('https://localhost:1', [], 1);
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('exito', $resultado);
        $this->assertArrayHasKey('codigo', $resultado);
        $this->assertArrayHasKey('tiempo', $resultado);
    }

    public function testPostConUrlInvalidaRetornaError(): void
    {
        $resultado = Http::post('https://localhost:1', ['dato' => 'test'], [], 1);
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('exito', $resultado);
        $this->assertArrayHasKey('codigo', $resultado);
    }

    public function testEnviarConMetodoGETRetornaEstructura(): void
    {
        $resultado = Http::enviar('GET', 'https://127.0.0.1:1', ['timeout' => 1]);
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('exito', $resultado);
        $this->assertArrayHasKey('codigo', $resultado);
        $this->assertArrayHasKey('tiempo', $resultado);
        $this->assertArrayHasKey('error', $resultado);
    }
}
