<div class="tarjeta-modulo">
    <div class="flex-entre margen-inferior-pequeno">
        <h3 class="margen-0 texto-lg">Archivos subidos <span class="etiqueta etiqueta-marca"><?= (int)$totalArchivos ?></span></h3>
    </div>

    <nav class="agrupador-flexible-filas brecha-pequena margen-inferior-normal" id="navegacion-migas" aria-label="Navegacion de carpetas">
        <a href="javascript:void(0)" class="accion-boton variante-texto tamano-pequeno miga<?= $rutaCarpeta ? '' : ' variante-solida' ?>" data-ruta="">Raíz</a>
        <?php if ($rutaCarpeta):
            $partes = explode('/', $rutaCarpeta);
            $acum = '';
            foreach ($partes as $i => $p):
                $acum .= ($i > 0 ? '/' : '') . $p;
                $esActivo = ($i === count($partes) - 1);
        ?>
        <span class="texto-suave texto-xs">›</span>
        <?php if ($esActivo): ?>
        <span class="accion-boton variante-texto tamano-pequeno variante-solida"><?= htmlspecialchars($p) ?></span>
        <?php else: ?>
        <a href="javascript:void(0)" class="accion-boton variante-texto tamano-pequeno miga" data-ruta="<?= htmlspecialchars($acum) ?>"><?= htmlspecialchars($p) ?></a>
        <?php endif; ?>
        <?php endforeach; endif; ?>
    </nav>

    <?= renderizarVistaCarpeta($archivosPaginados, $rutaCarpeta ?? '') ?>
</div>
