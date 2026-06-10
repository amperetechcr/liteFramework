<?php
use PHPUnit\Framework\TestCase;

class ConexionBaseDatosTest extends TestCase {
    protected function tearDown(): void {
        ConexionBaseDatos::resetearInstancia();
    }

    public function testObtenerInstanciaDevuelveSingleton(): void {
        $instancia1 = ConexionBaseDatos::obtenerInstancia();
        $instancia2 = ConexionBaseDatos::obtenerInstancia();
        $this->assertSame($instancia1, $instancia2);
    }

    public function testObtenerConectorDevuelvePDO(): void {
        $conector = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $this->assertInstanceOf(PDO::class, $conector);
    }

    public function testResetearInstanciaCreaNuevaConexion(): void {
        $instancia1 = ConexionBaseDatos::obtenerInstancia();
        ConexionBaseDatos::resetearInstancia();
        $instancia2 = ConexionBaseDatos::obtenerInstancia();
        $this->assertNotSame($instancia1, $instancia2);
    }

    public function testSqliteConectaYObtieneDatos(): void {
        $conector = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
        $roles = $conector->query("SELECT COUNT(*) FROM rbac_rol")->fetchColumn();
        $this->assertGreaterThanOrEqual(2, (int)$roles);
        $permisos = $conector->query("SELECT COUNT(*) FROM permisos")->fetchColumn();
        $this->assertGreaterThanOrEqual(5, (int)$permisos);
    }
}
