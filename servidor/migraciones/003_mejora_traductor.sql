-- =============================================
-- Migracion 003: Mejora de calibracion del Traductor
-- Nuevas categorias, templates flexibles, denylist
-- =============================================

-- Evitar duplicados futuros en plantilla_prompt
ALTER TABLE plantilla_prompt ADD UNIQUE INDEX IF NOT EXISTS uk_plantilla (categoria, nombre, plantilla_humano(255));

-- Nuevas plantillas para crud_escribir (mas flexibles)
INSERT IGNORE INTO plantilla_prompt (categoria, nombre, plantilla_humano, plantilla_ia, descripcion, version) VALUES
('crud_escribir', 'Crear entidad inferida',
 'crea un %s en %s',
 'crear ENTIDAD en DESTINO',
 'Creacion de entidades por inferencia', 1),

('crud_escribir', 'Insertar valores',
 'inserta en %s los valores %s',
 'insertar en TABLA valores DATOS',
 'Insercion directa de datos', 1),

('crud_escribir', 'Registrar entidad',
 'registra un %s en %s',
 'registrar ENTIDAD en DESTINO',
 'Registro de nuevas entidades', 1);

-- Variante mas flexible para generar_modulo
INSERT IGNORE INTO plantilla_prompt (categoria, nombre, plantilla_humano, plantilla_ia, descripcion, version) VALUES
('generar_modulo', 'Crear modulo simple',
 'crea el modulo %s',
 'generar modulo CRUD para ENTIDAD',
 'Creacion de modulo sin especificar campos', 1);

-- Cache
INSERT IGNORE INTO plantilla_prompt (categoria, nombre, plantilla_humano, plantilla_ia, descripcion, version) VALUES
('cache', 'Guardar en cache',
 'guardar %s en cache con datos %s por %s segundos',
 'guardar en cache CLAVE con datos DATOS por TTL segundos',
 'Almacenar valor en cache', 1),

('cache', 'Guardar simple',
 'guardar %s en cache por %s segundos',
 'guardar en cache CLAVE por TTL segundos',
 'Almacenar en cache sin datos extra', 1),

('cache', 'Obtener de cache',
 'obtener %s de la cache',
 'obtener de cache CLAVE',
 'Lectura de valor en cache', 1),

('cache', 'Limpiar cache',
 'limpiar toda la cache',
 'limpiar toda la cache',
 'Vaciado completo de cache', 1),

('cache', 'Olvidar clave',
 'olvidar %s de la cache',
 'olvidar de cache CLAVE',
 'Eliminar clave especifica de cache', 1);

-- HTTP
INSERT IGNORE INTO plantilla_prompt (categoria, nombre, plantilla_humano, plantilla_ia, descripcion, version) VALUES
('http', 'GET request',
 'hacer una peticion GET a %s',
 'ejecutar peticion GET a URL',
 'Peticion HTTP GET', 1),

('http', 'POST request',
 'enviar POST a %s con datos %s',
 'enviar POST a URL con datos DATOS',
 'Peticion HTTP POST', 1),

('http', 'Llamar API',
 'llamar a la API %s',
 'llamar a API URL',
 'Consumo de API externa', 1);

-- Rendimiento
INSERT IGNORE INTO plantilla_prompt (categoria, nombre, plantilla_humano, plantilla_ia, descripcion, version) VALUES
('rendimiento', 'Benchmark operacion',
 'medir el rendimiento de %s',
 'medir rendimiento de OPERACION',
 'Benchmark de una operacion', 1),

('rendimiento', 'Comparar rendimiento',
 'comparar rendimiento entre %s y %s',
 'comparar rendimiento entre OPCION1 y OPCION2',
 'Comparacion A/B de rendimiento', 1);

-- Usuario/Operador
INSERT IGNORE INTO plantilla_prompt (categoria, nombre, plantilla_humano, plantilla_ia, descripcion, version) VALUES
('usuario', 'Quien soy',
 'mostrar mi informacion de operador',
 'obtener informacion del operador actual',
 'Ver datos del operador en sesion', 1),

('usuario', 'Que rol tengo',
 'cual es mi rol en el sistema',
 'obtener rol del operador actual',
 'Consultar rol del operador', 1),

('usuario', 'Tengo permiso',
 'tengo permiso para %s',
 'tiene permiso CLAVE',
 'Verificar permiso especifico', 1);

-- Scores iniciales para nuevas categorias
INSERT IGNORE INTO traduccion_score (categoria, aciertos, fallos, total_uso, confianza) VALUES
('cache', 0, 0, 0, 0.50),
('http', 0, 0, 0, 0.50),
('rendimiento', 0, 0, 0, 0.50),
('usuario', 0, 0, 0, 0.50);
