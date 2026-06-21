<?php

declare(strict_types=1);

namespace
{
    if (!function_exists('h')) {
        function h(string $texto): string
        {
            return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
        }
    }
}

namespace LiteFramework\Tests\Integracion\Servicios
{
        use LiteFramework\Servicios\GeneradorEstadisticas;

    class GeneradorEstadisticasTest extends \TestBase
    {
        private GeneradorEstadisticas $est;

    public function setUp(): void
        {
            $this->est = new GeneradorEstadisticas();
        }

        public function testConstructorConSql(): void
        {
            $est = new GeneradorEstadisticas('SELECT * FROM test');
            $this->assertInstanceOf(GeneradorEstadisticas::class, $est);
        }

        public function testEstablecerTituloYDescripcion(): void
        {
            $this->est->establecerTitulo('Mi Titulo');
            $this->est->establecerDescripcion('Descripcion de prueba');
            $this->assertSame('Mi Titulo', $this->est->obtenerTitulo());
        }

        public function testFluentInterfaceTiposVisualizacion(): void
        {
            $this->assertSame($this->est, $this->est->comoTarjetas());
            $this->assertSame($this->est, $this->est->comoBarras());
            $this->assertSame($this->est, $this->est->comoPastel());
            $this->assertSame($this->est, $this->est->comoKpi());
        }

        public function testConAliasYColores(): void
        {
            $this->assertSame($this->est, $this->est->conAlias(['nombre' => 'Nombre']));
            $this->assertSame($this->est, $this->est->conColores(['#ff0000']));

            $alias = $this->est->obtenerAliasColumnas();
            $this->assertSame('Nombre', $alias['nombre']);
        }

        public function testEjecutarConSqlVacioEstableceError(): void
        {
            $this->est->ejecutar();
            $this->assertTrue($this->est->tieneError());
            $this->assertStringContainsString('No se ha definido', $this->est->obtenerError());
        }

        public function testRenderHtml(): void
        {
            $this->est->establecerTitulo('Estadisticas');
            $html = $this->est->generarHtml();
            $this->assertStringContainsString('<!DOCTYPE html>', $html);
        }

        public function testRenderContenidoDashboard(): void
        {
            $this->est->establecerTitulo('Dashboard');
            $html = $this->est->generarContenidoDashboard();
            $this->assertIsString($html);
        }

        public function testTipoVisualizacionInvalidoDefectoTarjetas(): void
        {
            $this->est->establecerTitulo('Test');
            $this->est->ejecutar();
            $this->assertSame('tarjetas', $this->est->obtenerTipoVisualizacion());
        }

        public function testGuardarCacheYcargarCacheRoundTrip(): void
        {
            $ref = new \ReflectionClass(GeneradorEstadisticas::class);
            $propCache = $ref->getProperty('cacheDir');
            $propCache->setAccessible(true);
            $propCache->setValue(null, $this->crearCacheDir());

            $this->est->establecerTitulo('Cache Test');
            $this->est->establecerDescripcion('Desc');

            $this->est->guardarCache(999);
            $data = $this->est->obtenerCache(999);
            $this->assertNotNull($data);
            $this->assertSame('Cache Test', $data['titulo']);

            $this->est->limpiarCache(999);
            $this->assertNull($this->est->obtenerCache(999));
        }

        private function crearCacheDir(): string
        {
            $dir = sys_get_temp_dir() . '/lf_est_cache_' . bin2hex(random_bytes(4));
            mkdir($dir, 0755, true);
            return $dir;
        }

        public function testObtenerDatosGrafico(): void
        {
            $ref = new \ReflectionClass(GeneradorEstadisticas::class);
            $propRes = $ref->getProperty('resultados');
            $propRes->setAccessible(true);
            $propRes->setValue($this->est, [
                ['nombre' => 'A', 'total' => '10'],
                ['nombre' => 'B', 'total' => '20'],
            ]);
            $propCol = $ref->getProperty('columnas');
            $propCol->setAccessible(true);
            $propCol->setValue($this->est, ['nombre', 'total']);

            $this->est->establecerTitulo('Grafico');
            $datos = $this->est->obtenerDatosGrafico();
            $this->assertSame('tarjetas', $datos['tipo']);
            $this->assertCount(2, $datos['datos']);
        }

        public function testObtenerColoresPorDefecto(): void
        {
            $colores = $this->est->obtenerColores();
            $this->assertCount(8, $colores);
            $this->assertSame('#4f46e5', $colores[0]);
        }

        public function testLimpiarCacheForzadoNoLanza(): void
        {
            $this->est->limpiarCacheForzado();
            $this->assertTrue(true);
        }
    }
}
