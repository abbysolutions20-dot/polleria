
-- =========================================================
-- BASE DE DATOS PRO: POLLERIA / RESTAURANTE POS
-- MySQL 8+
-- Incluye:
--   * POS completo
--   * inventario y recetas
--   * compras a proveedores
--   * caja y gastos
--   * comprobantes y anulaciones
--   * delivery y motorizado
--   * auditoría
--   * procedimientos almacenados
--   * triggers
--   * vistas para dashboard
-- =========================================================

DROP DATABASE IF EXISTS polleria_pos_pro;
CREATE DATABASE polleria_pos_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE polleria_pos_pro;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
-- 1. MAESTROS GENERALES
-- =========================================================

CREATE TABLE empresas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(200) NOT NULL,
    nombre_comercial VARCHAR(200) NOT NULL,
    ruc VARCHAR(11) NOT NULL UNIQUE,
    direccion_fiscal VARCHAR(255),
    telefono VARCHAR(30),
    correo VARCHAR(150),
    logo_url VARCHAR(255),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE sucursales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_empresa BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    direccion VARCHAR(255),
    telefono VARCHAR(30),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sucursal_empresa FOREIGN KEY (id_empresa) REFERENCES empresas(id)
) ENGINE=InnoDB;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE permisos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(100) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE rol_permisos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_rol BIGINT UNSIGNED NOT NULL,
    id_permiso BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_rol_permiso (id_rol, id_permiso),
    CONSTRAINT fk_rp_rol FOREIGN KEY (id_rol) REFERENCES roles(id),
    CONSTRAINT fk_rp_permiso FOREIGN KEY (id_permiso) REFERENCES permisos(id)
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_sucursal BIGINT UNSIGNED NULL,
    id_rol BIGINT UNSIGNED NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100),
    usuario VARCHAR(100) NOT NULL UNIQUE,
    correo VARCHAR(150) UNIQUE,
    telefono VARCHAR(30),
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales(id),
    CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE auditoria_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(100) NOT NULL,
    accion ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    id_registro VARCHAR(50) NOT NULL,
    usuario_db VARCHAR(100) NOT NULL,
    detalle TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 2. CLIENTES / DELIVERY
-- =========================================================

CREATE TABLE clientes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_documento ENUM('DNI','RUC','CE','PASAPORTE','OTRO') NOT NULL DEFAULT 'DNI',
    numero_documento VARCHAR(20) NOT NULL,
    nombre_razon_social VARCHAR(200) NOT NULL,
    direccion VARCHAR(255),
    telefono VARCHAR(30),
    correo VARCHAR(150),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cliente_doc (tipo_documento, numero_documento)
) ENGINE=InnoDB;

CREATE TABLE direcciones_cliente (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cliente BIGINT UNSIGNED NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    referencia VARCHAR(255),
    latitud DECIMAL(10,7) NULL,
    longitud DECIMAL(10,7) NULL,
    principal TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dir_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id)
) ENGINE=InnoDB;

CREATE TABLE motorizados (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100),
    dni VARCHAR(20),
    telefono VARCHAR(30),
    placa VARCHAR(20),
    estado ENUM('DISPONIBLE','EN_RUTA','INACTIVO') NOT NULL DEFAULT 'DISPONIBLE',
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE delivery_ordenes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_orden BIGINT UNSIGNED NOT NULL,
    id_direccion_cliente BIGINT UNSIGNED NOT NULL,
    id_motorizado BIGINT UNSIGNED NULL,
    tarifa_delivery DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    estado_delivery ENUM('PENDIENTE','ASIGNADO','EN_RUTA','ENTREGADO','NO_ENTREGADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
    fecha_asignacion DATETIME NULL,
    fecha_salida DATETIME NULL,
    fecha_entrega DATETIME NULL,
    observaciones VARCHAR(255)
) ENGINE=InnoDB;

-- =========================================================
-- 3. MESAS / RESERVAS
-- =========================================================

CREATE TABLE zonas_mesa (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_sucursal BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_zona_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales(id)
) ENGINE=InnoDB;

CREATE TABLE mesas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_sucursal BIGINT UNSIGNED NOT NULL,
    id_zona BIGINT UNSIGNED NULL,
    numero VARCHAR(20) NOT NULL,
    nombre VARCHAR(100),
    capacidad INT NOT NULL DEFAULT 4,
    estado ENUM('LIBRE','OCUPADA','RESERVADA','INACTIVA') NOT NULL DEFAULT 'LIBRE',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_mesa_sucursal_numero (id_sucursal, numero),
    CONSTRAINT fk_mesa_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales(id),
    CONSTRAINT fk_mesa_zona FOREIGN KEY (id_zona) REFERENCES zonas_mesa(id)
) ENGINE=InnoDB;

CREATE TABLE reservas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cliente BIGINT UNSIGNED NOT NULL,
    id_mesa BIGINT UNSIGNED NOT NULL,
    fecha_reserva DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NULL,
    cantidad_personas INT NOT NULL DEFAULT 1,
    estado ENUM('PENDIENTE','CONFIRMADA','ATENDIDA','CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
    observaciones VARCHAR(255),
    id_usuario BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reserva_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id),
    CONSTRAINT fk_reserva_mesa FOREIGN KEY (id_mesa) REFERENCES mesas(id),
    CONSTRAINT fk_reserva_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE historial_mesas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_mesa BIGINT UNSIGNED NOT NULL,
    estado_anterior ENUM('LIBRE','OCUPADA','RESERVADA','INACTIVA') NULL,
    estado_nuevo ENUM('LIBRE','OCUPADA','RESERVADA','INACTIVA') NOT NULL,
    motivo VARCHAR(255),
    id_orden BIGINT UNSIGNED NULL,
    id_usuario BIGINT UNSIGNED NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hm_mesa FOREIGN KEY (id_mesa) REFERENCES mesas(id),
    CONSTRAINT fk_hm_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- =========================================================
-- 4. PRODUCTOS / PROMOCIONES / MENUS
-- =========================================================

CREATE TABLE categorias_producto (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    tipo VARCHAR(50),
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE areas_preparacion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE productos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) UNIQUE,
    id_categoria BIGINT UNSIGNED NOT NULL,
    id_area_preparacion BIGINT UNSIGNED NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    tipo_producto ENUM('PLATO','BEBIDA','COMBO','MENU','OTRO') NOT NULL DEFAULT 'PLATO',
    precio_venta DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    costo_unitario DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    margen_porcentaje DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    imagen VARCHAR(255),
    afecto_igv TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_prod_categoria FOREIGN KEY (id_categoria) REFERENCES categorias_producto(id),
    CONSTRAINT fk_prod_area FOREIGN KEY (id_area_preparacion) REFERENCES areas_preparacion(id)
) ENGINE=InnoDB;

CREATE TABLE producto_variaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_producto BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    precio_adicional DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_pv_producto FOREIGN KEY (id_producto) REFERENCES productos(id)
) ENGINE=InnoDB;

CREATE TABLE producto_extras (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_producto BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_pe_producto FOREIGN KEY (id_producto) REFERENCES productos(id)
) ENGINE=InnoDB;

CREATE TABLE promociones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255),
    tipo_descuento ENUM('PORCENTAJE','FIJO','COMBO') NOT NULL DEFAULT 'PORCENTAJE',
    valor_descuento DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE promocion_productos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_promocion BIGINT UNSIGNED NOT NULL,
    id_producto BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_promocion_producto (id_promocion, id_producto),
    CONSTRAINT fk_pp_promocion FOREIGN KEY (id_promocion) REFERENCES promociones(id) ON DELETE CASCADE,
    CONSTRAINT fk_pp_producto FOREIGN KEY (id_producto) REFERENCES productos(id)
) ENGINE=InnoDB;

CREATE TABLE menus_dia (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255),
    precio DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE menu_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_menu BIGINT UNSIGNED NOT NULL,
    id_producto BIGINT UNSIGNED NOT NULL,
    tipo ENUM('ENTRADA','FONDO','BEBIDA','POSTRE','OTRO') NOT NULL DEFAULT 'FONDO',
    UNIQUE KEY uq_menu_producto (id_menu, id_producto, tipo),
    CONSTRAINT fk_md_menu FOREIGN KEY (id_menu) REFERENCES menus_dia(id) ON DELETE CASCADE,
    CONSTRAINT fk_md_producto FOREIGN KEY (id_producto) REFERENCES productos(id)
) ENGINE=InnoDB;

-- =========================================================
-- 5. INVENTARIO / PROVEEDORES / COMPRAS
-- =========================================================

CREATE TABLE categorias_inventario (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    tipo ENUM('INGREDIENTE','ARTICULO') NOT NULL DEFAULT 'INGREDIENTE',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_cat_inv (nombre, tipo)
) ENGINE=InnoDB;

CREATE TABLE unidades_medida (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    abreviatura VARCHAR(20) NOT NULL UNIQUE,
    factor_base DECIMAL(12,4) NOT NULL DEFAULT 1.0000,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE ingredientes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255),
    id_categoria_inventario BIGINT UNSIGNED NOT NULL,
    id_unidad_medida BIGINT UNSIGNED NOT NULL,
    stock_actual DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    stock_minimo DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    stock_maximo DECIMAL(14,3) NULL,
    costo_unitario DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    compuesto TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ing_cat FOREIGN KEY (id_categoria_inventario) REFERENCES categorias_inventario(id),
    CONSTRAINT fk_ing_um FOREIGN KEY (id_unidad_medida) REFERENCES unidades_medida(id)
) ENGINE=InnoDB;

