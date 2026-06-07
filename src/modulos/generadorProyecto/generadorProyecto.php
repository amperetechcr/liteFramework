<?php
require_once __DIR__ . '/../../plantillas/modulo_cabecera.php';

$moduloTitulo = 'Generar Proyecto';
$nombreModulo = 'generadorProyecto';
$tokenCSRF = SeguridadServidor::generarTokenAntiFalsificacion();

if ($esAjax) {
    echo '<div data-titulo-pagina="' . $moduloTitulo . '"></div>';
}
?>
<?php if (!$esAjax): $tituloPagina = $moduloTitulo; $moduloActivo = 'generadorProyecto'; require DIRECTORIO_RAIZ . '/src/plantillas/encabezado.php'; endif; ?>

<link rel="stylesheet" href="<?= URL_BASE ?>/src/css/generadorProyecto.css">

<div class="generador-proyecto">

    <h1 class="margen-inferior-normal"><?= h($moduloTitulo) ?></h1>

    <div class="pasos-progreso" id="pasos-progreso">
        <div class="paso-indicador activo" data-paso="1">1. Datos</div>
        <div class="paso-indicador" data-paso="2">2. BD</div>
        <div class="paso-indicador" data-paso="3">3. Apariencia</div>
        <div class="paso-indicador" data-paso="4">4. Módulos</div>
        <div class="paso-indicador" data-paso="5">5. Entidades</div>
        <div class="paso-indicador" data-paso="6">6. Admin</div>
    </div>

    <form id="form-generar-proyecto" novalidate>
        <input type="hidden" name="token_peticion" value="<?= h($tokenCSRF) ?>">

        <!-- Paso 1: Datos del Proyecto -->
        <section class="paso-contenido activo" data-paso="1">
            <h2 class="margen-inferior-normal">Datos del Proyecto</h2>
            <div class="grupo-campo">
                <label for="proy-nombre">Nombre del proyecto</label>
                <input type="text" id="proy-nombre" name="proy_nombre" class="campo-entrada" required placeholder="Sistema de Inventario" value="Mi Aplicacion">
            </div>
            <div class="grupo-campo">
                <label for="proy-codigo">Código (identificador interno)</label>
                <input type="text" id="proy-codigo" name="proy_codigo" class="campo-entrada" required placeholder="inventario" value="miapp" pattern="[a-z][a-z0-9_-]*" title="Solo minusculas, numeros, guiones">
            </div>
            <div class="grupo-campo">
                <label for="proy-descripcion">Descripción corta</label>
                <input type="text" id="proy-descripcion" name="proy_descripcion" class="campo-entrada" placeholder="Panel de control" value="Panel de control">
            </div>
            <div class="grupo-campo">
                <label for="proy-version">Versión</label>
                <input type="text" id="proy-version" name="proy_version" class="campo-entrada" value="1.0.0">
            </div>
            <div class="grupo-campo">
                <label for="proy-empresa">Empresa / Organización</label>
                <input type="text" id="proy-empresa" name="proy_empresa" class="campo-entrada" placeholder="Mi Empresa S.A.">
            </div>
            <div class="grupo-campo">
                <label for="proy-directorio">Directorio de salida</label>
                <input type="text" id="proy-directorio" name="proy_directorio" class="campo-entrada" required placeholder="C:\xampp\htdocs\miapp">
                <span class="texto-pequeno texto-suave">Ruta absoluta donde se creará el proyecto.</span>
            </div>
            <div class="agrupador-flexible-filas brecha-normal margen-superior-normal">
                <button type="button" class="accion-boton variante-solida paso-siguiente" data-paso="1">Siguiente &rarr;</button>
            </div>
        </section>

        <!-- Paso 2: Base de Datos -->
        <section class="paso-contenido" data-paso="2">
            <h2 class="margen-inferior-normal">Base de Datos</h2>
            <div class="grupo-campo">
                <label for="db-anfitrion">Host</label>
                <input type="text" id="db-anfitrion" name="db_anfitrion" class="campo-entrada" value="localhost">
            </div>
            <div class="grupo-campo">
                <label for="db-nombre">Nombre de BD</label>
                <input type="text" id="db-nombre" name="db_nombre" class="campo-entrada" required placeholder="miapp_db">
            </div>
            <div class="grupo-campo">
                <label for="db-usuario">Usuario</label>
                <input type="text" id="db-usuario" name="db_usuario" class="campo-entrada" value="root">
            </div>
            <div class="grupo-campo">
                <label for="db-clave">Contraseña</label>
                <input type="password" id="db-clave" name="db_clave" class="campo-entrada">
            </div>
            <div class="agrupador-flexible-filas brecha-normal margen-superior-normal">
                <button type="button" class="accion-boton variante-borde paso-anterior" data-paso="2">&larr; Anterior</button>
                <button type="button" class="accion-boton variante-solida paso-siguiente" data-paso="2">Siguiente &rarr;</button>
            </div>
        </section>

        <!-- Paso 3: Apariencia -->
        <section class="paso-contenido" data-paso="3">
            <h2 class="margen-inferior-normal">Apariencia</h2>
            <div class="rejilla-automatica">
                <div class="grupo-campo">
                    <label for="apariencia-paleta">Paleta de colores</label>
                    <select id="apariencia-paleta" name="apariencia_paleta" class="campo-entrada">
                        <option value="indigo">Índigo</option>
                        <option value="azul">Azul</option>
                        <option value="esmeralda">Esmeralda</option>
                        <option value="rosa">Rosa</option>
                        <option value="ambar">Ámbar</option>
                        <option value="violeta">Violeta</option>
                        <option value="pizarra">Pizarra</option>
                        <option value="cereza">Cereza</option>
                        <option value="cielo">Cielo</option>
                    </select>
                </div>
                <div class="grupo-campo">
                    <label for="apariencia-estilo">Estilo visual</label>
                    <select id="apariencia-estilo" name="apariencia_estilo" class="campo-entrada">
                        <option value="moderno">Moderno</option>
                        <option value="minimalista">Minimalista</option>
                        <option value="elegante">Elegante</option>
                        <option value="redondeado">Redondeado</option>
                        <option value="contraste">Contraste</option>
                    </select>
                </div>
            </div>
            <div class="agrupador-flexible-filas brecha-normal margen-superior-normal">
                <button type="button" class="accion-boton variante-borde paso-anterior" data-paso="3">&larr; Anterior</button>
                <button type="button" class="accion-boton variante-solida paso-siguiente" data-paso="3">Siguiente &rarr;</button>
            </div>
        </section>

        <!-- Paso 4: Módulos -->
        <section class="paso-contenido" data-paso="4">
            <h2 class="margen-inferior-normal">Módulos del Panel</h2>
            <p class="texto-suave margen-inferior-normal">Selecciona los módulos que estarán visibles en el menú lateral.</p>
            <div class="lista-modulos" id="lista-modulos"></div>
            <div class="agrupador-flexible-filas brecha-normal margen-superior-normal">
                <button type="button" class="accion-boton variante-borde paso-anterior" data-paso="4">&larr; Anterior</button>
                <button type="button" class="accion-boton variante-solida paso-siguiente" data-paso="4">Siguiente &rarr;</button>
            </div>
        </section>

        <!-- Paso 5: Entidades -->
        <section class="paso-contenido" data-paso="5">
            <h2 class="margen-inferior-normal">Entidades del Proyecto</h2>
            <p class="texto-suave margen-inferior-normal">Define las entidades (tablas) que tendrá tu proyecto.</p>
            <div id="lista-entidades"></div>
            <button type="button" class="accion-boton variante-borde" id="agregar-entidad">+ Agregar entidad</button>
            <div class="agrupador-flexible-filas brecha-normal margen-superior-normal">
                <button type="button" class="accion-boton variante-borde paso-anterior" data-paso="5">&larr; Anterior</button>
                <button type="button" class="accion-boton variante-solida paso-siguiente" data-paso="5">Siguiente &rarr;</button>
            </div>
        </section>

        <!-- Paso 6: Admin + Generar -->
        <section class="paso-contenido" data-paso="6">
            <h2 class="margen-inferior-normal">Operador Inicial</h2>
            <div class="rejilla-automatica">
                <div class="grupo-campo">
                    <label for="admin-nombre">Nombre</label>
                    <input type="text" id="admin-nombre" name="admin_nombre" class="campo-entrada" value="Administrador">
                </div>
                <div class="grupo-campo">
                    <label for="admin-correo">Correo</label>
                    <input type="email" id="admin-correo" name="admin_correo" class="campo-entrada" value="admin@miapp.com">
                </div>
                <div class="grupo-campo">
                    <label for="admin-clave">Contraseña</label>
                    <input type="text" id="admin-clave" name="admin_clave" class="campo-entrada" value="Admin123!">
                    <span class="texto-pequeno texto-suave">Se aplicará hash automáticamente.</span>
                </div>
            </div>

            <div class="preview-resumen margen-superior-normal" id="preview-resumen"></div>

            <div class="agrupador-flexible-filas brecha-normal margen-superior-normal">
                <button type="button" class="accion-boton variante-borde paso-anterior" data-paso="6">&larr; Anterior</button>
                <button type="submit" class="accion-boton variante-solida tamano-grande" id="btn-generar-proyecto">Generar Proyecto</button>
            </div>
        </section>

    </form>

    <div id="resultado-generacion" hidden>
        <h2 class="margen-inferior-normal">Resultado</h2>
        <div id="resultado-generacion-contenido"></div>
    </div>
</div>

<script src="<?= URL_BASE ?>/src/js/modulos/generadorProyecto.js"></script>

<?php if (!$esAjax): require DIRECTORIO_RAIZ . '/src/plantillas/pie.php'; endif; ?>
