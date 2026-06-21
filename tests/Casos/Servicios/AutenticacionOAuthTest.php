<?php

declare(strict_types=1);

namespace LiteFramework\Tests\Casos\Servicios;

use LiteFramework\Servicios\AutenticacionOAuth;

class AutenticacionOAuthTest extends \TestBase
{
    public function setUp(): void
    {
        $_SESSION = [];
    }

    private function crearAuth(): ?AutenticacionOAuth
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension required');
        }
        try {
            return new AutenticacionOAuth();
        } catch (\Throwable $e) {
            $this->markTestSkipped('DB not available: ' . $e->getMessage());
            return null;
        }
    }

    public function testUrlGoogleRetornaUrlValida(): void
    {
        $auth = $this->crearAuth();
        if ($auth === null) {
            return;
        }
        $url = $auth->urlGoogle();
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=', $url);
        $this->assertStringContainsString('scope=', $url);
    }

    public function testUrlGithubRetornaUrlValida(): void
    {
        $auth = $this->crearAuth();
        if ($auth === null) {
            return;
        }
        $url = $auth->urlGithub();
        $this->assertStringStartsWith('https://github.com/login/oauth/authorize?', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('state=', $url);
        $this->assertStringContainsString('scope=', $url);
    }

    public function testConstructorLeeConstantesOauth(): void
    {
        $auth = $this->crearAuth();
        if ($auth === null) {
            return;
        }
        $ref = new \ReflectionClass($auth);
        $this->assertNotNull($ref->getProperty('googleId')->getValue($auth));
        $this->assertNotNull($ref->getProperty('githubId')->getValue($auth));
    }

    public function testProcesarGoogleConStateInvalidoRetornaError(): void
    {
        $auth = $this->crearAuth();
        if ($auth === null) {
            return;
        }
        $resultado = $this->invocarProcesarGoogle($auth, 'codigo_cualquiera', 'state_invalido');
        $this->assertFalse($resultado['exito']);
        $this->assertStringContainsString('State', $resultado['mensaje']);
    }

    public function testProcesarGithubConStateInvalidoRetornaError(): void
    {
        $auth = $this->crearAuth();
        if ($auth === null) {
            return;
        }
        $resultado = $this->invocarProcesarGithub($auth, 'codigo_cualquiera', 'state_invalido');
        $this->assertFalse($resultado['exito']);
        $this->assertStringContainsString('State', $resultado['mensaje']);
    }

    public function testStateExpiradoEsRechazado(): void
    {
        $auth = $this->crearAuth();
        if ($auth === null) {
            return;
        }
        $ref = new \ReflectionClass($auth);
        $method = $ref->getMethod('validarState');
        $method->setAccessible(true);

        $_SESSION['oauth_state'] = [
            'token' => 'token_valido',
            'expira' => time() - 1,
        ];

        $resultado = $method->invoke($auth, 'token_valido');
        $this->assertFalse($resultado);
    }

    public function testStateTtlVerification(): void
    {
        $ref = new \ReflectionClass(AutenticacionOAuth::class);
        $ttl = $ref->getReflectionConstant('STATE_TTL');
        $this->assertNotNull($ttl);
        $this->assertSame(600, $ttl->getValue());
    }

    public function testProcesarGoogleConStateValidoContinua(): void
    {
        $auth = $this->crearAuth();
        if ($auth === null) {
            return;
        }
        $_SESSION['oauth_state'] = [
            'token' => 'state_valido_google',
            'expira' => time() + 60,
        ];
        $resultado = $this->invocarProcesarGoogle($auth, 'codigo_test', 'state_valido_google');
        $this->assertIsArray($resultado);
    }

    public function testProcesarGithubConStateValidoContinua(): void
    {
        $auth = $this->crearAuth();
        if ($auth === null) {
            return;
        }
        $_SESSION['oauth_state'] = [
            'token' => 'state_valido_gh',
            'expira' => time() + 60,
        ];
        $resultado = $this->invocarProcesarGithub($auth, 'codigo_gh', 'state_valido_gh');
        $this->assertIsArray($resultado);
    }

    public function testUrlGoogleGeneraStateEnSesion(): void
    {
        $auth = $this->crearAuth();
        if ($auth === null) {
            return;
        }
        $auth->urlGoogle();
        $this->assertArrayHasKey('oauth_state', $_SESSION);
        $this->assertArrayHasKey('token', $_SESSION['oauth_state']);
        $this->assertArrayHasKey('expira', $_SESSION['oauth_state']);
    }

    public function testUrlGithubGeneraStateEnSesion(): void
    {
        $auth = $this->crearAuth();
        if ($auth === null) {
            return;
        }
        $auth->urlGithub();
        $this->assertArrayHasKey('oauth_state', $_SESSION);
    }

    public function testStateLifetimeIs600(): void
    {
        $ref = new \ReflectionClass(AutenticacionOAuth::class);
        $this->assertSame(600, $ref->getReflectionConstant('STATE_TTL')->getValue());
    }

    private function invocarProcesarGoogle(AutenticacionOAuth $auth, string $codigo, string $state): array
    {
        $ref = new \ReflectionClass($auth);
        $method = $ref->getMethod('procesarGoogle');
        $method->setAccessible(true);
        return $method->invoke($auth, $codigo, $state);
    }

    private function invocarProcesarGithub(AutenticacionOAuth $auth, string $codigo, string $state): array
    {
        $ref = new \ReflectionClass($auth);
        $method = $ref->getMethod('procesarGithub');
        $method->setAccessible(true);
        return $method->invoke($auth, $codigo, $state);
    }
}
