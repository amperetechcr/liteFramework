<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Helpers;

use LiteFramework\Nucleo\Helpers\Cadena;

class CadenaTest extends \TestBase
{
    public function testLimitarTextoMasLargoQueLimite(): void
    {
        $this->assertSame('Hola mu...', Cadena::limitar('Hola mundo cruel', 10));
    }

    public function testLimitarTextoMasCortoQueLimite(): void
    {
        $this->assertSame('Hola', Cadena::limitar('Hola', 10));
    }

    public function testLimitarTextoVacio(): void
    {
        $this->assertSame('', Cadena::limitar('', 10));
    }

    public function testLimitarConNull(): void
    {
        $this->assertSame('', Cadena::limitar(null, 10));
    }

    public function testLimitarConFinPersonalizado(): void
    {
        $this->assertSame('Hola mu---', Cadena::limitar('Hola mundo cruel', 10, '---'));
    }

    public function testLimitarConTextoExactoAlLimite(): void
    {
        $this->assertSame('Hola mundo', Cadena::limitar('Hola mundo', 10));
    }

    public function testTruncarNoCortaPalabras(): void
    {
        $resultado = Cadena::truncar('Hola mundo cruel adios', 14);
        $this->assertStringEndsWith('...', $resultado);
        $this->assertStringContainsString('mundo', $resultado);
    }

    public function testTruncarTextoVacio(): void
    {
        $this->assertSame('', Cadena::truncar('', 10));
    }

    public function testTruncarConNull(): void
    {
        $this->assertSame('', Cadena::truncar(null, 10));
    }

    public function testSlug(): void
    {
        $this->assertSame('hola-mundo', Cadena::slug('Hola Mundo'));
    }

    public function testSlugConCaracteresEspeciales(): void
    {
        $this->assertSame('hola-mundo', Cadena::slug('Hola   Mundo!!!'));
    }

    public function testSlugVacio(): void
    {
        $this->assertSame('', Cadena::slug(''));
    }

    public function testSlugConNull(): void
    {
        $this->assertSame('', Cadena::slug(null));
    }

    public function testCapitalizar(): void
    {
        $this->assertSame('Hola', Cadena::capitalizar('hola'));
    }

    public function testCapitalizarYaCapitalizado(): void
    {
        $this->assertSame('Hola', Cadena::capitalizar('Hola'));
    }

    public function testCapitalizarConMayusculasInteriores(): void
    {
        $this->assertSame('Hola', Cadena::capitalizar('HOLA'));
    }

    public function testCapitalizarVacio(): void
    {
        $this->assertSame('', Cadena::capitalizar(''));
    }

    public function testCapitalizarConNull(): void
    {
        $this->assertSame('', Cadena::capitalizar(null));
    }

    public function testTitulo(): void
    {
        $this->assertSame('Hola Mundo', Cadena::titulo('hola mundo'));
    }

    public function testTituloVacio(): void
    {
        $this->assertSame('', Cadena::titulo(''));
    }

    public function testMinusculas(): void
    {
        $this->assertSame('hola mundo', Cadena::minusculas('HOLA MUNDO'));
    }

    public function testMinusculasVacio(): void
    {
        $this->assertSame('', Cadena::minusculas(''));
    }

    public function testMayusculas(): void
    {
        $this->assertSame('HOLA MUNDO', Cadena::mayusculas('hola mundo'));
    }

    public function testMayusculasVacio(): void
    {
        $this->assertSame('', Cadena::mayusculas(''));
    }

    public function testContieneEncuentraSubcadena(): void
    {
        $this->assertTrue(Cadena::contiene('Hola mundo', 'mundo'));
    }

    public function testContieneNoEncuentraSubcadena(): void
    {
        $this->assertFalse(Cadena::contiene('Hola mundo', 'adiós'));
    }

    public function testContieneCaseInsensitivePorDefecto(): void
    {
        $this->assertTrue(Cadena::contiene('Hola Mundo', 'mundo'));
    }

    public function testContieneCaseSensitive(): void
    {
        $this->assertFalse(Cadena::contiene('Hola Mundo', 'mundo', true));
        $this->assertTrue(Cadena::contiene('Hola Mundo', 'Mundo', true));
    }

