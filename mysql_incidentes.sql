-- ============================================================
-- SISTEMA DE REGISTRO DE INCIDENTES DE SEGURIDAD LABORAL
-- Motor: MySQL (XAMPP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS incidentes_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE incidentes_db;

-- ============================================================
-- TABLAS CATÁLOGO
-- ============================================================

-- 1. ÁREAS
CREATE TABLE areas (
    id_area      INT AUTO_INCREMENT PRIMARY KEY,
    nombre_area  VARCHAR(100) NOT NULL,
    descripcion  VARCHAR(255)
);

-- 2. TIPOS DE INCIDENTE
CREATE TABLE tipos_incidente (
    id_tipo      INT AUTO_INCREMENT PRIMARY KEY,
    nombre_tipo  VARCHAR(100) NOT NULL,
    descripcion  VARCHAR(255)
);

-- 3. NIVELES DE GRAVEDAD
CREATE TABLE niveles_gravedad (
    id_gravedad      INT AUTO_INCREMENT PRIMARY KEY,
    nombre_gravedad  VARCHAR(50) NOT NULL,
    descripcion      VARCHAR(255)
);

-- 4. ESTADOS DE INCIDENTE
CREATE TABLE estados_incidente (
    id_estado      INT AUTO_INCREMENT PRIMARY KEY,
    nombre_estado  VARCHAR(50) NOT NULL
);

-- 5. CARGOS
CREATE TABLE cargos (
    id_cargo      INT AUTO_INCREMENT PRIMARY KEY,
    nombre_cargo  VARCHAR(100) NOT NULL
);

-- 6. ROLES DEL SISTEMA
CREATE TABLE roles (
    id_rol      INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol  VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255)
);

-- ============================================================
-- TABLAS PRINCIPALES
-- ============================================================

-- 7. EMPLEADOS
CREATE TABLE empleados (
    id_empleado  INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    apellido     VARCHAR(100) NOT NULL,
    ci           VARCHAR(20)  NOT NULL UNIQUE,
    id_cargo     INT NOT NULL,
    id_area      INT NOT NULL,
    FOREIGN KEY (id_cargo) REFERENCES cargos(id_cargo),
    FOREIGN KEY (id_area)  REFERENCES areas(id_area)
);

-- 8. USUARIOS DEL SISTEMA
CREATE TABLE usuarios (
    id_usuario    INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    id_empleado   INT NOT NULL,
    id_rol        INT NOT NULL,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado),
    FOREIGN KEY (id_rol)      REFERENCES roles(id_rol)
);

-- 9. INCIDENTES
CREATE TABLE incidentes (
    id_incidente    INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora      DATETIME     NOT NULL DEFAULT NOW(),
    descripcion     TEXT         NOT NULL,
    id_tipo         INT          NOT NULL,
    id_area         INT          NOT NULL,
    id_gravedad     INT          NOT NULL,
    id_estado       INT          NOT NULL DEFAULT 1,
    id_usuario_reg  INT          NOT NULL,
    FOREIGN KEY (id_tipo)        REFERENCES tipos_incidente(id_tipo),
    FOREIGN KEY (id_area)        REFERENCES areas(id_area),
    FOREIGN KEY (id_gravedad)    REFERENCES niveles_gravedad(id_gravedad),
    FOREIGN KEY (id_estado)      REFERENCES estados_incidente(id_estado),
    FOREIGN KEY (id_usuario_reg) REFERENCES usuarios(id_usuario)
);

-- 10. ACCIONES CORRECTIVAS
CREATE TABLE acciones_correctivas (
    id_accion    INT AUTO_INCREMENT PRIMARY KEY,
    id_incidente INT  NOT NULL,
    descripcion  TEXT NOT NULL,
    fecha_accion DATETIME NOT NULL DEFAULT NOW(),
    id_usuario   INT NOT NULL,
    FOREIGN KEY (id_incidente) REFERENCES incidentes(id_incidente),
    FOREIGN KEY (id_usuario)   REFERENCES usuarios(id_usuario)
);

-- TABLA INTERMEDIA: EMPLEADOS INVOLUCRADOS EN INCIDENTES
CREATE TABLE empleados_incidentes (
    id_incidente     INT NOT NULL,
    id_empleado      INT NOT NULL,
    rol_en_incidente VARCHAR(100),
    PRIMARY KEY (id_incidente, id_empleado),
    FOREIGN KEY (id_incidente) REFERENCES incidentes(id_incidente),
    FOREIGN KEY (id_empleado)  REFERENCES empleados(id_empleado)
);

