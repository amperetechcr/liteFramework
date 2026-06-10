<?php
/**
 * Genera documentacion de las MCP Tools desde lite_mcp.py en vivo.
 * Se sincroniza automaticamente con la configuracion real de herramientas.
 */
$cacheFile = __DIR__ . '/../../../storage/cache/mcp_tools_cache.json';
$cacheTtl = 300; // 5 min

function obtenerToolsMCP(): array {
    global $cacheFile, $cacheTtl;
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        return json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    $python = 'C:\\Users\\Tech\\.crewai-venv\\Scripts\\python.exe';
    $script = 'C:\\Users\\Tech\\.config\\crewai\\lite_mcp.py';
    $cmd = sprintf('%s -c "import sys; sys.path.insert(0,\'C:\\\\Users\\\\Tech\\\\.config\\\\crewai\'); from lite_mcp import TOOLS; import json; print(json.dumps([{\'name\':t[\'name\'],\'desc\':t.get(\'description\',\'\'),\'schema\':t.get(\'inputSchema\',{})} for t in TOOLS]))"', $python);
    $output = shell_exec($cmd);
    $tools = $output ? json_decode($output, true) : [];
    if ($tools) {
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) { mkdir($dir, 0777, true); }
        file_put_contents($cacheFile, json_encode($tools, JSON_UNESCAPED_UNICODE));
    }
    return $tools ?: [];
}

$tools = obtenerToolsMCP();
$mecanicas = array_filter($tools, fn($t) => in_array($t['name'], [
    'lite_read_file','lite_write_file','lite_list_dir','lite_read_dir_tree','lite_run'
]));
$clis = array_filter($tools, fn($t) => !in_array($t['name'], [
    'lite_read_file','lite_write_file','lite_list_dir','lite_read_dir_tree','lite_run',
    'lite_kickoff','lite_design_review','lite_develop'
]));
$tacticas = array_filter($tools, fn($t) => in_array($t['name'], [
    'lite_kickoff','lite_design_review','lite_develop'
]));

$html = '<p>El framework expone <strong>' . count($tools) . ' herramientas MCP</strong> organizadas en 3 niveles.</p>';

$html .= '<h3 class="margen-inferior-pequeno">N1 — Mecanicas (' . count($mecanicas) . ')</h3>';
$html .= '<p>Sin LLM. Ejecucion directa en Python. <strong>0ms promedio.</strong></p>';
$html .= '<div class="rejilla-automatica">';
foreach ($mecanicas as $t) {
    $props = $t['schema']['properties'] ?? [];
    $req = $t['schema']['required'] ?? [];
    $params = '';
    foreach ($props as $pname => $pdef) {
        $r = in_array($pname, $req) ? ' <span class="etiqueta etiqueta-roja">req</span>' : '';
        $params .= "<code>{$pname}</code>: {$pdef['type']}{$r}<br>";
    }
    $html .= '<article class="tarjeta-seccion-doc" style="padding:1rem;border:1px solid #e5e7eb;border-radius:8px">';
    $html .= "<h4 class=\"margen-inferior-0\"><code>{$t['name']}</code></h4>";
    $html .= '<p class="texto-pequeno texto-suave">' . htmlspecialchars($t['desc']) . '</p>';
    if ($params) $html .= '<div class="texto-pequeno">' . $params . '</div>';
    $html .= '</article>';
}
$html .= '</div>';

$html .= '<h3 class="margen-inferior-pequeno margen-superior">N2 — CLI Framework (' . count($clis) . ')</h3>';
$html .= '<p>Ejecutan <code>php consola &lt;comando&gt;</code>. <strong>~60ms promedio.</strong></p>';
$html .= '<div class="rejilla-automatica">';
foreach ($clis as $t) {
    $props = $t['schema']['properties'] ?? [];
    $req = $t['schema']['required'] ?? [];
    $params = '';
    foreach ($props as $pname => $pdef) {
        $r = in_array($pname, $req) ? ' <span class="etiqueta etiqueta-roja">req</span>' : '';
        $params .= "<code>{$pname}</code>: {$pdef['type']}{$r}<br>";
    }
    $html .= '<article class="tarjeta-seccion-doc" style="padding:1rem;border:1px solid #e5e7eb;border-radius:8px">';
    $html .= "<h4 class=\"margen-inferior-0\"><code>{$t['name']}</code></h4>";
    $html .= '<p class="texto-pequeno texto-suave">' . htmlspecialchars($t['desc']) . '</p>';
    if ($params) $html .= '<div class="texto-pequeno">' . $params . '</div>';
    $html .= '</article>';
}
$html .= '</div>';

$html .= '<h3 class="margen-inferior-pequeno margen-superior">N2 — Tacticas (' . count($tacticas) . ')</h3>';
$html .= '<p>Ejecutan CrewAI con <code>manager_agent=pm</code>. <strong>~45s promedio.</strong></p>';
$html .= '<div class="rejilla-automatica">';
foreach ($tacticas as $t) {
    $props = $t['schema']['properties'] ?? [];
    $req = $t['schema']['required'] ?? [];
    $params = '';
    foreach ($props as $pname => $pdef) {
        $r = in_array($pname, $req) ? ' <span class="etiqueta etiqueta-roja">req</span>' : '';
        $params .= "<code>{$pname}</code>: {$pdef['type']}{$r}<br>";
    }
    $html .= '<article class="tarjeta-seccion-doc" style="padding:1rem;border:1px solid #e5e7eb;border-radius:8px">';
    $html .= "<h4 class=\"margen-inferior-0\"><code>{$t['name']}</code></h4>";
    $html .= '<p class="texto-pequeno texto-suave">' . htmlspecialchars($t['desc']) . '</p>';
    if ($params) $html .= '<div class="texto-pequeno">' . $params . '</div>';
    $html .= '</article>';
}
$html .= '</div>';

$html .= '<p class="margen-superior texto-pequeno texto-suave">';
$html .= 'Documentacion generada en vivo desde <code>lite_mcp.py</code>. ';
$html .= 'Cache: 5 min. Total: <strong>' . count($tools) . ' tools</strong>.';
$html .= '</p>';

return [
    'id' => 'mcp_tools',
    'titulo' => 'MCP Tools (' . count($tools) . ')',
    'icono' => '🔧',
    'etiquetas' => 'mcp tools ia agentes lite_mcp python crewai',
    'descripcion' => count($tools) . ' herramientas MCP organizadas en 3 niveles: Mecanicas (0ms), CLI Framework (~60ms), Tacticas (~45s). Sincronizado automaticamente con la configuracion real.',
    'contenido' => $html,
];