    public function testContieneConCadenaVacia(): void
    {
        $this->assertFalse(Cadena::contiene('', 'algo'));
    }

    public function testContieneConNull(): void
    {
        $this->assertFalse(Cadena::contiene(null, 'algo'));
    }

    public function testIniciar(): void
    {
        $this->assertSame('Hol', Cadena::iniciar('Hola mundo', 3));
    }

    public function testIniciarVacio(): void
    {
        $this->assertSame('', Cadena::iniciar('', 3));
    }

    public function testTerminar(): void
    {
        $this->assertSame('ndo', Cadena::terminar('Hola mundo', 3));
    }

    public function testTerminarVacio(): void
    {
        $this->assertSame('', Cadena::terminar('', 3));
    }

    public function testAleatorioLongitud(): void
    {
        $this->assertSame(16, strlen(Cadena::aleatorio()));
    }

    public function testAleatorioLongitudPersonalizada(): void
    {
        $this->assertSame(32, strlen(Cadena::aleatorio(32)));
    }

    public function testAleatorioGeneraValoresDistintos(): void
    {
        $this->assertNotSame(Cadena::aleatorio(), Cadena::aleatorio());
    }

    public function testAleatorioCaracteresValidos(): void
    {
        $resultado = Cadena::aleatorio(64);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $resultado);
    }

    public function testEspaciar(): void
    {
        $this->assertSame('hola mundo', Cadena::espaciar('  hola   mundo  '));
    }

    public function testEspaciarVacio(): void
    {
        $this->assertSame('', Cadena::espaciar(''));
    }

    public function testEspaciarConNull(): void
    {
        $this->assertSame('', Cadena::espaciar(null));
    }

    public function testEnvolver(): void
    {
        $this->assertSame('<b>texto</b>', Cadena::envolver('texto', '<b>'));
    }

    public function testEnvolverConCierre(): void
    {
        $this->assertSame('<div class="x">texto</div>', Cadena::envolver('texto', '<div class="x">'));
    }

    public function testEnvolverConCierreExplicito(): void
    {
        $this->assertSame('<a>texto</a>', Cadena::envolver('texto', '<a>', '</a>'));
    }

    public function testEnvolverVacio(): void
    {
        $this->assertSame('', Cadena::envolver('', '<b>'));
    }

    public function testReemplazarEntre(): void
    {
        $this->assertSame('texto <reemplazado> mas', Cadena::reemplazarEntre('texto <original>contenido</original> mas', '<original>', '<reemplazado>'));
    }

    public function testReemplazarEntreVacio(): void
    {
        $this->assertSame('', Cadena::reemplazarEntre('', '[tag]', '[nuevo]'));
    }

    public function testExtraer(): void
    {
        $this->assertSame('mundo', Cadena::extraer('Hola [mundo] cruel', '[', ']'));
    }

    public function testExtraerSinMarcadorInicio(): void
    {
        $this->assertSame('', Cadena::extraer('Hola mundo', '{', '}'));
    }

    public function testExtraerSinMarcadorFin(): void
    {
        $this->assertSame('', Cadena::extraer('Hola {mundo cruel', '{', '}'));
    }

    public function testExtraerVacio(): void
    {
        $this->assertSame('', Cadena::extraer('', '[', ']'));
    }

    public function testPalabras(): void
    {
        $this->assertSame(3, Cadena::palabras('Hola mundo cruel'));
    }

    public function testPalabrasConEspaciosMultiples(): void
    {
        $this->assertSame(3, Cadena::palabras('  Hola   mundo  cruel  '));
    }

    public function testPalabrasVacio(): void
    {
        $this->assertSame(0, Cadena::palabras(''));
    }

    public function testPalabrasSoloEspacios(): void
    {
        $this->assertSame(0, Cadena::palabras('   '));
    }

    public function testPalabrasConNull(): void
    {
        $this->assertSame(0, Cadena::palabras(null));
    }

    public function testContarCaracteres(): void
    {
        $this->assertSame(9, Cadena::contarCaracteres('Hola mundo'));
    }

    public function testContarCaracteresVacio(): void
    {
        $this->assertSame(0, Cadena::contarCaracteres(''));
    }

    public function testContarCaracteresConNull(): void
    {
        $this->assertSame(0, Cadena::contarCaracteres(null));
    }

    public function testInvertir(): void
    {
        $this->assertSame('odnum aloH', Cadena::invertir('Hola mundo'));
    }

    public function testInvertirVacio(): void
    {
        $this->assertSame('', Cadena::invertir(''));
    }

    public function testHash(): void
    {
        $this->assertSame(8, strlen(Cadena::hash('Hola mundo')));
    }

    public function testHashEsDeterminista(): void
    {
        $this->assertSame(Cadena::hash('test'), Cadena::hash('test'));
    }

    public function testHashVacio(): void
    {
        $this->assertSame('', Cadena::hash(''));
    }

    public function testStripTags(): void
    {
        $this->assertSame('Hola mundo', Cadena::stripTags('<b>Hola</b> <i>mundo</i>'));
    }

    public function testStripTagsConPermitidas(): void
    {
        $this->assertSame('<b>Hola</b> mundo', Cadena::stripTags('<b>Hola</b> <i>mundo</i>', '<b>'));
    }

    public function testStripTagsVacio(): void
    {
        $this->assertSame('', Cadena::stripTags(''));
    }

    public function testEscapar(): void
    {
        $this->assertSame('&lt;b&gt;hola&lt;/b&gt;', Cadena::escapar('<b>hola</b>'));
    }

    public function testEscaparVacio(): void
    {
        $this->assertSame('', Cadena::escapar(''));
    }

    public function testDesescapar(): void
    {
        $this->assertSame('<b>hola</b>', Cadena::desescapar('&lt;b&gt;hola&lt;/b&gt;'));
    }

    public function testDesescaparVacio(): void
    {
        $this->assertSame('', Cadena::desescapar(''));
    }

    public function testEsEmailValido(): void
    {
        $this->assertTrue(Cadena::esEmail('usuario@ejemplo.com'));
    }

    public function testEsEmailInvalido(): void
    {
        $this->assertFalse(Cadena::esEmail('no-es-un-email'));
    }

    public function testEsEmailVacio(): void
    {
        $this->assertFalse(Cadena::esEmail(''));
    }

    public function testEsUrlValida(): void
    {
        $this->assertTrue(Cadena::esUrl('https://ejemplo.com'));
    }

    public function testEsUrlInvalida(): void
    {
        $this->assertFalse(Cadena::esUrl('no-es-una-url'));
    }

    public function testEsUrlVacio(): void
    {
        $this->assertFalse(Cadena::esUrl(''));
    }

    public function testEnmascarar(): void
    {
        $this->assertSame('1234****9012', Cadena::enmascarar('123456789012'));
    }

    public function testEnmascararCadenaCorta(): void
    {
        $resultado = Cadena::enmascarar('123456', '*', 2, 2);
        $this->assertSame('12**56', $resultado);
    }

    public function testEnmascararMuyCorta(): void
    {
        $resultado = Cadena::enmascarar('abc', '*', 4, 4);
        $this->assertSame('***', $resultado);
    }

    public function testEnmascararVacio(): void
    {
        $this->assertSame('', Cadena::enmascarar(''));
    }

    public function testEnmascararConNull(): void
    {
        $this->assertSame('', Cadena::enmascarar(null));
    }

    public function testNormalizar(): void
    {
        $this->assertSame('a e i o u n', Cadena::normalizar('á é í ó ú ñ'));
    }

    public function testNormalizarMayusculas(): void
    {
        $this->assertSame('A E I O U N', Cadena::normalizar('Á É Í Ó Ú Ñ'));
    }

    public function testNormalizarSinAcentos(): void
    {
        $this->assertSame('hola', Cadena::normalizar('hola'));
    }

    public function testNormalizarVacio(): void
    {
        $this->assertSame('', Cadena::normalizar(''));
    }

    public function testLimitarConNullPasaATipoString(): void
    {
        $this->assertSame('', Cadena::limitar(null, 5));
    }
}
