-- =====================================================
-- DISEÑO FÍSICO — "RAO MOTOS"  (proyecto EMAIL / Etapa 1)
-- Base de Datos: PostgreSQL
-- Materia: Tecnología Web INF-513 SA — grupo02sa
--
-- Este esquema replica el modelo de la app Laravel (Etapa 2, RAO_MOTOS)
-- adaptado al proyecto de comandos por email.
--   - 4 roles reales (admin, vendedor, almacenero, cliente) vía tabla `rol`.
--   - Sin `tipo_cliente`: el mayoreo se decide por cantidad/línea (RN3/RN19).
--   - Proveedor es solo un dato (no entra al sistema).
--   - Producto con dos precios + umbral de mayoreo propio.
--   - Venta con estado PAGADA + vendedor_id + numero_venta + columnas PagoFácil.
--   - Pedido con flujo aprobar → venta → cobro → despacho.
--   - Tablas de soporte: configuracion, notificacion, bitacora, metodo_pago, producto_imagen.
--
-- Nomenclatura alineada al base (español): `correo` (identidad del remitente, NOT NULL
-- en el medio email), `rol_id`, columnas PagoFácil en español, marcas de tiempo
-- `creado_en` / `actualizado_en` en todas las tablas del dominio.
-- =====================================================

-- Limpieza idempotente (orden inverso de dependencias)
DROP TABLE IF EXISTS bitacora              CASCADE;
DROP TABLE IF EXISTS notificacion          CASCADE;
DROP TABLE IF EXISTS configuracion         CASCADE;
DROP TABLE IF EXISTS detalle_pedido        CASCADE;
DROP TABLE IF EXISTS pedido                CASCADE;
DROP TABLE IF EXISTS pago_cuota            CASCADE;
DROP TABLE IF EXISTS credito               CASCADE;
DROP TABLE IF EXISTS detalle_venta         CASCADE;
DROP TABLE IF EXISTS venta                 CASCADE;
DROP TABLE IF EXISTS metodo_pago           CASCADE;
DROP TABLE IF EXISTS detalle_compra        CASCADE;
DROP TABLE IF EXISTS compra                CASCADE;
DROP TABLE IF EXISTS movimiento_inventario CASCADE;
DROP TABLE IF EXISTS inventario            CASCADE;
DROP TABLE IF EXISTS producto_imagen       CASCADE;
DROP TABLE IF EXISTS producto              CASCADE;
DROP TABLE IF EXISTS proveedor             CASCADE;
DROP TABLE IF EXISTS cliente               CASCADE;
DROP TABLE IF EXISTS usuario               CASCADE;
DROP TABLE IF EXISTS rol                   CASCADE;


-- =====================================================
-- CU1 — ROLES Y USUARIOS
-- =====================================================

