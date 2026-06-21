<?php
if (!defined('DIRECTORIO_RAIZ')) {
    require_once __DIR__ . '/../../servidor/autoload.php';
    GestorEntorno::cargar();
}
if (!defined('URL_BASE')) {
    define('URL_BASE', rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\'));
}

$nombreOperador = $_SESSION['operador_nombre'] ?? 'Invitado';
$tokenCSRF = $_SESSION['csrf_token'] ?? '';

// Estadisticas dinámicas del framework
$totalPruebas = 0;
$totalAseveraciones = 0;
$dirTests = defined('DIRECTORIO_RAIZ') ? DIRECTORIO_RAIZ . '/tests' : __DIR__ . '/../../tests';
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

$paletaClase = 'paleta-' . (configUI('paleta') ?? 'indigo');
$estiloClase = 'estilo-' . (configUI('estilo') ?? 'moderno');
$fondoClase = claseFondoHTML();
$fuenteClase = claseFuenteHTML();
$espaciadoClase = claseEspaciadoHTML();
$tamanoClase = claseTamanoHTML();
$clasesHtml = trim($paletaClase . ' ' . $estiloClase . ' ' . $fondoClase . ' ' . $fuenteClase . ' ' . $espaciadoClase . ' ' . $tamanoClase);
?><!DOCTYPE html>
<html lang="es" class="<?= $clasesHtml ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio — LiteFramework</title>
    <link rel="icon" type="image/png" href="<?= URL_BASE ?>/src/img/favicon.png">
    <meta name="csrf-token" content="<?= $tokenCSRF ?>">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/tema.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/paletas.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/maquetacion.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/componentes.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/modales.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/subirArchivos.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/generadorPdf.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/estadisticas.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/rendimiento.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/documentacion.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/apariencia.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/estilos.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/utilidades.css">
    <link rel="stylesheet" href="<?= URL_BASE ?>/src/css/personalizacion.css">
    <script type="module" src="<?= URL_BASE ?>/src/js/principal.js"></script>
    <style>
        .card-ia {
            background: linear-gradient(135deg, var(--fondo-alterno) 0%, var(--color-marca-claro) 100%);
            border: 1px solid var(--trazo-enfoque);
            border-radius: var(--radio-redondeado);
            padding: var(--espacio-gigante);
            text-align: center;
        }
        .card-ia-valor {
            font-size: clamp(var(--tamano-3xl), 4vw, var(--tamano-4xl));
            font-weight: 800;
            line-height: 1.1;
            color: var(--color-marca);
        }
        .card-ia-etiqueta {
            font-size: var(--tamano-sm);
            color: var(--texto-suave);
            margin-top: var(--espacio-minimo);
        }
    </style>
</head>
<body>

<header class="cabecera-inicio">
    <div class="cabecera-inicio-contenedor envoltura-contenido">
        <h1 class="cabecera-inicio-titulo">LiteFramework</h1>
        <nav aria-label="Navegación">
            <a href="<?= URL_BASE ?>/panelControl" class="cabecera-inicio-enlace">Panel</a>
            <span><?= h($nombreOperador) ?></span>
        </nav>
    </div>
</header>

<main class="envoltura-contenido relleno-superior-grande">

    <section aria-label="Bienvenida" class="seccion-bienvenida">
        <h2>
            Bienvenido, <?= h($nombreOperador) ?>
        </h2>
        <p>
            Has llegado al dashboard de LiteFramework. Tú das las instrucciones en lenguaje natural,
            la IA escribe el código, el sistema ejecuta. Cero alucinaciones.
        </p>
    </section>

    <section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--espacio-normal);margin-top:var(--espacio-gigante);">
        <div class="card-ia">
            <div class="card-ia-valor"><?= $totalPruebas ?></div>
            <div class="card-ia-etiqueta">🧪 Tests automatizados</div>
            <div style="font-size:var(--tamano-xs);color:var(--texto-suave);margin-top:4px;"><?= $totalAseveraciones ?> aserciones</div>
        </div>
        <div class="card-ia">
            <div class="card-ia-valor"><?= $totalSkills ?></div>
            <div class="card-ia-etiqueta">🎯 Skill de orquestación</div>
        </div>
        <div class="card-ia">
            <div class="card-ia-valor"><?= $totalMCPs ?></div>
            <div class="card-ia-etiqueta">🔗 Servidores conectados</div>
        </div>
        <div class="card-ia">
            <div class="card-ia-valor">0</div>
            <div class="card-ia-etiqueta">🧹 Dependencias externas</div>
        </div>
    </section>

    <section style="margin-top:var(--espacio-gigante);text-align:center;">
        <p class="texto-suave">
            Quieres crear algo nuevo? Dile a la IA:
        </p>
        <div style="display:flex;gap:var(--espacio-pequeno);justify-content:center;flex-wrap:wrap;margin-top:var(--espacio-normal);">
            <code style="display:inline-block;padding:12px 20px;background:var(--fondo-elemento);border:1px solid var(--trazo-suave);border-radius:var(--radio-redondeado);font-size:var(--tamano-sm);">
                opencode run "Crear modulo Producto con campos nombre, precio, stock"
            </code>
        </div>
        <div style="display:flex;gap:var(--espacio-pequeno);justify-content:center;flex-wrap:wrap;margin-top:var(--espacio-pequeno);">
            <code style="display:inline-block;padding:12px 20px;background:var(--fondo-elemento);border:1px solid var(--trazo-suave);border-radius:var(--radio-redondeado);font-size:var(--tamano-sm);">
                opencode run validate
            </code>
            <span class="texto-xs texto-suave" style="display:flex;align-items:center;">PHPStan + PHPCS + liteTest</span>
        </div>
    </section>

</main>

<footer class="pie-inicio">
    <div class="pie-inicio-contenedor envoltura-contenido">
        &copy; <?= date('Y') ?> — Humano instruye &middot; IA escribe &middot; Cero alucinaciones
    </div>
</footer>

</body>
</html>