<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Servicios;

use LiteFramework\Servicios\Correo;
use Psr\Log\NullLogger;

class CorreoTest extends \TestBase
{
    private Correo $correo;

    public function setUp(): void
    {
        $this->correo = new Correo();
    }

    public function testDesdeEntornoConConstantes(): void
    {
        defined('MAIL_ANFITRION') || define('MAIL_ANFITRION', 'smtp.ejemplo.com');
        defined('MAIL_PUERTO') || define('MAIL_PUERTO', '587');
        defined('MAIL_USUARIO') || define('MAIL_USUARIO', 'user');
        defined('MAIL_CLAVE') || define('MAIL_CLAVE', 'secret');
        defined('MAIL_REMITENTE') || define('MAIL_REMITENTE', 'test@ejemplo.com');
        defined('MAIL_TLS') || define('MAIL_TLS', true);

        $correo = Correo::desdeEntorno();
        $this->assertInstanceOf(Correo::class, $correo);
    }

    public function testConfigurarAsignaPropiedades(): void
    {
        $this->correo->configurar('smtp.test.com', 587, 'user', 'pass', 'from@test.com', 'FromName', true);
        $this->correo->para('to@test.com');

        $ref = new \ReflectionClass($this->correo);
        $getProp = fn(string $p) => $ref->getProperty($p)->getValue($this->correo);

        $this->assertSame('from@test.com', $getProp('remitente'));
        $this->assertSame('FromName', $getProp('remitenteNombre'));
        $this->assertSame(587, $getProp('puerto'));
    }

    public function testParaConEmailValido(): void
    {
        $ret = $this->correo->para('user@example.com', 'User');
        $this->assertSame($this->correo, $ret);

        $ref = new \ReflectionClass($this->correo);
        $this->assertSame('user@example.com', $ref->getProperty('destinatario')->getValue($this->correo));
    }

    public function testParaConEmailInvalido(): void
    {
        $ret = $this->correo->para('no-es-email');
        $this->assertSame($this->correo, $ret);
    }

    public function testAsuntoAlmacena(): void
    {
        $ret = $this->correo->asunto('Mi asunto');
        $this->assertSame($this->correo, $ret);
    }

    public function testCuerpoConHtmlGeneraTextoPlano(): void
    {
        $this->correo->cuerpo('<p>Hola <b>Mundo</b></p>');
        $ref = new \ReflectionClass($this->correo);
        $this->assertSame('<p>Hola <b>Mundo</b></p>', $ref->getProperty('cuerpoHtml')->getValue($this->correo));
        $this->assertSame('Hola Mundo', $ref->getProperty('cuerpoTexto')->getValue($this->correo));
    }

    public function testAdjuntarConArchivoValido(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'correo_');
        file_put_contents($tmpFile, 'contenido');
        $ret = $this->correo->adjuntar($tmpFile);
        $this->assertSame($this->correo, $ret);
        unlink($tmpFile);
    }

    public function testAdjuntarConArchivoInexistenteLogeaWarning(): void
    {
        $logger = new class implements \Psr\Log\LoggerInterface {
            public array $logs = [];
            public function emergency($message, array $context = []): void { $this->logs[] = 'emergency'; }
            public function alert($message, array $context = []): void { $this->logs[] = 'alert'; }
            public function critical($message, array $context = []): void { $this->logs[] = 'critical'; }
            public function error($message, array $context = []): void { $this->logs[] = 'error'; }
            public function warning($message, array $context = []): void { $this->logs[] = 'warning'; }
            public function notice($message, array $context = []): void { $this->logs[] = 'notice'; }
            public function info($message, array $context = []): void { $this->logs[] = 'info'; }
            public function debug($message, array $context = []): void { $this->logs[] = 'debug'; }
            public function log($level, $message, array $context = []): void { $this->logs[] = $level; }
        };
        $correo = new Correo($logger);
        $correo->adjuntar('/ruta/que/no/existe.pdf');
        $this->assertNotEmpty($logger->logs);
    }

    public function testAgregarCopiaAcumula(): void
    {
        $this->correo->agregarCopia('cc1@test.com');
        $this->correo->agregarCopia('cc2@test.com');

        $ref = new \ReflectionClass($this->correo);
        $ccs = $ref->getProperty('destinatariosCopia')->getValue($this->correo);
        $this->assertCount(2, $ccs);
        $this->assertContains('cc1@test.com', $ccs);
    }

    public function testEnviarFallaGraciablementeCuandoSmptNoAlcanzable(): void
    {
        $this->correo->configurar('192.0.2.1', 25, '', '', 'test@test.com')
            ->para('dest@test.com')
            ->asunto('Test')
            ->cuerpo('<p>test</p>');

        $resultado = $this->correo->enviar();
        $this->assertFalse($resultado);
    }

    public function testConstructorConNullLoggerPorDefecto(): void
    {
        $ref = new \ReflectionClass($this->correo);
        $logger = $ref->getProperty('logger')->getValue($this->correo);
        $this->assertInstanceOf(NullLogger::class, $logger);
    }

    public function testConstructorConLoggerPersonalizado(): void
    {
        $logger = new NullLogger();
        $correo = new Correo($logger);
        $ref = new \ReflectionClass($correo);
        $this->assertSame($logger, $ref->getProperty('logger')->getValue($correo));
    }

    public function testFluentInterfaceRetornaThis(): void
    {
        $this->assertSame($this->correo, $this->correo->configurar('h', 25, '', '', 't@t.com'));
        $this->assertSame($this->correo, $this->correo->para('t@t.com'));
        $this->assertSame($this->correo, $this->correo->asunto('s'));
        $this->assertSame($this->correo, $this->correo->cuerpo('<p>c</p>'));
        $this->assertSame($this->correo, $this->correo->agregarCopia('c@t.com'));
    }
}
