-- ============================================================
-- SISTEMA DE REGISTRO DE INCIDENTES DE SEGURIDAD LABORAL
-- Motor: PostgreSQL (Supabase)
-- ============================================================
-- DIFERENCIAS TÉCNICAS RESPECTO A MySQL:
-- AUTO_INCREMENT  → SERIAL
-- DATETIME        → TIMESTAMP
-- MD5()           → pgcrypto / md5()
-- CREATE USER     → CREATE ROLE
-- GRANT en MySQL  → Row Level Security (RLS) en PostgreSQL
-- SHOW TABLES     → \dt
-- ENUM            → CHECK constraint
-- DEFAULT NOW()   → DEFAULT NOW() (igual pero más estricto)
-- ============================================================

-- ============================================================
-- TABLAS CATÁLOGO
-- ============================================================

-- 1. ÁREAS
-- MySQL:      id_area INT AUTO_INCREMENT PRIMARY KEY
-- PostgreSQL: id_area SERIAL PRIMARY KEY
CREATE TABLE areas (
    id_area      SERIAL PRIMARY KEY,
    nombre_area  VARCHAR(100) NOT NULL,
    descripcion  VARCHAR(255)
);

-- 2. TIPOS DE INCIDENTE
CREATE TABLE tipos_incidente (
    id_tipo      SERIAL PRIMARY KEY,
    nombre_tipo  VARCHAR(100) NOT NULL,
    descripcion  VARCHAR(255)
);

-- 3. NIVELES DE GRAVEDAD
CREATE TABLE niveles_gravedad (
    id_gravedad      SERIAL PRIMARY KEY,
    nombre_gravedad  VARCHAR(50) NOT NULL,
    descripcion      VARCHAR(255)
);

-- 4. ESTADOS DE INCIDENTE
CREATE TABLE estados_incidente (
    id_estado      SERIAL PRIMARY KEY,
    nombre_estado  VARCHAR(50) NOT NULL
);

-- 5. CARGOS
CREATE TABLE cargos (
    id_cargo      SERIAL PRIMARY KEY,
    nombre_cargo  VARCHAR(100) NOT NULL
);

-- 6. ROLES DEL SISTEMA
CREATE TABLE roles (
    id_rol      SERIAL PRIMARY KEY,
    nombre_rol  VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255)
);

-- ============================================================
-- TABLAS PRINCIPALES
-- ============================================================

-- 7. EMPLEADOS
CREATE TABLE empleados (
    id_empleado  SERIAL PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    apellido     VARCHAR(100) NOT NULL,
    ci           VARCHAR(20)  NOT NULL UNIQUE,
    id_cargo     INT NOT NULL REFERENCES cargos(id_cargo),
    id_area      INT NOT NULL REFERENCES areas(id_area)
);

-- 8. USUARIOS DEL SISTEMA
CREATE TABLE usuarios (
    id_usuario    SERIAL PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    id_empleado   INT NOT NULL REFERENCES empleados(id_empleado),
    id_rol        INT NOT NULL REFERENCES roles(id_rol)
);

-- 9. INCIDENTES
-- MySQL:      DATETIME
-- PostgreSQL: TIMESTAMP
CREATE TABLE incidentes (
    id_incidente    SERIAL PRIMARY KEY,
    fecha_hora      TIMESTAMP   NOT NULL DEFAULT NOW(),
    descripcion     TEXT        NOT NULL,
    id_tipo         INT         NOT NULL REFERENCES tipos_incidente(id_tipo),
    id_area         INT         NOT NULL REFERENCES areas(id_area),
    id_gravedad     INT         NOT NULL REFERENCES niveles_gravedad(id_gravedad),
    id_estado       INT         NOT NULL DEFAULT 1 REFERENCES estados_incidente(id_estado),
    id_usuario_reg  INT         NOT NULL REFERENCES usuarios(id_usuario)
);

-- 10. ACCIONES CORRECTIVAS
CREATE TABLE acciones_correctivas (
    id_accion    SERIAL PRIMARY KEY,
    id_incidente INT  NOT NULL REFERENCES incidentes(id_incidente),
    descripcion  TEXT NOT NULL,
    fecha_accion TIMESTAMP NOT NULL DEFAULT NOW(),
    id_usuario   INT NOT NULL REFERENCES usuarios(id_usuario)
);

