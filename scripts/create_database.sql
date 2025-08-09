-- Script completo para crear la base de datos y tablas del sistema de banda marcial
-- Ejecutar en phpMyAdmin o desde línea de comandos MySQL

-- Eliminar la base de datos si ya existe (¡CUIDADO! Esto borrará todos los datos existentes)
DROP DATABASE IF EXISTS banda_marcial;

-- Crear la base de datos
CREATE DATABASE banda_marcial;

-- Usar la base de datos recién creada
USE banda_marcial;

-- Tabla de usuarios (NUEVA)
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL, -- Almacenará el hash de la contraseña
  role ENUM('admin') DEFAULT 'admin', -- Rol del usuario (por ahora solo 'admin')
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de instrumentos
-- Gestiona instrumentos individualmente
CREATE TABLE instrumentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  estado ENUM('Disponible', 'Prestado', 'Dañado', 'En reparación') DEFAULT 'Disponible',
  codigo_serie VARCHAR(50) UNIQUE,
  observaciones TEXT,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de uniformes
-- MODIFICADA para gestionar uniformes individualmente, con código de serie y estado
DROP TABLE IF EXISTS uniformes; -- Eliminar tabla existente para recrearla con la nueva estructura
CREATE TABLE uniformes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('Chaleco', 'Boina') NOT NULL,
  talla VARCHAR(10) NULL, -- Talla para chalecos (XS, S, M, L, XL, XXL) o número para boinas (1-10)
  codigo_serie VARCHAR(50) UNIQUE NOT NULL, -- Código único para cada uniforme
  estado ENUM('Disponible', 'Prestado', 'Dañado', 'En reparación') DEFAULT 'Disponible', -- Estado individual del uniforme
  observaciones TEXT,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de accesorios
-- Gestiona accesorios individualmente
CREATE TABLE accesorios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  codigo_serie VARCHAR(50) UNIQUE NOT NULL, -- Código único para cada accesorio
  estado ENUM('Disponible', 'Prestado', 'Dañado', 'En reparación') DEFAULT 'Disponible', -- Estado individual del accesorio
  observaciones TEXT,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de repuestos
-- Gestiona repuestos con cantidad en stock
CREATE TABLE repuestos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  cantidad_disponible INT NOT NULL DEFAULT 0,
  observaciones TEXT,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de movimientos de repuestos
-- Registra entradas y salidas de repuestos
-- CAMBIO: repuesto_id ahora es NULLable y ON DELETE SET NULL
DROP TABLE IF EXISTS movimientos_repuestos; -- Eliminar tabla existente para recrearla con la nueva estructura
CREATE TABLE movimientos_repuestos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  repuesto_id INT NULL, -- CAMBIO: Ahora puede ser NULL
  tipo_movimiento ENUM('entrada', 'salida') NOT NULL,
  cantidad INT NOT NULL,
  fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  instrumento_id INT NULL, -- FK a instrumentos.id (para salidas a instrumentos)
  observaciones TEXT,
  FOREIGN KEY (repuesto_id) REFERENCES repuestos(id) ON DELETE SET NULL, -- CAMBIO: ON DELETE SET NULL
  FOREIGN KEY (instrumento_id) REFERENCES instrumentos(id) ON DELETE SET NULL
);

-- Tabla de estudiantes
CREATE TABLE IF NOT EXISTS estudiantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_completo VARCHAR(150) NOT NULL,
  grado VARCHAR(20) NOT NULL,
  documento_identidad VARCHAR(50) UNIQUE NOT NULL,
  direccion VARCHAR(255) NULL, -- NUEVO: Campo para la dirección
  telefono VARCHAR(20) NULL,   -- NUEVO: Campo para el número telefónico
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  activo BOOLEAN DEFAULT TRUE
);

-- Tabla de préstamos (encabezado del préstamo)
CREATE TABLE prestamos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  estudiante_id INT NOT NULL,
  fecha_prestamo DATE NOT NULL,
  fecha_devolucion_esperada DATE NOT NULL,
  fecha_devolucion_real DATE NULL,
  observaciones TEXT,
  estado ENUM('Activo', 'Devuelto', 'Vencido') DEFAULT 'Activo',
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE
);

