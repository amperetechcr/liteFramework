<?php
$paletaClase = 'paleta-' . (configUI('paleta') ?? 'indigo');
$estiloClase = 'estilo-' . (configUI('estilo') ?? 'moderno');
$fondoClase = claseFondoHTML();
$fuenteClase = claseFuenteHTML();
$espaciadoClase = claseEspaciadoHTML();
$tamanoClase = claseTamanoHTML();
$clasesHtml = trim($paletaClase . ' ' . $estiloClase . ' ' . $fondoClase . ' ' . $fuenteClase . ' ' . $espaciadoClase . ' ' . $tamanoClase);
?>
<!DOCTYPE html>
<html lang="es-CR" class="<?= $clasesHtml ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lite Framework &mdash; Framework PHP sin dependencias</title>
    <meta name="description" content="Framework PHP con MVC, ORM, RBAC, CSRF, migraciones y personalización de UI. Sin Composer, npm ni dependencias externas.">
    <meta name="author" content="Ampere Tech Costa Rica S.A.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= URL_BASE ?>/ingreso">

    <meta property="og:title" content="Lite Framework">
    <meta property="og:description" content="Framework PHP con MVC, ORM, RBAC, CSRF y personalización de UI. Cero dependencias.">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_CR">

    <meta name="twitter:card" content="summary">

    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/tema.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/maquetacion.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/componentes.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/estilos.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/utilidades.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/personalizacion.css">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Lite Framework",
        "url": "<?= URL_BASE ?>/",
        "description": "Framework PHP con MVC, ORM, RBAC, CSRF, migraciones y personalización de UI. Sin Composer, npm ni dependencias externas.",
        "inLanguage": "es-CR",
        "publisher": {
            "@type": "Organization",
            "name": "Ampere Tech Costa Rica S.A."
        }
    }
    </script>
    <meta name="api-base" content="<?= URL_BASE ?>/api">
    <script type="module" src="<?= URL_BASE ?>/src/js/principal.js"></script>
