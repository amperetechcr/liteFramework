<?php
$paletaClase = 'paleta-' . (configUI('paleta') ?? 'indigo');
$estiloClase = 'estilo-' . (configUI('estilo') ?? 'moderno');
$fondoClase = claseFondoHTML();
$fuenteClase = claseFuenteHTML();
$espaciadoClase = claseEspaciadoHTML();
$tamanoClase = claseTamanoHTML();
$clasesHtml = trim($paletaClase . ' ' . $estiloClase . ' ' . $fondoClase . ' ' . $fuenteClase . ' ' . $espaciadoClase . ' ' . $tamanoClase);
$modulos = count(glob(__DIR__ . '/../modulos/*', GLOB_ONLYDIR));

// Estadisticas dinámicas del framework — calculadas desde el filesystem, no hardcodeadas
$totalPruebas = 0;
$totalAseveraciones = 0;
$dirTests = DIRECTORIO_RAIZ . '/tests';
if (is_dir($dirTests)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirTests));
    foreach ($iterator as $archivo) {
        if ($archivo->getExtension() === 'php' && str_contains($archivo->getFilename(), 'Test')) {
            $contenido = file_get_contents($archivo->getPathname());
            $totalPruebas += preg_match_all('/function\s+test[A-Za-z0-9_]/', $contenido);
            $totalAseveraciones += preg_match_all('/\$this->assert/', $contenido);
        }
    }
}

$totalSkills = 1; // REGLAS.md centralizado

