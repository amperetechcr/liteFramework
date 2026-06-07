<?php

declare(strict_types=1);

namespace LiteFramework\Servicios;

use PDO;
use LiteFramework\Config\ConexionBaseDatos;
use LiteFramework\Seguridad\GestorSesiones;
use LiteFramework\Nucleo\DialectoBaseDatos;

class AutenticacionOAuth
{
    private string $redirectBase;
    private string $googleId;
    private string $googleSecret;
    private string $githubId;
    private string $githubSecret;
    private PDO $conexion;

    private const STATE_TTL = 600;

    public function __construct()
    {
        $this->redirectBase = defined('OAUTH_REDIRECT_BASE') ? OAUTH_REDIRECT_BASE : URL_BASE;
        $this->googleId = defined('OAUTH_GOOGLE_ID') ? OAUTH_GOOGLE_ID : '';
        $this->googleSecret = defined('OAUTH_GOOGLE_SECRET') ? OAUTH_GOOGLE_SECRET : '';
        $this->githubId = defined('OAUTH_GITHUB_ID') ? OAUTH_GITHUB_ID : '';
        $this->githubSecret = defined('OAUTH_GITHUB_SECRET') ? OAUTH_GITHUB_SECRET : '';
        $this->conexion = ConexionBaseDatos::obtenerInstancia()->obtenerConector();
    }