CREATE TABLE articulos_inventario (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255),
    id_categoria_inventario BIGINT UNSIGNED NOT NULL,
    id_unidad_medida BIGINT UNSIGNED NOT NULL,
    stock_actual DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    stock_minimo DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    costo_unitario DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_ai_cat FOREIGN KEY (id_categoria_inventario) REFERENCES categorias_inventario(id),
    CONSTRAINT fk_ai_um FOREIGN KEY (id_unidad_medida) REFERENCES unidades_medida(id)
) ENGINE=InnoDB;

CREATE TABLE recetas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_producto BIGINT UNSIGNED NOT NULL,
    rendimiento DECIMAL(12,3) NOT NULL DEFAULT 1.000,
    costo_total DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_receta_producto (id_producto),
    CONSTRAINT fk_receta_producto FOREIGN KEY (id_producto) REFERENCES productos(id)
) ENGINE=InnoDB;

CREATE TABLE receta_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_receta BIGINT UNSIGNED NOT NULL,
    id_ingrediente BIGINT UNSIGNED NOT NULL,
    cantidad DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    id_unidad_medida BIGINT UNSIGNED NOT NULL,
    merma_porcentaje DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    costo_unitario DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    costo_total DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    UNIQUE KEY uq_receta_ingrediente (id_receta, id_ingrediente),
    CONSTRAINT fk_rd_receta FOREIGN KEY (id_receta) REFERENCES recetas(id) ON DELETE CASCADE,
    CONSTRAINT fk_rd_ingrediente FOREIGN KEY (id_ingrediente) REFERENCES ingredientes(id),
    CONSTRAINT fk_rd_um FOREIGN KEY (id_unidad_medida) REFERENCES unidades_medida(id)
) ENGINE=InnoDB;

CREATE TABLE inventario_alertas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_item ENUM('INGREDIENTE','ARTICULO') NOT NULL,
    id_item BIGINT UNSIGNED NOT NULL,
    tipo_alerta ENUM('STOCK_BAJO','AGOTADO','VENCIMIENTO','INCONSISTENCIA') NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    estado ENUM('ACTIVA','RESUELTA') NOT NULL DEFAULT 'ACTIVA',
    fecha_generada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_resuelta DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE proveedores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_documento ENUM('RUC','DNI','OTRO') NOT NULL DEFAULT 'RUC',
    numero_documento VARCHAR(20) NOT NULL,
    razon_social VARCHAR(200) NOT NULL,
    contacto VARCHAR(150),
    telefono VARCHAR(30),
    correo VARCHAR(150),
    direccion VARCHAR(255),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_proveedor_doc (tipo_documento, numero_documento)
) ENGINE=InnoDB;