-- TABLA INTERMEDIA: EMPLEADOS INVOLUCRADOS EN INCIDENTES
CREATE TABLE empleados_incidentes (
    id_incidente     INT NOT NULL REFERENCES incidentes(id_incidente),
    id_empleado      INT NOT NULL REFERENCES empleados(id_empleado),
    rol_en_incidente VARCHAR(100),
    PRIMARY KEY (id_incidente, id_empleado)
);

-- ============================================================
-- VISTAS (la consigna las pide explícitamente)
-- ============================================================

-- Vista 1: Incidentes sin resolver por área
-- MySQL y PostgreSQL tienen la misma sintaxis para vistas
-- la diferencia está en los tipos de datos internos
CREATE VIEW v_incidentes_sin_resolver AS
SELECT
    i.id_incidente,
    i.fecha_hora,
    i.descripcion,
    t.nombre_tipo,
    a.nombre_area,
    g.nombre_gravedad,
    e.nombre_estado
FROM incidentes i
JOIN tipos_incidente    t ON i.id_tipo     = t.id_tipo
JOIN areas              a ON i.id_area     = a.id_area
JOIN niveles_gravedad   g ON i.id_gravedad = g.id_gravedad
JOIN estados_incidente  e ON i.id_estado   = e.id_estado
WHERE e.nombre_estado != 'Cerrado'
ORDER BY a.nombre_area, i.fecha_hora DESC;

-- Vista 2: Frecuencia histórica por tipo de incidente
CREATE VIEW v_frecuencia_por_tipo AS
SELECT
    t.nombre_tipo,
    COUNT(i.id_incidente) AS total_incidentes
FROM incidentes i
JOIN tipos_incidente t ON i.id_tipo = t.id_tipo
GROUP BY t.nombre_tipo
ORDER BY total_incidentes DESC;

-- ============================================================
-- ROLES Y SEGURIDAD
-- PostgreSQL usa Row Level Security (RLS)
-- MySQL usa GRANT simple por tabla
-- RLS es más granular: controla acceso fila por fila
-- ============================================================

-- Crear roles
-- MySQL:      CREATE USER 'operario'@'localhost' IDENTIFIED BY '...'
-- PostgreSQL: CREATE ROLE operario
CREATE ROLE operario;
CREATE ROLE supervisor;

-- Permisos por tabla
-- Operario: solo puede ver y registrar, no cerrar incidentes
GRANT SELECT, INSERT        ON incidentes            TO operario;
GRANT SELECT, INSERT        ON acciones_correctivas  TO operario;
GRANT SELECT                ON areas                 TO operario;
GRANT SELECT                ON tipos_incidente       TO operario;
GRANT SELECT                ON niveles_gravedad      TO operario;
GRANT SELECT                ON estados_incidente     TO operario;

-- Supervisor: puede además actualizar (cerrar incidentes)
GRANT SELECT, INSERT, UPDATE ON incidentes            TO supervisor;
GRANT SELECT, INSERT, UPDATE ON acciones_correctivas  TO supervisor;
GRANT SELECT                  ON areas                TO supervisor;
GRANT SELECT                  ON tipos_incidente      TO supervisor;
GRANT SELECT                  ON niveles_gravedad     TO supervisor;
GRANT SELECT                  ON estados_incidente    TO supervisor;

-- ============================================================
-- ROW LEVEL SECURITY (RLS)
-- Esto NO existe en MySQL. Es exclusivo de PostgreSQL.
-- Permite controlar el acceso a nivel de fila individual.
-- La consigna pide que solo supervisores cierren incidentes.
-- ============================================================

-- Activar RLS en la tabla incidentes
ALTER TABLE incidentes ENABLE ROW LEVEL SECURITY;

-- Política: operarios solo pueden ver incidentes abiertos
-- y registrar nuevos, pero no actualizar estado a Cerrado
CREATE POLICY politica_operario ON incidentes
    FOR ALL
    TO operario
    USING (true)
    WITH CHECK (
        id_estado != (
            SELECT id_estado FROM estados_incidente
            WHERE nombre_estado = 'Cerrado'
        )
    );