    public function urlGoogle(): string
    {
        $state = $this->generarState();
        $params = http_build_query([
            'client_id' => $this->googleId,
            'redirect_uri' => $this->redirectBase . '/auth/google/callback',
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
            'state' => $state,
            'access_type' => 'online',
        ]);
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    public function urlGithub(): string
    {
        $state = $this->generarState();
        $params = http_build_query([
            'client_id' => $this->githubId,
            'redirect_uri' => $this->redirectBase . '/auth/github/callback',
            'scope' => 'read:user,user:email',
            'state' => $state,
        ]);
        return 'https://github.com/login/oauth/authorize?' . $params;
    }

    public function procesarGoogle(string $codigo, string $state): array
    {
        if (!$this->validarState($state)) {
            return $this->error('State inválido o expirado. Intenta de nuevo.');
        }

        $token = $this->peticionJson('https://oauth2.googleapis.com/token', [
            'code' => $codigo,
            'client_id' => $this->googleId,
            'client_secret' => $this->googleSecret,
            'redirect_uri' => $this->redirectBase . '/auth/google/callback',
            'grant_type' => 'authorization_code',
        ]);

        if (!isset($token['access_token'])) {
            return $this->error('Error al obtener token de Google.');
        }

        $perfil = $this->peticionGet('https://www.googleapis.com/oauth2/v2/userinfo', $token['access_token']);

        if (!isset($perfil['id']) || !isset($perfil['email'])) {
            return $this->error('No se pudo obtener el perfil de Google.');
        }

        if (empty($perfil['verified_email'])) {
            return $this->error('El correo no está verificado en Google.');
        }

        return $this->autenticar('google', $perfil['id'], $perfil['email'], $perfil['name'] ?? 'Usuario Google');
    }

    public function procesarGithub(string $codigo, string $state): array
    {
        if (!$this->validarState($state)) {
            return $this->error('State inválido o expirado. Intenta de nuevo.');
        }

        $token = $this->peticionJson('https://github.com/login/oauth/access_token', [
            'client_id' => $this->githubId,
            'client_secret' => $this->githubSecret,
            'code' => $codigo,
            'redirect_uri' => $this->redirectBase . '/auth/github/callback',
            'state' => $state,
        ], ['Accept: application/json']);

        if (!isset($token['access_token'])) {
            return $this->error('Error al obtener token de GitHub.');
        }

        $accessToken = $token['access_token'];

        $perfil = $this->peticionGet('https://api.github.com/user', $accessToken);

        if (!isset($perfil['id'])) {
            return $this->error('No se pudo obtener el perfil de GitHub.');
        }

        $correo = $perfil['email'] ?? null;

        if (!$correo) {
            $emails = $this->peticionGet('https://api.github.com/user/emails', $accessToken);
            if (is_array($emails)) {
                foreach ($emails as $e) {
                    if (!empty($e['primary']) && !empty($e['verified'])) {
                        $correo = $e['email'];
                        break;
                    }
                }
            }
        }

        if (!$correo) {
            return $this->error('No se pudo obtener un correo verificado de GitHub.');
        }

        return $this->autenticar('github', (string)$perfil['id'], $correo, $perfil['name'] ?? $perfil['login'] ?? 'Usuario GitHub');
    }

    private function autenticar(string $proveedor, string $idProveedor, string $correo, string $nombre): array
    {
        $vinculo = $this->buscarVinculo($proveedor, $idProveedor);

        if ($vinculo) {
            $operador = $this->buscarOperador($vinculo['id_operador']);
            if ($operador) {
                $this->iniciarSesion($operador);
                return $this->exito('/inicio');
            }
        }

        $operadorExistente = $this->buscarOperadorPorCorreo($correo);

        if ($operadorExistente) {
            $this->crearVinculo($proveedor, $idProveedor, (int)$operadorExistente['id_operador']);
            $this->iniciarSesion($operadorExistente);
            return $this->exito('/inicio');
        }

        $nuevoId = $this->crearOperador($correo, $nombre);
        $this->crearVinculo($proveedor, $idProveedor, $nuevoId);

        $operadorNuevo = $this->buscarOperador($nuevoId);
        $this->iniciarSesion($operadorNuevo);

        return $this->exito('/inicio');
    }

    public function desvincular(string $proveedor, int $idOperador): void
    {
        $stmt = $this->conexion->prepare(
            "DELETE FROM oauth_vinculo WHERE proveedor = :prov AND id_operador = :op"
        );
        $stmt->execute([':prov' => $proveedor, ':op' => $idOperador]);
    }

    private function generarState(): string
    {
        $token = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = [
            'token' => $token,
            'expira' => time() + self::STATE_TTL,
        ];
        return $token;
    }

    private function validarState(string $state): bool
    {
        $guardado = $_SESSION['oauth_state'] ?? null;
        unset($_SESSION['oauth_state']);

        if (!$guardado || !isset($guardado['token'], $guardado['expira'])) {
            return false;
        }

        if ($guardado['expira'] < time()) {
            return false;
        }

        return hash_equals($guardado['token'], $state);
    }

    private function buscarVinculo(string $proveedor, string $idProveedor): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT id_vinculo, id_operador FROM oauth_vinculo WHERE proveedor = :prov AND id_proveedor = :id"
        );
        $stmt->execute([':prov' => $proveedor, ':id' => $idProveedor]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function buscarOperador(int $id): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT id_operador, nombre_completo, correo_electronico, id_rol, estado_cuenta FROM operador WHERE id_operador = :id"
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function buscarOperadorPorCorreo(string $correo): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT id_operador, nombre_completo, correo_electronico, id_rol, estado_cuenta FROM operador WHERE correo_electronico = :correo"
        );
        $stmt->execute([':correo' => $correo]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function crearOperador(string $correo, string $nombre): int
    {
        $stmt = $this->conexion->prepare(
            "INSERT INTO operador (id_rol, nombre_completo, correo_electronico, clave_acceso, estado_cuenta)
             VALUES (2, :nombre, :correo, '', 1)"
        );
        $stmt->execute([':nombre' => $nombre, ':correo' => $correo]);
        return (int)$this->conexion->lastInsertId();
    }

    private function crearVinculo(string $proveedor, string $idProveedor, int $idOperador): void
    {
        $stmt = $this->conexion->prepare(
            "INSERT IGNORE INTO oauth_vinculo (proveedor, id_proveedor, id_operador) VALUES (:prov, :id, :op)"
        );
        $stmt->execute([':prov' => $proveedor, ':id' => $idProveedor, ':op' => $idOperador]);
    }

    private function iniciarSesion(array $operador): void
    {
        $_SESSION['operador_id'] = (int)$operador['id_operador'];
        $_SESSION['operador_nombre'] = $operador['nombre_completo'];
        $_SESSION['operador_correo'] = $operador['correo_electronico'];
        $_SESSION['operador_id_rol'] = (int)$operador['id_rol'];
        $_SESSION['operador_es_admin'] = (int)$operador['id_rol'] === 1;
        session_regenerate_id(true);
    }

    private function peticionJson(string $url, array $datos, array $cabecerasExtras = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($datos),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/x-www-form-urlencoded'], $cabecerasExtras),
        ]);
        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($respuesta === false || $httpCode >= 400) {
            return [];
        }

        $datos = json_decode($respuesta, true);
        return is_array($datos) ? $datos : [];
    }

    private function peticionGet(string $url, string $accessToken): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
                'User-Agent: liteFramework-OAuth',
            ],
        ]);
        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($respuesta === false || $httpCode >= 400) {
            return [];
        }

        $datos = json_decode($respuesta, true);
        return is_array($datos) ? $datos : [];
    }

    private function error(string $mensaje): array
    {
        return ['exito' => false, 'mensaje' => $mensaje];
    }

    private function exito(string $redireccion): array
    {
        return ['exito' => true, 'redireccion' => $redireccion];
    }
}