CREATE TABLE compras (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_sucursal BIGINT UNSIGNED NOT NULL,
    id_proveedor BIGINT UNSIGNED NOT NULL,
    tipo_documento VARCHAR(20) NOT NULL,
    serie VARCHAR(10),
    numero VARCHAR(20),
    fecha_compra DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    igv DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    estado ENUM('REGISTRADA','ANULADA') NOT NULL DEFAULT 'REGISTRADA',
    id_usuario BIGINT UNSIGNED NULL,
    observaciones VARCHAR(255),
    CONSTRAINT fk_compra_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales(id),
    CONSTRAINT fk_compra_proveedor FOREIGN KEY (id_proveedor) REFERENCES proveedores(id),
    CONSTRAINT fk_compra_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE compra_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_compra BIGINT UNSIGNED NOT NULL,
    tipo_item ENUM('INGREDIENTE','ARTICULO') NOT NULL,
    id_item BIGINT UNSIGNED NOT NULL,
    descripcion VARCHAR(150) NOT NULL,
    cantidad DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    costo_unitario DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_cd_compra FOREIGN KEY (id_compra) REFERENCES compras(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE kardex_movimientos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_item ENUM('INGREDIENTE','ARTICULO') NOT NULL,
    id_item BIGINT UNSIGNED NOT NULL,
    tipo_movimiento ENUM('ENTRADA','SALIDA','AJUSTE','MERMA','PRODUCCION','CONSUMO') NOT NULL,
    origen ENUM('COMPRA','VENTA','RECETA','AJUSTE_MANUAL','TRASLADO','INICIAL') NOT NULL,
    id_referencia BIGINT UNSIGNED NULL,
    cantidad DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    saldo_anterior DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    saldo_nuevo DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    costo_unitario DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    costo_total DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario BIGINT UNSIGNED NULL,
    observacion VARCHAR(255),
    CONSTRAINT fk_km_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- =========================================================
-- 6. ORDENES / CAJA / PAGOS / COMPROBANTES
-- =========================================================

CREATE TABLE turnos_caja (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_sucursal BIGINT UNSIGNED NOT NULL,
    id_usuario_apertura BIGINT UNSIGNED NOT NULL,
    id_usuario_cierre BIGINT UNSIGNED NULL,
    fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME NULL,
    monto_apertura DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    monto_cierre DECIMAL(12,2) NULL,
    estado ENUM('ABIERTO','CERRADO') NOT NULL DEFAULT 'ABIERTO',
    observaciones VARCHAR(255),
    CONSTRAINT fk_tc_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales(id),
    CONSTRAINT fk_tc_uap FOREIGN KEY (id_usuario_apertura) REFERENCES usuarios(id),
    CONSTRAINT fk_tc_ucie FOREIGN KEY (id_usuario_cierre) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE metodos_pago (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    requiere_evidencia TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE ordenes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    numero_correlativo BIGINT NOT NULL,
    id_sucursal BIGINT UNSIGNED NOT NULL,
    id_mesa BIGINT UNSIGNED NULL,
    id_cliente BIGINT UNSIGNED NULL,
    id_reserva BIGINT UNSIGNED NULL,
    id_usuario_creador BIGINT UNSIGNED NULL,
    tipo_servicio ENUM('MESA','LLEVAR','DELIVERY') NOT NULL DEFAULT 'MESA',
    canal_origen ENUM('CAJA','MOZO','WEB','APP') NOT NULL DEFAULT 'CAJA',
    estado ENUM('BORRADOR','ABIERTA','EN_PREPARACION','LISTA','SERVIDA','COMPLETADA','PAGADA','ANULADA') NOT NULL DEFAULT 'BORRADOR',
    cantidad_personas INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    descuento DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    igv DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    observaciones VARCHAR(255),
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME NULL,
    CONSTRAINT fk_o_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales(id),
    CONSTRAINT fk_o_mesa FOREIGN KEY (id_mesa) REFERENCES mesas(id),
    CONSTRAINT fk_o_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id),
    CONSTRAINT fk_o_reserva FOREIGN KEY (id_reserva) REFERENCES reservas(id),
    CONSTRAINT fk_o_usuario FOREIGN KEY (id_usuario_creador) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE orden_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_orden BIGINT UNSIGNED NOT NULL,
    id_producto BIGINT UNSIGNED NOT NULL,
    nombre_producto VARCHAR(150) NOT NULL,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    cantidad DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    observacion VARCHAR(255),
    estado_item ENUM('PENDIENTE','PREPARACION','LISTO','SERVIDO','ANULADO') NOT NULL DEFAULT 'PENDIENTE',
    id_area_preparacion BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_od_orden FOREIGN KEY (id_orden) REFERENCES ordenes(id) ON DELETE CASCADE,
    CONSTRAINT fk_od_producto FOREIGN KEY (id_producto) REFERENCES productos(id),
    CONSTRAINT fk_od_area FOREIGN KEY (id_area_preparacion) REFERENCES areas_preparacion(id)
) ENGINE=InnoDB;

CREATE TABLE orden_estados_historial (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_orden BIGINT UNSIGNED NOT NULL,
    estado_anterior ENUM('BORRADOR','ABIERTA','EN_PREPARACION','LISTA','SERVIDA','COMPLETADA','PAGADA','ANULADA') NULL,
    estado_nuevo ENUM('BORRADOR','ABIERTA','EN_PREPARACION','LISTA','SERVIDA','COMPLETADA','PAGADA','ANULADA') NOT NULL,
    comentario VARCHAR(255),
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario BIGINT UNSIGNED NULL,
    CONSTRAINT fk_oeh_orden FOREIGN KEY (id_orden) REFERENCES ordenes(id) ON DELETE CASCADE,
    CONSTRAINT fk_oeh_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE comandas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_orden BIGINT UNSIGNED NOT NULL,
    numero_comanda VARCHAR(50) NOT NULL,
    id_area_preparacion BIGINT UNSIGNED NOT NULL,
    estado ENUM('EMITIDA','IMPRESA','EN_PROCESO','ATENDIDA','ANULADA') NOT NULL DEFAULT 'EMITIDA',
    fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_impresion DATETIME NULL,
    id_usuario BIGINT UNSIGNED NULL,
    CONSTRAINT fk_com_orden FOREIGN KEY (id_orden) REFERENCES ordenes(id) ON DELETE CASCADE,
    CONSTRAINT fk_com_area FOREIGN KEY (id_area_preparacion) REFERENCES areas_preparacion(id),
    CONSTRAINT fk_com_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE comanda_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_comanda BIGINT UNSIGNED NOT NULL,
    id_orden_detalle BIGINT UNSIGNED NOT NULL,
    cantidad DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    observacion VARCHAR(255),
    estado ENUM('PENDIENTE','PREPARANDO','LISTO','ENTREGADO','ANULADO') NOT NULL DEFAULT 'PENDIENTE',
    CONSTRAINT fk_cd_comanda FOREIGN KEY (id_comanda) REFERENCES comandas(id) ON DELETE CASCADE,
    CONSTRAINT fk_cd_od FOREIGN KEY (id_orden_detalle) REFERENCES orden_detalle(id)
) ENGINE=InnoDB;

CREATE TABLE pagos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_orden BIGINT UNSIGNED NOT NULL,
    id_turno_caja BIGINT UNSIGNED NOT NULL,
    id_metodo_pago BIGINT UNSIGNED NOT NULL,
    monto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    monto_recibido DECIMAL(12,2) NULL,
    vuelto DECIMAL(12,2) NULL,
    numero_operacion VARCHAR(100) NULL,
    evidencia_url VARCHAR(255) NULL,
    observaciones VARCHAR(255),
    fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario BIGINT UNSIGNED NULL,
    estado ENUM('REGISTRADO','VALIDADO','ANULADO') NOT NULL DEFAULT 'REGISTRADO',
    CONSTRAINT fk_p_orden FOREIGN KEY (id_orden) REFERENCES ordenes(id),
    CONSTRAINT fk_p_turno FOREIGN KEY (id_turno_caja) REFERENCES turnos_caja(id),
    CONSTRAINT fk_p_metodo FOREIGN KEY (id_metodo_pago) REFERENCES metodos_pago(id),
    CONSTRAINT fk_p_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE categorias_gasto (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE gastos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_turno_caja BIGINT UNSIGNED NOT NULL,
    id_categoria_gasto BIGINT UNSIGNED NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    monto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    comprobante_url VARCHAR(255),
    id_usuario BIGINT UNSIGNED NULL,
    estado ENUM('REGISTRADO','APROBADO','ANULADO') NOT NULL DEFAULT 'REGISTRADO',
    CONSTRAINT fk_g_turno FOREIGN KEY (id_turno_caja) REFERENCES turnos_caja(id),
    CONSTRAINT fk_g_cat FOREIGN KEY (id_categoria_gasto) REFERENCES categorias_gasto(id),
    CONSTRAINT fk_g_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE movimientos_caja (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_turno_caja BIGINT UNSIGNED NOT NULL,
    tipo_movimiento ENUM('INGRESO','EGRESO') NOT NULL,
    origen ENUM('PAGO_ORDEN','GASTO','APERTURA','CIERRE','AJUSTE') NOT NULL,
    id_referencia BIGINT UNSIGNED NULL,
    descripcion VARCHAR(255),
    monto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario BIGINT UNSIGNED NULL,
    CONSTRAINT fk_mc_turno FOREIGN KEY (id_turno_caja) REFERENCES turnos_caja(id),
    CONSTRAINT fk_mc_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE tipos_comprobante (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    serie VARCHAR(10) NOT NULL,
    correlativo_actual BIGINT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE comprobantes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_orden BIGINT UNSIGNED NOT NULL,
    id_pago BIGINT UNSIGNED NULL,
    id_cliente BIGINT UNSIGNED NULL,
    id_tipo_comprobante BIGINT UNSIGNED NOT NULL,
    serie VARCHAR(10) NOT NULL,
    numero BIGINT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    igv DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    estado ENUM('EMITIDO','ANULADO') NOT NULL DEFAULT 'EMITIDO',
    motivo_anulacion VARCHAR(255) NULL,
    fecha_anulacion DATETIME NULL,
    xml_url VARCHAR(255),
    pdf_url VARCHAR(255),
    cdr_url VARCHAR(255),
    fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_comprobante (serie, numero),
    CONSTRAINT fk_c_orden FOREIGN KEY (id_orden) REFERENCES ordenes(id),
    CONSTRAINT fk_c_pago FOREIGN KEY (id_pago) REFERENCES pagos(id),
    CONSTRAINT fk_c_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id),
    CONSTRAINT fk_c_tipo FOREIGN KEY (id_tipo_comprobante) REFERENCES tipos_comprobante(id),
    CONSTRAINT fk_c_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE comprobante_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_comprobante BIGINT UNSIGNED NOT NULL,
    id_producto BIGINT UNSIGNED NULL,
    descripcion VARCHAR(255) NOT NULL,
    cantidad DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    valor_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    igv_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    importe_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_cdet_comp FOREIGN KEY (id_comprobante) REFERENCES comprobantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_cdet_prod FOREIGN KEY (id_producto) REFERENCES productos(id)
) ENGINE=InnoDB;

-- =========================================================
-- 7. VISTAS PRO DASHBOARD
-- =========================================================

CREATE OR REPLACE VIEW vw_stock_critico AS
SELECT i.id, i.sku, i.nombre, i.stock_actual, i.stock_minimo, um.abreviatura
FROM ingredientes i
JOIN unidades_medida um ON um.id = i.id_unidad_medida
WHERE i.activo = 1 AND i.stock_actual <= i.stock_minimo;

CREATE OR REPLACE VIEW vw_ventas_resumen AS
SELECT
    o.id,
    o.codigo,
    o.tipo_servicio,
    o.estado,
    o.subtotal,
    o.descuento,
    o.igv,
    o.total,
    o.fecha_creacion,
    c.nombre_razon_social AS cliente,
    m.nombre AS mesa,
    CONCAT(u.nombres, ' ', COALESCE(u.apellidos,'')) AS usuario
FROM ordenes o
LEFT JOIN clientes c ON c.id = o.id_cliente
LEFT JOIN mesas m ON m.id = o.id_mesa
LEFT JOIN usuarios u ON u.id = o.id_usuario_creador;

CREATE OR REPLACE VIEW vw_caja_turno_resumen AS
SELECT
    tc.id AS id_turno,
    tc.fecha_apertura,
    tc.fecha_cierre,
    tc.estado,
    tc.monto_apertura,
    COALESCE(SUM(CASE WHEN mc.tipo_movimiento='INGRESO' THEN mc.monto ELSE 0 END),0) AS ingresos,
    COALESCE(SUM(CASE WHEN mc.tipo_movimiento='EGRESO' THEN mc.monto ELSE 0 END),0) AS egresos
FROM turnos_caja tc
LEFT JOIN movimientos_caja mc ON mc.id_turno_caja = tc.id
GROUP BY tc.id, tc.fecha_apertura, tc.fecha_cierre, tc.estado, tc.monto_apertura;

CREATE OR REPLACE VIEW vw_top_productos AS
SELECT
    p.id,
    p.nombre,
    SUM(od.cantidad) AS cantidad_vendida,
    SUM(od.subtotal) AS total_vendido
FROM orden_detalle od
JOIN productos p ON p.id = od.id_producto
JOIN ordenes o ON o.id = od.id_orden
WHERE o.estado = 'PAGADA'
  AND od.estado_item <> 'ANULADO'
GROUP BY p.id, p.nombre
ORDER BY cantidad_vendida DESC;

CREATE OR REPLACE VIEW vw_ventas_por_dia AS
SELECT
    DATE(fecha_creacion) AS fecha,
    COUNT(*) AS ordenes_pagadas,
    SUM(total) AS total_vendido
FROM ordenes
WHERE estado = 'PAGADA'
GROUP BY DATE(fecha_creacion)
ORDER BY fecha DESC;

CREATE OR REPLACE VIEW vw_delivery_resumen AS
SELECT
    d.id,
    o.codigo,
    c.nombre_razon_social AS cliente,
    dc.direccion,
    CONCAT(mo.nombres, ' ', COALESCE(mo.apellidos,'')) AS motorizado,
    d.tarifa_delivery,
    d.estado_delivery,
    d.fecha_asignacion,
    d.fecha_salida,
    d.fecha_entrega
FROM delivery_ordenes d
JOIN ordenes o ON o.id = d.id_orden
JOIN direcciones_cliente dc ON dc.id = d.id_direccion_cliente
LEFT JOIN clientes c ON c.id = o.id_cliente
LEFT JOIN motorizados mo ON mo.id = d.id_motorizado;

-- =========================================================
-- 8. PROCEDIMIENTOS ALMACENADOS
-- =========================================================

DELIMITER $$

CREATE PROCEDURE sp_recalcular_totales_orden(IN p_id_orden BIGINT UNSIGNED)
BEGIN
    DECLARE v_subtotal DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_subtotal_afecto DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_igv DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_descuento DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_descuento_afecto DECIMAL(12,2) DEFAULT 0.00;

    SELECT
        COALESCE(SUM(od.subtotal), 0),
        COALESCE(SUM(CASE WHEN COALESCE(p.afecto_igv, 0) = 1 THEN od.subtotal ELSE 0 END), 0)
      INTO v_subtotal, v_subtotal_afecto
    FROM orden_detalle od
    LEFT JOIN productos p ON p.id = od.id_producto
    WHERE od.id_orden = p_id_orden
      AND od.estado_item <> 'ANULADO';

    SELECT COALESCE(descuento,0)
      INTO v_descuento
    FROM ordenes
    WHERE id = p_id_orden;

    SET v_descuento = LEAST(v_descuento, v_subtotal);
    SET v_descuento_afecto = CASE
        WHEN v_subtotal > 0 THEN ROUND(v_descuento * (v_subtotal_afecto / v_subtotal), 2)
        ELSE 0.00
    END;
    SET v_igv = ROUND(
        GREATEST(v_subtotal_afecto - v_descuento_afecto, 0)
        - (GREATEST(v_subtotal_afecto - v_descuento_afecto, 0) / 1.18),
        2
    );

    UPDATE ordenes
       SET subtotal = v_subtotal,
           igv = v_igv,
           total = ROUND(v_subtotal - v_descuento, 2),
           fecha_actualizacion = NOW()
     WHERE id = p_id_orden;
END$$

CREATE PROCEDURE sp_recalcular_costo_receta(IN p_id_receta BIGINT UNSIGNED)
BEGIN
    DECLARE v_total DECIMAL(12,4) DEFAULT 0.0000;

    SELECT COALESCE(SUM(rd.cantidad * i.costo_unitario),0)
      INTO v_total
    FROM receta_detalle rd
    INNER JOIN ingredientes i ON i.id = rd.id_ingrediente
    WHERE rd.id_receta = p_id_receta;

    UPDATE recetas
       SET costo_total = v_total, updated_at = NOW()
     WHERE id = p_id_receta;

    UPDATE productos p
    JOIN recetas r ON r.id_producto = p.id
       SET p.costo_unitario = r.costo_total,
           p.margen_porcentaje = CASE
               WHEN p.precio_venta > 0 THEN ROUND(((p.precio_venta - r.costo_total) / p.precio_venta) * 100, 2)
               ELSE 0
           END
     WHERE r.id = p_id_receta;
END$$

CREATE PROCEDURE sp_abrir_turno_caja(
    IN p_id_sucursal BIGINT UNSIGNED,
    IN p_id_usuario BIGINT UNSIGNED,
    IN p_monto_apertura DECIMAL(12,2),
    IN p_observaciones VARCHAR(255)
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_turno BIGINT UNSIGNED;

    SELECT COUNT(*) INTO v_count
    FROM turnos_caja
    WHERE id_sucursal = p_id_sucursal
      AND estado = 'ABIERTO';

    IF v_count > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya existe un turno abierto para la sucursal.';
    END IF;

    INSERT INTO turnos_caja(id_sucursal, id_usuario_apertura, monto_apertura, observaciones)
    VALUES (p_id_sucursal, p_id_usuario, p_monto_apertura, p_observaciones);

    SET v_turno = LAST_INSERT_ID();

    INSERT INTO movimientos_caja(id_turno_caja, tipo_movimiento, origen, id_referencia, descripcion, monto, id_usuario)
    VALUES (v_turno, 'INGRESO', 'APERTURA', v_turno, 'Apertura de caja', p_monto_apertura, p_id_usuario);

    SELECT v_turno AS id_turno_creado;
END$$

CREATE PROCEDURE sp_cerrar_turno_caja(
    IN p_id_turno BIGINT UNSIGNED,
    IN p_id_usuario BIGINT UNSIGNED,
    IN p_monto_cierre DECIMAL(12,2)
)
BEGIN
    UPDATE turnos_caja
       SET estado = 'CERRADO',
           fecha_cierre = NOW(),
           id_usuario_cierre = p_id_usuario,
           monto_cierre = p_monto_cierre
     WHERE id = p_id_turno
       AND estado = 'ABIERTO';

    IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Turno no encontrado o ya cerrado.';
    END IF;

    INSERT INTO movimientos_caja(id_turno_caja, tipo_movimiento, origen, id_referencia, descripcion, monto, id_usuario)
    VALUES (p_id_turno, 'EGRESO', 'CIERRE', p_id_turno, 'Cierre de caja', p_monto_cierre, p_id_usuario);
END$$

CREATE PROCEDURE sp_crear_orden(
    IN p_id_sucursal BIGINT UNSIGNED,
    IN p_id_mesa BIGINT UNSIGNED,
    IN p_id_cliente BIGINT UNSIGNED,
    IN p_id_usuario BIGINT UNSIGNED,
    IN p_tipo_servicio VARCHAR(20),
    IN p_cantidad_personas INT,
    IN p_observaciones VARCHAR(255)
)
BEGIN
    DECLARE v_correlativo BIGINT DEFAULT 1;
    DECLARE v_codigo VARCHAR(50);
    DECLARE v_id_orden BIGINT UNSIGNED;

    SELECT COALESCE(MAX(numero_correlativo),0)+1
      INTO v_correlativo
    FROM ordenes
    WHERE id_sucursal = p_id_sucursal;

    SET v_codigo = CONCAT('ORD-', LPAD(p_id_sucursal,2,'0'), '-', LPAD(v_correlativo,8,'0'));

    INSERT INTO ordenes(
        codigo, numero_correlativo, id_sucursal, id_mesa, id_cliente, id_usuario_creador,
        tipo_servicio, cantidad_personas, observaciones, estado
    )
    VALUES(
        v_codigo, v_correlativo, p_id_sucursal, p_id_mesa, p_id_cliente, p_id_usuario,
        p_tipo_servicio, p_cantidad_personas, p_observaciones, 'ABIERTA'
    );

    SET v_id_orden = LAST_INSERT_ID();

    IF p_id_mesa IS NOT NULL THEN
        UPDATE mesas SET estado = 'OCUPADA' WHERE id = p_id_mesa;
        INSERT INTO historial_mesas(id_mesa, estado_anterior, estado_nuevo, motivo, id_orden, id_usuario)
        VALUES (p_id_mesa, 'LIBRE', 'OCUPADA', 'Apertura de orden', v_id_orden, p_id_usuario);
    END IF;

    INSERT INTO orden_estados_historial(id_orden, estado_anterior, estado_nuevo, comentario, id_usuario)
    VALUES (v_id_orden, NULL, 'ABIERTA', 'Orden creada', p_id_usuario);

    SELECT v_id_orden AS id_orden, v_codigo AS codigo_orden;
END$$

CREATE PROCEDURE sp_agregar_item_orden(
    IN p_id_orden BIGINT UNSIGNED,
    IN p_id_producto BIGINT UNSIGNED,
    IN p_cantidad DECIMAL(12,2),
    IN p_observacion VARCHAR(255)
)
BEGIN
    DECLARE v_nombre VARCHAR(150);
    DECLARE v_precio DECIMAL(12,2);
    DECLARE v_area BIGINT UNSIGNED;

    SELECT nombre, precio_venta, id_area_preparacion
      INTO v_nombre, v_precio, v_area
    FROM productos
    WHERE id = p_id_producto
      AND activo = 1;

    IF v_nombre IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Producto no encontrado o inactivo.';
    END IF;

    IF p_cantidad <= 0 OR p_cantidad <> FLOOR(p_cantidad) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cantidad debe ser un numero entero mayor a cero.';
    END IF;

    INSERT INTO orden_detalle(
        id_orden, id_producto, nombre_producto, precio_unitario, cantidad, subtotal, observacion, id_area_preparacion
    )
    VALUES (
        p_id_orden, p_id_producto, v_nombre, v_precio, FLOOR(p_cantidad), ROUND(v_precio * FLOOR(p_cantidad), 2), p_observacion, v_area
    );

    CALL sp_recalcular_totales_orden(p_id_orden);
END$$

CREATE PROCEDURE sp_aplicar_descuento_orden(
    IN p_id_orden BIGINT UNSIGNED,
    IN p_descuento DECIMAL(12,2)
)
BEGIN
    UPDATE ordenes
       SET descuento = p_descuento,
           fecha_actualizacion = NOW()
     WHERE id = p_id_orden;
    CALL sp_recalcular_totales_orden(p_id_orden);
END$$

CREATE PROCEDURE sp_generar_comanda(
    IN p_id_orden BIGINT UNSIGNED,
    IN p_id_area BIGINT UNSIGNED,
    IN p_id_usuario BIGINT UNSIGNED
)
BEGIN
    DECLARE v_numero VARCHAR(50);
    DECLARE v_id_comanda BIGINT UNSIGNED;

    SET v_numero = CONCAT('COM-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), '-', p_id_area);

    INSERT INTO comandas(id_orden, numero_comanda, id_area_preparacion, id_usuario)
    VALUES (p_id_orden, v_numero, p_id_area, p_id_usuario);

    SET v_id_comanda = LAST_INSERT_ID();

    INSERT INTO comanda_detalle(id_comanda, id_orden_detalle, cantidad, observacion)
    SELECT v_id_comanda, od.id, od.cantidad, od.observacion
    FROM orden_detalle od
    WHERE od.id_orden = p_id_orden
      AND od.id_area_preparacion = p_id_area
      AND od.estado_item <> 'ANULADO';

    UPDATE ordenes
       SET estado = 'EN_PREPARACION'
     WHERE id = p_id_orden
       AND estado = 'ABIERTA';

    INSERT INTO orden_estados_historial(id_orden, estado_anterior, estado_nuevo, comentario, id_usuario)
    SELECT p_id_orden, 'ABIERTA', 'EN_PREPARACION', 'Comanda generada', p_id_usuario
    WHERE EXISTS (SELECT 1 FROM ordenes WHERE id = p_id_orden AND estado = 'EN_PREPARACION');

    SELECT v_id_comanda AS id_comanda, v_numero AS numero_comanda;
END$$

CREATE PROCEDURE sp_cambiar_estado_orden(
    IN p_id_orden BIGINT UNSIGNED,
    IN p_nuevo_estado VARCHAR(20),
    IN p_id_usuario BIGINT UNSIGNED,
    IN p_comentario VARCHAR(255)
)
BEGIN
    DECLARE v_estado_anterior VARCHAR(20);
    DECLARE v_id_mesa BIGINT UNSIGNED;

    SELECT estado, id_mesa INTO v_estado_anterior, v_id_mesa
    FROM ordenes
    WHERE id = p_id_orden;

    UPDATE ordenes
       SET estado = p_nuevo_estado,
           fecha_actualizacion = NOW(),
           fecha_cierre = CASE WHEN p_nuevo_estado IN ('COMPLETADA','PAGADA','ANULADA') THEN NOW() ELSE fecha_cierre END
     WHERE id = p_id_orden;

    INSERT INTO orden_estados_historial(id_orden, estado_anterior, estado_nuevo, comentario, id_usuario)
    VALUES (p_id_orden, v_estado_anterior, p_nuevo_estado, p_comentario, p_id_usuario);

    IF p_nuevo_estado IN ('PAGADA','ANULADA') AND v_id_mesa IS NOT NULL THEN
        UPDATE mesas SET estado = 'LIBRE' WHERE id = v_id_mesa;
        INSERT INTO historial_mesas(id_mesa, estado_anterior, estado_nuevo, motivo, id_orden, id_usuario)
        VALUES (v_id_mesa, 'OCUPADA', 'LIBRE', CONCAT('Orden ', p_nuevo_estado), p_id_orden, p_id_usuario);
    END IF;
END$$

CREATE PROCEDURE sp_consumir_inventario_orden(
    IN p_id_orden BIGINT UNSIGNED,
    IN p_id_usuario BIGINT UNSIGNED
)
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_id_producto BIGINT UNSIGNED;
    DECLARE v_cantidad_producto DECIMAL(12,2);
    DECLARE v_id_receta BIGINT UNSIGNED;

    DECLARE cur CURSOR FOR
        SELECT od.id_producto, od.cantidad
        FROM orden_detalle od
        WHERE od.id_orden = p_id_orden
          AND od.estado_item <> 'ANULADO';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    ciclo: LOOP
        FETCH cur INTO v_id_producto, v_cantidad_producto;
        IF done = 1 THEN
            LEAVE ciclo;
        END IF;

        SET v_id_receta = NULL;

        SELECT id INTO v_id_receta
        FROM recetas
        WHERE id_producto = v_id_producto
          AND activo = 1
        LIMIT 1;

        IF v_id_receta IS NOT NULL THEN
            INSERT INTO kardex_movimientos(
                tipo_item, id_item, tipo_movimiento, origen, id_referencia,
                cantidad, saldo_anterior, saldo_nuevo, costo_unitario, costo_total, id_usuario, observacion
            )
            SELECT
                'INGREDIENTE',
                rd.id_ingrediente,
                'SALIDA',
                'VENTA',
                p_id_orden,
                ROUND(rd.cantidad * v_cantidad_producto, 3),
                i.stock_actual,
                i.stock_actual - ROUND(rd.cantidad * v_cantidad_producto, 3),
                i.costo_unitario,
                ROUND((rd.cantidad * v_cantidad_producto) * i.costo_unitario, 4),
                p_id_usuario,
                CONCAT('Consumo por orden ', p_id_orden)
            FROM receta_detalle rd
            JOIN ingredientes i ON i.id = rd.id_ingrediente
            WHERE rd.id_receta = v_id_receta;

            UPDATE ingredientes i
            JOIN receta_detalle rd ON rd.id_ingrediente = i.id
               SET i.stock_actual = i.stock_actual - ROUND(rd.cantidad * v_cantidad_producto, 3)
             WHERE rd.id_receta = v_id_receta;
        END IF;
    END LOOP;
    CLOSE cur;

    INSERT INTO inventario_alertas(tipo_item, id_item, tipo_alerta, descripcion)
    SELECT 'INGREDIENTE', i.id,
           CASE WHEN i.stock_actual <= 0 THEN 'AGOTADO' ELSE 'STOCK_BAJO' END,
           CONCAT('Stock bajo o agotado: ', i.nombre)
    FROM ingredientes i
    WHERE i.stock_actual <= i.stock_minimo
      AND NOT EXISTS (
          SELECT 1 FROM inventario_alertas ia
          WHERE ia.tipo_item = 'INGREDIENTE'
            AND ia.id_item = i.id
            AND ia.estado = 'ACTIVA'
      );
END$$

CREATE PROCEDURE sp_registrar_pago_orden(
    IN p_id_orden BIGINT UNSIGNED,
    IN p_id_turno BIGINT UNSIGNED,
    IN p_id_metodo_pago BIGINT UNSIGNED,
    IN p_monto DECIMAL(12,2),
    IN p_monto_recibido DECIMAL(12,2),
    IN p_numero_operacion VARCHAR(100),
    IN p_observaciones VARCHAR(255),
    IN p_id_usuario BIGINT UNSIGNED
)
BEGIN
    DECLARE v_total DECIMAL(12,2);
    DECLARE v_pago BIGINT UNSIGNED;

    SELECT total INTO v_total
    FROM ordenes
    WHERE id = p_id_orden;

    IF v_total IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Orden no encontrada.';
    END IF;

    IF p_monto < v_total THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Monto menor al total de la orden.';
    END IF;

    INSERT INTO pagos(
        id_orden, id_turno_caja, id_metodo_pago, monto, monto_recibido, vuelto,
        numero_operacion, observaciones, id_usuario, estado
    )
    VALUES(
        p_id_orden, p_id_turno, p_id_metodo_pago, p_monto, p_monto_recibido,
        CASE
            WHEN p_monto_recibido IS NOT NULL AND p_monto_recibido > p_monto THEN ROUND(p_monto_recibido - p_monto, 2)
            ELSE 0.00
        END,
        p_numero_operacion, p_observaciones, p_id_usuario, 'VALIDADO'
    );

    SET v_pago = LAST_INSERT_ID();

    INSERT INTO movimientos_caja(id_turno_caja, tipo_movimiento, origen, id_referencia, descripcion, monto, id_usuario)
    VALUES (p_id_turno, 'INGRESO', 'PAGO_ORDEN', v_pago, CONCAT('Cobro orden ', p_id_orden), p_monto, p_id_usuario);

    CALL sp_consumir_inventario_orden(p_id_orden, p_id_usuario);
    CALL sp_cambiar_estado_orden(p_id_orden, 'PAGADA', p_id_usuario, 'Pago registrado');

    SELECT v_pago AS id_pago_registrado;
END$$

CREATE PROCEDURE sp_emitir_comprobante(
    IN p_id_orden BIGINT UNSIGNED,
    IN p_id_pago BIGINT UNSIGNED,
    IN p_id_cliente BIGINT UNSIGNED,
    IN p_id_tipo_comprobante BIGINT UNSIGNED,
    IN p_id_usuario BIGINT UNSIGNED
)
BEGIN
    DECLARE v_serie VARCHAR(10);
    DECLARE v_numero BIGINT;
    DECLARE v_subtotal DECIMAL(12,2);
    DECLARE v_igv DECIMAL(12,2);
    DECLARE v_total DECIMAL(12,2);
    DECLARE v_id_comp BIGINT UNSIGNED;

    START TRANSACTION;

    SELECT serie, correlativo_actual + 1
      INTO v_serie, v_numero
    FROM tipos_comprobante
    WHERE id = p_id_tipo_comprobante
      AND activo = 1
    FOR UPDATE;

    IF v_serie IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tipo de comprobante inválido.';
    END IF;

    SELECT subtotal, igv, total
      INTO v_subtotal, v_igv, v_total
    FROM ordenes
    WHERE id = p_id_orden
    FOR UPDATE;

    UPDATE tipos_comprobante
       SET correlativo_actual = v_numero
     WHERE id = p_id_tipo_comprobante;

    INSERT INTO comprobantes(
        id_orden, id_pago, id_cliente, id_tipo_comprobante,
        serie, numero, subtotal, igv, total, id_usuario
    )
    VALUES(
        p_id_orden, p_id_pago, p_id_cliente, p_id_tipo_comprobante,
        v_serie, v_numero, v_subtotal, v_igv, v_total, p_id_usuario
    );

    SET v_id_comp = LAST_INSERT_ID();

    INSERT INTO comprobante_detalle(
        id_comprobante, id_producto, descripcion, cantidad, valor_unitario, precio_unitario, igv_unitario, importe_total
    )
    SELECT
        v_id_comp,
        od.id_producto,
        od.nombre_producto,
        od.cantidad,
        ROUND(od.precio_unitario / 1.18, 2),
        od.precio_unitario,
        ROUND(od.precio_unitario - (od.precio_unitario / 1.18), 2),
        od.subtotal
    FROM orden_detalle od
    WHERE od.id_orden = p_id_orden
      AND od.estado_item <> 'ANULADO';

    COMMIT;

    SELECT v_id_comp AS id_comprobante, v_serie AS serie, v_numero AS numero;
END$$

CREATE PROCEDURE sp_anular_comprobante(
    IN p_id_comprobante BIGINT UNSIGNED,
    IN p_motivo VARCHAR(255)
)
BEGIN
    UPDATE comprobantes
       SET estado = 'ANULADO',
           motivo_anulacion = p_motivo,
           fecha_anulacion = NOW()
     WHERE id = p_id_comprobante
       AND estado = 'EMITIDO';

    IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pudo anular el comprobante.';
    END IF;
END$$

CREATE PROCEDURE sp_registrar_compra(
    IN p_id_sucursal BIGINT UNSIGNED,
    IN p_id_proveedor BIGINT UNSIGNED,
    IN p_tipo_documento VARCHAR(20),
    IN p_serie VARCHAR(10),
    IN p_numero VARCHAR(20),
    IN p_id_usuario BIGINT UNSIGNED,
    IN p_observaciones VARCHAR(255)
)
BEGIN
    INSERT INTO compras(
        id_sucursal, id_proveedor, tipo_documento, serie, numero, id_usuario, observaciones
    )
    VALUES (
        p_id_sucursal, p_id_proveedor, p_tipo_documento, p_serie, p_numero, p_id_usuario, p_observaciones
    );

    SELECT LAST_INSERT_ID() AS id_compra;
END$$

CREATE PROCEDURE sp_agregar_detalle_compra(
    IN p_id_compra BIGINT UNSIGNED,
    IN p_tipo_item VARCHAR(20),
    IN p_id_item BIGINT UNSIGNED,
    IN p_descripcion VARCHAR(150),
    IN p_cantidad DECIMAL(14,3),
    IN p_costo_unitario DECIMAL(12,4)
)
BEGIN
    INSERT INTO compra_detalle(
        id_compra, tipo_item, id_item, descripcion, cantidad, costo_unitario, subtotal
    )
    VALUES(
        p_id_compra, p_tipo_item, p_id_item, p_descripcion, p_cantidad, p_costo_unitario, ROUND(p_cantidad * p_costo_unitario, 2)
    );

    UPDATE compras
       SET subtotal = (SELECT COALESCE(SUM(subtotal),0) FROM compra_detalle WHERE id_compra = p_id_compra),
           igv = ROUND((SELECT COALESCE(SUM(subtotal),0) FROM compra_detalle WHERE id_compra = p_id_compra) * 0.18, 2),
           total = ROUND((SELECT COALESCE(SUM(subtotal),0) FROM compra_detalle WHERE id_compra = p_id_compra) * 1.18, 2)
     WHERE id = p_id_compra;
END$$

CREATE PROCEDURE sp_confirmar_compra(IN p_id_compra BIGINT UNSIGNED)
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_tipo_item VARCHAR(20);
    DECLARE v_id_item BIGINT UNSIGNED;
    DECLARE v_cantidad DECIMAL(14,3);
    DECLARE v_costo DECIMAL(12,4);

    DECLARE cur CURSOR FOR
        SELECT tipo_item, id_item, cantidad, costo_unitario
        FROM compra_detalle
        WHERE id_compra = p_id_compra;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    loop_compra: LOOP
        FETCH cur INTO v_tipo_item, v_id_item, v_cantidad, v_costo;
        IF done = 1 THEN
            LEAVE loop_compra;
        END IF;

        IF v_tipo_item = 'INGREDIENTE' THEN
            INSERT INTO kardex_movimientos(
                tipo_item, id_item, tipo_movimiento, origen, id_referencia,
                cantidad, saldo_anterior, saldo_nuevo, costo_unitario, costo_total, observacion
            )
            SELECT
                'INGREDIENTE', i.id, 'ENTRADA', 'COMPRA', p_id_compra,
                v_cantidad, i.stock_actual, i.stock_actual + v_cantidad, v_costo, ROUND(v_cantidad * v_costo, 4),
                CONCAT('Compra #', p_id_compra)
            FROM ingredientes i WHERE i.id = v_id_item;

            UPDATE ingredientes
               SET stock_actual = stock_actual + v_cantidad,
                   costo_unitario = v_costo
             WHERE id = v_id_item;
        ELSE
            INSERT INTO kardex_movimientos(
                tipo_item, id_item, tipo_movimiento, origen, id_referencia,
                cantidad, saldo_anterior, saldo_nuevo, costo_unitario, costo_total, observacion
            )
            SELECT
                'ARTICULO', a.id, 'ENTRADA', 'COMPRA', p_id_compra,
                v_cantidad, a.stock_actual, a.stock_actual + v_cantidad, v_costo, ROUND(v_cantidad * v_costo, 4),
                CONCAT('Compra #', p_id_compra)
            FROM articulos_inventario a WHERE a.id = v_id_item;

            UPDATE articulos_inventario
               SET stock_actual = stock_actual + v_cantidad,
                   costo_unitario = v_costo
             WHERE id = v_id_item;
        END IF;
    END LOOP;
    CLOSE cur;
END$$

CREATE PROCEDURE sp_asignar_delivery(
    IN p_id_orden BIGINT UNSIGNED,
    IN p_id_direccion_cliente BIGINT UNSIGNED,
    IN p_id_motorizado BIGINT UNSIGNED,
    IN p_tarifa DECIMAL(12,2),
    IN p_observaciones VARCHAR(255)
)
BEGIN
    INSERT INTO delivery_ordenes(
        id_orden, id_direccion_cliente, id_motorizado, tarifa_delivery, estado_delivery, fecha_asignacion, observaciones
    )
    VALUES(
        p_id_orden, p_id_direccion_cliente, p_id_motorizado, p_tarifa, 'ASIGNADO', NOW(), p_observaciones
    );

    UPDATE motorizados
       SET estado = 'EN_RUTA'
     WHERE id = p_id_motorizado;

    UPDATE ordenes
       SET tipo_servicio = 'DELIVERY'
     WHERE id = p_id_orden;
END$$

CREATE PROCEDURE sp_marcar_delivery_entregado(IN p_id_delivery BIGINT UNSIGNED)
BEGIN
    DECLARE v_id_motorizado BIGINT UNSIGNED;

    SELECT id_motorizado INTO v_id_motorizado
    FROM delivery_ordenes
    WHERE id = p_id_delivery;

    UPDATE delivery_ordenes
       SET estado_delivery = 'ENTREGADO',
           fecha_entrega = NOW()
     WHERE id = p_id_delivery;

    UPDATE motorizados
       SET estado = 'DISPONIBLE'
     WHERE id = v_id_motorizado;
END$$

-- =========================================================
-- 9. TRIGGERS
-- =========================================================

CREATE TRIGGER trg_orden_bu
BEFORE UPDATE ON ordenes
FOR EACH ROW
BEGIN
    SET NEW.fecha_actualizacion = NOW();
END$$

CREATE TRIGGER trg_od_bi
BEFORE INSERT ON orden_detalle
FOR EACH ROW
BEGIN
    SET NEW.subtotal = ROUND(NEW.precio_unitario * NEW.cantidad, 2);
END$$

CREATE TRIGGER trg_od_bu
BEFORE UPDATE ON orden_detalle
FOR EACH ROW
BEGIN
    SET NEW.subtotal = ROUND(NEW.precio_unitario * NEW.cantidad, 2);
END$$

CREATE TRIGGER trg_od_ai
AFTER INSERT ON orden_detalle
FOR EACH ROW
BEGIN
    CALL sp_recalcular_totales_orden(NEW.id_orden);
END$$

CREATE TRIGGER trg_od_au
AFTER UPDATE ON orden_detalle
FOR EACH ROW
BEGIN
    CALL sp_recalcular_totales_orden(NEW.id_orden);
END$$

CREATE TRIGGER trg_od_ad
AFTER DELETE ON orden_detalle
FOR EACH ROW
BEGIN
    CALL sp_recalcular_totales_orden(OLD.id_orden);
END$$

CREATE TRIGGER trg_rd_bi
BEFORE INSERT ON receta_detalle
FOR EACH ROW
BEGIN
    DECLARE v_costo_unitario DECIMAL(12,4) DEFAULT 0.0000;

    SELECT costo_unitario
      INTO v_costo_unitario
    FROM ingredientes
    WHERE id = NEW.id_ingrediente;

    SET NEW.costo_unitario = COALESCE(v_costo_unitario, 0.0000);
    SET NEW.costo_total = ROUND(NEW.cantidad * NEW.costo_unitario, 4);
END$$

CREATE TRIGGER trg_rd_ai
AFTER INSERT ON receta_detalle
FOR EACH ROW
BEGIN
    CALL sp_recalcular_costo_receta(NEW.id_receta);
END$$

CREATE TRIGGER trg_rd_bu
BEFORE UPDATE ON receta_detalle
FOR EACH ROW
BEGIN
    DECLARE v_costo_unitario DECIMAL(12,4) DEFAULT 0.0000;

    SELECT costo_unitario
      INTO v_costo_unitario
    FROM ingredientes
    WHERE id = NEW.id_ingrediente;

    SET NEW.costo_unitario = COALESCE(v_costo_unitario, 0.0000);
    SET NEW.costo_total = ROUND(NEW.cantidad * NEW.costo_unitario, 4);
END$$

CREATE TRIGGER trg_rd_au
AFTER UPDATE ON receta_detalle
FOR EACH ROW
BEGIN
    CALL sp_recalcular_costo_receta(NEW.id_receta);

    IF OLD.id_receta <> NEW.id_receta THEN
        CALL sp_recalcular_costo_receta(OLD.id_receta);
    END IF;
END$$

CREATE TRIGGER trg_rd_ad
AFTER DELETE ON receta_detalle
FOR EACH ROW
BEGIN
    CALL sp_recalcular_costo_receta(OLD.id_receta);
END$$

CREATE TRIGGER trg_pago_bi
BEFORE INSERT ON pagos
FOR EACH ROW
BEGIN
    IF NEW.monto_recibido IS NOT NULL AND NEW.monto_recibido < NEW.monto THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El monto recibido no puede ser menor al monto.';
    END IF;

    IF NEW.monto_recibido IS NOT NULL THEN
        SET NEW.vuelto = ROUND(NEW.monto_recibido - NEW.monto, 2);
    ELSE
        SET NEW.vuelto = 0.00;
    END IF;
END$$

CREATE TRIGGER trg_gasto_ai
AFTER INSERT ON gastos
FOR EACH ROW
BEGIN
    INSERT INTO movimientos_caja(
        id_turno_caja, tipo_movimiento, origen, id_referencia, descripcion, monto, id_usuario
    )
    VALUES(
        NEW.id_turno_caja, 'EGRESO', 'GASTO', NEW.id,
        CONCAT('Gasto: ', NEW.descripcion), NEW.monto, NEW.id_usuario
    );
END$$

CREATE TRIGGER trg_auditoria_orden_ai
AFTER INSERT ON ordenes
FOR EACH ROW
BEGIN
    INSERT INTO auditoria_log(tabla, accion, id_registro, usuario_db, detalle)
    VALUES('ordenes', 'INSERT', NEW.id, CURRENT_USER(), CONCAT('Orden creada: ', NEW.codigo));
END$$

CREATE TRIGGER trg_auditoria_pago_ai
AFTER INSERT ON pagos
FOR EACH ROW
BEGIN
    INSERT INTO auditoria_log(tabla, accion, id_registro, usuario_db, detalle)
    VALUES('pagos', 'INSERT', NEW.id, CURRENT_USER(), CONCAT('Pago registrado por monto: ', NEW.monto));
END$$

CREATE TRIGGER trg_auditoria_comp_ai
AFTER INSERT ON comprobantes
FOR EACH ROW
BEGIN
    INSERT INTO auditoria_log(tabla, accion, id_registro, usuario_db, detalle)
    VALUES('comprobantes', 'INSERT', NEW.id, CURRENT_USER(), CONCAT('Comprobante emitido: ', NEW.serie, '-', NEW.numero));
END$$

DELIMITER ;

-- =========================================================
-- 10. INDICES
-- =========================================================

CREATE INDEX idx_o_fecha ON ordenes(fecha_creacion);
CREATE INDEX idx_o_estado ON ordenes(estado);
CREATE INDEX idx_o_mesa ON ordenes(id_mesa);
CREATE INDEX idx_od_orden ON orden_detalle(id_orden);
CREATE INDEX idx_p_orden ON pagos(id_orden);
CREATE INDEX idx_k_item ON kardex_movimientos(tipo_item, id_item);
CREATE INDEX idx_ing_nombre ON ingredientes(nombre);
CREATE INDEX idx_prod_nombre ON productos(nombre);
CREATE INDEX idx_compra_fecha ON compras(fecha_compra);
CREATE INDEX idx_delivery_estado ON delivery_ordenes(estado_delivery);

-- =========================================================
-- 11. DATOS DE PRUEBA REALES
-- =========================================================

INSERT INTO empresas(razon_social, nombre_comercial, ruc, direccion_fiscal, telefono, correo)
VALUES ('Pollería El Sabor Ayacuchano S.A.C.', 'Pollería El Sabor Ayacuchano', '20601234567', 'Jr. Asamblea 245, Ayacucho', '966111222', 'ventas@elsabor.com');

INSERT INTO sucursales(id_empresa, nombre, direccion, telefono)
VALUES (1, 'Sucursal Centro', 'Jr. Asamblea 245, Ayacucho', '966111222');

INSERT INTO roles(nombre, descripcion) VALUES
('PROPIETARIO','Acceso total'),
('ADMINISTRADOR','Administración general'),
('CAJERO','Caja y cobros'),
('MOZO','Atención y pedidos'),
('COCINA','Preparación'),
('DELIVERY','Reparto');

INSERT INTO permisos(codigo, nombre, descripcion) VALUES
('dashboard.ver','Ver dashboard','Acceso al panel'),
('ordenes.crear','Crear órdenes','Crear pedidos'),
('ordenes.editar','Editar órdenes','Editar pedidos'),
('caja.gestionar','Gestionar caja','Abrir/cerrar caja'),
('inventario.gestionar','Gestionar inventario','Administrar stock'),
('comprobantes.emitir','Emitir comprobantes','Boletas/facturas'),
('mesas.gestionar','Gestionar mesas','Cambios de estado'),
('compras.gestionar','Gestionar compras','Registrar compras'),
('delivery.gestionar','Gestionar delivery','Asignar motorizados'),
('usuarios.gestionar','Gestionar usuarios','Administrar usuarios');

INSERT INTO rol_permisos(id_rol, id_permiso)
SELECT 1, id FROM permisos;

INSERT INTO rol_permisos(id_rol, id_permiso)
SELECT 2, id FROM permisos WHERE codigo <> 'usuarios.gestionar';

INSERT INTO rol_permisos(id_rol, id_permiso)
SELECT 3, id FROM permisos WHERE codigo IN ('dashboard.ver','caja.gestionar','comprobantes.emitir','ordenes.crear');

INSERT INTO rol_permisos(id_rol, id_permiso)
SELECT 4, id FROM permisos WHERE codigo IN ('dashboard.ver','ordenes.crear','ordenes.editar','mesas.gestionar');

INSERT INTO rol_permisos(id_rol, id_permiso)
SELECT 5, id FROM permisos WHERE codigo IN ('dashboard.ver');

INSERT INTO rol_permisos(id_rol, id_permiso)
SELECT 6, id FROM permisos WHERE codigo IN ('dashboard.ver','delivery.gestionar');

INSERT INTO usuarios(id_sucursal, id_rol, nombres, apellidos, usuario, correo, telefono, password_hash) VALUES
(1,1,'Juan','Huamán','propietario','propietario@polleria.com','900111111','$2y$10$abcdefghijklmnopqrstuv'),
(1,2,'María','Quispe','admin','admin@polleria.com','900222222','$2y$10$abcdefghijklmnopqrstuv'),
(1,3,'Rosa','Flores','cajera','caja@polleria.com','900333333','$2y$10$abcdefghijklmnopqrstuv'),
(1,4,'Luis','García','mozo1','mozo1@polleria.com','900444444','$2y$10$abcdefghijklmnopqrstuv'),
(1,5,'Pedro','Mendoza','cocina1','cocina1@polleria.com','900555555','$2y$10$abcdefghijklmnopqrstuv'),
(1,6,'Carlos','Rojas','delivery1','delivery1@polleria.com','900666666','$2y$10$abcdefghijklmnopqrstuv');

INSERT INTO clientes(tipo_documento, numero_documento, nombre_razon_social, direccion, telefono, correo) VALUES
('DNI','70881122','Carlos Ramos','Av. Mariscal Cáceres 120','980111111','carlos@gmail.com'),
('DNI','74556677','Ana Torres','Jr. Bellido 430','980222222','ana@gmail.com'),
('RUC','20604567891','Constructora Wari SAC','Av. Perú 560','980333333','ventas@wari.com');

INSERT INTO direcciones_cliente(id_cliente, direccion, referencia, principal) VALUES
(1,'Av. Mariscal Cáceres 120','Frente a farmacia',1),
(2,'Jr. Bellido 430','Media cuadra del mercado',1),
(3,'Av. Perú 560','Segundo piso',1);

INSERT INTO motorizados(nombres, apellidos, dni, telefono, placa, estado) VALUES
('Carlos','Rojas','71223344','900666666','1234-8Y','DISPONIBLE'),
('Miguel','Paredes','74445566','900777777','5678-3A','DISPONIBLE');

INSERT INTO zonas_mesa(id_sucursal, nombre, descripcion) VALUES
(1,'Salón Principal','Zona central'),
(1,'Terraza','Zona exterior'),
(1,'VIP Familiar','Zona reservada');

INSERT INTO mesas(id_sucursal, id_zona, numero, nombre, capacidad, estado) VALUES
(1,1,'01','Mesa 01',4,'LIBRE'),
(1,1,'02','Mesa 02',4,'LIBRE'),
(1,1,'03','Mesa 03',6,'LIBRE'),
(1,2,'04','Mesa 04',4,'LIBRE'),
(1,2,'05','Mesa 05',2,'LIBRE'),
(1,3,'06','Mesa 06',8,'LIBRE');

INSERT INTO categorias_producto(nombre, descripcion, tipo) VALUES
('Pollos','Platos a base de pollo','PLATOS'),
('Parrillas','Brasas y parrillas','PLATOS'),
('Bebidas','Gaseosas y refrescos','BEBIDAS'),
('Guarniciones','Acompañamientos','EXTRAS'),
('Postres','Postres caseros','POSTRES'),
('Combos','Promociones y combos','COMBOS');

INSERT INTO areas_preparacion(nombre, descripcion) VALUES
('COCINA','Platos y pollos'),
('BAR','Bebidas');

INSERT INTO productos(sku, id_categoria, id_area_preparacion, nombre, descripcion, tipo_producto, precio_venta, costo_unitario, margen_porcentaje, afecto_igv, activo) VALUES
('POL-14',1,1,'Pollo a la brasa 1/4','Incluye papas fritas y ensalada','PLATO',22.00,0,0,1,1),
('POL-12',1,1,'Pollo a la brasa 1/2','Incluye papas fritas y ensalada','PLATO',40.00,0,0,1,1),
('POL-01',1,1,'Pollo a la brasa entero','Incluye papas familiares y ensalada','PLATO',74.00,0,0,1,1),
('BROA',1,1,'Broaster al plato','Presa broaster con papas','PLATO',21.00,0,0,1,1),
('ANTI1',2,1,'Anticuchos x 2','Corazón con papa y choclo','PLATO',18.00,0,0,1,1),
('CHAUF',2,1,'Chaufa de pollo','Arroz chaufa especial','PLATO',20.00,0,0,1,1),
('GAS500',3,2,'Gaseosa 500 ml','Coca Cola/Inca Kola','BEBIDA',5.00,2.50,50.00,1,1),
('CHI1L',3,2,'Chicha morada 1 litro','Bebida de la casa','BEBIDA',12.00,4.20,65.00,1,1),
('PAPEX',4,1,'Papas fritas extra','Porción adicional','PLATO',9.00,0,0,1,1),
('ENSAL',4,1,'Ensalada fresca extra','Porción adicional','PLATO',7.00,0,0,1,1),
('HELAD',5,1,'Helado familiar','Postre helado','PLATO',10.00,4.00,60.00,1,1),
('COMBO1',6,1,'Combo Familiar','1 pollo entero + 1 chicha de litro','COMBO',85.00,0,0,1,1);

INSERT INTO producto_variaciones(id_producto, nombre, descripcion, precio_adicional, activo) VALUES
(1,'Con más papas','Agrega papas fritas extra',3.50,1),
(2,'Sin ensalada','Sin ensalada',0.00,1),
(3,'Bien dorado','Cocción dorada especial',0.00,1);

INSERT INTO producto_extras(id_producto, nombre, precio, activo) VALUES
(1,'Mayonesa extra',1.50,1),
(1,'Ají extra',1.50,1),
(2,'Mayonesa extra',1.50,1),
(3,'Ensalada adicional',4.00,1);

INSERT INTO promociones(nombre, descripcion, tipo_descuento, valor_descuento, fecha_inicio, fecha_fin, activo) VALUES
('Promo Almuerzo','10% en pollos de 12 a 3 pm','PORCENTAJE',10.00,NOW(),DATE_ADD(NOW(), INTERVAL 30 DAY),1);

INSERT INTO promocion_productos(id_promocion, id_producto) VALUES
(1,1),(1,2);

INSERT INTO menus_dia(nombre, descripcion, precio, fecha_inicio, fecha_fin, activo) VALUES
('Menú Ejecutivo','Sopa + fondo + refresco',16.00,CURDATE(),DATE_ADD(CURDATE(), INTERVAL 30 DAY),1);

INSERT INTO menu_detalle(id_menu, id_producto, tipo) VALUES
(1,6,'FONDO'),
(1,8,'BEBIDA');

INSERT INTO categorias_inventario(nombre, descripcion, tipo) VALUES
('Aves','Pollo fresco y derivados','INGREDIENTE'),
('Tubérculos','Papas y similares','INGREDIENTE'),
('Verduras','Vegetales y ensaladas','INGREDIENTE'),
('Salsas','Insumos de salsas','INGREDIENTE'),
('Bebidas embotelladas','Bebidas para venta','ARTICULO'),
('Abarrotes','Arroz, aceite y otros','INGREDIENTE');

INSERT INTO unidades_medida(nombre, abreviatura, factor_base) VALUES
('Kilogramo','kg',1.0000),
('Gramo','g',0.0010),
('Litro','L',1.0000),
('Mililitro','ml',0.0010),
('Unidad','und',1.0000),
('Porción','porc',1.0000);

INSERT INTO ingredientes(sku, nombre, descripcion, id_categoria_inventario, id_unidad_medida, stock_actual, stock_minimo, stock_maximo, costo_unitario, compuesto, activo) VALUES
('ING-POLLO','Pollo fresco','Pollo entero adobado',1,1,80.000,15.000,150.000,13.8000,0,1),
('ING-PAPA','Papa canchán','Papa para fritura',2,1,120.000,20.000,200.000,2.7000,0,1),
('ING-LECH','Lechuga','Lechuga americana',3,5,50.000,8.000,100.000,1.5000,0,1),
('ING-TOMA','Tomate','Tomate ensalada',3,1,25.000,5.000,60.000,4.5000,0,1),
('ING-CEBO','Cebolla','Cebolla roja',3,1,20.000,5.000,50.000,3.8000,0,1),
('ING-MAYO','Mayonesa','Salsa mayonesa',4,1,15.000,3.000,40.000,9.2000,0,1),
('ING-AJI','Ají crema','Salsa de ají',4,1,12.000,3.000,30.000,8.6000,0,1),
('ING-ARRZ','Arroz','Arroz superior',6,1,40.000,8.000,80.000,4.2000,0,1),
('ING-ACEI','Aceite vegetal','Aceite para freír',6,3,35.000,6.000,60.000,8.9000,0,1),
('ING-CHIC','Chicha morada preparada','Bebida de la casa',6,3,25.000,5.000,40.000,4.2000,0,1);

INSERT INTO articulos_inventario(sku, nombre, descripcion, id_categoria_inventario, id_unidad_medida, stock_actual, stock_minimo, costo_unitario, activo) VALUES
('ART-G500','Gaseosa 500 ml','Botella personal',5,5,120.000,20.000,2.5000,1);

INSERT INTO proveedores(tipo_documento, numero_documento, razon_social, contacto, telefono, correo, direccion) VALUES
('RUC','20456789012','Avícola Los Andes SAC','Julio Peña','944111222','ventas@avicolaandes.com','Parque Industrial Ayacucho'),
('RUC','20567890123','Distribuidora Santa Rosa EIRL','Maribel Soto','944333444','contacto@santarosa.com','Av. Independencia 504');

INSERT INTO recetas(id_producto, rendimiento, costo_total, activo) VALUES
(1,1.000,0.0000,1),
(2,1.000,0.0000,1),
(3,1.000,0.0000,1),
(4,1.000,0.0000,1),
(6,1.000,0.0000,1),
(8,1.000,0.0000,1),
(9,1.000,0.0000,1),
(10,1.000,0.0000,1);

INSERT INTO receta_detalle(id_receta, id_ingrediente, cantidad, id_unidad_medida, merma_porcentaje) VALUES
(1,1,0.25,1,0.00),(1,2,0.25,1,0.00),(1,3,0.10,5,0.00),(1,4,0.05,1,0.00),(1,5,0.03,1,0.00),(1,9,0.05,3,0.00),
(2,1,0.50,1,0.00),(2,2,0.45,1,0.00),(2,3,0.15,5,0.00),(2,4,0.08,1,0.00),(2,5,0.05,1,0.00),(2,9,0.09,3,0.00),
(3,1,1.00,1,0.00),(3,2,0.90,1,0.00),(3,3,0.25,5,0.00),(3,4,0.12,1,0.00),(3,5,0.10,1,0.00),(3,9,0.18,3,0.00),
(4,1,0.30,1,0.00),(4,2,0.25,1,0.00),(4,9,0.06,3,0.00),
(5,8,0.20,1,0.00),(5,1,0.15,1,0.00),(5,5,0.02,1,0.00),
(6,10,1.00,3,0.00),
(7,2,0.20,1,0.00),(7,9,0.03,3,0.00),
(8,3,0.15,5,0.00),(8,4,0.05,1,0.00),(8,5,0.03,1,0.00);

INSERT INTO metodos_pago(nombre, requiere_evidencia, activo) VALUES
('EFECTIVO',0,1),
('YAPE',1,1),
('TRANSFERENCIA',1,1),
('TARJETA',0,1),
('PLIN',1,1),
('MIXTO',0,1);

INSERT INTO categorias_gasto(nombre, descripcion, activo) VALUES
('MOVILIDAD','Traslados del personal',1),
('COMPRAS MENORES','Compras operativas',1),
('SERVICIOS','Pagos varios',1);

INSERT INTO tipos_comprobante(codigo, nombre, serie, correlativo_actual, activo) VALUES
('NV','NOTA DE VENTA','NV01',0,1),
('BOL','BOLETA','B001',0,1),
('FAC','FACTURA','F001',0,1);

INSERT INTO kardex_movimientos(tipo_item, id_item, tipo_movimiento, origen, id_referencia, cantidad, saldo_anterior, saldo_nuevo, costo_unitario, costo_total, id_usuario, observacion)
SELECT 'INGREDIENTE', id, 'ENTRADA', 'INICIAL', NULL, stock_actual, 0, stock_actual, costo_unitario, stock_actual * costo_unitario, 1, 'Carga inicial'
FROM ingredientes;

INSERT INTO kardex_movimientos(tipo_item, id_item, tipo_movimiento, origen, id_referencia, cantidad, saldo_anterior, saldo_nuevo, costo_unitario, costo_total, id_usuario, observacion)
SELECT 'ARTICULO', id, 'ENTRADA', 'INICIAL', NULL, stock_actual, 0, stock_actual, costo_unitario, stock_actual * costo_unitario, 1, 'Carga inicial'
FROM articulos_inventario;

-- =========================================================
-- 12. DEMO DE USO
-- =========================================================
-- CALL sp_abrir_turno_caja(1, 3, 200.00, 'Turno mañana');
-- CALL sp_crear_orden(1, 1, 1, 4, 'MESA', 3, 'Mesa familiar');
-- CALL sp_agregar_item_orden(1, 3, 1, 'Bien dorado');
-- CALL sp_agregar_item_orden(1, 7, 2, NULL);
-- CALL sp_aplicar_descuento_orden(1, 3.00);
-- CALL sp_generar_comanda(1, 1, 5);
-- CALL sp_registrar_pago_orden(1, 1, 1, 57.82, 60.00, NULL, 'Pago en efectivo', 3);
-- CALL sp_emitir_comprobante(1, 1, 1, 2, 3);
-- CALL sp_registrar_compra(1, 1, 'FACTURA', 'F001', '000123', 2, 'Compra semanal de insumos');
-- CALL sp_agregar_detalle_compra(1, 'INGREDIENTE', 1, 'Pollo fresco', 20.000, 14.20);
-- CALL sp_agregar_detalle_compra(1, 'INGREDIENTE', 2, 'Papa canchán', 30.000, 2.90);
-- CALL sp_confirmar_compra(1);
-- CALL sp_asignar_delivery(1, 1, 1, 5.00, 'Entrega rápida');
-- CALL sp_marcar_delivery_entregado(1);

SET FOREIGN_KEY_CHECKS = 1;
