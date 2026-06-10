<?php
/**
 * Panel completo del entorno: framework, PHP, BD, Python/CrewAI, MCP tools, agentes, frozen.
 */
use LiteFramework\Config\EntornoCompleto;
use LiteFramework\Config\ConexionBaseDatos;

$env = EntornoCompleto::obtenerTodo();

$html = '';

// ─── Framework ───
$fw = $env['framework'];
$html .= '<h3 class="margen-inferior-pequeno">Framework</h3>';
$html .= '<div class="rejilla-automatica">';
$html .= tarjeta('Version', $fw['version']);
$html .= tarjeta('Entorno', $fw['entorno'], $fw['entorno'] === 'produccion' ? 'roja' : 'verde');
$html .= tarjeta('Depuracion', $fw['depuracion'] ? 'Activada' : 'Desactivada');
$html .= tarjeta('AI Agent Role', $fw['ai_agent_role']);
$html .= '</div>';

// ─── PHP ───
$php = $env['php'];
$html .= '<h3 class="margen-inferior-pequeno margen-superior">PHP</h3>';
$html .= '<div class="rejilla-automatica">';
$html .= tarjeta('Version', $php['version']);
$html .= tarjeta('SAPI', $php['sapi']);
$html .= tarjeta('OS', $php['os']);
$html .= tarjeta('Memoria Max', $php['memoria_maxima']);
$html .= tarjeta('Timeout', $php['timeout'] . 's');
$html .= '</div>';
$html .= '<p class="texto-pequeno margen-superior-0">Extensiones: ';
foreach ($php['extensiones'] as $ext => $activa) {
    $c = $activa ? 'verde' : 'roja';
    $html .= "<span class=\"etiqueta etiqueta-{$c}\">{$ext}</span> ";
}
$html .= '</p>';

// ─── Base de Datos ───
$bd = $env['base_datos'];
$html .= '<h3 class="margen-inferior-pequeno margen-superior">Base de Datos</h3>';
if ($bd['conectado']) {
    $html .= '<div class="rejilla-automatica">';
    $html .= tarjeta('Estado', 'Conectado', 'verde');
    $html .= tarjeta('Version', $bd['version'] ?? '?');
    $html .= tarjeta('Nombre', $bd['nombre'] ?? '?');
    $html .= tarjeta('Anfitrion', $bd['anfitrion'] ?? '?');
    $html .= '</div>';
} else {
    $html .= '<p class="texto-peligro">Desconectado: ' . h($bd['error'] ?? '?') . '</p>';
}

// ─── Python / CrewAI ───
$py = $env['python_crewai'];
$html .= '<h3 class="margen-inferior-pequeno margen-superior">Python &amp; CrewAI</h3>';
$html .= '<div class="rejilla-automatica">';
$html .= tarjeta('Python', $py['version_python'] ?? '?');
$html .= tarjeta('CrewAI', $py['crewai_version'] ?? '?');
$html .= tarjeta('DeepSeek API', $py['deepseek'] ?? '?', $py['deepseek'] === 'CONFIGURADA' ? 'verde' : 'roja');
$html .= tarjeta('Agentes', (string)($py['agentes_registrados'] ?? 0));
$html .= tarjeta('MCPs', (string)($py['mcps_registrados'] ?? 0));
$html .= tarjeta('Congelados', (string)($py['archivos_congelados'] ?? 0));
$html .= '</div>';

// ─── MCP Tools ───
$tools = $env['herramientas_mcp'] ?? [];
$html .= '<h3 class="margen-inferior-pequeno margen-superior">MCP Tools (' . ($tools['total'] ?? 0) . ')</h3>';
$mecanicas = ['lite_read_file','lite_write_file','lite_list_dir','lite_read_dir_tree','lite_run','lite_filtrar'];
$html .= '<div class="rejilla-automatica">';
foreach ($tools['lista'] ?? [] as $t) {
    $es_mecanica = in_array($t['name'], $mecanicas);
    $clase = $es_mecanica ? 'verde' : 'azul';
    $params = $t['params'] ?? [];
    $paramStr = $params ? '<code>' . implode('</code>, <code>', array_slice($params, 0, 4)) . '</code>' : '—';
    if (count($params) > 4) $paramStr .= ' <span class="texto-xs">+'.(count($params)-4).'</span>';
    $html .= '<article class="tarjeta-seccion-doc" style="padding:0.75rem;border:1px solid #e5e7eb;border-radius:8px">';
    $html .= '<h4 class="margen-inferior-0 texto-xs"><code>' . $t['name'] . '</code></h4>';
    $html .= '<p class="texto-xs texto-suave">' . htmlspecialchars(mb_substr($t['desc'] ?? '', 0, 80)) . '</p>';
    $html .= '<p class="texto-xs">Params: ' . $paramStr . '</p>';
    $html .= '<span class="etiqueta etiqueta-' . $clase . '" style="font-size:0.65rem">' . ($es_mecanica ? 'Mecanica' : 'CLI') . '</span>';
    $html .= '</article>';
}
$html .= '</div>';

