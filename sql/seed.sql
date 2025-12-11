-- ARCHIVO: seed.sql
-- DATOS MÍNIMOS REQUERIDOS PARA INSTALAR Y PROBAR EL SISTEMA

USE libreria_db;

-- 1. Configuración inicial del negocio
INSERT INTO configuracion (id, razon_social, rfc, domicilio, telefono) 
VALUES (1, 'Librería Universitaria S.A. de C.V.', 'LUN230101XYZ', 'Av. del Conocimiento #456, Campus Central', '55-1234-5678');

-- 2. Usuario Admin y Operador
-- La contraseña real para entrar será: 12345
-- El valor insertado abajo es el HASH generado por password_hash('12345', PASSWORD_DEFAULT)
SET @password_hash = '$2y$10$KgeAaNy.gtpPiWnOmWRF8OLrZ.wfJI4eEeQlvixFcRRqCZioMEj6a';

INSERT INTO usuarios (nombre_completo, username, password, rol) 
VALUES 
('Administrador Principal', 'admin', @password_hash, 'admin'),
('Juan Pérez Cajero', 'cajero', @password_hash, 'operador');

-- 3. Proveedor genérico
INSERT INTO proveedores (nombre, contacto, telefono) 
VALUES ('Editorial Nacional', 'Maria Lopez', '55-9999-8888');

-- 4. Productos de ejemplo (Items)
INSERT INTO libros (codigo, titulo, precio_venta, estatus)
VALUES 
('A101', 'Introducción a PHP y MySQL', 350.50, 1),
('B202', 'Modelado de Bases de Datos Relacionales', 620.00, 1);

-- 5. Existencias iniciales
INSERT INTO existencias (id_libro, cantidad)
VALUES 
(1, 50), 
(2, 30); 

-- 6. Código de barras secundario de ejemplo
INSERT INTO libros_codigos (id_libro, codigo_barras)
VALUES 
(1, '750100000001');