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

$totalSkills = 0;
foreach ([DIRECTORIO_RAIZ . '/.agents/skills', DIRECTORIO_RAIZ . '/.opencode/skills'] as $dir) {
    if (is_dir($dir)) {
        $totalSkills += count(glob($dir . '/*', GLOB_ONLYDIR));
    }
}

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
    <title>liteFramework &mdash; El framework que usa la IA, no el humano</title>
    <link rel="icon" type="image/png" href="<?= URL_BASE ?>/src/img/favicon.png">
    <meta name="description" content="Framework PHP zero-dependency diseñado para que la IA lo use, no el humano. OpenCode + OpenClaw nativos. <?= $totalPruebas ?> tests, PHPStan level 7. Cero alucinaciones.">
    <meta name="author" content="Ampere Tech Costa Rica S.A.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= URL_BASE ?>/">

    <meta property="og:title" content="liteFramework — El framework que usa la IA">
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
        "description": "Framework PHP zero-dependency para que la IA lo use. El humano instruye, la IA escribe, cero alucinaciones.",
        "inLanguage": "es-CR",
        "publisher": {
            "@type": "Organization",
            "name": "Ampere Tech Costa Rica S.A."
        }
    }
    </script>
    <meta name="api-base" content="<?= URL_BASE ?>/api">
    <script type="module" src="<?= URL_BASE ?>/src/js/principal.js"></script>
    <style>
        .hero-gradient {
            background: linear-gradient(135deg, var(--fondo-pantalla) 0%, var(--color-marca-claro) 100%);
        }
        .hero-stat {
            text-align: center;
            padding: var(--espacio-mediano);
        }
        .hero-stat-valor {
            font-size: clamp(var(--tamano-3xl), 4vw, var(--tamano-4xl));
            font-weight: 800;
            line-height: 1.1;
            color: var(--color-marca);
        }
        .hero-stat-etiqueta {
            font-size: var(--tamano-sm);
            color: var(--texto-suave);
            margin-top: var(--espacio-minimo);
        }
        .badge-ia {
            display: inline-flex;
            align-items: center;
            gap: var(--espacio-pequeno);
            background: var(--color-marca-claro);
            color: var(--color-marca);
            border: 1px solid var(--trazo-enfoque);
            border-radius: 100px;
            padding: var(--espacio-pequeno) var(--espacio-normal);
            font-size: var(--tamano-sm);
            font-weight: 600;
        }
        .pill-stack {
            display: inline-flex;
            align-items: center;
            gap: var(--espacio-pequeno);
            background: var(--fondo-alterno);
            border: 1px solid var(--trazo-suave);
            border-radius: 100px;
            padding: var(--espacio-pequeno) var(--espacio-normal);
            font-size: var(--tamano-xs);
            color: var(--texto-suave);
        }
        .seccion-ai {
            background: linear-gradient(135deg, var(--fondo-alterno) 0%, var(--color-marca-claro) 100%);
            border: 1px solid var(--trazo-enfoque);
            border-radius: var(--radio-redondeado);
            padding: var(--espacio-gigante);
        }
        .paso-ia {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: var(--espacio-normal);
        }
        .paso-ia-numero {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--color-marca);
            color: var(--texto-invertido);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: var(--tamano-lg);
        }
        .seccion-acceso {
            background: var(--fondo-pantalla);
            border: 1px solid var(--trazo-suave);
            border-radius: var(--radio-redondeado);
            padding: var(--espacio-gigante);
            max-width: 900px;
            margin: 0 auto;
        }
        @media (max-width: 640px) {
            .seccion-ai,
            .seccion-acceso {
                padding: var(--espacio-normal);
            }
        }
    </style>
