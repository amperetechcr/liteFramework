<?php

return [
        'id' => 'autenticacion',
        'titulo' => 'Autenticacion',
        'icono' => '🔑',
        'etiquetas' => 'autenticacion login sesion sesiones contrasena password politica',
        'descripcion' => 'Flujo de inicio de sesion, validacion de credenciales, politica de contrasenas, renovacion de sesion y cierre.',
        'contenido' => '
            <p>El sistema de autenticacion usa sesiones PHP estrictas con regeneracion de ID tras login exitoso, huella digital del cliente, y politica de contrasenas configurable.</p>

            <h3 class="margen-inferior-pequeno">Iniciar sesion</h3>
            <pre><code>SeguridadServidor::iniciarSesionEstricta();

$correo = SanitizadorEntrada::limpiar($_POST[\'correo_electronico\'] ?? \'\');
$clave = $_POST[\'clave_acceso\'] ?? \'\';

$operador = Operador::donde(\'correo_electronico\', \'=\', $correo)->primero();

if (!$operador || !password_verify($clave, $operador->clave_acceso)) {
    echo json_encode([\'error\' => \'Credenciales invalidas\']);
    return;
}

if ($operador->estado_cuenta != 1) {
    echo json_encode([\'error\' => \'Cuenta suspendida\']);
    return;
}

session_regenerate_id(true);

$_SESSION[\'operador_id\'] = $operador->id_operador;
$_SESSION[\'operador_nombre\'] = $operador->nombre_completo;
$_SESSION[\'operador_rol\'] = (int)$operador->id_rol;
$_SESSION[\'matriz_permisos\'] = ControlAccesoRBAC::obtenerPermisos($operador->id_rol);

RegistroAuditoria::auditoria(\'autenticacion.iniciar\', \'Inicio de sesion\', [
    \'operador_id\' => $operador->id_operador,
]);

echo json_encode([\'exito\' => true, \'redireccion\' => URL_BASE . \'/panelControl\']);</code></pre>

            <h3 class="margen-inferior-pequeno">Verificar autenticacion</h3>
            <p>Los controladores extienden <code>ControladorBase</code> que provee metodos de verificacion:</p>
            <pre><code>class MiControlador extends ControladorBase {
    public function indice(): void {
        $this->verificarAutenticacion();
        $id = $this->obtenerIdOperador();
        $nombre = $this->obtenerNombreOperador();
        $rol = $this->obtenerIdRol();
        $permisos = $this->obtenerPermisos();
    }
}</code></pre>

            <h3 class="margen-inferior-pequeno">Politica de contrasenas</h3>
            <pre><code>$politica = new PoliticaContrasena();
$resultado = $politica->validar($clave);

if (!$resultado[\'valida\']) {
    $errores = $resultado[\'errores\'];
    // [\'minimo_8_caracteres\', \'al_menos_1_mayuscula\', \'al_menos_1_numero\', \'al_menos_1_simbolo\']
}

$hash = password_hash($clave, PASSWORD_BCRYPT, [\'cost\' => 12]);
$operador->clave_acceso = $hash;
$operador->guardar();</code></pre>

            <h3 class="margen-inferior-pequeno">Cerrar sesion</h3>
            <pre><code>SeguridadServidor::iniciarSesionEstricta();
$_SESSION = [];
session_destroy();
header(\'Location: \' . URL_BASE . \'/\');
exit;</code></pre>
        ',
    ];
