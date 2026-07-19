-- ============================================================================
--  RAO MOTOS — Esquema de base de datos (modelo de negocio)
--  Tienda de repuestos de motocicletas: ventas al contado y a crédito (cuotas),
--  pago electrónico por QR (PagoFácil).
--  Motor: PostgreSQL 18
--
--  Nota: NO incluye las tablas propias de la infraestructura de Laravel
--  (sessions, failed_jobs, password_reset_tokens, personal_access_tokens),
--  ya que no forman parte del modelo de negocio.
-- ============================================================================

-- Búsquedas que ignoran tildes: unaccent('Créditos') = 'Creditos'
CREATE EXTENSION IF NOT EXISTS unaccent;

-- ============================================================================
--  SEGURIDAD Y USUARIOS
-- ============================================================================

-- Roles del sistema: admin, vendedor, almacenero, cliente
CREATE TABLE rol (
    id              BIGSERIAL   PRIMARY KEY,
    nombre          VARCHAR(50) NOT NULL,
    descripcion     TEXT,
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP
);

-- Usuarios del sistema (un rol por usuario)
CREATE TABLE usuario (
    id                          BIGSERIAL     PRIMARY KEY,
    nombre                      VARCHAR(255)  NOT NULL,
    apellidos                   VARCHAR(255)  NOT NULL,
    ci                          VARCHAR(255)  NOT NULL UNIQUE,   -- carnet de identidad personal
    telefono                    VARCHAR(15)   NOT NULL,
    direccion                   VARCHAR(255),
    correo                      VARCHAR(255)  UNIQUE,            -- nullable solo para clientes
    correo_verificado_en        TIMESTAMP,
    password                    VARCHAR(255)  NOT NULL,
    two_factor_secret           TEXT,
    two_factor_recovery_codes   TEXT,
    two_factor_confirmed_at     TIMESTAMP,
    token_recordar              VARCHAR(100),
    current_team_id             BIGINT,
    profile_photo_path          VARCHAR(2048),
    estado                      BOOLEAN       NOT NULL DEFAULT TRUE,  -- activo/inactivo
    fecha_nacimiento            DATE,
    rol_id                      BIGINT,
    creado_en                   TIMESTAMP,
    actualizado_en              TIMESTAMP,
    CONSTRAINT usuario_rol_id_foreign
        FOREIGN KEY (rol_id) REFERENCES rol (id) ON DELETE RESTRICT
);

-- Cliente: subtabla 1:1 de usuario (PK = usuario.id). Único dato propio: NIT de facturación
CREATE TABLE cliente (
    id              BIGINT      PRIMARY KEY,
    nit_ci          VARCHAR(20),
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP,
    CONSTRAINT cliente_id_foreign
        FOREIGN KEY (id) REFERENCES usuario (id) ON DELETE CASCADE
);

-- ============================================================================
--  CONFIGURACIÓN Y MENÚ (transversales)
-- ============================================================================

-- Parámetros configurables del sistema (cada uno con su valor por defecto)
CREATE TABLE configuracion (
    id              BIGSERIAL     PRIMARY KEY,
    clave           VARCHAR(100)  NOT NULL UNIQUE,
    valor           VARCHAR(255)  NOT NULL,
    descripcion     VARCHAR(255),
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP
);

-- Menú dinámico por rol
CREATE TABLE item_menu (
    id              BIGSERIAL     PRIMARY KEY,
    etiqueta        VARCHAR(255)  NOT NULL,
    ruta_laravel    VARCHAR(255)  NOT NULL,
    icono           VARCHAR(255),
    orden           INTEGER       NOT NULL DEFAULT 0,
    rol_id          BIGINT        NOT NULL,
    padre_id        BIGINT,
    activo          BOOLEAN       NOT NULL DEFAULT TRUE,
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP,
    CONSTRAINT item_menu_rol_id_foreign
        FOREIGN KEY (rol_id) REFERENCES rol (id) ON DELETE CASCADE,
    CONSTRAINT item_menu_padre_id_foreign
        FOREIGN KEY (padre_id) REFERENCES item_menu (id) ON DELETE CASCADE
);

-- Métodos de pago: EFECTIVO, QR
CREATE TABLE metodo_pago (
    id              BIGSERIAL     PRIMARY KEY,
    nombre          VARCHAR(50)   NOT NULL UNIQUE,
    activo          BOOLEAN       NOT NULL DEFAULT TRUE,
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP
);

-- ============================================================================
--  PROVEEDORES, PRODUCTOS E INVENTARIO
-- ============================================================================