$mcpRuta = getenv('USERPROFILE') . '/.config/opencode/opencode.json';
$totalMCPs = 0;
if (is_file($mcpRuta)) {
    $mcpConfig = json_decode(file_get_contents($mcpRuta), true);
    $totalMCPs = isset($mcpConfig['mcp']) ? count($mcpConfig['mcp']) : 0;
}
?>
<!DOCTYPE html>
<html lang="es-CR" class="<?= $clasesHtml ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>liteFramework &mdash; La IA escribe el código, tú das las instrucciones</title>
    <link rel="icon" type="image/png" href="<?= URL_BASE ?>/src/img/favicon.png">
    <meta name="description" content="Plataforma de desarrollo donde le das instrucciones en lenguaje natural y la IA escribe el código. Zero dependencias externas, la IA conoce cada línea. <?= $totalPruebas ?> tests. Cero alucinaciones.">
    <meta name="author" content="Ampere Tech Costa Rica S.A.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= URL_BASE ?>/">

    <meta property="og:title" content="liteFramework — La IA escribe el código, tú das las instrucciones">
    <meta property="og:description" content="Zero dependencias. Hecho para que la IA escriba el código, no el humano. OpenCode + OpenClaw. Cero alucinaciones.">
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
        "name": "liteFramework",
        "url": "<?= URL_BASE ?>/",
        "description": "Plataforma de desarrollo donde la IA escribe el código. El humano instruye, la IA escribe, cero alucinaciones.",
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
    <header class="cabecera-landing">
        <div class="cabecera-landing-contenedor">
            <strong class="cabecera-landing-titulo">
                <span class="texto-color-marca">◆</span> liteFramework
            </strong>
            <nav class="cabecera-landing-navegacion">
                <a href="#features" class="cabecera-landing-enlace">Características</a>
                <a href="#ai-first" class="cabecera-landing-enlace">AI-First</a>
                <a href="#acceso" class="cabecera-landing-enlace">Acceder</a>
                <button type="button" id="alternador-tema" class="alternador-tema margen-0" aria-label="Alternar tema claro/oscuro">&#x2600;</button>
            </nav>
        </div>
    </header>

    <main id="contenido-principal">

        <!-- Hero -->
        <section class="hero-gradient hero-seccion">
            <div class="hero-contenido alineacion-centrada">
                <div class="hero-badges">
                    <span class="badge-ia">🤖 AI-First</span>
                    <span class="badge-ia">🧹 Zero Deps</span>
                    <span class="badge-ia">🧪 <?= $totalPruebas ?> tests</span>
                </div>
                <h1 class="hero-titulo alineacion-centrada">
                    La IA escribe el código.<br>
                    <span class="hero-titulo-destacado">Tú solo das instrucciones.</span>
                </h1>
                <p class="hero-descripcion">
                    Plataforma de desarrollo donde le das instrucciones en lenguaje natural y la IA escribe el código. Sin bibliotecas externas, sin cajas negras. <?= $totalSkills ?> skill central, <?= $totalMCPs ?> MCP servers. La IA conoce cada línea. Cero alucinaciones.
                </p>
                <a href="#acceso" class="btn hero-cta ancho-max-320 margen-horizontal-auto">
                    Comenzar ahora →
                </a>
            </div>
        </section>

        <!-- Stats -->
        <section class="seccion-exterior seccion-alterno">
            <div class="hero-estadisticas">
                <div class="hero-stat">
                    <div class="hero-stat-valor"><?= $totalPruebas ?></div>
                    <div class="hero-stat-etiqueta">🧪 Tests automatizados</div>
                    <div class="hero-stat-sub hero-stat-sub-exito"><?= $totalAseveraciones ?> aserciones</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-valor"><?= $totalMCPs ?></div>
                    <div class="hero-stat-etiqueta">🔗 Servidores conectados</div>
                    <div class="hero-stat-sub hero-stat-sub-suave">OpenCode + OpenClaw</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-valor"><?= $totalSkills ?></div>
                    <div class="hero-stat-etiqueta">🎯 Skill de orquestación</div>
                    <div class="hero-stat-sub hero-stat-sub-suave">conocimiento centralizado</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-valor">0</div>
                    <div class="hero-stat-etiqueta">🧹 Dependencias externas</div>
                    <div class="hero-stat-sub hero-stat-sub-suave">Sin Composer · sin npm</div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="seccion-exterior seccion-alterno">
            <div class="seccion-encabezado">
                <h2 class="seccion-titulo">Hecho para que la IA lo use</h2>
                <p class="seccion-descripcion">No es un sistema al que le agregamos IA. Es un sistema donde la IA es la única que escribe código. El humano solo da instrucciones y supervisa.</p>
            </div>
            <div class="rejilla-automatica rejilla-automatica-extendida margen-inferior-mediano">
                <article class="tarjeta tarjeta-borde-marca">
                    <p class="tarjeta-icono">🤖</p>
                    <h3 class="texto-color-marca texto-negrita margen-inferior-pequeno">La IA escribe, no tú</h3>
                    <p class="texto-pequeno texto-suave paso-ia-texto">Tú das la instrucción en lenguaje natural. La IA escribe el modelo, la ruta, el controlador, la vista y el JS. <?= $totalMCPs ?> MCP servers, <?= $totalSkills ?> skill de orquestación. Cero alucinaciones porque la IA conoce cada línea del sistema.</p>
                </article>
                <article class="tarjeta tarjeta-borde-exito">
                    <p class="tarjeta-icono">🔍</p>
                    <h3 class="texto-color-marca texto-negrita margen-inferior-pequeno">Zero Dependencies</h3>
                    <p class="texto-pequeno texto-suave paso-ia-texto">Sin Composer, sin npm, sin vendor/. La IA lee cada línea del sistema. No hay cajas negras donde la IA pueda inventar APIs que no existen. Cero alucinaciones.</p>
                </article>
                <article class="tarjeta tarjeta-borde-info">
                    <p class="tarjeta-icono">🧪</p>
                    <h3 class="texto-color-marca texto-negrita margen-inferior-pequeno">Quality Gates</h3>
                    <p class="texto-pequeno texto-suave paso-ia-texto"><?= $totalPruebas ?> tests / <?= $totalAseveraciones ?> aserciones, PHPStan level 7, PHPCS 0 errores. La IA escribe, la IA verifica, la IA corrige. El humano solo supervisa que todo funcione.</p>
                </article>
            </div>

            <div class="rejilla-automatica rejilla-automatica-extendida">
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita margen-inferior-pequeno">Arquitectura MVC + API</h3>
                    <p class="texto-pequeno texto-suave paso-ia-texto">Enrutador con parámetros dinámicos, interceptors, rutas nombradas y middleware jerárquico. Controladores con tipado estricto. API REST para el panel SPA + endpoints externos.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita margen-inferior-pequeno">Seguridad integral</h3>
                    <p class="texto-pequeno texto-suave paso-ia-texto">CSRF con rotación de tokens y gracia de 60s. RBAC granular, fingerprint anti-secuestro, rate limiting, WAF integrado, auditoría dual BD + archivo, CSP/HSTS.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita margen-inferior-pequeno">Reglas centralizadas</h3>
                    <p class="texto-pequeno texto-suave paso-ia-texto"><code>REGLAS.md</code> es el único archivo de reglas del sistema. 540 líneas, 13 secciones. La IA las conoce todas y las aplica sin desviación. Sin reglas dispersas en múltiples archivos.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita margen-inferior-pequeno">Archivos protegidos</h3>
                    <p class="texto-pequeno texto-suave paso-ia-texto">Más de 500 archivos con integridad verificada vía SHA-256. La IA no puede modificar archivos críticos sin autorización explícita. Control de cambios granular por nivel de protección.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita margen-inferior-pequeno">Backend modular</h3>
                    <p class="texto-pequeno texto-suave paso-ia-texto">El backend actual es PHP 8.2+ pero el núcleo de orquestación es independiente del lenguaje. La IA opera sobre una capa de abstracción, no sobre el backend directamente.</p>
                </article>
            </div>
        </section>

        <!-- AI-First Architecture -->
        <section id="ai-first" class="seccion-exterior">
            <div class="seccion-encabezado">
                <h2 class="seccion-titulo">Humano instruye → IA escribe → Sistema ejecuta</h2>
                <p class="seccion-descripcion">La IA conoce el sistema al 100%. No hay vendor oculto, no hay dependencias mágicas. Cada línea es visible y comprensible. Por eso no alucina.</p>
            </div>

            <div class="rejilla-automatica-extendida">
                <div class="rejilla-automatica margen-inferior-grande">
                    <div class="paso-ia">
                        <div class="paso-ia-numero">1</div>
                        <h3 class="paso-ia-titulo">Tú das la instrucción</h3>
                        <p class="texto-pequeno texto-suave paso-ia-texto">"Crea un módulo Producto con nombre, precio y stock." La IA entiende la arquitectura completa gracias a REGLAS.md y skills de orquestación.</p>
                    </div>
                    <div class="paso-ia">
                        <div class="paso-ia-numero">2</div>
                        <h3 class="paso-ia-titulo">La IA escribe el código</h3>
                        <p class="texto-pequeno texto-suave paso-ia-texto">Modelo, ruta, controlador, vista, JS, CSS. Sin alucinaciones porque conoce cada línea del sistema. Skills en <code>.opencode/skills/</code>.</p>
                    </div>
                    <div class="paso-ia">
                        <div class="paso-ia-numero">3</div>
                        <h3 class="paso-ia-titulo">La IA verifica sola</h3>
                        <p class="texto-pequeno texto-suave paso-ia-texto"><?= $totalPruebas ?> tests liteTest, PHPStan level 7, PHPCS PSR-12. <?= $totalMCPs ?> MCP servers. <code>opencode run validate</code>. La IA no entrega código que no pase.</p>
                    </div>
                </div>

                <div class="seccion-ai alineacion-centrada">
                    <p class="seccion-ai-encabezado">Pipeline de verificación en 1 comando</p>
                    <div class="flex-envolver brecha-pequena margen-inferior-normal alineacion-centrada">
                        <span class="pill-stack">PHPStan level 7</span>
                        <span class="pill-stack">PHPCS PSR-12</span>
                        <span class="pill-stack">liteTest <?= $totalPruebas ?> tests</span>
                    </div>
                    <code class="codigo-verificacion">
                        opencode run validate
                    </code>
                    <p class="texto-xs texto-suave margen-superior-pequeno">Un solo comando. PHPStan + PHPCS + liteTest. La IA verifica que no rompe nada.</p>
                </div>
            </div>
        </section>

        <!-- Tech Stack -->
        <section class="seccion-exterior seccion-alterno">
            <div class="seccion-encabezado">
                <h2 class="seccion-titulo">Tecnologías</h2>
            </div>
            <div class="tecnologias-contenedor">
                <span class="pill-stack">🤖 OpenCode</span>
                <span class="pill-stack">🦞 OpenClaw</span>
                <span class="pill-stack">🐘 PHP 8.2+</span>
                <span class="pill-stack">🗄️ MySQL / SQLite</span>
                <span class="pill-stack">📡 SSE</span>
                <span class="pill-stack">🐙 Git MCP</span>
                <span class="pill-stack">🔍 Sentry</span>
                <span class="pill-stack">📋 liteTest</span>
                <span class="pill-stack">🔬 PHPStan 7</span>
            </div>
        </section>

        <!-- Login / Register -->
        <section id="acceso" class="seccion-exterior">
            <div class="ancho-max-720 margen-horizontal-auto alineacion-centrada margen-inferior-mediano">
                <h2 class="seccion-titulo">Acceder al sistema</h2>
                <p class="texto-suave">Inicia sesión o crea una cuenta para acceder al panel de control.</p>
            </div>
            <div class="seccion-acceso">
                <div class="rejilla-automatica">
                    <article>
                        <h3 class="margen-inferior-normal">Iniciar sesión</h3>
                        <form id="formularioInicioSesion" class="agrupador-flexible-columnas" method="POST" novalidate>
                            <input type="hidden" name="token_peticion" value="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="accion_crud" value="iniciar_sesion">
                            <div class="grupo-campo campo-agrupado">
                                <label for="correo_login">Correo electrónico</label>
                                <input type="email" id="correo_login" name="correo" required autocomplete="username" placeholder="usuario@dominio.com" aria-required="true">
                            </div>
                            <div class="grupo-campo campo-agrupado">
                                <label for="clave_login">Contraseña</label>
                                <input type="password" id="clave_login" name="clave" required autocomplete="current-password" placeholder="••••••••••••" aria-required="true">
                            </div>
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
                                <input type="text" id="nombre_registro" name="nombre_completo" required autocomplete="name" placeholder="Escriba su nombre completo" aria-required="true">
                            </div>
                            <div class="grupo-campo campo-agrupado">
                                <label for="correo_registro">Correo electrónico</label>
                                <input type="email" id="correo_registro" name="correo_electronico" required autocomplete="email" placeholder="operador@dominio.com" aria-required="true">
                            </div>
                            <div class="grupo-campo campo-agrupado">
                                <label for="clave_registro">Contraseña</label>
                                <input type="password" id="clave_registro" name="clave_registro" required autocomplete="new-password" pattern="(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}" title="Mínimo 8 caracteres, una mayúscula, un número y un símbolo" placeholder="Mín. 8 carac., 1 mayúscula, 1 número, 1 símbolo" aria-required="true">
                            </div>
                            <button type="submit" class="ancho-total">Crear cuenta</button>
                        </form>
                    </article>
                </div>

                <div class="margen-superior-normal oauth-divisor">
                    <p class="texto-pequeno texto-suave alineacion-centrada margen-inferior-normal">O accede con</p>
                    <div class="oauth-botones">
                        <a href="<?= URL_BASE ?>/auth/google" class="btn-oauth ancho-total">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                            Google
                        </a>
                        <a href="<?= URL_BASE ?>/auth/github" class="btn-oauth ancho-total">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                            GitHub
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="footer-principal">
        <div class="footer-pills">
            <span class="pill-stack">Humano instruye</span>
            <span class="pill-stack">IA escribe</span>
            <span class="pill-stack">Cero alucinaciones</span>
        </div>
        <p class="texto-pequeno texto-suave">
            &copy; <?= date('Y') ?> Ampere Tech Costa Rica S.A. &bull; liteFramework
        </p>
        <p class="texto-xs texto-suave margen-superior-pequeno">
            Apache 2.0 + Commons Clause &bull;
            <a href="https://github.com/amperetechcr/liteFramework" target="_blank" rel="noopener">GitHub</a>
        </p>
    </footer>

</body>
</html>
