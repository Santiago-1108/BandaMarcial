-- Script para agregar algunos platillos de ejemplo a la base de datos
-- Ejecutar en phpMyAdmin o desde línea de comandos MySQL

USE banda_marcial;

-- Insertar algunos platillos de ejemplo
INSERT INTO instrumentos (nombre, codigo_serie, observaciones) VALUES
('Platillos', 'PLAT001', 'Par de platillos en buen estado'),
('Platillos', 'PLAT002', 'Platillos de repuesto'),
('Platillos', 'PLAT003', 'Platillos nuevos');
