<article>
    <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm margen-inferior-normal">
        <div>
            <h2>Limites del servidor</h2>
            <p class="texto-suave texto-pequeno margen-superior-pequeno">
                Los cambios afectan a todos los usuarios. Se regenera <code>.user.ini</code> y <code>.htaccess</code>.
            </p>
        </div>
        <div class="agrupador-flexible-filas brecha-pequena">
            <span class="etiqueta etiqueta-info">Subida max: <?= $limitesPhp['upload_max_filesize'] ?>M</span>
            <span class="etiqueta etiqueta-info">POST max: <?= $limitesPhp['post_max_size'] ?>M</span>
            <span class="etiqueta etiqueta-info">Memoria: <?= $limitesPhp['memory_limit'] ?>M</span>
            <span class="etiqueta etiqueta-info">Ejecucion: <?= $limitesPhp['max_execution_time'] ?>s</span>
            <span class="etiqueta etiqueta-info">Simultaneas: <?= $limitesPhp['max_file_uploads'] ?></span>
        </div>
    </div>

    <form id="formularioConfiguracionArchivos" class="agrupador-flexible-columnas" novalidate>
        <input type="hidden" name="token_peticion" value="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="accion_crud" value="actualizar_configuracion_archivos">

        <div class="rejilla-automatica">
            <div class="tarjeta">
                <label class="texto-negrita" for="cfg-tamano-maximo">Tamano maximo por archivo</label>
                <div class="entrada-con-ayuda">
                    <input type="number" id="cfg-tamano-maximo" name="tamano_maximo_mb"
                        min="1" max="2048" required
                        value="<?= htmlspecialchars((string)$configArchivos['tamano_maximo_mb'], ENT_QUOTES, 'UTF-8') ?>"
                        data-version="<?= $detallesConfig['ARCHIVO_TAMANO_MAXIMO_MB']['version'] ?? 1 ?>">
                    <span class="texto-suave texto-pequeno">MB</span>
                </div>
            </div>

            <div class="tarjeta">
                <label class="texto-negrita" for="cfg-post-max-size">Tamano maximo de POST</label>
                <div class="entrada-con-ayuda">
                    <input type="number" id="cfg-post-max-size" name="post_max_size_mb"
                        min="1" max="2048" required
                        value="<?= htmlspecialchars((string)$configArchivos['post_max_size_mb'], ENT_QUOTES, 'UTF-8') ?>"
                        data-version="<?= $detallesConfig['ARCHIVO_POST_MAX_SIZE_MB']['version'] ?? 1 ?>">
                    <span class="texto-suave texto-pequeno">MB</span>
                </div>
            </div>

            <div class="tarjeta">
                <label class="texto-negrita" for="cfg-cuota-usuario">Cuota de almacenamiento por usuario</label>
                <div class="entrada-con-ayuda">
                    <input type="number" id="cfg-cuota-usuario" name="cuota_usuario_mb"
                        min="0" max="10240" required
                        value="<?= htmlspecialchars((string)$configArchivos['cuota_usuario_mb'], ENT_QUOTES, 'UTF-8') ?>"
                        data-version="<?= $detallesConfig['ARCHIVO_CUOTA_USUARIO_MB']['version'] ?? 1 ?>">
                    <span class="texto-suave texto-pequeno">MB</span>
                </div>
            </div>

            <div class="tarjeta">
                <label class="texto-negrita" for="cfg-memoria-php">Limite de memoria PHP</label>
                <div class="entrada-con-ayuda">
                    <input type="number" id="cfg-memoria-php" name="memoria_php_mb"
                        min="64" max="2048" required
                        value="<?= htmlspecialchars((string)$configArchivos['memoria_php_mb'], ENT_QUOTES, 'UTF-8') ?>"
                        data-version="<?= $detallesConfig['ARCHIVO_MEMORIA_PHP_MB']['version'] ?? 1 ?>">
                    <span class="texto-suave texto-pequeno">MB</span>
                </div>
            </div>
        </div>

        <fieldset class="tarjeta">
            <legend class="texto-negrita margen-inferior-normal">Categorias MIME permitidas</legend>
            <div class="rejilla-automatica brecha-pequena">
                <?php
                $categoriasDisponibles = [
                    'imagenes' => 'Imagenes (JPG, PNG, GIF, WebP, SVG, BMP, TIFF)',
                    'documentos' => 'Documentos (PDF, DOC, DOCX, XLS, XLSX, ODT, TXT, CSV, MD)',
                    'videos' => 'Videos (MP4, MPEG, MOV, WebM, AVI, MKV)',
                    'audio' => 'Audio (MP3, WAV, OGG, WebM, FLAC)',
                    'comprimidos' => 'Comprimidos (ZIP, RAR, 7Z, TAR, GZ)',
                    'ejecutables' => 'Ejecutables (EXE, MSI, DLL, ELF, Mach-O)',
                    'codigo' => 'Codigo (PHP, JS, CSS, HTML, SQL, JSON, XML, YAML, SH)',
                    'datos' => 'Datos (TXT, LOG, INI, ENV, MD, EXAMPLE, BACKUP)',
                ];
                $catsActivas = array_map('trim', explode(',', $configArchivos['tipos_mime_permitidos']));
                foreach ($categoriasDisponibles as $valor => $etiqueta):
                    $activo = in_array($valor, $catsActivas, true);
                ?>
                <label class="opcion-mime<?= $activo ? ' activo' : '' ?>">
                    <input type="checkbox" name="categorias_mime[]" value="<?= $valor ?>" <?= $activo ? 'checked' : '' ?>>
                    <span class="texto-pequeno"><?= $etiqueta ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="margen-superior-normal">
                <label class="opcion-mime">
                    <input type="checkbox" name="modo_repositorio" value="1" id="cfg-modo-repositorio" <?= $configArchivos['extensiones_permitidas'] === '*' ? 'checked' : '' ?>>
                    <span class="texto-negrita">Modo repositorio (permitir todos los tipos de archivo)</span>
                </label>
                <p class="texto-suave texto-pequeno margen-superior-pequeno">Desactiva las restricciones de MIME y extensiones. Usar solo para respaldos del sistema.</p>
            </div>
        </fieldset>

        <div class="tarjeta" id="cfg-seccion-extensiones">
            <label class="texto-negrita" for="cfg-extensiones">Extensiones permitidas</label>
            <input type="text" id="cfg-extensiones" name="extensiones_permitidas"
                placeholder="jpg,png,pdf,docx"
                value="<?= htmlspecialchars($configArchivos['extensiones_permitidas'], ENT_QUOTES, 'UTF-8') ?>"
                data-version="<?= $detallesConfig['ARCHIVO_EXTENSIONES_PERMITIDAS']['version'] ?? 1 ?>"
                class="margen-superior-pequeno campo-entrada">
            <p class="texto-suave texto-pequeno margen-superior-pequeno">Sin punto, separadas por comas. Ej: jpg,png,pdf</p>
        </div>

        <details class="tarjeta detalle-servidor">
            <summary class="texto-negrita">Configuracion avanzada</summary>
            <div class="rejilla-automatica margen-superior-normal">
                <div class="grupo-campo campo-agrupado">
                    <label for="cfg-tiempo-ejecucion">Tiempo maximo de ejecucion</label>
                    <div class="entrada-con-ayuda">
                        <input type="number" id="cfg-tiempo-ejecucion" name="tiempo_ejecucion_seg"
                            min="30" max="3600" required
                            value="<?= htmlspecialchars((string)$configArchivos['tiempo_ejecucion_seg'], ENT_QUOTES, 'UTF-8') ?>"
                            data-version="<?= $detallesConfig['ARCHIVO_TIEMPO_EJECUCION_SEG']['version'] ?? 1 ?>">
                        <span class="texto-suave texto-pequeno">segundos</span>
                    </div>
                </div>
                <div class="grupo-campo campo-agrupado">
                    <label for="cfg-max-subidas">Maximo de subidas simultaneas</label>
                    <div class="entrada-con-ayuda">
                        <input type="number" id="cfg-max-subidas" name="maximo_subidas_simultaneas"
                            min="1" max="1000" required
                            value="<?= htmlspecialchars((string)$configArchivos['maximo_subidas_simultaneas'], ENT_QUOTES, 'UTF-8') ?>"
                            data-version="<?= $detallesConfig['ARCHIVO_MAXIMO_SUBIDAS_SIMULTANEAS']['version'] ?? 1 ?>">
                        <span class="texto-suave texto-pequeno">archivos</span>
                    </div>
                </div>
            </div>
        </details>

        <div class="tarjeta tarjeta-peligro">
            <div class="alerta alerta-peligro">
                <strong>Advertencia:</strong> Cambiar los limites del servidor afecta a todos los usuarios.
                Se regeneraran <code>.user.ini</code> y <code>.htaccess</code> con los nuevos valores.
            </div>

            <div class="grupo-campo campo-agrupado margen-superior-normal">
                <label for="cfg-confirmacion">
                    Para aplicar los cambios, escribe <code>CONFIRMAR</code>:
                </label>
                <input type="text" id="cfg-confirmacion" name="confirmacion" required
                    placeholder="CONFIRMAR" autocomplete="off"
                    class="campo-confirmacion">
            </div>

            <div class="agrupador-flexible-filas brecha-normal">
                <button type="submit" id="btn-guardar-config" class="ancho-completo-sm" disabled>
                    Guardar configuracion del servidor
                </button>
            </div>
        </div>

        <div id="mensaje-config-archivos" role="status" aria-live="polite"></div>
    </form>

    <?php if (true): /* Siempre visible para super admin */ ?>
    <hr class="margen-superior-normal margen-inferior-normal">
    <div class="agrupador-flexible-filas distribucion-espaciada flex-columna-sm">
        <div>
            <h2>Modo mantenimiento</h2>
            <p class="texto-suave texto-pequeno margen-superior-pequeno">
                Al activarlo, solo los administradores pueden acceder al sistema.
                Los demas usuarios veran una pantalla de servicio no disponible.
            </p>
        </div>
        <form method="POST" action="<?= URL_BASE ?>/configuracion">
            <input type="hidden" name="accion" value="alternar_mantenimiento">
            <input type="hidden" name="token_peticion" value="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">
            <?php $modoMantenimiento = \LiteFramework\Config\ConfiguracionSistema::obtener('MODO_MANTENIMIENTO', false); ?>
            <button type="submit" class="btn <?= $modoMantenimiento ? 'btn-peligro' : 'btn-advertencia' ?>">
                <?= $modoMantenimiento ? 'Desactivar mantenimiento' : 'Activar mantenimiento' ?>
            </button>
        </form>
    </div>

    <?php if (!empty($contenidoDirectivasPhp)): ?>
    <details class="tarjeta detalle-servidor margen-superior-normal">
        <summary class="texto-negrita">Ver directivas PHP en <code>.htaccess</code></summary>
        <pre class="codigo-directivas margen-superior-normal"><?= htmlspecialchars($contenidoDirectivasPhp, ENT_QUOTES, 'UTF-8') ?></pre>
    </details>
    <?php endif; ?>
</article>
