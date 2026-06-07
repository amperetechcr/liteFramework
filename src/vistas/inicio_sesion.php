<?php
$paletaClase = 'paleta-' . (configUI('paleta') ?? 'indigo');
$estiloClase = 'estilo-' . (configUI('estilo') ?? 'moderno');
$fondoClase = claseFondoHTML();
$fuenteClase = claseFuenteHTML();
$espaciadoClase = claseEspaciadoHTML();
$tamanoClase = claseTamanoHTML();
$clasesHtml = trim($paletaClase . ' ' . $estiloClase . ' ' . $fondoClase . ' ' . $fuenteClase . ' ' . $espaciadoClase . ' ' . $tamanoClase);
$modulos = count(glob(__DIR__ . '/../modulos/*', GLOB_ONLYDIR));
?>
<!DOCTYPE html>
<html lang="es-CR" class="<?= $clasesHtml ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lite Framework &mdash; Framework PHP sin dependencias</title>
    <link rel="icon" type="image/png" href="<?= URL_BASE ?>/src/img/favicon.png">
    <meta name="description" content="Framework PHP con MVC, ORM, RBAC, CSRF, migraciones y personalización de UI. Sin Composer, npm ni dependencias externas.">
    <meta name="author" content="Ampere Tech Costa Rica S.A.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= URL_BASE ?>/">

    <meta property="og:title" content="Lite Framework">
    <meta property="og:description" content="Framework PHP con MVC, ORM, RBAC, CSRF y personalización de UI. Cero dependencias.">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_CR">

    <meta name="twitter:card" content="summary">

    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/tema.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/paletas.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/maquetacion.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/componentes.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/modales.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/estilos.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/utilidades.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/personalizacion.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/apariencia.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/oauth.css">

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

    <!-- Navbar -->
    <header class="cabecera-principal" style="position:sticky;top:0;z-index:100;background:var(--fondo-pantalla);border-bottom:1px solid var(--trazo-suave);">
        <div style="display:flex;align-items:center;justify-content:space-between;max-width:1200px;margin:0 auto;padding:12px 24px;width:100%;">
            <strong style="font-size:1.25rem;">Lite Framework</strong>
            <div style="display:flex;align-items:center;gap:20px;">
                <a href="#features" style="color:inherit;text-decoration:none;font-size:.9rem;">Características</a>
                <a href="#stack" style="color:inherit;text-decoration:none;font-size:.9rem;">Stack</a>
                <a href="#acceso" style="color:inherit;text-decoration:none;font-size:.9rem;">Acceder</a>
                <button type="button" id="alternador-tema" class="alternador-tema" aria-label="Alternar tema claro/oscuro" style="margin:0;">&#x2600;</button>
            </div>
        </div>
    </header>

    <main id="contenido-principal">

        <!-- Hero -->
        <section class="marco-del-sitio alineacion-centrada" style="padding:100px 24px 60px;">
            <div class="ancho-max-720 margen-horizontal-auto">
                <h1 style="font-size:clamp(2.2rem,5vw,3.5rem);font-weight:800;line-height:1.15;margin-bottom:16px;">
                    Framework PHP <span class="texto-color-marca">sin dependencias</span>
                </h1>
                <p class="texto-grande texto-suave" style="line-height:1.7;margin-bottom:40px;">
                    MVC, ORM Active Record, RBAC, CSRF con rotación, migraciones versionadas, auditoría con trazabilidad, panel SPA con 13 módulos y generación de CRUD en un comando. Todo en PHP 8.2+ nativo.
                </p>
                <a href="#acceso" class="btn ancho-max-320 margen-horizontal-auto" style="text-decoration:none;display:block;text-align:center;padding:16px;font-size:1.1rem;font-weight:600;">
                    Comenzar ahora
                </a>
            </div>
            <div class="rejilla-automatica" style="margin-top:60px;max-width:900px;margin-left:auto;margin-right:auto;">
                <article class="tarjeta alineacion-centrada">
                    <p style="font-size:2rem;margin-bottom:4px;">👥</p>
                    <p class="texto-gigante texto-color-marca texto-negrita"><?= $totalUsuarios ?></p>
                    <p class="texto-xs texto-suave">Operadores registrados</p>
                </article>
                <article class="tarjeta alineacion-centrada">
                    <p style="font-size:2rem;margin-bottom:4px;">🔐</p>
                    <p class="texto-gigante texto-color-marca texto-negrita"><?= $totalRoles ?></p>
                    <p class="texto-xs texto-suave">Roles de acceso (RBAC)</p>
                </article>
                <article class="tarjeta alineacion-centrada">
                    <p style="font-size:2rem;margin-bottom:4px;">📦</p>
                    <p class="texto-gigante texto-color-marca texto-negrita"><?= $modulos ?></p>
                    <p class="texto-xs texto-suave">Módulos del sistema</p>
                </article>
                <article class="tarjeta alineacion-centrada">
                    <p style="font-size:2rem;margin-bottom:4px;">🧹</p>
                    <p class="texto-gigante texto-color-marca texto-negrita">0</p>
                    <p class="texto-xs texto-suave">Dependencias externas</p>
                </article>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="marco-del-sitio" style="padding:80px 24px;background:var(--fondo-alterno);">
            <div class="ancho-max-720 margen-horizontal-auto alineacion-centrada margen-inferior-grande">
                <h2 class="texto-gigante texto-negrita" style="margin-bottom:12px;">Características principales</h2>
                <p class="texto-suave">Todo lo que necesitas para construir y administrar aplicaciones web modernas.</p>
            </div>
            <div class="rejilla-automatica" style="max-width:1100px;margin:0 auto;">
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">Enrutador MVC</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">Rutas con parámetros dinámicos, interceptores por ruta, rutas nombradas y agrupación con herencia de middleware. Despacho automático a controladores con tipado estricto.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">Seguridad integral</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">CSRF con rotación de tokens por petición y ventana de gracia de 60s. RBAC granular con matriz de permisos. Huella digital de sesión anti-secuestro. Rate limiting y WAF integrado.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">ORM Active Record</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">Query builder fluido con relaciones, type casting automático, timestamps, migraciones versionadas con verificación SHA-256 y paginación. Soporte MySQL + SQLite con failover automático.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">Auditoría forense</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">Registro dual en BD y archivo. Trazabilidad con Trace ID único por petición. 5 niveles de severidad. Notificaciones SSE en tiempo real. Exportación JSON/CSV. Auto-diagnóstico con remediación.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">Panel SPA completo</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">13 módulos funcionales: dashboard, operadores, auditoría, archivos, PDF, estadísticas, migraciones, generación de código y más. Navegación SPA con AJAX. Personalización UI en tiempo real.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">Cero dependencias</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">Sin Composer, sin npm, sin vendor, sin node_modules. 16,259 líneas de PHP, 137 archivos. Lo que ves es todo lo que hay. git clone + php -S localhost:8000 y funciona.</p>
                </article>
            </div>
        </section>

        <!-- Tech Stack -->
        <section id="stack" class="marco-del-sitio" style="padding:80px 24px;">
            <div class="ancho-max-720 margen-horizontal-auto alineacion-centrada margen-inferior-grande">
                <h2 class="texto-gigante texto-negrita" style="margin-bottom:12px;">Stack tecnológico</h2>
                <p class="texto-suave">Construido exclusivamente con tecnologías nativas del ecosistema PHP moderno.</p>
            </div>
            <div class="rejilla-automatica" style="max-width:900px;margin:0 auto;">
                <article class="alineacion-centrada">
                    <h3 class="texto-color-marca texto-negrita">PHP 8.2+</h3>
                    <p class="texto-pequeno texto-suave" style="max-width:280px;margin:0 auto;">Tipado estricto, PDO con prepared statements, sesiones nativas seguras, error handler personalizado y autoloader PSR-4 propio.</p>
                </article>
                <article class="alineacion-centrada">
                    <h3 class="texto-color-marca texto-negrita">JavaScript ES6+</h3>
                    <p class="texto-pequeno texto-suave" style="max-width:280px;margin:0 auto;">Módulos nativos type="module", SPA con fetch + pushState, notificaciones toast, SSE en tiempo real y validación asíncrona.</p>
                </article>
                <article class="alineacion-centrada">
                    <h3 class="texto-color-marca texto-negrita">CSS nativo</h3>
                    <p class="texto-pequeno texto-suave" style="max-width:280px;margin:0 auto;">Variables CSS, sistema de paletas intercambiables, 8 estilos visuales, diseño responsivo sin frameworks. 0 preprocesadores.</p>
                </article>
                <article class="alineacion-centrada">
                    <h3 class="texto-color-marca texto-negrita">MySQL / SQLite</h3>
                    <p class="texto-pequeno texto-suave" style="max-width:280px;margin:0 auto;">MySQL con failover automático a SQLite in-memory. Migraciones versionadas, charset utf8mb4, consultas parametrizadas en toda la superficie.</p>
                </article>
            </div>
        </section>

        <!-- Login / Register -->
        <section id="acceso" class="marco-del-sitio" style="padding:80px 24px;background:var(--fondo-alterno);">
            <div class="ancho-max-720 margen-horizontal-auto alineacion-centrada margen-inferior-grande">
                <h2 class="texto-gigante texto-negrita" style="margin-bottom:12px;">Acceder al sistema</h2>
                <p class="texto-suave">Inicia sesión o crea una cuenta para acceder al panel de administración.</p>
            </div>
            <div class="rejilla-automatica" style="max-width:900px;margin:0 auto;">
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

            <div class="margen-superior-normal" style="border-top:1px solid var(--trazo-suave);padding-top:24px;max-width:400px;margin-left:auto;margin-right:auto;">
                <p class="texto-pequeno texto-suave alineacion-centrada margen-inferior-normal">O accede con</p>
                <div style="display:flex;gap:12px;">
                    <a href="<?= URL_BASE ?>/auth/google" class="btn-oauth ancho-total" style="text-decoration:none;gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                        Google
                    </a>
                    <a href="<?= URL_BASE ?>/auth/github" class="btn-oauth ancho-total" style="text-decoration:none;gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                        GitHub
                    </a>
                </div>
            </div>
        </section>

    </main>

    <footer class="alineacion-centrada" style="padding:40px 24px;border-top:1px solid var(--trazo-suave);">
        <p class="texto-pequeno">&copy; <?= date('Y') ?> Ampere Tech Costa Rica S.A. &bull; Lite Framework v1.1.0</p>
        <p class="texto-xs texto-suave margen-superior-minimo">
            Al acceder y utilizar este sistema, usted acepta los
            <a href="<?= URL_BASE ?>/LICENSE.md" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;text-underline-offset:2px">términos y condiciones</a>
            establecidos en la licencia <strong>Apache 2.0 + Commons Clause</strong>.
        </p>
    </footer>

</body>
</html>
