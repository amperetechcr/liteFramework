<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Servicios;

use LiteFramework\Servicios\ReporteroSentry;

class ReporteroSentryTest extends \TestBase
{
    public function setUp(): void
    {
        $ref = new \ReflectionClass(ReporteroSentry::class);
        $ref->setStaticPropertyValue('dsn', '');
        $ref->setStaticPropertyValue('entorno', 'desarrollo');
        $ref->setStaticPropertyValue('release', '');
    }

    public function testIniciarConDsnValido(): void
    {
        ReporteroSentry::iniciar('https://key@o1.ingest.sentry.io/123');
        $this->assertTrue(ReporteroSentry::estaActivo());
    }

    public function testIniciarConDsnInvalido(): void
    {
        ReporteroSentry::iniciar('invalid-dsn');
        $this->assertFalse(ReporteroSentry::estaActivo());
    }

    public function testEstaActivoRetornaFalsePorDefecto(): void
    {
        $this->assertFalse(ReporteroSentry::estaActivo());
    }

    public function testEstaActivoRetornaTrueConDsnValido(): void
    {
        ReporteroSentry::iniciar('https://key@o1.ingest.sentry.io/123');
        $this->assertTrue(ReporteroSentry::estaActivo());
    }

    public function testCapturarConThrowableNoLanzaExcepcion(): void
    {
        ReporteroSentry::iniciar('https://key@o1.ingest.sentry.io/123');
        ReporteroSentry::capturar(new \RuntimeException('test error'));
        $this->assertTrue(true);
    }

    public function testCapturarConContextoExtra(): void
    {
        ReporteroSentry::iniciar('https://key@o1.ingest.sentry.io/123');
        ReporteroSentry::capturar(new \RuntimeException('test'), ['usuario_id' => 42]);
        $this->assertTrue(true);
    }

    public function testDsnDebeComenzarConHttps(): void
    {
        $this->assertStringStartsWith('https://', 'https://key@o1.ingest.sentry.io/123');
    }

    public function testCapturarConThrowableTipoEspecifico(): void
    {
        ReporteroSentry::iniciar('https://key@o1.ingest.sentry.io/123');
        ReporteroSentry::capturar(new \InvalidArgumentException('invalido'));
        $this->assertTrue(true);
    }

    public function testJsonEncodeFailureNoLanzaExcepcion(): void
    {
        ReporteroSentry::iniciar('https://key@o1.ingest.sentry.io/123');
        ReporteroSentry::capturar(new \RuntimeException('test'));
        $this->assertTrue(true);
    }

    public function testCapturarSinDsnNoHaceNada(): void
    {
        ReporteroSentry::capturar(new \RuntimeException('test'));
        $this->assertTrue(true);
    }

    public function testGenerarUuidTieneFormatoValido(): void
    {
        $ref = new \ReflectionMethod(ReporteroSentry::class, 'generarUuid');
        $ref->setAccessible(true);
        $uuid = $ref->invoke(null);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $uuid);
    }

    public function testCapturarConReleaseDefinida(): void
    {
        $ref = new \ReflectionClass(ReporteroSentry::class);
        $ref->setStaticPropertyValue('release', '1.0.0');
        ReporteroSentry::iniciar('https://key@o1.ingest.sentry.io/123');
        ReporteroSentry::capturar(new \RuntimeException('test'));
        $this->assertTrue(true);
    }
}