CREATE TABLE rol
(
    id             SERIAL PRIMARY KEY,
    nombre         VARCHAR(50) UNIQUE NOT NULL,   -- admin | vendedor | almacenero | cliente
    descripcion    TEXT,
    creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE usuario
(
    id               SERIAL PRIMARY KEY,
    nombre           VARCHAR(100)        NOT NULL,
    apellidos        VARCHAR(100),
    ci               VARCHAR(20) UNIQUE,             -- carnet de identidad personal
    correo           VARCHAR(255) UNIQUE NOT NULL,   -- identidad del remitente (NOT NULL en email)
    telefono         VARCHAR(20),
    direccion        VARCHAR(255),
    foto_url         VARCHAR(500),
    password         VARCHAR(255)        NOT NULL,
    rol_id           INTEGER             NOT NULL,
    estado           BOOLEAN             DEFAULT TRUE,   -- activo/inactivo
    fecha_nacimiento DATE,
    creado_en        TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    actualizado_en   TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_rol
        FOREIGN KEY (rol_id) REFERENCES rol (id) ON DELETE RESTRICT
);

CREATE INDEX idx_usuario_rol    ON usuario (rol_id);
CREATE INDEX idx_usuario_correo ON usuario (correo);
CREATE INDEX idx_usuario_estado ON usuario (estado);

-- Subtabla CLIENTE (1:1 con usuario). Sin tipo_cliente (RN19).
CREATE TABLE cliente
(
    id             INTEGER PRIMARY KEY,
    nit_ci         VARCHAR(20),
    creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cliente_usuario
        FOREIGN KEY (id) REFERENCES usuario (id) ON DELETE CASCADE
);

-- PROVEEDOR: entidad comercial independiente (NO es usuario).
CREATE TABLE proveedor
(
    id                 SERIAL PRIMARY KEY,
    razon_social       VARCHAR(255) NOT NULL,
    contacto_principal VARCHAR(100),
    nit                VARCHAR(20),
    telefono           VARCHAR(20),
    activo             BOOLEAN DEFAULT TRUE,
    creado_en          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_proveedor_activo ON proveedor (activo);


-- =====================================================
-- CU2 — PRODUCTOS  (dos precios + umbral mayorista por producto)
-- =====================================================

CREATE TABLE producto
(
    id                        SERIAL PRIMARY KEY,
    codigo                    VARCHAR(50) UNIQUE NOT NULL,
    nombre                    VARCHAR(200)       NOT NULL,
    marca                     VARCHAR(100),
    modelo                    VARCHAR(100),
    descripcion               TEXT,
    precio_venta_base         DECIMAL(10,2)      NOT NULL CHECK (precio_venta_base > 0),  -- minorista
    precio_mayorista          DECIMAL(10,2)      NOT NULL CHECK (precio_mayorista  > 0),  -- por volumen
    cantidad_minima_mayorista INTEGER            NOT NULL DEFAULT 1 CHECK (cantidad_minima_mayorista >= 1),
    foto_url                  VARCHAR(500),
    activo                    BOOLEAN            DEFAULT TRUE,
    creado_en                 TIMESTAMP          DEFAULT CURRENT_TIMESTAMP,
    actualizado_en            TIMESTAMP          DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_producto_codigo ON producto (codigo);
CREATE INDEX idx_producto_activo ON producto (activo);
CREATE INDEX idx_producto_marca  ON producto (marca);

-- Galería de imágenes (además de la portada foto_url). Uso mínimo por email.
CREATE TABLE producto_imagen
(
    id             SERIAL PRIMARY KEY,
    producto_id    INTEGER      NOT NULL,
    ruta           VARCHAR(255) NOT NULL,
    orden          INTEGER      NOT NULL DEFAULT 0,
    creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_producto_imagen_producto
        FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE CASCADE
);

CREATE INDEX idx_producto_imagen_producto ON producto_imagen (producto_id);


-- =====================================================
-- CU5 — INVENTARIO  (+ stock_minimo)
-- =====================================================

CREATE TABLE inventario
(
    id                  SERIAL PRIMARY KEY,
    producto_id         INTEGER     NOT NULL,
    stock_actual        INTEGER     NOT NULL DEFAULT 0 CHECK (stock_actual >= 0),
    stock_minimo        INTEGER     NOT NULL DEFAULT 0 CHECK (stock_minimo >= 0),
    tecnica_inventario  VARCHAR(20) NOT NULL DEFAULT 'PERMANENTE'
                            CHECK (tecnica_inventario IN ('PERMANENTE', 'PERIODICO')),
    tecnica_costo       VARCHAR(20) NOT NULL DEFAULT 'PROMEDIO'
                            CHECK (tecnica_costo IN ('PEPS', 'UEPS', 'PROMEDIO')),
    fecha_actualizacion TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    creado_en           TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventario_producto
        FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE RESTRICT
);

CREATE INDEX idx_inventario_producto ON inventario (producto_id);

CREATE TABLE movimiento_inventario
(
    id              SERIAL PRIMARY KEY,
    inventario_id   INTEGER     NOT NULL,
    tipo_movimiento VARCHAR(10) NOT NULL CHECK (tipo_movimiento IN ('INGRESO', 'EGRESO')),
    cantidad        INTEGER     NOT NULL CHECK (cantidad > 0),
    motivo          VARCHAR(255),
    fecha           TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    creado_en       TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimiento_inventario
        FOREIGN KEY (inventario_id) REFERENCES inventario (id) ON DELETE RESTRICT
);

CREATE INDEX idx_movimiento_inventario ON movimiento_inventario (inventario_id);
CREATE INDEX idx_movimiento_tipo       ON movimiento_inventario (tipo_movimiento);
CREATE INDEX idx_movimiento_fecha      ON movimiento_inventario (fecha);


-- =====================================================
-- CU3 — COMPRAS
-- =====================================================

CREATE TABLE compra
(
    id             SERIAL PRIMARY KEY,
    proveedor_id   INTEGER       NOT NULL,
    fecha          TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    total          DECIMAL(12,2) NOT NULL DEFAULT 0 CHECK (total >= 0),  -- calculado por el servidor
    estado         VARCHAR(20)   NOT NULL DEFAULT 'PENDIENTE'
                       CHECK (estado IN ('PENDIENTE', 'RECIBIDA', 'ANULADA')),
    creado_en      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_compra_proveedor
        FOREIGN KEY (proveedor_id) REFERENCES proveedor (id) ON DELETE RESTRICT
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
    creado_en       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_detalle_compra_compra
        FOREIGN KEY (compra_id) REFERENCES compra (id) ON DELETE CASCADE,
    CONSTRAINT fk_detalle_compra_producto
        FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE RESTRICT
);

CREATE INDEX idx_detalle_compra_compra   ON detalle_compra (compra_id);
CREATE INDEX idx_detalle_compra_producto ON detalle_compra (producto_id);


-- =====================================================
-- Catálogo de MÉTODOS DE PAGO  (EFECTIVO, QR)
-- =====================================================

CREATE TABLE metodo_pago
(
    id             SERIAL PRIMARY KEY,
    nombre         VARCHAR(50) UNIQUE NOT NULL,
    activo         BOOLEAN DEFAULT TRUE,
    creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =====================================================
-- CU6 — VENTAS  (estado PAGADA + vendedor + numero_venta + PagoFácil)
-- =====================================================

CREATE TABLE venta
(
    id                         SERIAL PRIMARY KEY,
    numero_venta               VARCHAR(30) UNIQUE,
    cliente_id                 INTEGER       NOT NULL,
    vendedor_id                INTEGER,                      -- usuario (vendedor/admin)
    fecha                      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    monto_total                DECIMAL(12,2) NOT NULL CHECK (monto_total > 0),  -- calculado por el servidor
    tipo_venta                 VARCHAR(10)   NOT NULL CHECK (tipo_venta IN ('CONTADO', 'CREDITO')),
    metodo_pago                VARCHAR(20)   NOT NULL CHECK (metodo_pago IN ('EFECTIVO', 'QR')),
    estado                     VARCHAR(20)   NOT NULL DEFAULT 'PENDIENTE'
                                   CHECK (estado IN ('PENDIENTE', 'PAGADA', 'COMPLETADA', 'ANULADA')),
    -- PagoFácil (pago por QR)
    pago_facil_id_transaccion  VARCHAR(100),
    pago_facil_numero_pago     VARCHAR(120),
    pago_facil_imagen_qr       TEXT,
    pago_facil_estado          VARCHAR(50),
    pago_facil_respuesta_cruda TEXT,
    creado_en                  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    actualizado_en             TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_venta_cliente
        FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON DELETE RESTRICT,
    CONSTRAINT fk_venta_vendedor
        FOREIGN KEY (vendedor_id) REFERENCES usuario (id) ON DELETE RESTRICT
);

CREATE INDEX idx_venta_cliente  ON venta (cliente_id);
CREATE INDEX idx_venta_vendedor ON venta (vendedor_id);
CREATE INDEX idx_venta_tipo     ON venta (tipo_venta);
CREATE INDEX idx_venta_fecha    ON venta (fecha);
CREATE INDEX idx_venta_estado   ON venta (estado);

CREATE TABLE detalle_venta
(
    id              SERIAL PRIMARY KEY,
    venta_id        INTEGER       NOT NULL,
    producto_id     INTEGER,                    -- nullable: línea de servicio/mano de obra
    descripcion     VARCHAR(255),
    cantidad        INTEGER       NOT NULL CHECK (cantidad > 0),
    precio_unitario DECIMAL(10,2) NOT NULL CHECK (precio_unitario > 0),
    creado_en       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_detalle_venta_venta
        FOREIGN KEY (venta_id) REFERENCES venta (id) ON DELETE CASCADE,
    CONSTRAINT fk_detalle_venta_producto
        FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE RESTRICT
);

CREATE INDEX idx_detalle_venta_venta    ON detalle_venta (venta_id);
CREATE INDEX idx_detalle_venta_producto ON detalle_venta (producto_id);


-- =====================================================
-- CU7 — CRÉDITOS Y PAGOS
-- =====================================================

CREATE TABLE credito
(
    id              SERIAL PRIMARY KEY,
    venta_id        INTEGER       NOT NULL UNIQUE,
    numero_cuotas   INTEGER       NOT NULL CHECK (numero_cuotas >= 2),
    tasa_interes    DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    saldo_pendiente DECIMAL(12,2) NOT NULL CHECK (saldo_pendiente >= 0),
    estado          VARCHAR(20)   NOT NULL DEFAULT 'VIGENTE'
                        CHECK (estado IN ('VIGENTE', 'PAGADO', 'MOROSO')),
    creado_en       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_credito_venta
        FOREIGN KEY (venta_id) REFERENCES venta (id) ON DELETE RESTRICT
);

CREATE INDEX idx_credito_venta  ON credito (venta_id);
CREATE INDEX idx_credito_estado ON credito (estado);

CREATE TABLE pago_cuota
(
    id                         SERIAL PRIMARY KEY,
    credito_id                 INTEGER       NOT NULL,
    numero_cuota               INTEGER       NOT NULL,
    monto_cuota                DECIMAL(10,2) NOT NULL CHECK (monto_cuota > 0),
    fecha_vencimiento          DATE          NOT NULL,
    fecha_pago                 DATE,
    mora                       DECIMAL(10,2) DEFAULT 0.00,
    estado                     VARCHAR(20)   NOT NULL DEFAULT 'PENDIENTE'
                                   CHECK (estado IN ('PENDIENTE', 'PAGADO', 'VENCIDO')),
    metodo_pago_id             INTEGER,
    -- PagoFácil (pago de cuota por QR)
    pago_facil_id_transaccion  VARCHAR(100),
    pago_facil_numero_pago     VARCHAR(120),
    pago_facil_imagen_qr       TEXT,
    pago_facil_expira_en       TIMESTAMP,
    pago_facil_estado          VARCHAR(50),
    pago_facil_respuesta_cruda TEXT,
    creado_en                  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    actualizado_en             TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pago_cuota_credito
        FOREIGN KEY (credito_id) REFERENCES credito (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pago_cuota_metodo
        FOREIGN KEY (metodo_pago_id) REFERENCES metodo_pago (id) ON DELETE SET NULL
);

CREATE INDEX idx_pago_cuota_credito     ON pago_cuota (credito_id);
CREATE INDEX idx_pago_cuota_estado      ON pago_cuota (estado);
CREATE INDEX idx_pago_cuota_vencimiento ON pago_cuota (fecha_vencimiento);


-- =====================================================
-- CU4 — PEDIDOS  (flujo aprobar → venta → despacho)
-- =====================================================

CREATE TABLE pedido
(
    id             SERIAL PRIMARY KEY,
    cliente_id     INTEGER     NOT NULL,
    fecha          TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    estado         VARCHAR(20) NOT NULL DEFAULT 'SOLICITADO'
                       CHECK (estado IN ('SOLICITADO', 'APROBADO', 'RECHAZADO',
                                         'EN_PROCESO', 'DESPACHADO', 'ANULADO')),
    motivo_rechazo VARCHAR(255),
    venta_id       INTEGER,                     -- venta generada al aprobar
    creado_en      TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedido_cliente
        FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pedido_venta
        FOREIGN KEY (venta_id) REFERENCES venta (id) ON DELETE SET NULL
);

CREATE INDEX idx_pedido_cliente ON pedido (cliente_id);
CREATE INDEX idx_pedido_estado  ON pedido (estado);
CREATE INDEX idx_pedido_fecha   ON pedido (fecha);

CREATE TABLE detalle_pedido
(
    id             SERIAL PRIMARY KEY,
    pedido_id      INTEGER NOT NULL,
    producto_id    INTEGER NOT NULL,
    cantidad       INTEGER NOT NULL CHECK (cantidad > 0),
    creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_detalle_pedido_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedido (id) ON DELETE CASCADE,
    CONSTRAINT fk_detalle_pedido_producto
        FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE RESTRICT
);

CREATE INDEX idx_detalle_pedido_pedido   ON detalle_pedido (pedido_id);
CREATE INDEX idx_detalle_pedido_producto ON detalle_pedido (producto_id);


-- =====================================================
-- SOPORTE — CONFIGURACIÓN, NOTIFICACIONES, BITÁCORA
-- =====================================================

CREATE TABLE configuracion
(
    id             SERIAL PRIMARY KEY,
    clave          VARCHAR(100) UNIQUE NOT NULL,
    valor          VARCHAR(255)        NOT NULL,
    descripcion    VARCHAR(255),
    creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notificaciones: aquí se envían como CORREOS salientes; se guardan para la bandeja.
CREATE TABLE notificacion
(
    id         SERIAL PRIMARY KEY,
    usuario_id INTEGER     NOT NULL,
    tipo       VARCHAR(50) NOT NULL,   -- STOCK_BAJO, PEDIDO_POR_APROBAR, VENTA_PAGADA,
                                       -- PEDIDO_APROBADO, PEDIDO_RECHAZADO, PEDIDO_DESPACHADO, MORA
    mensaje    VARCHAR(255) NOT NULL,
    recurso    VARCHAR(255),
    leido      BOOLEAN     DEFAULT FALSE,
    fecha      TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificacion_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE CASCADE
);

CREATE INDEX idx_notificacion_usuario ON notificacion (usuario_id);
CREATE INDEX idx_notificacion_leido   ON notificacion (leido);

CREATE TABLE bitacora
(
    id             SERIAL PRIMARY KEY,
    usuario_id     INTEGER,
    correo         VARCHAR(255),
    accion         VARCHAR(20) NOT NULL CHECK (accion IN ('LOGIN_OK', 'LOGIN_FAIL', 'ACCESO_RECURSO')),
    recurso        VARCHAR(255),
    ip             VARCHAR(45),
    agente_usuario VARCHAR(255),
    fecha          TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bitacora_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE SET NULL
);

CREATE INDEX idx_bitacora_usuario ON bitacora (usuario_id);
CREATE INDEX idx_bitacora_accion  ON bitacora (accion);
CREATE INDEX idx_bitacora_fecha   ON bitacora (fecha);