</head>
<body
    data-error="<?= htmlspecialchars($codigoError ?? '', ENT_QUOTES, 'UTF-8') ?>"
    data-mensaje="<?= htmlspecialchars($codigoMensaje ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
    <a href="#contenido-principal" class="enlace-salto">Saltar al contenido principal</a>

    <!-- Navbar -->
    <header style="position:sticky;top:0;z-index:100;background:color-mix(in srgb, var(--fondo-pantalla) 90%, transparent);backdrop-filter:blur(8px);border-bottom:1px solid var(--trazo-suave);">
        <div style="display:flex;align-items:center;justify-content:space-between;max-width:1200px;margin:0 auto;padding:12px 24px;width:100%;">
            <strong style="font-size:1.1rem;letter-spacing:-0.02em;display:flex;align-items:center;gap:8px;">
                <span style="color:var(--color-marca);">◆</span> liteFramework
            </strong>
            <nav style="display:flex;align-items:center;gap:20px;">
                <a href="#features" style="color:var(--texto-suave);text-decoration:none;font-size:.875rem;transition:color var(--transicion-base);">Características</a>
                <a href="#ai-first" style="color:var(--texto-suave);text-decoration:none;font-size:.875rem;transition:color var(--transicion-base);">AI-First</a>
                <a href="#acceso" style="color:var(--texto-suave);text-decoration:none;font-size:.875rem;transition:color var(--transicion-base);">Acceder</a>
                <button type="button" id="alternador-tema" class="alternador-tema" aria-label="Alternar tema claro/oscuro" style="margin:0;">&#x2600;</button>
            </nav>
        </div>
    </header>

    <main id="contenido-principal">

        <!-- Hero -->
        <section class="hero-gradient" style="padding:100px 24px 80px;">
            <div class="ancho-max-720 margen-horizontal-auto alineacion-centrada">
                <div style="display:flex;justify-content:center;gap:8px;margin-bottom:24px;flex-wrap:wrap;">
                    <span class="badge-ia">🤖 AI-First</span>
                    <span class="badge-ia">🧹 Zero Deps</span>
                    <span class="badge-ia">🧪 <?= $totalPruebas ?> tests</span>
                </div>
                <h1 style="font-size:clamp(2.5rem,6vw,4rem);font-weight:800;line-height:1.1;letter-spacing:-0.03em;margin-bottom:16px;">
                    La IA escribe el código.<br>
                    <span style="color:var(--color-marca);background:linear-gradient(135deg,var(--color-marca),var(--color-marca-hover));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Tú solo das instrucciones.</span>
                </h1>
                <p style="font-size:clamp(var(--tamano-lg),1.5vw,var(--tamano-xl));line-height:1.7;color:var(--texto-suave);margin-bottom:40px;max-width:600px;margin-left:auto;margin-right:auto;">
                    El único framework PHP <strong>hecho para que la IA lo use</strong>, no para que el humano toque código. OpenCode + OpenClaw nativos, <?= $totalSkills ?> skills, <?= $totalMCPs ?> MCP servers. Cero alucinaciones. Cero código desperdiciado.
                </p>
                <a href="#acceso" class="btn ancho-max-320 margen-horizontal-auto" style="text-decoration:none;display:block;text-align:center;padding:16px 32px;font-size:1.1rem;font-weight:600;">
                    Comenzar ahora →
                </a>
            </div>

            <!-- Stats -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:var(--espacio-normal);max-width:800px;margin:60px auto 0;padding:0 24px;">
                <div class="hero-stat">
                    <div class="hero-stat-valor"><?= $totalPruebas ?></div>
                    <div class="hero-stat-etiqueta">🧪 Tests PHPUnit</div>
                    <div style="font-size:var(--tamano-xs);color:var(--color-exito);margin-top:4px;"><?= $totalAseveraciones ?> aserciones</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-valor"><?= $totalSkills ?></div>
                    <div class="hero-stat-etiqueta">🤖 Skills IA</div>
                    <div style="font-size:var(--tamano-xs);color:var(--texto-suave);margin-top:4px;">OpenClaw + OpenCode</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-valor"><?= $totalMCPs ?></div>
                    <div class="hero-stat-etiqueta">🔗 MCP Servers</div>
                    <div style="font-size:var(--tamano-xs);color:var(--texto-suave);margin-top:4px;">compartidos OpenCode/Claw</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-valor">0</div>
                    <div class="hero-stat-etiqueta">🧹 Dependencias</div>
                    <div style="font-size:var(--tamano-xs);color:var(--texto-suave);margin-top:4px;">Sin Composer · sin npm</div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" style="padding:80px 24px;background:var(--fondo-alterno);">
            <div class="ancho-max-720 margen-horizontal-auto alineacion-centrada" style="margin-bottom:48px;">
                <h2 style="font-size:clamp(var(--tamano-2xl),3vw,var(--tamano-3xl));font-weight:700;letter-spacing:-0.02em;margin-bottom:12px;">Hecho para que la IA lo use</h2>
                <p style="color:var(--texto-suave);font-size:var(--tamano-lg);">No es un framework al que le agregamos IA. Es un framework donde la IA es la única que escribe código. El humano solo da instrucciones y supervisa.</p>
            </div>
            <div class="rejilla-automatica" style="max-width:1100px;margin:0 auto;">
                <article class="tarjeta" style="border-top:3px solid var(--color-marca);">
                    <p style="font-size:2rem;margin-bottom:8px;">🤖</p>
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">La IA escribe, no tú</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">Tú das la instrucción en lenguaje natural. La IA escribe el modelo, la ruta, el controlador, la vista y el JS. <?= $totalSkills ?> skills, <?= $totalMCPs ?> MCP servers, AGENTS.md por capa. Cero alucinaciones porque la IA conoce todo el código.</p>
                </article>
                <article class="tarjeta" style="border-top:3px solid var(--color-exito);">
                    <p style="font-size:2rem;margin-bottom:8px;">🔍</p>
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">Zero Dependencies</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">Sin Composer, sin npm, sin vendor/. La IA lee cada línea del framework. No hay cajas negras donde la IA pueda inventar APIs que no existen. Cero alucinaciones.</p>
                </article>
                <article class="tarjeta" style="border-top:3px solid var(--color-info);">
                    <p style="font-size:2rem;margin-bottom:8px;">🧪</p>
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">Quality Gates</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;"><?= $totalPruebas ?> tests / <?= $totalAseveraciones ?> aserciones, PHPStan level 7, PHPCS 0 errores. La IA escribe, la IA verifica, la IA corrige. El humano solo supervisa que todo funcione.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">Enrutador MVC</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">Rutas con parámetros dinámicos, interceptors por ruta, rutas nombradas y agrupación con herencia de middleware. Despacho automático a controladores con tipado estricto.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">Seguridad integral</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">CSRF con rotación de tokens y gracia de 60s. RBAC granular, fingerprint anti-secuestro, rate limiting, WAF integrado, auditoría dual BD + archivo, CSP/HSTS.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">ORM Active Record</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">Query builder fluido con relaciones, type casting automático, timestamps, migraciones versionadas con backup/restore y paginación. MySQL + SQLite con failover.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">Sentry nativo</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">Reporte de errores a Sentry sin SDK externo. ~170 líneas de código que cualquier IA puede leer y modificar. Stack trace completo, sesión, release. Timeout 3s.</p>
                </article>
                <article class="tarjeta">
                    <h3 class="texto-color-marca texto-negrita" style="margin-bottom:8px;">Panel SPA + SSE</h3>
                    <p class="texto-pequeno texto-suave" style="line-height:1.7;">13 módulos funcionales, navegación SPA con AJAX, notificaciones SSE en tiempo real, personalización UI con 13 paletas y 8 estilos. Sin frameworks frontend.</p>
                </article>
            </div>
        </section>

        <!-- AI-First Architecture -->
        <section id="ai-first" style="padding:80px 24px;">
            <div class="ancho-max-720 margen-horizontal-auto alineacion-centrada" style="margin-bottom:48px;">
                <h2 style="font-size:clamp(var(--tamano-2xl),3vw,var(--tamano-3xl));font-weight:700;letter-spacing:-0.02em;margin-bottom:12px;">Humano instruye → IA escribe → Framework ejecuta</h2>
                <p style="color:var(--texto-suave);font-size:var(--tamano-lg);">La IA conoce el framework al 100%. No hay vendor oculto, no hay dependencias mágicas. Cada línea es visible y comprensible. Por eso no alucina.</p>
            </div>

            <div style="max-width:1100px;margin:0 auto;">
                <div class="rejilla-automatica" style="margin-bottom:48px;">
                    <div class="paso-ia">
                        <div class="paso-ia-numero">1</div>
                        <h3 style="font-weight:700;font-size:var(--tamano-lg);">Tú das la instrucción</h3>
                        <p class="texto-pequeno texto-suave" style="line-height:1.7;">"Crea un módulo Producto con nombre, precio y stock." La IA entiende la arquitectura completa gracias a AGENTS.md y skills.</p>
                    </div>
                    <div class="paso-ia">
                        <div class="paso-ia-numero">2</div>
                        <h3 style="font-weight:700;font-size:var(--tamano-lg);">La IA escribe el código</h3>
                        <p class="texto-pequeno texto-suave" style="line-height:1.7;">Modelo, ruta, controlador, vista, JS, CSS. Sin alucinaciones porque conoce cada línea del framework. Skills en <code>.opencode/skills/</code>.</p>
                    </div>
                    <div class="paso-ia">
                        <div class="paso-ia-numero">3</div>
                        <h3 style="font-weight:700;font-size:var(--tamano-lg);">La IA verifica sola</h3>
                        <p class="texto-pequeno texto-suave" style="line-height:1.7;"><?= $totalPruebas ?> tests PHPUnit, PHPStan level 7, PHPCS PSR-12. <?= $totalMCPs ?> MCP servers. <code>opencode run validate</code>. La IA no entrega código que no pase.</p>
                    </div>
                </div>

                <div class="seccion-ai" style="text-align:center;">
                    <p style="font-weight:600;margin-bottom:16px;font-size:var(--tamano-lg);">Pipeline de verificación en 1 comando</p>
                    <div style="display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
                        <span class="pill-stack">PHPStan level 7</span>
                        <span class="pill-stack">PHPCS PSR-12</span>
                        <span class="pill-stack">PHPUnit <?= $totalPruebas ?> tests</span>
                    </div>
                    <code style="display:inline-block;padding:12px 24px;background:var(--fondo-elemento);border:1px solid var(--trazo-suave);border-radius:var(--radio-redondeado);font-size:var(--tamano-sm);">
                        opencode run validate
                    </code>
                    <p class="texto-xs texto-suave" style="margin-top:12px;">Un solo comando. PHPStan + PHPCS + PHPUnit. La IA verifica que no rompe nada.</p>
                </div>
            </div>
        </section>

        <!-- Tech Stack -->
        <section style="padding:60px 24px;background:var(--fondo-alterno);">
            <div class="ancho-max-720 margen-horizontal-auto alineacion-centrada" style="margin-bottom:32px;">
                <h2 style="font-size:clamp(var(--tamano-2xl),3vw,var(--tamano-3xl));font-weight:700;letter-spacing:-0.02em;margin-bottom:12px;">Stack tecnológico</h2>
            </div>
            <div style="display:flex;justify-content:center;gap:8px;flex-wrap:wrap;max-width:800px;margin:0 auto;">
                <span class="pill-stack">🤖 OpenCode</span>
                <span class="pill-stack">🦞 OpenClaw</span>
                <span class="pill-stack">🐘 PHP 8.2+</span>
                <span class="pill-stack">📜 JS ES6+</span>
                <span class="pill-stack">🎨 CSS nativo</span>
                <span class="pill-stack">🗄️ MySQL / SQLite</span>
                <span class="pill-stack">📡 SSE</span>
                <span class="pill-stack">🔍 Sentry</span>
                <span class="pill-stack">🐙 Git MCP</span>
                <span class="pill-stack">⚡ Apache</span>
                <span class="pill-stack">📋 PHPUnit 11</span>
                <span class="pill-stack">🔬 PHPStan 7</span>
            </div>
        </section>

        <!-- Login / Register -->
        <section id="acceso" style="padding:80px 24px;">
            <div class="ancho-max-720 margen-horizontal-auto alineacion-centrada" style="margin-bottom:40px;">
                <h2 style="font-size:clamp(var(--tamano-2xl),3vw,var(--tamano-3xl));font-weight:700;letter-spacing:-0.02em;margin-bottom:12px;">Acceder al sistema</h2>
                <p style="color:var(--texto-suave);">Inicia sesión o crea una cuenta para acceder al panel de administración.</p>
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
            </div>
        </section>

    </main>

    <footer style="text-align:center;padding:40px 24px;border-top:1px solid var(--trazo-suave);">
        <div style="display:flex;justify-content:center;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
                    <span class="pill-stack">Humano instruye</span>
                    <span class="pill-stack">IA escribe</span>
                    <span class="pill-stack">Cero alucinaciones</span>
        </div>
        <p class="texto-pequeno texto-suave">
            &copy; <?= date('Y') ?> Ampere Tech Costa Rica S.A. &bull; liteFramework v1.4.0
        </p>
        <p class="texto-xs texto-suave" style="margin-top:8px;">
            Apache 2.0 + Commons Clause &bull;
            <a href="https://github.com/amperetechcr/liteFramework" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;text-underline-offset:2px;">GitHub</a>
        </p>
    </footer>

</body>
</html>
