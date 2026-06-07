<?php
use PHPUnit\Framework\TestCase;

class AuditoriaCompletaTest extends TestCase {
    public function testAuditoriaPhpUsaHEnVezDeHtmlspecialchars(): void {
        $vista = file_get_contents(DIRECTORIO_RAIZ . '/src/modulos/auditoria/auditoria.php');
        $this->assertStringNotContainsString('htmlspecialchars(', $vista);
        $this->assertStringContainsString('h($ev', $vista);
    }

    public function testSubirArchivosAuditaDescargas(): void {
        $ctrl = file_get_contents(DIRECTORIO_RAIZ . '/servidor/controladores/SubirArchivosControlador.php');
        $this->assertStringContainsString("'Descarga de archivo'", $ctrl);
    }

    public function testOperadorApiAuditaCambioContrasena(): void {
        $ctrl = file_get_contents(DIRECTORIO_RAIZ . '/servidor/api/controladores/OperadorApiControlador.php');
        $this->assertStringContainsString("'Cambio de contrasena'", $ctrl);
    }

    public function testProcesarPeticionAuditaPayloadInvalido(): void {
        $api = file_get_contents(DIRECTORIO_RAIZ . '/servidor/api/procesarPeticionPost.php');
        $this->assertStringContainsString("'Payload invalido'", $api);
    }

    public function testRegistroAuditoriaContextoIncluyeSessionId(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/servidor/seguridad/RegistroAuditoria.php');
        $this->assertStringContainsString('session_id', $contenido);
    }

    public function testRegistroAuditoriaContextoIncluyeHttpReferer(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/servidor/seguridad/RegistroAuditoria.php');
        $this->assertStringContainsString('http_referer', $contenido);
    }

    public function testRegistroAuditoriaContextoIncluyeDuracion(): void {
        $contenido = file_get_contents(DIRECTORIO_RAIZ . '/servidor/seguridad/RegistroAuditoria.php');
        $this->assertStringContainsString('duracion_ms', $contenido);
    }
}
