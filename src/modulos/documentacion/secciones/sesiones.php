<?php

return [
        'id' => 'sesiones',
        'titulo' => 'Sesiones y fingerprinting',
        'icono' => '🛡',
        'etiquetas' => 'sesion sesiones fingerprint huella seguridad gestor',
        'descripcion' => 'Gestion de sesiones PHP estrictas con huella digital del cliente, regeneracion de ID y proteccion contra secuestro de sesion.',
        'contenido' => '
            <p>El framework implementa sesiones seguras con multiples capas de proteccion. La clase <code>GestorSesiones</code> maneja la configuracion de cookies, mientras que <code>SeguridadServidor</code> aplica fingerprinting para detectar secuestros de sesion.</p>

            <h3 class="margen-inferior-pequeno">Iniciar sesion estricta</h3>
            <pre><code>SeguridadServidor::iniciarSesionEstricta();

// Configuracion de cookies de sesion
session_set_cookie_params([
    \'lifetime\' => 0,
    \'path\' => \'/\',
    \'domain\' => \'\',
    \'secure\' => true,
    \'httponly\' => true,
    \'samesite\' => \'Lax\',
]);</code></pre>

            <h3 class="margen-inferior-pequeno">Huella digital del cliente</h3>
            <p>Al iniciar sesion, se almacena una huella digital compuesta por User-Agent + IP + otros factores. En cada peticion se verifica que no haya cambiado. Si cambia, la sesion se invalida.</p>
            <pre><code>$_SESSION[\'huella_seguridad_cliente\'] = hash(\'sha256\',
    $_SERVER[\'HTTP_USER_AGENT\'] ?? \'\' .
    $_SERVER[\'REMOTE_ADDR\'] ?? \'\' .
    $_SERVER[\'HTTP_ACCEPT_LANGUAGE\'] ?? \'\'
);

$huellaActual = hash(\'sha256\',
    $_SERVER[\'HTTP_USER_AGENT\'] ?? \'\' .
    $_SERVER[\'REMOTE_ADDR\'] ?? \'\' .
    $_SERVER[\'HTTP_ACCEPT_LANGUAGE\'] ?? \'\'
);

if ($huellaActual !== ($_SESSION[\'huella_seguridad_cliente\'] ?? \'\')) {
    session_destroy();
    header(\'Location: \' . URL_BASE . \'/?error=sesion_invalida\');
    exit;
}</code></pre>

            <h3 class="margen-inferior-pequeno">Cabeceras de seguridad HTTP</h3>
            <pre><code>SeguridadServidor::establecerCabecerasSeguras();

header(\'X-Frame-Options: DENY\');
header(\'X-Content-Type-Options: nosniff\');
header(\'Referrer-Policy: strict-origin-when-cross-origin\');
header(\'X-XSS-Protection: 1; mode=block\');
header(\'Strict-Transport-Security: max-age=31536000; includeSubDomains\');</code></pre>

            <h3 class="margen-inferior-pequeno">Regenerar ID de sesion</h3>
            <p>Despues de login, siempre regenerar el ID para prevenir session fixation:</p>
            <pre><code>session_regenerate_id(true);  // true = borrar archivo de sesion anterior</code></pre>
        ',
    ];