-- Política: supervisores tienen acceso completo
CREATE POLICY politica_supervisor ON incidentes
    FOR ALL
    TO supervisor
    USING (true)
    WITH CHECK (true);

-- ============================================================
-- DATOS DE PRUEBA
-- ============================================================

INSERT INTO areas (nombre_area, descripcion) VALUES
('Planta 1', 'Área de producción principal'),
('Planta 2', 'Área de ensamblaje'),
('Almacén',  'Depósito de materiales'),
('Oficinas', 'Área administrativa');

INSERT INTO tipos_incidente (nombre_tipo, descripcion) VALUES
('Caída',           'Caída de persona al mismo o distinto nivel'),
('Incendio',        'Inicio o propagación de fuego'),
('Derrame químico', 'Derrame de sustancias peligrosas'),
('Golpe',           'Golpe con objeto o maquinaria'),
('Electrocución',   'Contacto con corriente eléctrica');

INSERT INTO niveles_gravedad (nombre_gravedad, descripcion) VALUES
('Leve',     'Sin lesiones o lesiones menores'),
('Moderada', 'Lesiones que requieren atención médica'),
('Alta',     'Lesiones graves o daño significativo'),
('Crítica',  'Riesgo de vida o daño irreversible');

INSERT INTO estados_incidente (nombre_estado) VALUES
('Abierto'),
('En proceso'),
('Cerrado');

INSERT INTO cargos (nombre_cargo) VALUES
('Operario'),
('Supervisor'),
('Técnico'),
('Jefe de planta');

INSERT INTO roles (nombre_rol, descripcion) VALUES
('operario',   'Puede registrar incidentes'),
('supervisor', 'Puede registrar y cerrar incidentes');

INSERT INTO empleados (nombre, apellido, ci, id_cargo, id_area) VALUES
('Carlos',  'Pérez',   '12345678', 1, 1),
('Ana',     'Gómez',   '87654321', 2, 1),
('Luis',    'Mamani',  '11223344', 3, 2),
('Sandra',  'Flores',  '44332211', 2, 3);

-- MySQL:      MD5() como función de hash
-- PostgreSQL: md5() igual pero se recomienda pgcrypto para producción
INSERT INTO usuarios (username, password_hash, id_empleado, id_rol) VALUES
('carlos.perez',  md5('1234'), 1, 1),
('ana.gomez',     md5('1234'), 2, 2),
('luis.mamani',   md5('1234'), 3, 1),
('sandra.flores', md5('1234'), 4, 2);

INSERT INTO incidentes (fecha_hora, descripcion, id_tipo, id_area, id_gravedad, id_estado, id_usuario_reg) VALUES
('2024-01-10 08:30:00', 'Operario resbaló por piso mojado sin señalización', 1, 1, 2, 1, 1),
('2024-01-15 14:00:00', 'Pequeño incendio en área de ensamblaje controlado',  2, 2, 3, 2, 3),
('2024-02-01 09:00:00', 'Derrame de aceite industrial en almacén',            3, 3, 2, 1, 1),
('2024-02-20 11:30:00', 'Golpe con maquinaria por falta de EPP',              4, 1, 1, 3, 3);

INSERT INTO empleados_incidentes (id_incidente, id_empleado, rol_en_incidente) VALUES
(1, 1, 'Afectado'),
(2, 3, 'Testigo'),
(2, 2, 'Responsable de control'),
(3, 1, 'Afectado'),
(4, 3, 'Afectado');

INSERT INTO acciones_correctivas (id_incidente, descripcion, fecha_accion, id_usuario) VALUES
(1, 'Se instaló señalización de piso mojado en toda la planta',   '2024-01-11 10:00:00', 2),
(2, 'Se revisó el sistema eléctrico y se reemplazaron cables',    '2024-01-16 09:00:00', 4),
(2, 'Capacitación al personal sobre manejo de extintores',        '2024-01-18 14:00:00', 4),
(3, 'Limpieza del área y disposición correcta de residuos',       '2024-02-02 08:00:00', 4),
(4, 'Obligatoriedad de uso de EPP en toda el área de producción', '2024-02-21 10:00:00', 2);