// ─── Agentes ───
$agentes = $env['agentes'] ?? [];
$html .= '<h3 class="margen-inferior-pequeno margen-superior">Agentes (' . count($agentes) . ')</h3>';
$html .= '<div class="rejilla-automatica">';
foreach ($agentes as $a) {
    $html .= '<article class="tarjeta-seccion-doc" style="padding:0.75rem;border:1px solid #e5e7eb;border-radius:8px">';
    $html .= '<h4 class="margen-inferior-0 texto-xs">' . htmlspecialchars($a['role']) . '</h4>';
    $html .= '<p class="texto-xs">max_iter: ' . $a['max_iter'] . ' | MCPs: ' . $a['mcps'] . ' | Tools: ' . $a['tools'] . ' | Delega: ' . ($a['delegacion'] ? 'Si' : 'No') . '</p>';
    $html .= '</article>';
}
$html .= '</div>';

// ─── Variables de Entorno (.env) ───
$vars = $env['variables_entorno'] ?? [];
$html .= '<h3 class="margen-inferior-pequeno margen-superior">Variables .env (' . count($vars) . ')</h3>';
$html .= '<div class="rejilla-automatica">';
foreach ($vars as $k => $v) {
    $html .= '<article class="tarjeta-seccion-doc" style="padding:0.5rem;border:1px solid #e5e7eb;border-radius:8px">';
    $html .= '<p class="texto-xs margen-inferior-0"><code>' . htmlspecialchars($k) . '</code></p>';
    $html .= '<p class="texto-xs texto-suave" style="word-break:break-all">' . htmlspecialchars($v) . '</p>';
    $html .= '</article>';
}
$html .= '</div>';

// ─── Sistema ───
$sis = $env['sistema'] ?? [];
$html .= '<h3 class="margen-inferior-pequeno margen-superior">Sistema</h3>';
$html .= '<div class="rejilla-automatica">';
if (isset($sis['espacio_disco'])) {
    $total = $sis['espacio_disco']['total'];
    $libre = $sis['espacio_disco']['libre'];
    $html .= tarjeta('Disco Total', $total ? round($total / 1073741824, 1) . ' GB' : '?');
    $html .= tarjeta('Disco Libre', $libre ? round($libre / 1073741824, 1) . ' GB' : '?');
    $html .= tarjeta('Disco Usado', $libre && $total ? round(($total - $libre) / 1073741824, 1) . ' GB' : '?');
}
$html .= tarjeta('Memoria PHP', round($sis['memoria_php']['uso'] / 1048576, 1) . ' MB');
$html .= tarjeta('Pico PHP', round($sis['memoria_php']['pico'] / 1048576, 1) . ' MB');
$html .= '</div>';

$html .= '<p class="margen-superior texto-xs texto-suave">';
$html .= 'Datos obtenidos en vivo desde PHP, Python/CrewAI y el sistema operativo. ';
$html .= 'API: <code>/api/entorno</code> — Cache: 0s.';
$html .= '</p>';

return [
    'id' => 'entorno',
    'titulo' => 'Entorno (' . ($tools['total'] ?? 0) . ' tools)',
    'icono' => "\u{1F4CA}",
    'etiquetas' => 'entorno configuracion php python crewai mcp agentes entorno variables',
    'descripcion' => 'Panel completo del entorno: framework, PHP, BD, Python/CrewAI, herramientas MCP, agentes, archivos congelados y variables de entorno.',
    'contenido' => $html,
];

function tarjeta(string $label, string $valor, string $color = ''): string
{
    $estilo = $color ? "color: var(--color-{$color}); font-weight:bold" : '';
    return '<article class="tarjeta-seccion-doc" style="padding:0.75rem;border:1px solid #e5e7eb;border-radius:8px;text-align:center">'
        . '<p class="texto-xs texto-suave margen-inferior-0">' . htmlspecialchars($label) . '</p>'
        . '<p class="texto-base" style="' . $estilo . '">' . htmlspecialchars($valor) . '</p>'
        . '</article>';
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