-- Proveedor: entidad comercial (no es usuario del sistema)
CREATE TABLE proveedor (
    id                  BIGSERIAL     PRIMARY KEY,
    razon_social        VARCHAR(255)  NOT NULL,
    contacto_principal  VARCHAR(255),
    nit                 VARCHAR(20),
    telefono            VARCHAR(20),
    activo              BOOLEAN       NOT NULL DEFAULT TRUE,
    creado_en           TIMESTAMP,
    actualizado_en      TIMESTAMP
);

-- Producto (repuesto de motocicleta)
CREATE TABLE producto (
    id                          BIGSERIAL       PRIMARY KEY,
    codigo                      VARCHAR(50)     NOT NULL UNIQUE,
    nombre                      VARCHAR(200)    NOT NULL,
    marca                       VARCHAR(100),
    modelo                      VARCHAR(100),
    descripcion                 TEXT,
    precio_venta_base           NUMERIC(10,2)   NOT NULL,           -- minorista
    precio_mayorista            NUMERIC(10,2)   NOT NULL,           -- por volumen
    cantidad_minima_mayorista   INTEGER         NOT NULL DEFAULT 1, -- umbral mayoreo por producto
    foto_url                    VARCHAR(500),
    activo                      BOOLEAN         NOT NULL DEFAULT TRUE,
    creado_en                   TIMESTAMP,
    actualizado_en              TIMESTAMP
);

-- Galería de imágenes del producto (carrusel)
CREATE TABLE producto_imagen (
    id              BIGSERIAL     PRIMARY KEY,
    producto_id     BIGINT        NOT NULL,
    ruta            VARCHAR(255)  NOT NULL,
    orden           INTEGER       NOT NULL DEFAULT 0,
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP,
    CONSTRAINT producto_imagen_producto_id_foreign
        FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE CASCADE
);

-- Inventario por producto
CREATE TABLE inventario (
    id                  BIGSERIAL     PRIMARY KEY,
    producto_id         BIGINT        NOT NULL,
    stock_actual        INTEGER       NOT NULL DEFAULT 0,
    stock_minimo        INTEGER       NOT NULL DEFAULT 0,
    tecnica_inventario  VARCHAR(255)  NOT NULL DEFAULT 'PERMANENTE',
    tecnica_costo       VARCHAR(255)  NOT NULL DEFAULT 'PROMEDIO',
    fecha_actualizacion TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    creado_en           TIMESTAMP,
    actualizado_en      TIMESTAMP,
    CONSTRAINT inventario_producto_id_foreign
        FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE RESTRICT,
    CONSTRAINT inventario_tecnica_inventario_check
        CHECK (tecnica_inventario IN ('PERMANENTE', 'PERIODICO')),
    CONSTRAINT inventario_tecnica_costo_check
        CHECK (tecnica_costo IN ('PEPS', 'UEPS', 'PROMEDIO'))
);

-- Movimientos de inventario (kardex: ingresos/egresos)
CREATE TABLE movimiento_inventario (
    id              BIGSERIAL     PRIMARY KEY,
    inventario_id   BIGINT        NOT NULL,
    tipo_movimiento VARCHAR(255)  NOT NULL,
    cantidad        INTEGER       NOT NULL,
    motivo          VARCHAR(255),
    fecha           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP,
    CONSTRAINT movimiento_inventario_inventario_id_foreign
        FOREIGN KEY (inventario_id) REFERENCES inventario (id) ON DELETE RESTRICT,
    CONSTRAINT movimiento_inventario_tipo_movimiento_check
        CHECK (tipo_movimiento IN ('INGRESO', 'EGRESO'))
);

-- ============================================================================
--  COMPRAS (a proveedores)
-- ============================================================================

CREATE TABLE compra (
    id              BIGSERIAL     PRIMARY KEY,
    proveedor_id    BIGINT        NOT NULL,
    fecha           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total           NUMERIC(12,2) NOT NULL DEFAULT 0,   -- calculado por el servidor desde el detalle
    estado          VARCHAR(255)  NOT NULL DEFAULT 'PENDIENTE',
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP,
    CONSTRAINT compra_proveedor_id_foreign
        FOREIGN KEY (proveedor_id) REFERENCES proveedor (id) ON DELETE RESTRICT,
    CONSTRAINT compra_estado_check
        CHECK (estado IN ('PENDIENTE', 'RECIBIDA', 'ANULADA'))
);

