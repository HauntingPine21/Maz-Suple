-- ARCHIVO: schema.sql
-- BASE DE DATOS PARA TIENDA DE SUPLEMENTOS DEPORTIVOS

DROP DATABASE IF EXISTS MazSupledb;
CREATE DATABASE MazSupledb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE MazSupledb;

-- 1. CONFIGURACIÓN Y SISTEMA
CREATE TABLE configuracion (
    id INT PRIMARY KEY DEFAULT 1,
    razon_social VARCHAR(150) NOT NULL,
    rfc VARCHAR(20) NOT NULL,
    domicilio TEXT NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    moneda VARCHAR(5) DEFAULT 'MXN',
    mensaje_ticket VARCHAR(255) DEFAULT '¡Gracias por su compra!',
    logo_empresa LONGBLOB
) ENGINE=InnoDB;

-- 2. USUARIOS Y ROLES
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'operador') NOT NULL DEFAULT 'operador',
    activo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. CATÁLOGOS
CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    contacto VARCHAR(100),
    telefono VARCHAR(20),
    estatus TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE suplementos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código SKU o interno',
    nombre VARCHAR(150) NOT NULL,
    marca VARCHAR(100),
    descripcion TEXT NULL,
    precio_venta DECIMAL(10,2) NOT NULL,
    estatus TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_busqueda (nombre, codigo)
) ENGINE=InnoDB;

CREATE TABLE suplementos_codigos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_suplemento INT NOT NULL,
    codigo_barras VARCHAR(50) NOT NULL UNIQUE,
    FOREIGN KEY (id_suplemento) REFERENCES suplementos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. MULTIMEDIA E INVENTARIO
CREATE TABLE imagenes_suplemento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_suplemento INT NOT NULL,
    contenido LONGBLOB NOT NULL,
    tipo_mime VARCHAR(50) DEFAULT 'image/jpeg',
    es_principal TINYINT(1) DEFAULT 0,
    FOREIGN KEY (id_suplemento) REFERENCES suplementos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE existencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_suplemento INT NOT NULL UNIQUE,
    cantidad INT NOT NULL DEFAULT 0,
    ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP 
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_suplemento) REFERENCES suplementos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. COMPRAS
CREATE TABLE compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio_proveedor VARCHAR(50),
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_proveedor INT NOT NULL,
    id_usuario INT NOT NULL,
    total_compra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    INDEX idx_fecha_compra (fecha_hora)
) ENGINE=InnoDB;

CREATE TABLE detalle_compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_suplemento INT NOT NULL,
    cantidad INT NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_dc_compra 
        FOREIGN KEY (id_compra) REFERENCES compras(id) ON DELETE CASCADE,
    CONSTRAINT fk_dc_suplemento 
        FOREIGN KEY (id_suplemento) REFERENCES suplementos(id)
) ENGINE=InnoDB;

-- 6. VENTAS
CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    iva DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('completada', 'devuelta_parcial', 'devuelta_total') DEFAULT 'completada',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    INDEX idx_fecha_venta (fecha_hora)
) ENGINE=InnoDB;

CREATE TABLE detalle_ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_suplemento INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_dv_venta 
        FOREIGN KEY (id_venta) REFERENCES ventas(id) ON DELETE CASCADE,
    CONSTRAINT fk_dv_suplemento 
        FOREIGN KEY (id_suplemento) REFERENCES suplementos(id)
) ENGINE=InnoDB;

-- 7. DEVOLUCIONES
CREATE TABLE devoluciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_reembolsado DECIMAL(10,2) NOT NULL,
    motivo VARCHAR(255),
    FOREIGN KEY (id_venta) REFERENCES ventas(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE detalle_devoluciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_devolucion INT NOT NULL,
    id_suplemento INT NOT NULL,
    cantidad INT NOT NULL,
    monto_reembolsado DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_dd_dev 
        FOREIGN KEY (id_devolucion) REFERENCES devoluciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_dd_suplemento 
        FOREIGN KEY (id_suplemento) REFERENCES suplementos(id)
) ENGINE=InnoDB;
