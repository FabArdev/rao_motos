-- =====================================================
-- DISEÑO FÍSICO - SISTEMA DE VENTAS AL CRÉDITO
-- "RAO MOTOS"
-- Base de Datos: PostgreSQL
-- Materia: Tecnología Web INF-513 SA
-- =====================================================

-- =====================================================
-- CU1 - GESTIÓN DE USUARIOS
-- (Propietario, Proveedor, Cliente)
-- =====================================================

CREATE TABLE usuario
(
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(100)        NOT NULL,
    email       VARCHAR(255) UNIQUE NOT NULL,
    telefono    VARCHAR(20),
    direccion   VARCHAR(255),
    foto_url    VARCHAR(500),
    password    VARCHAR(255)        NOT NULL,
    rol         VARCHAR(20)         NOT NULL
                    CHECK (rol IN ('PROPIETARIO', 'CLIENTE')),
    activo      BOOLEAN             DEFAULT TRUE,
    fecha_reg   TIMESTAMP           DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_usuario_rol    ON usuario (rol);
CREATE INDEX idx_usuario_email  ON usuario (email);
CREATE INDEX idx_usuario_activo ON usuario (activo);

-- -------------------------------------------------------
-- Subtabla: CLIENTE
-- -------------------------------------------------------
CREATE TABLE cliente
(
    id            INTEGER PRIMARY KEY,
    nit_ci        VARCHAR(20) NOT NULL,
    tipo_cliente  VARCHAR(20) NOT NULL
                      CHECK (tipo_cliente IN ('REGULAR', 'FRECUENTE', 'MAYORISTA')),
    CONSTRAINT fk_cliente_usuario
        FOREIGN KEY (id) REFERENCES usuario (id)
        ON DELETE CASCADE
);

-- -------------------------------------------------------
-- PROVEEDOR (entidad comercial independiente, sin cuenta de usuario)
-- -------------------------------------------------------
CREATE TABLE proveedor
(
    id                  SERIAL PRIMARY KEY,
    razon_social        VARCHAR(255) NOT NULL,
    contacto_principal  VARCHAR(100),
    telefono            VARCHAR(20),
    activo              BOOLEAN DEFAULT TRUE
);

CREATE INDEX idx_proveedor_activo ON proveedor (activo);

-- -------------------------------------------------------
-- Subtabla: PROPIETARIO
-- -------------------------------------------------------
CREATE TABLE propietario
(
    id            INTEGER PRIMARY KEY,
    nivel_acceso  VARCHAR(20) NOT NULL DEFAULT 'TOTAL',
    CONSTRAINT fk_propietario_usuario
        FOREIGN KEY (id) REFERENCES usuario (id)
        ON DELETE CASCADE
);


-- =====================================================
-- CU2 - GESTIÓN DE PRODUCTOS
-- =====================================================

CREATE TABLE producto
(
    id                  SERIAL PRIMARY KEY,
    codigo              VARCHAR(50) UNIQUE NOT NULL,
    nombre              VARCHAR(200)       NOT NULL,
    marca               VARCHAR(100),
    modelo              VARCHAR(100),
    descripcion         TEXT,
    precio_venta_base   DECIMAL(10,2)      NOT NULL CHECK (precio_venta_base > 0),
    foto_url            VARCHAR(500),
    activo              BOOLEAN            DEFAULT TRUE,
    fecha_reg           TIMESTAMP          DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_producto_codigo  ON producto (codigo);
CREATE INDEX idx_producto_activo  ON producto (activo);
CREATE INDEX idx_producto_marca   ON producto (marca);


-- =====================================================
-- CU5 - GESTIÓN DE INVENTARIO
-- =====================================================

CREATE TABLE inventario
(
    id                  SERIAL PRIMARY KEY,
    producto_id         INTEGER        NOT NULL,
    stock_actual        INTEGER        NOT NULL DEFAULT 0 CHECK (stock_actual >= 0),
    tecnica_inventario  VARCHAR(20)    NOT NULL
                            CHECK (tecnica_inventario IN ('PERMANENTE', 'PERIODICO')),
    tecnica_costo       VARCHAR(20)    NOT NULL
                            CHECK (tecnica_costo IN ('PEPS', 'UEPS', 'PROMEDIO')),
    fecha_actualizacion TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventario_producto
        FOREIGN KEY (producto_id) REFERENCES producto (id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_inventario_producto ON inventario (producto_id);

-- -------------------------------------------------------
-- Movimientos de Inventario (Ingresos y Egresos)
-- -------------------------------------------------------
CREATE TABLE movimiento_inventario
(
    id               SERIAL PRIMARY KEY,
    inventario_id    INTEGER      NOT NULL,
    tipo_movimiento  VARCHAR(10)  NOT NULL
                         CHECK (tipo_movimiento IN ('INGRESO', 'EGRESO')),
    cantidad         INTEGER      NOT NULL CHECK (cantidad > 0),
    motivo           VARCHAR(255),
    fecha            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimiento_inventario
        FOREIGN KEY (inventario_id) REFERENCES inventario (id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_movimiento_inventario ON movimiento_inventario (inventario_id);
CREATE INDEX idx_movimiento_tipo       ON movimiento_inventario (tipo_movimiento);
CREATE INDEX idx_movimiento_fecha      ON movimiento_inventario (fecha);


-- =====================================================
-- CU3 - GESTIÓN DE COMPRAS
-- =====================================================

CREATE TABLE compra
(
    id           SERIAL PRIMARY KEY,
    proveedor_id INTEGER        NOT NULL,
    fecha        TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    total        DECIMAL(12,2)  NOT NULL CHECK (total > 0),
    estado       VARCHAR(20)    NOT NULL
                     CHECK (estado IN ('PENDIENTE', 'RECIBIDA', 'ANULADA')),
    CONSTRAINT fk_compra_proveedor
        FOREIGN KEY (proveedor_id) REFERENCES proveedor (id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_compra_proveedor ON compra (proveedor_id);
CREATE INDEX idx_compra_fecha     ON compra (fecha);
CREATE INDEX idx_compra_estado    ON compra (estado);

CREATE TABLE detalle_compra
(
    id              SERIAL PRIMARY KEY,
    compra_id       INTEGER       NOT NULL,
    producto_id     INTEGER       NOT NULL,
    cantidad        INTEGER       NOT NULL CHECK (cantidad > 0),
    precio_unitario DECIMAL(10,2) NOT NULL CHECK (precio_unitario > 0),
    CONSTRAINT fk_detalle_compra_compra
        FOREIGN KEY (compra_id) REFERENCES compra (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_detalle_compra_producto
        FOREIGN KEY (producto_id) REFERENCES producto (id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_detalle_compra_compra   ON detalle_compra (compra_id);
CREATE INDEX idx_detalle_compra_producto ON detalle_compra (producto_id);


-- =====================================================
-- CU4 - GESTIÓN DE PEDIDOS
-- =====================================================

CREATE TABLE pedido
(
    id          SERIAL PRIMARY KEY,
    cliente_id  INTEGER     NOT NULL,
    fecha       TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    estado      VARCHAR(20) NOT NULL
                    CHECK (estado IN ('SOLICITADO', 'EN_PROCESO', 'DESPACHADO', 'ANULADO')),
    CONSTRAINT fk_pedido_cliente
        FOREIGN KEY (cliente_id) REFERENCES cliente (id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_pedido_cliente ON pedido (cliente_id);
CREATE INDEX idx_pedido_estado  ON pedido (estado);
CREATE INDEX idx_pedido_fecha   ON pedido (fecha);

CREATE TABLE detalle_pedido
(
    id          SERIAL PRIMARY KEY,
    pedido_id   INTEGER NOT NULL,
    producto_id INTEGER NOT NULL,
    cantidad    INTEGER NOT NULL CHECK (cantidad > 0),
    CONSTRAINT fk_detalle_pedido_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedido (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_detalle_pedido_producto
        FOREIGN KEY (producto_id) REFERENCES producto (id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_detalle_pedido_pedido   ON detalle_pedido (pedido_id);
CREATE INDEX idx_detalle_pedido_producto ON detalle_pedido (producto_id);


-- =====================================================
-- CU6 - GESTIÓN DE VENTAS
-- (Contado, Crédito, QR, Tarjeta)
-- =====================================================

CREATE TABLE venta
(
    id           SERIAL PRIMARY KEY,
    cliente_id   INTEGER        NOT NULL,
    fecha        TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    monto_total  DECIMAL(12,2)  NOT NULL CHECK (monto_total > 0),
    tipo_venta   VARCHAR(10)    NOT NULL
                     CHECK (tipo_venta IN ('CONTADO', 'CREDITO')),
    metodo_pago  VARCHAR(20)    NOT NULL
                     CHECK (metodo_pago IN ('EFECTIVO', 'QR', 'TARJETA')),
    estado       VARCHAR(20)    NOT NULL
                     CHECK (estado IN ('COMPLETADA', 'PENDIENTE', 'ANULADA'))
                     DEFAULT 'PENDIENTE',
    CONSTRAINT fk_venta_cliente
        FOREIGN KEY (cliente_id) REFERENCES cliente (id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_venta_cliente     ON venta (cliente_id);
CREATE INDEX idx_venta_tipo        ON venta (tipo_venta);
CREATE INDEX idx_venta_fecha       ON venta (fecha);
CREATE INDEX idx_venta_estado      ON venta (estado);

CREATE TABLE detalle_venta
(
    id              SERIAL PRIMARY KEY,
    venta_id        INTEGER       NOT NULL,
    producto_id     INTEGER       NOT NULL,
    cantidad        INTEGER       NOT NULL CHECK (cantidad > 0),
    precio_unitario DECIMAL(10,2) NOT NULL CHECK (precio_unitario > 0),
    CONSTRAINT fk_detalle_venta_venta
        FOREIGN KEY (venta_id) REFERENCES venta (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_detalle_venta_producto
        FOREIGN KEY (producto_id) REFERENCES producto (id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_detalle_venta_venta    ON detalle_venta (venta_id);
CREATE INDEX idx_detalle_venta_producto ON detalle_venta (producto_id);


-- =====================================================
-- CU7 - GESTIÓN DE PAGOS
-- (Créditos, Moras, Interés)
-- =====================================================

CREATE TABLE credito
(
    id               SERIAL PRIMARY KEY,
    venta_id         INTEGER        NOT NULL UNIQUE,
    numero_cuotas    INTEGER        NOT NULL CHECK (numero_cuotas >= 2),
    tasa_interes     DECIMAL(5,2)   NOT NULL DEFAULT 0.00,
    saldo_pendiente  DECIMAL(12,2)  NOT NULL CHECK (saldo_pendiente >= 0),
    estado           VARCHAR(20)    NOT NULL
                         CHECK (estado IN ('VIGENTE', 'PAGADO', 'MOROSO'))
                         DEFAULT 'VIGENTE',
    CONSTRAINT fk_credito_venta
        FOREIGN KEY (venta_id) REFERENCES venta (id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_credito_venta  ON credito (venta_id);
CREATE INDEX idx_credito_estado ON credito (estado);

CREATE TABLE pago_cuota
(
    id               SERIAL PRIMARY KEY,
    credito_id       INTEGER       NOT NULL,
    numero_cuota     INTEGER       NOT NULL,
    monto_cuota      DECIMAL(10,2) NOT NULL CHECK (monto_cuota > 0),
    fecha_vencimiento DATE          NOT NULL,
    fecha_pago       DATE,
    mora             DECIMAL(10,2) DEFAULT 0.00,
    estado           VARCHAR(20)   NOT NULL
                         CHECK (estado IN ('PENDIENTE', 'PAGADO', 'VENCIDO'))
                         DEFAULT 'PENDIENTE',
    CONSTRAINT fk_pago_cuota_credito
        FOREIGN KEY (credito_id) REFERENCES credito (id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_pago_cuota_credito    ON pago_cuota (credito_id);
CREATE INDEX idx_pago_cuota_estado     ON pago_cuota (estado);
CREATE INDEX idx_pago_cuota_vencimiento ON pago_cuota (fecha_vencimiento);
