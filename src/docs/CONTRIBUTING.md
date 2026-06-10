# Contribuir a liteFramework

## Convenciones

- **Idioma**: Todo en español (variables, comentarios, commits, issues)
- **PHP/JS**: camelCase español (`$correoElectronico`, `buscarPorEstado()`)
- **Clases**: PascalCase español (`ControladorBase`, `GeneradorModulo`)
- **BD**: snake_case español (`operador`, `correo_electronico`)
- **Rutas URL**: kebab-case (`/panel-control`), nombres dot.notation (`api.operadores.listar`)
- **CSS**: kebab-case (`.btn-primario`, `.tarjeta-modulo`)
- **Directorio**: lower_snake_case (`servidor/nucleo/`, `src/modulos/`)
- **Type hints**: **OBLIGATORIO** en parámetros y retorno de toda función/método (`function foo(string $name): array`)

Ver `AGENTS.md` para referencia completa de nomenclatura.

## Ubicaciones

| Qué | Dónde |
|-----|-------|
| Rutas | `rutas/web.php` (antes de `Enrutador::registrarInstancia()`) |
| Módulos (vistas) | `src/modulos/{nombre}/{nombre}.php` |
| Modelos | `servidor/modelos/{Nombre}.php` (extienden `Modelo`) |
| API Controladores | `servidor/api/controladores/{Nombre}Controlador.php` |
| Migraciones SQL | `servidor/migraciones/00X_*.sql` |
| Clases nuevas | Registrar en `servidor/autoload.php` (clase + alias) |
| Helpers | `servidor/nucleo/Helpers/Ayudante{Dominio}.php` |
| JS módulos | `src/js/modulos/{nombre}.js` |
| CSS módulos | `src/css/{nombre}.css` |

## Seguridad (obligatorio)

- Usar `h($variable)` en toda salida HTML (XSS)
- Prepared statements via ORM o PDO (SQLi)
- Validar en servidor (`Validador`) aunque se valide en cliente
- Token CSRF en toda petición POST (`token_peticion` o header `X-CSRF-Token`)
- Auditoría con `RegistroAuditoria` para operaciones importantes
- Sin credenciales en código (usar `.env` + `GestorEntorno`)
- Sin exposición de info sensible en errores o logs

## Git

- Commits descriptivos en español
- Prefijos: `feat:`, `fix:`, `refactor:`, `docs:`, `style:`, `test:`, `chore:`
- No incluir archivos de entorno (`.env`), logs (`storage/logs/`), o archivos subidos (`storage/archivos/`)
- El `.gitignore` ya excluye: `.env`, `storage/archivos/`, `storage/logs/`, `*.log`
- Verificar con `git diff --stat` antes de commitear

## Testing (cuando exista)

- Tests en directorio `tests/` (si se implementa)
- Los tests unitarios usan SQLite in-memory (ver `TESTS_RUNNING` en `ConexionBaseDatos`)
- Cobertura mínima esperada: controladores API, modelos, validación

## Proceso de PR

1. Rama desde `main` con nombre descriptivo: `feat/mi-modulo`, `fix/error-login`
2. Ejecutar migraciones existentes: `php servidor/migrar.php`
3. Verificar que no hay errores PHP
4. Commit con mensaje claro
5. PR descriptivo con qué cambia y por qué
