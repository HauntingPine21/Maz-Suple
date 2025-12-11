-- ARCHIVO: seed.sql
-- DATOS INICIALES PARA TIENDA DE SUPLEMENTOS

USE MazSupledb;

-- 1. Configuración inicial del negocio
INSERT INTO configuracion (id, razon_social, rfc, domicilio, telefono) 
VALUES 
(1, 'Maz Suplementos S.A. de C.V.', 'MSU250101XYZ', 
 'Av. Fuerza Deportiva #123, Zona Fitness', '55-1234-5678');

-- 2. Usuarios iniciales
-- Contraseña real: 12345
-- HASH generado por password_hash('12345', PASSWORD_DEFAULT)
SET @password_hash = '$2y$10$KgeAaNy.gtpPiWnOmWRF8OLrZ.wfJI4eEeQlvixFcRRqCZioMEj6a';

INSERT INTO usuarios (nombre_completo, username, password, rol)
VALUES
('Administrador Principal', 'admin', @password_hash, 'admin'),
('Operador de Caja', 'operador1', @password_hash, 'operador');

-- 3. Proveedor genérico
INSERT INTO proveedores (nombre, contacto, telefono)
VALUES 
('Distribuidora Fitness MX', 'Carlos Martínez', '55-8888-7777');

-- 4. Productos iniciales (suplementos)
INSERT INTO suplementos (codigo, nombre, marca, precio_venta, estatus)
VALUES
('SUP-CREA-01', 'Creatina Monohidratada 500g', 'Gorilla Labs', 420.00, 1),
('SUP-WHEY-02', 'Proteína Whey 2LB Chocolate', 'Titan Pro', 780.00, 1);

-- 5. Existencias iniciales
INSERT INTO existencias (id_suplemento, cantidad)
VALUES
(1, 40),
(2, 25);

-- 6. Códigos de barras alternos
INSERT INTO suplementos_codigos (id_suplemento, codigo_barras)
VALUES
(1, '750200000001');