CREATE TABLE detalle_compra (
    id              BIGSERIAL     PRIMARY KEY,
    compra_id       BIGINT        NOT NULL,
    producto_id     BIGINT        NOT NULL,
    cantidad        INTEGER       NOT NULL,
    precio_unitario NUMERIC(10,2) NOT NULL,
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP,
    CONSTRAINT detalle_compra_compra_id_foreign
        FOREIGN KEY (compra_id) REFERENCES compra (id) ON DELETE CASCADE,
    CONSTRAINT detalle_compra_producto_id_foreign
        FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE RESTRICT
);

-- ============================================================================
--  VENTAS (al contado y a crédito)
-- ============================================================================

CREATE TABLE venta (
    id                          BIGSERIAL     PRIMARY KEY,
    numero_venta                VARCHAR(30)   UNIQUE,
    cliente_id                  BIGINT        NOT NULL,
    vendedor_id                 BIGINT,                             -- usuario.id (vendedor/admin)
    fecha                       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monto_total                 NUMERIC(12,2) NOT NULL,             -- calculado por el servidor desde el detalle
    tipo_venta                  VARCHAR(255)  NOT NULL,
    metodo_pago                 VARCHAR(255)  NOT NULL,
    estado                      VARCHAR(255)  NOT NULL DEFAULT 'PENDIENTE',
    -- PagoFácil (pago por QR)
    pago_facil_id_transaccion   VARCHAR(100),
    pago_facil_numero_pago      VARCHAR(120),
    pago_facil_imagen_qr        TEXT,
    pago_facil_estado           VARCHAR(50),                        -- pending, completed, failed
    pago_facil_respuesta_cruda  TEXT,
    creado_en                   TIMESTAMP,
    actualizado_en              TIMESTAMP,
    CONSTRAINT venta_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON DELETE RESTRICT,
    CONSTRAINT venta_vendedor_id_foreign
        FOREIGN KEY (vendedor_id) REFERENCES usuario (id) ON DELETE RESTRICT,
    CONSTRAINT venta_tipo_venta_check
        CHECK (tipo_venta IN ('CONTADO', 'CREDITO')),
    CONSTRAINT venta_metodo_pago_check
        CHECK (metodo_pago IN ('EFECTIVO', 'QR')),
    CONSTRAINT venta_estado_check
        CHECK (estado IN ('COMPLETADA', 'PENDIENTE', 'PAGADA', 'ANULADA'))
);

CREATE TABLE detalle_venta (
    id              BIGSERIAL     PRIMARY KEY,
    venta_id        BIGINT        NOT NULL,
    producto_id     BIGINT,
    descripcion     VARCHAR(255),                       -- texto opcional de la línea
    cantidad        INTEGER       NOT NULL,
    precio_unitario NUMERIC(10,2) NOT NULL,
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP,
    CONSTRAINT detalle_venta_venta_id_foreign
        FOREIGN KEY (venta_id) REFERENCES venta (id) ON DELETE CASCADE,
    CONSTRAINT detalle_venta_producto_id_foreign
        FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE RESTRICT
);

-- ============================================================================
--  CRÉDITOS Y CUOTAS (ventas a crédito)
-- ============================================================================

CREATE TABLE credito (
    id              BIGSERIAL     PRIMARY KEY,
    venta_id        BIGINT        NOT NULL UNIQUE,
    numero_cuotas   INTEGER       NOT NULL,             -- >= 2 (validado en negocio)
    tasa_interes    NUMERIC(5,2)  NOT NULL DEFAULT 0,
    saldo_pendiente NUMERIC(12,2) NOT NULL,
    estado          VARCHAR(255)  NOT NULL DEFAULT 'VIGENTE',
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP,
    CONSTRAINT credito_venta_id_foreign
        FOREIGN KEY (venta_id) REFERENCES venta (id) ON DELETE RESTRICT,
    CONSTRAINT credito_estado_check
        CHECK (estado IN ('VIGENTE', 'PAGADO', 'MOROSO'))
);