-- Tabla de detalle de préstamos (ítems prestados)
-- La estructura de prestamo_items se mantiene, pero la lógica de cantidad_prestada/devuelta
-- para uniformes volverá a ser de 1 por cada item individual.
DROP TABLE IF EXISTS prestamo_items; -- Eliminar tabla existente para recrearla con la nueva estructura
CREATE TABLE prestamo_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prestamo_id INT NOT NULL,
  tipo_item ENUM('instrumento', 'uniforme', 'accesorio') NOT NULL,
  item_id INT NOT NULL, -- FK a instrumentos.id o uniformes.id (el ID del item individual)
  cantidad_prestada INT NOT NULL DEFAULT 1, -- Siempre 1 para items individuales
  cantidad_devuelta INT NOT NULL DEFAULT 0, -- 0 si no devuelto, 1 si devuelto
  fecha_devolucion TIMESTAMP NULL, -- Fecha de la última devolución para este item_id
  observaciones_devolucion TEXT,
  FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Datos de ejemplo
-- -----------------------------------------------------
-- Insertar usuario administrador de ejemplo (contraseña: admin123)
INSERT INTO usuarios (username, password, role) VALUES
('admin', 'admin123', 'admin');
-- Insertar datos de ejemplo para instrumentos (sin cambios)
INSERT INTO instrumentos (nombre, codigo_serie, observaciones) VALUES
('Caja', 'CAJA001', 'Instrumento en buen estado'),
('Bombo', 'BOMBO001', 'Requiere mantenimiento menor'),
('Redoblante', 'RED001', 'Nuevo'),
('Redoblante Mayor', 'REDMAY001', 'Buen estado'),
('Lira', 'LIRA001', 'Instrumento clásico'),
('Trompeta', 'TROMP001', 'Recién reparada'),
('Granadera', 'GRAN001', 'Estado excelente'),
('Bastón', 'BAST001', 'Para director'),
('Platillos', 'PLAT001', 'Par de platillos en buen estado'),
('Platillos', 'PLAT002', 'Platillos de repuesto'),
('Platillos', 'PLAT003', 'Platillos nuevos');

-- Insertar datos de ejemplo para uniformes (NUEVOS, como ítems individuales)
INSERT INTO uniformes (tipo, talla, codigo_serie, estado, observaciones) VALUES
('Chaleco', 'S', 'CHALECOS001', 'Disponible', 'Chaleco talla S en buen estado'),
('Chaleco', 'S', 'CHALECOS002', 'Disponible', 'Chaleco talla S'),
('Chaleco', 'M', 'CHALECOM001', 'Disponible', 'Chaleco talla M'),
('Chaleco', 'M', 'CHALECOM002', 'Disponible', 'Chaleco talla M'),
('Chaleco', 'L', 'CHALECOL001', 'Disponible', 'Chaleco talla L'),
('Boina', '1', 'BOINA001', 'Disponible', 'Boina talla 1'),
('Boina', '1', 'BOINA002', 'Disponible', 'Boina talla 1'),
('Boina', '5', 'BOINA005', 'Disponible', 'Boina talla 5'),
('Boina', '10', 'BOINA010', 'Disponible', 'Boina talla 10');

-- Insertar datos de ejemplo para accesorios
INSERT INTO accesorios (nombre, codigo_serie, estado, observaciones) VALUES
('Boquilla Trompeta', 'BQTROM001', 'Disponible', 'Boquilla estándar para trompeta'),
('Correa Redoblante', 'CORRED001', 'Disponible', 'Correa de repuesto para redoblante'),
('Atril Plegable', 'ATRIL001', 'Disponible', 'Atril portátil para partituras'),
('Baquetas Caja', 'BAQCAJA001', 'Disponible', 'Par de baquetas para caja');

-- Insertar datos de ejemplo para repuestos
INSERT INTO repuestos (nombre, cantidad_disponible, observaciones) VALUES
('Parches Redoblante 14"', 5, 'Parches de repuesto para redoblantes de 14 pulgadas'),
('Aceite para Válvulas', 10, 'Aceite lubricante para válvulas de instrumentos de viento'),
('Cuerdas Lira', 20, 'Juego de cuerdas de repuesto para lira');

-- Insertar datos de ejemplo para estudiantes (sin cambios)
INSERT INTO estudiantes (nombre_completo, grado, documento_identidad) VALUES
('Ana María González', '10°', '1234567890'),
('Carlos Eduardo Pérez', '11°', '0987654321'),
('María José Rodríguez', '9°', '1122334455'),
('Juan Pablo Martínez', '10°', '5566778899');

-- Datos de ejemplo para préstamos (se recomienda crear desde la interfaz de usuario)
-- No se insertan aquí para evitar conflictos con la nueva lógica de ítems individuales.