</head>
<body
    data-error="<?= htmlspecialchars($codigoError ?? '', ENT_QUOTES, 'UTF-8') ?>"
    data-mensaje="<?= htmlspecialchars($codigoMensaje ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
    <a href="#contenido-principal" class="enlace-salto">Saltar al contenido principal</a>

    <main id="contenido-principal" class="marco-del-sitio lienzo-centrado-vertical">
        <div class="ancho-total">

            <header class="cabecera-principal margen-inferior-grande">
                <div>
                    <h1>Lite Framework</h1>
                    <p class="texto-color-marca texto-negrita">Framework PHP &bull; MVC &bull; ORM &bull; RBAC &bull; CSRF &bull; Cero dependencias</p>
                </div>
                <button type="button" id="alternador-tema" class="alternador-tema" aria-label="Alternar tema claro/oscuro">&#x2600;</button>
            </header>

            <section class="margen-inferior-normal alineacion-centrada" aria-label="Descripción del framework">
                <p class="texto-grande ancho-max-720 margen-horizontal-auto">
                    Un framework PHP completo construido exclusivamente con tecnologías nativas.
                    Arquitectura MVC, ORM Active Record, autenticación con RBAC, protección CSRF,
                    migraciones de base de datos y personalización de UI en tiempo real.
                    <strong>Sin Composer, sin npm, sin dependencias externas.</strong>
                </p>
            </section>

            <section class="margen-inferior-normal" aria-label="Estadísticas del sistema">
                <div class="rejilla-automatica">
                    <article class="alineacion-centrada">
                        <p class="texto-gigante texto-color-marca"><?= $totalUsuarios ?></p>
                        <p class="texto-pequeno">Operadores registrados</p>
                    </article>
                    <article class="alineacion-centrada">
                        <p class="texto-gigante texto-color-marca"><?= $totalRoles ?></p>
                        <p class="texto-pequeno">Roles de acceso (RBAC)</p>
                    </article>
                    <article class="alineacion-centrada">
                        <p class="texto-gigante texto-color-marca">4</p>
                        <p class="texto-pequeno">Módulos del sistema</p>
                    </article>
                    <article class="alineacion-centrada">
                        <p class="texto-gigante texto-color-marca">0</p>
                        <p class="texto-pequeno">Dependencias externas</p>
                    </article>
                </div>
            </section>

            <section class="margen-inferior-normal" aria-label="Stack tecnológico">
                <h2 class="margen-inferior-normal alineacion-centrada">Stack tecnológico</h2>
                <div class="rejilla-automatica">
                    <article class="alineacion-centrada">
                        <h3 class="texto-color-marca">PHP 8+</h3>
                        <p class="texto-pequeno">Backend puro con PDO, prepared statements, sesiones nativas, error handlers y autoloader propio.</p>
                    </article>
                    <article class="alineacion-centrada">
                        <h3 class="texto-color-marca">JavaScript ES6</h3>
                        <p class="texto-pequeno">Módulos nativos, AJAX con fetch, navegación SPA, notificaciones toast y validación de formularios.</p>
                    </article>
                    <article class="alineacion-centrada">
                        <h3 class="texto-color-marca">CSS3</h3>
                        <p class="texto-pequeno">Variables CSS, sistema de paletas, estilos intercambiables y personalización completa sin preprocesadores.</p>
                    </article>
                    <article class="alineacion-centrada">
                        <h3 class="texto-color-marca">MySQL</h3>
                        <p class="texto-pequeno">Esquema normalizado con migraciones versionadas, charset utf8mb4 y consultas parametrizadas.</p>
                    </article>
                </div>
            </section>

            <section class="margen-inferior-normal" aria-label="Características principales">
                <h2 class="margen-inferior-normal alineacion-centrada">Características principales</h2>
                <div class="rejilla-automatica">
                    <article>
                        <h3 class="texto-color-marca">Enrutador MVC</h3>
                        <p class="texto-pequeno">Sistema de rutas con parámetros dinámicos, interceptores por ruta, rutas nombradas y despacho automático a controladores.</p>
                    </article>
                    <article>
                        <h3 class="texto-color-marca">Seguridad integral</h3>
                        <p class="texto-pequeno">CSRF con rotación de tokens por petición y ventana de gracia de 60s. RBAC granular con permisos por entidad. Sesiones con huella digital anti-secuestro.</p>
                    </article>
                    <article>
                        <h3 class="texto-color-marca">ORM Active Record</h3>
                        <p class="texto-pequeno">Query builder encadenable, relaciones entre modelos, migraciones versionadas, paginación automática y validación de esquemas contra whitelist.</p>
                    </article>
                    <article>
                        <h3 class="texto-color-marca">Autenticación completa</h3>
                        <p class="texto-pequeno">Login y registro asíncrono con AJAX. Rate limiting configurable, bloqueo por intentos fallidos, políticas de contraseña y cierre de sesión seguro.</p>
                    </article>
                    <article>
                        <h3 class="texto-color-marca">Personalización UI</h3>
                        <p class="texto-pequeno">8 paletas de color, 5 estilos visuales, 8 presets completos, 5 tipografías y 3 densidades. Todo intercambiable en tiempo real sin recargar.</p>
                    </article>
                    <article>
                        <h3 class="texto-color-marca">Auditoría y trazabilidad</h3>
                        <p class="texto-pequeno">Registro dual de eventos en base de datos y archivo de log. Cada petición recibe un Trace ID único para seguimiento completo.</p>
                    </article>
                </div>
            </section>

            <section class="margen-inferior-normal" aria-label="Módulos del panel">
                <h2 class="margen-inferior-normal alineacion-centrada">Módulos del panel</h2>
                <div class="rejilla-automatica">
                    <article>
                        <h3 class="texto-color-marca">Panel de inicio</h3>
                        <p class="texto-pequeno">Dashboard con estadísticas generales del sistema: operadores activos, roles y resumen de actividad reciente.</p>
                    </article>
                    <article>
                        <h3 class="texto-color-marca">Gestión de operadores</h3>
                        <p class="texto-pequeno">CRUD completo de usuarios con asignación de roles, búsqueda, paginación y filtros. Cada acción registrada en auditoría.</p>
                    </article>
                    <article>
                        <h3 class="texto-color-marca">Auditoría</h3>
                        <p class="texto-pequeno">Bitácora completa del sistema con filtros por módulo, operador, acción y rango de fechas. Exportable y consultable en tiempo real.</p>
                    </article>
                    <article>
                        <h3 class="texto-color-marca">Configuración</h3>
                        <p class="texto-pequeno">Perfil de usuario y personalización completa de la interfaz: paleta, estilo, fuente, espaciado y tamaño. Cambios instantáneos.</p>
                    </article>
                </div>
            </section>

            <section aria-label="Autenticación">
                <h2 class="margen-inferior-normal alineacion-centrada">Acceder al sistema</h2>
                <div class="rejilla-automatica">
                    <article>
                        <h3 class="margen-inferior-normal">Iniciar sesión</h3>
                        <form id="formularioInicioSesion" class="agrupador-flexible-columnas" method="POST" novalidate>
                            <input type="hidden" name="token_peticion" value="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="accion_crud" value="iniciar_sesion">
                            <div class="grupo-campo campo-agrupado">
                                <label for="correo_login">Correo electrónico</label>
                                <input type="email" id="correo_login" name="correo" required autocomplete="username" placeholder="usuario@dominio.com" aria-required="true" aria-describedby="desc-correo-login">
                                <span id="desc-correo-login" class="texto-pequeno texto-suave">Ingresa el correo con el que te registraste.</span>
                            </div>
                            <div class="grupo-campo campo-agrupado">
                                <label for="clave_login">Contraseña</label>
                                <input type="password" id="clave_login" name="clave" required autocomplete="current-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" aria-required="true" aria-describedby="desc-clave-login">
                                <span id="desc-clave-login" class="texto-pequeno texto-suave">Tu contraseña personal de acceso.</span>
                            </div>
                            <p class="texto-xs texto-suave margen-superior-minimo">Al iniciar sesión, aceptas nuestros <a href="<?= URL_BASE ?>/LICENSE.md" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;text-underline-offset:2px">términos y condiciones</a>.</p>
                            <button type="submit" class="ancho-total">Iniciar sesión</button>
                        </form>
                    </article>

                    <article>
                        <h3 class="margen-inferior-normal">Registrar operador</h3>
                        <form id="formularioRegistro" class="agrupador-flexible-columnas" method="POST" novalidate>
                            <input type="hidden" name="token_peticion" value="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="accion_crud" value="registrar_operador">
                            <div class="grupo-campo campo-agrupado">
                                <label for="nombre_registro">Nombre completo</label>
                                <input type="text" id="nombre_registro" name="nombre_completo" required autocomplete="name" placeholder="Escriba su nombre completo" aria-required="true" aria-describedby="desc-nombre-registro">
                                <span id="desc-nombre-registro" class="texto-pequeno texto-suave">Tu nombre real para identificar tu cuenta.</span>
                            </div>
                            <div class="grupo-campo campo-agrupado">
                                <label for="correo_registro">Correo electrónico</label>
                                <input type="email" id="correo_registro" name="correo_electronico" required autocomplete="email" placeholder="operador@dominio.com" aria-required="true" aria-describedby="desc-correo-registro">
                                <span id="desc-correo-registro" class="texto-pequeno texto-suave">Se usará para iniciar sesión.</span>
                            </div>
                            <div class="grupo-campo campo-agrupado">
                                <label for="clave_registro">Contraseña</label>
                                <input type="password" id="clave_registro" name="clave_registro" required autocomplete="new-password" pattern="(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}" title="Mínimo 8 caracteres, una mayúscula, un número y un símbolo (@$!%*?&)" placeholder="Mín. 8 carac., 1 mayúscula, 1 número, 1 símbolo" aria-required="true" aria-describedby="desc-clave-registro">
                                <span id="desc-clave-registro" class="texto-pequeno texto-suave">Mínimo 8 caracteres, una mayúscula, un número y un símbolo.</span>
                            </div>
                            <p class="texto-xs texto-suave margen-superior-minimo">Al crear una cuenta, aceptas nuestros <a href="<?= URL_BASE ?>/LICENSE.md" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;text-underline-offset:2px">términos y condiciones</a>.</p>
                            <button type="submit" class="ancho-total">Crear cuenta</button>
                        </form>
                    </article>
                </div>
            </section>

            <footer class="alineacion-centrada margen-superior-normal">
                <hr>
                <p class="texto-pequeno">&copy; <?= date('Y') ?> Ampere Tech Costa Rica S.A. &bull; Lite Framework v1.1.0</p>
                <p class="texto-xs texto-suave margen-superior-minimo">
                    Al acceder y utilizar este sistema, usted acepta los
                    <a href="<?= URL_BASE ?>/LICENSE.md" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;text-underline-offset:2px">términos y condiciones</a>
                    establecidos en la licencia <strong>Apache 2.0 + Commons Clause</strong>.
                </p>
            </footer>

        </div>
    </main>

</body>
</html>