CREATE TABLE pago_cuota (
    id                          BIGSERIAL     PRIMARY KEY,
    credito_id                  BIGINT        NOT NULL,
    numero_cuota                INTEGER       NOT NULL,
    monto_cuota                 NUMERIC(10,2) NOT NULL,
    fecha_vencimiento           DATE          NOT NULL,
    fecha_pago                  DATE,
    mora                        NUMERIC(10,2) NOT NULL DEFAULT 0,
    estado                      VARCHAR(255)  NOT NULL DEFAULT 'PENDIENTE',
    -- PagoFácil (pago de cuota por QR)
    metodo_pago_id              BIGINT,
    pago_facil_id_transaccion   VARCHAR(100),
    pago_facil_numero_pago      VARCHAR(120),
    pago_facil_imagen_qr        TEXT,
    pago_facil_expira_en        TIMESTAMP,
    pago_facil_estado           VARCHAR(50),
    pago_facil_respuesta_cruda  TEXT,
    creado_en                   TIMESTAMP,
    actualizado_en              TIMESTAMP,
    CONSTRAINT pago_cuota_credito_id_foreign
        FOREIGN KEY (credito_id) REFERENCES credito (id) ON DELETE RESTRICT,
    CONSTRAINT pago_cuota_metodo_pago_id_foreign
        FOREIGN KEY (metodo_pago_id) REFERENCES metodo_pago (id) ON DELETE SET NULL,
    CONSTRAINT pago_cuota_estado_check
        CHECK (estado IN ('PENDIENTE', 'PAGADO', 'VENCIDO'))
);

-- ============================================================================
--  PEDIDOS (catálogo del cliente → venta → despacho)
-- ============================================================================

CREATE TABLE pedido (
    id              BIGSERIAL     PRIMARY KEY,
    cliente_id      BIGINT        NOT NULL,
    fecha           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado          VARCHAR(255)  NOT NULL DEFAULT 'SOLICITADO',
    motivo_rechazo  VARCHAR(255),
    venta_id        BIGINT,                             -- venta generada al aprobar
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP,
    CONSTRAINT pedido_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON DELETE RESTRICT,
    CONSTRAINT pedido_venta_id_foreign
        FOREIGN KEY (venta_id) REFERENCES venta (id) ON DELETE SET NULL,
    CONSTRAINT pedido_estado_check
        CHECK (estado IN ('SOLICITADO', 'APROBADO', 'RECHAZADO', 'EN_PROCESO', 'DESPACHADO', 'ANULADO'))
);

CREATE TABLE detalle_pedido (
    id              BIGSERIAL     PRIMARY KEY,
    pedido_id       BIGINT        NOT NULL,
    producto_id     BIGINT        NOT NULL,
    cantidad        INTEGER       NOT NULL,
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP,
    CONSTRAINT detalle_pedido_pedido_id_foreign
        FOREIGN KEY (pedido_id) REFERENCES pedido (id) ON DELETE CASCADE,
    CONSTRAINT detalle_pedido_producto_id_foreign
        FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE RESTRICT
);

-- ============================================================================
--  TRANSVERSALES: BITÁCORA, VISITAS Y NOTIFICACIONES
-- ============================================================================

-- Bitácora de accesos (LOGIN_OK / LOGIN_FAIL / ACCESO_RECURSO)
CREATE TABLE bitacora (
    id              BIGSERIAL     PRIMARY KEY,
    usuario_id      BIGINT,
    correo          VARCHAR(255),
    accion          VARCHAR(255)  NOT NULL,
    recurso         VARCHAR(255),
    ip              VARCHAR(45),
    agente_usuario  VARCHAR(255),
    fecha           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT bitacora_usuario_id_foreign
        FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE SET NULL,
    CONSTRAINT bitacora_accion_check
        CHECK (accion IN ('LOGIN_OK', 'LOGIN_FAIL', 'ACCESO_RECURSO'))
);

-- Contador de visitas por ruta (footer)
CREATE TABLE visita_pagina (
    id              BIGSERIAL     PRIMARY KEY,
    ruta            VARCHAR(255)  NOT NULL UNIQUE,
    contador        BIGINT        NOT NULL DEFAULT 0,
    creado_en       TIMESTAMP,
    actualizado_en  TIMESTAMP
);

-- Notificaciones in-app (badge en el navbar)
-- tipos: STOCK_BAJO, PEDIDO_POR_APROBAR, VENTA_PAGADA, PEDIDO_APROBADO,
--        PEDIDO_RECHAZADO, PEDIDO_DESPACHADO, MORA
CREATE TABLE notificacion (
    id              BIGSERIAL     PRIMARY KEY,
    usuario_id      BIGINT        NOT NULL,
    tipo            VARCHAR(50)   NOT NULL,
    mensaje         VARCHAR(255)  NOT NULL,
    recurso         VARCHAR(255),
    leido           BOOLEAN       NOT NULL DEFAULT FALSE,
    fecha           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT notificacion_usuario_id_foreign
        FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE CASCADE
);
