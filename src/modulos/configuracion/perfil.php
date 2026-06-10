<article class="margen-inferior-normal">
    <h2 class="margen-inferior-normal">Mi perfil</h2>

    <?php if (!$operador): ?>
    <p class="texto-peligro texto-negrita">No se pudieron cargar tus datos.</p>
    <?php else: ?>

    <form id="formularioMiPerfil" class="agrupador-flexible-columnas" method="POST" novalidate>
        <input type="hidden" name="token_peticion" value="<?= $tokenCSRF ?>">
        <input type="hidden" name="id_operador" value="<?= $idOperador ?>">
        <input type="hidden" name="accion_crud" value="actualizar_mi_perfil">

        <div class="rejilla-automatica">
            <div class="grupo-campo campo-agrupado">
                <label for="perfil-nombre">Nombre completo</label>
                <input type="text" id="perfil-nombre" name="nombre_completo"
                    value="<?= htmlspecialchars($operador['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>"
                    required autocomplete="name">
            </div>

            <div class="grupo-campo campo-agrupado">
                <label for="perfil-correo">Correo electronico</label>
                <input type="email" id="perfil-correo" name="correo_electronico"
                    value="<?= htmlspecialchars($operador['correo_electronico'], ENT_QUOTES, 'UTF-8') ?>"
                    required autocomplete="email">
            </div>
        </div>

        <hr class="separador">

        <p class="texto-pequeno texto-suave">Si no deseas cambiar tu contrasena, deja estos campos vacios.</p>

        <div class="rejilla-automatica">
            <div class="grupo-campo campo-agrupado">
                <label for="perfil-clave">Nueva contrasena</label>
                <input type="password" id="perfil-clave" name="clave_acceso"
                    placeholder="Min. 8 carac., 1 mayuscula, 1 numero, 1 simbolo"
                    autocomplete="new-password">
            </div>

            <div class="grupo-campo campo-agrupado">
                <label for="perfil-clave-confirmar">Confirmar contrasena</label>
                <input type="password" id="perfil-clave-confirmar" name="clave_acceso_confirmar"
                    placeholder="Repite la nueva contrasena"
                    autocomplete="new-password">
            </div>
        </div>

        <div class="agrupador-flexible-filas flex-columna-sm brecha-normal">
            <button type="submit" class="ancho-completo-sm">Guardar cambios</button>
            <button type="reset" data-variante="borde" class="ancho-completo-sm">Restablecer</button>
        </div>
    </form>
    <?php endif; ?>
</article>

<article>
    <h3 class="margen-inferior-normal">Informacion de la cuenta</h3>
    <?php if ($operador): ?>
    <div class="agrupador-flexible-columnas">
        <div class="agrupador-flexible-filas distribucion-espaciada">
            <span class="texto-pequeno">ID de operador</span>
            <span class="texto-negrita">#<?= $operador['id_operador'] ?></span>
        </div>
        <div class="agrupador-flexible-filas distribucion-espaciada">
            <span class="texto-pequeno">Rol asignado</span>
            <span class="etiqueta etiqueta-marca"><?= htmlspecialchars($operador['nombre_rol'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="agrupador-flexible-filas distribucion-espaciada">
            <span class="texto-pequeno">Estado</span>
            <span class="etiqueta <?= $operador['estado_cuenta'] == 1 ? 'etiqueta-exito' : 'etiqueta-peligro' ?>">
                <?= $operador['estado_cuenta'] == 1 ? 'Activo' : 'Suspendido' ?>
            </span>
        </div>
        <div class="agrupador-flexible-filas distribucion-espaciada">
            <span class="texto-pequeno">Registrado el</span>
            <span class="texto-pequeno"><?= htmlspecialchars($operador['fecha_registro'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <?php endif; ?>
</article>