-- ============================================================
-- VISTAS (la consigna las pide explícitamente)
-- ============================================================

-- Vista 1: Incidentes sin resolver por área
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
-- USUARIOS Y ROLES DEL SISTEMA
-- (la consigna pide que solo supervisores cierren incidentes)
-- ============================================================

-- Crear usuario operario (solo puede INSERT y SELECT)
CREATE USER IF NOT EXISTS 'operario'@'localhost' IDENTIFIED BY 'operario123';
GRANT SELECT, INSERT ON incidentes_db.incidentes          TO 'operario'@'localhost';
GRANT SELECT, INSERT ON incidentes_db.acciones_correctivas TO 'operario'@'localhost';
GRANT SELECT          ON incidentes_db.areas               TO 'operario'@'localhost';
GRANT SELECT          ON incidentes_db.tipos_incidente     TO 'operario'@'localhost';
GRANT SELECT          ON incidentes_db.niveles_gravedad    TO 'operario'@'localhost';
GRANT SELECT          ON incidentes_db.estados_incidente   TO 'operario'@'localhost';

-- Crear usuario supervisor (puede además hacer UPDATE para cerrar incidentes)
CREATE USER IF NOT EXISTS 'supervisor'@'localhost' IDENTIFIED BY 'supervisor123';
GRANT SELECT, INSERT, UPDATE ON incidentes_db.incidentes           TO 'supervisor'@'localhost';
GRANT SELECT, INSERT, UPDATE ON incidentes_db.acciones_correctivas TO 'supervisor'@'localhost';
GRANT SELECT                  ON incidentes_db.areas               TO 'supervisor'@'localhost';
GRANT SELECT                  ON incidentes_db.tipos_incidente     TO 'supervisor'@'localhost';
GRANT SELECT                  ON incidentes_db.niveles_gravedad    TO 'supervisor'@'localhost';
GRANT SELECT                  ON incidentes_db.estados_incidente   TO 'supervisor'@'localhost';

FLUSH PRIVILEGES;

-- ============================================================
-- DATOS DE PRUEBA
-- ============================================================

INSERT INTO areas (nombre_area, descripcion) VALUES
('Planta 1', 'Área de producción principal'),
('Planta 2', 'Área de ensamblaje'),
('Almacén',  'Depósito de materiales'),
('Oficinas', 'Área administrativa');

INSERT INTO tipos_incidente (nombre_tipo, descripcion) VALUES
('Caída',             'Caída de persona al mismo o distinto nivel'),
('Incendio',          'Inicio o propagación de fuego'),
('Derrame químico',   'Derrame de sustancias peligrosas'),
('Golpe',             'Golpe con objeto o maquinaria'),
('Electrocución',     'Contacto con corriente eléctrica');

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
('operario',    'Puede registrar incidentes'),
('supervisor',  'Puede registrar y cerrar incidentes');

INSERT INTO empleados (nombre, apellido, ci, id_cargo, id_area) VALUES
('Carlos',  'Pérez',    '12345678', 1, 1),
('Ana',     'Gómez',    '87654321', 2, 1),
('Luis',    'Mamani',   '11223344', 3, 2),
('Sandra',  'Flores',   '44332211', 2, 3);

INSERT INTO usuarios (username, password_hash, id_empleado, id_rol) VALUES
('carlos.perez',  MD5('1234'), 1, 1),
('ana.gomez',     MD5('1234'), 2, 2),
('luis.mamani',   MD5('1234'), 3, 1),
('sandra.flores', MD5('1234'), 4, 2);

INSERT INTO incidentes (fecha_hora, descripcion, id_tipo, id_area, id_gravedad, id_estado, id_usuario_reg) VALUES
('2024-01-10 08:30:00', 'Operario resbaló por piso mojado sin señalización',  1, 1, 2, 1, 1),
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
(1, 'Se instaló señalización de piso mojado en toda la planta',     '2024-01-11 10:00:00', 2),
(2, 'Se revisó el sistema eléctrico y se reemplazaron cables',      '2024-01-16 09:00:00', 4),
(2, 'Capacitación al personal sobre manejo de extintores',          '2024-01-18 14:00:00', 4),
(3, 'Limpieza del área y disposición correcta de residuos',         '2024-02-02 08:00:00', 4),
(4, 'Obligatoriedad de uso de EPP en toda el área de producción',   '2024-02-21 10:00:00', 2);
