-- =====================================================
-- DATOS DE PRUEBA - RAO MOTOS
-- Repuestos y accesorios para motocicletas
-- Ejecutar LUEGO de database_schema.sql
-- psql -h mail.tecnoweb.org.bo -U grupo02sa -d db_grupo02sa -f seed_data.sql
-- =====================================================

BEGIN;

-- =====================================================
-- LIMPIEZA (orden inverso a FK)
-- =====================================================
DELETE FROM pago_cuota;
DELETE FROM credito;
DELETE FROM detalle_venta;
DELETE FROM venta;
DELETE FROM detalle_pedido;
DELETE FROM pedido;
DELETE FROM detalle_compra;
DELETE FROM compra;
DELETE FROM movimiento_inventario;
DELETE FROM inventario;
DELETE FROM producto;
DELETE FROM propietario;
DELETE FROM proveedor;
DELETE FROM cliente;
DELETE FROM usuario;

ALTER SEQUENCE usuario_id_seq              RESTART WITH 1;
ALTER SEQUENCE proveedor_id_seq            RESTART WITH 1;
ALTER SEQUENCE producto_id_seq             RESTART WITH 1;
ALTER SEQUENCE inventario_id_seq           RESTART WITH 1;
ALTER SEQUENCE movimiento_inventario_id_seq RESTART WITH 1;
ALTER SEQUENCE compra_id_seq               RESTART WITH 1;
ALTER SEQUENCE detalle_compra_id_seq       RESTART WITH 1;
ALTER SEQUENCE pedido_id_seq               RESTART WITH 1;
ALTER SEQUENCE detalle_pedido_id_seq       RESTART WITH 1;
ALTER SEQUENCE venta_id_seq                RESTART WITH 1;
ALTER SEQUENCE detalle_venta_id_seq        RESTART WITH 1;
ALTER SEQUENCE credito_id_seq              RESTART WITH 1;
ALTER SEQUENCE pago_cuota_id_seq           RESTART WITH 1;

-- =====================================================
-- CU1 - USUARIOS
-- IDs 1-3: propietarios | IDs 6-10: clientes
-- (IDs 4-5 reservados para registros históricos)
-- =====================================================
INSERT INTO usuario (id, nombre, email, telefono, direccion, password, rol, activo) VALUES
(1,  'Carlos Diego Marca Peñaranda',    'marcacarlosestudio@gmail.com',        '77123401', 'Av. America 123, Cochabamba',   'admin123', 'PROPIETARIO', TRUE),
(2,  'Arnez Fernández Fabio Alejandro', 'fabioarnez200@gmail.com',         '77123402', 'Calle Punata 456, Cochabamba',  'admin123', 'PROPIETARIO', TRUE),
(3,  'Reymar Loaiza Labarden',          'loaizalabardenreymar@gmail.com',        '77123403', 'Av. Ayacucho 789, Cochabamba',  'admin123', 'PROPIETARIO', TRUE),
(6,  'Juan Perez Mamani',               'juan.perez@email.com',      '72123401', 'Calle Lanza 100, Cochabamba',   'cli1234',  'CLIENTE', TRUE),
(7,  'Maria Flores Quispe',             'maria.flores@email.com',    '72123402', 'Av. Heroinas 200, Cochabamba',  'cli1234',  'CLIENTE', TRUE),
(8,  'Pedro Gutierrez Soliz',           'pedro.gutierrez@email.com', '72123403', 'Calle Bolivar 300, Cochabamba', 'cli1234',  'CLIENTE', TRUE),
(9,  'Ana Rodriguez Lopez',             'ana.rodriguez@email.com',   '72123404', 'Av. Oquendo 400, Cochabamba',   'cli1234',  'CLIENTE', TRUE),
(10, 'Luis Vargas Rojas',               'luis.vargas@email.com',     '72123405', 'Calle Espana 500, Cochabamba',  'cli1234',  'CLIENTE', TRUE);

INSERT INTO propietario (id, nivel_acceso) VALUES
(1, 'TOTAL'),
(2, 'TOTAL'),
(3, 'TOTAL');

INSERT INTO proveedor (razon_social, contacto_principal, telefono, activo) VALUES
('Distribuidora Japonesa Ltda.', 'Tanaka Suzuki', '44123401', TRUE),
('Importadora China del Sur',    'Li Wei',         '44123402', TRUE);

INSERT INTO cliente (id, nit_ci, tipo_cliente) VALUES
(6,  '12345601', 'REGULAR'),
(7,  '12345602', 'FRECUENTE'),
(8,  '12345603', 'MAYORISTA'),
(9,  '12345604', 'REGULAR'),
(10, '12345605', 'FRECUENTE');

-- =====================================================
-- CU2 - PRODUCTOS (15 repuestos de moto)
-- =====================================================
INSERT INTO producto (id, codigo, nombre, marca, modelo, descripcion, precio_venta_base, activo) VALUES
(1,  'CAD-001', 'Kit Transmision Completo',   'DID',        '520VX3',  'Kit cadena 520 + pinon + corona 150cc',        350.00, TRUE),
(2,  'CAD-002', 'Cadena de Distribucion',     'Tsubaki',    'DID830',  'Cadena distribucion para motos 200cc',         180.00, TRUE),
(3,  'CAD-003', 'Pinon de Acero 15T',         'Sunstar',    '15T-520', 'Pinon delantero 15 dientes cadena 520',         85.00, TRUE),
(4,  'FRE-001', 'Pastillas Freno Delantero',  'EBC',        'FA209',   'Pastillas sinterizadas para freno disco',      120.00, TRUE),
(5,  'FRE-002', 'Disco Freno Trasero',        'Brembo',     'DB203',   'Disco freno trasero 220mm',                    250.00, TRUE),
(6,  'FRE-003', 'Cable de Freno Acero',       'Venhill',    'CB-15',   'Cable freno delantero con funda acero',         65.00, TRUE),
(7,  'BUJ-001', 'Bujia Iridium',              'NGK',        'CR8EIX',  'Bujia iridio para motos 125-250cc',             55.00, TRUE),
(8,  'BUJ-002', 'Bobina de Encendido',        'Denso',      '129700',  'Bobina encendido universal 12V',               180.00, TRUE),
(9,  'BUJ-003', 'CDI Electronico',            'Mitsubishi', 'CDI-150', 'Modulo encendido CDI para motos 150cc',        220.00, TRUE),
(10, 'FIL-001', 'Filtro de Aceite',           'Hiflofiltro','HF-204',  'Filtro aceite para motos 125-250cc',            35.00, TRUE),
(11, 'FIL-002', 'Filtro Aire Deportivo',      'K&N',        'KA-1508', 'Filtro aire alto flujo',                       160.00, TRUE),
(12, 'FIL-003', 'Filtro de Gasolina',         'Bosch',      '045-123', 'Filtro combustible universal',                  25.00, TRUE),
(13, 'ACC-001', 'Espejo Retrovisor Universal','TST',        'MR-01',   'Espejo retrovisor negro universal',             70.00, TRUE),
(14, 'ACC-002', 'Manillar Deportivo',         'Renthal',    'RC-971',  'Manillar aluminio 28mm',                       200.00, TRUE),
(15, 'ACC-003', 'Cubre Carter Aluminio',      'Givi',       'GC-150',  'Cubre carter aluminio pulido',                 310.00, TRUE);

-- =====================================================
-- INVENTARIO (stock actual tras todas las operaciones)
-- =====================================================
INSERT INTO inventario (id, producto_id, stock_actual, tecnica_inventario, tecnica_costo) VALUES
(1,  1,  14, 'PERMANENTE', 'PROMEDIO'),
(2,  2,  11, 'PERMANENTE', 'PROMEDIO'),
(3,  3,  21, 'PERMANENTE', 'PROMEDIO'),
(4,  4,   8, 'PERMANENTE', 'PROMEDIO'),
(5,  5,   4, 'PERMANENTE', 'PROMEDIO'),
(6,  6,  24, 'PERMANENTE', 'PROMEDIO'),
(7,  7,   8, 'PERMANENTE', 'PROMEDIO'),
(8,  8,  24, 'PERMANENTE', 'PROMEDIO'),
(9,  9,   4, 'PERMANENTE', 'PROMEDIO'),
(10, 10, 94, 'PERMANENTE', 'PROMEDIO'),
(11, 11, 31, 'PERMANENTE', 'PROMEDIO'),
(12, 12,208, 'PERMANENTE', 'PROMEDIO'),
(13, 13, 55, 'PERMANENTE', 'PROMEDIO'),
(14, 14,  3, 'PERMANENTE', 'PROMEDIO'),
(15, 15, 19, 'PERMANENTE', 'PROMEDIO');

INSERT INTO movimiento_inventario (inventario_id, tipo_movimiento, cantidad, motivo, fecha) VALUES
-- Compra #1 (enero - proveedor 1: cadenas, frenos, bujias)
(1,  'INGRESO', 10, 'Compra #1', '2026-01-10 09:00:00'),
(2,  'INGRESO', 15, 'Compra #1', '2026-01-10 09:00:00'),
(3,  'INGRESO', 25, 'Compra #1', '2026-01-10 09:00:00'),
(4,  'INGRESO', 20, 'Compra #1', '2026-01-10 09:00:00'),
(5,  'INGRESO', 12, 'Compra #1', '2026-01-10 09:00:00'),
(6,  'INGRESO', 30, 'Compra #1', '2026-01-10 09:00:00'),
(7,  'INGRESO', 35, 'Compra #1', '2026-01-10 09:00:00'),
-- Compra #2 (marzo - proveedor 2: electricos, filtros, accesorios)
(8,  'INGRESO',  8, 'Compra #2', '2026-03-15 09:00:00'),
(9,  'INGRESO', 10, 'Compra #2', '2026-03-15 09:00:00'),
(10, 'INGRESO', 50, 'Compra #2', '2026-03-15 09:00:00'),
(11, 'INGRESO', 12, 'Compra #2', '2026-03-15 09:00:00'),
(12, 'INGRESO', 60, 'Compra #2', '2026-03-15 09:00:00'),
(13, 'INGRESO', 20, 'Compra #2', '2026-03-15 09:00:00'),
(14, 'INGRESO', 10, 'Compra #2', '2026-03-15 09:00:00'),
(15, 'INGRESO',  8, 'Compra #2', '2026-03-15 09:00:00'),
-- Compra #3 (marzo - proveedor 1: reposicion electricos)
(8,  'INGRESO', 20, 'Compra #3', '2026-03-25 09:00:00'),
(9,  'INGRESO', 15, 'Compra #3', '2026-03-25 09:00:00'),
(11, 'INGRESO', 20, 'Compra #3', '2026-03-25 09:00:00'),
-- Compra #4 (mayo - proveedor 2: reposicion filtros y accesorios)
(10, 'INGRESO',100, 'Compra #4', '2026-05-10 09:00:00'),
(12, 'INGRESO',150, 'Compra #4', '2026-05-10 09:00:00'),
(13, 'INGRESO', 40, 'Compra #4', '2026-05-10 09:00:00'),
(15, 'INGRESO', 15, 'Compra #4', '2026-05-10 09:00:00');

-- =====================================================
-- CU3 - COMPRAS (4 compras a proveedores)
-- =====================================================
INSERT INTO compra (id, proveedor_id, fecha, total, estado) VALUES
(1, 1, '2026-01-10 09:00:00', 12500.00, 'RECIBIDA'),
(2, 2, '2026-03-15 14:00:00',  8400.00, 'RECIBIDA'),
(3, 1, '2026-03-25 10:00:00',  7300.00, 'RECIBIDA'),
(4, 2, '2026-05-10 11:00:00',  9275.00, 'RECIBIDA');

INSERT INTO detalle_compra (compra_id, producto_id, cantidad, precio_unitario) VALUES
-- Compra 1 (Distribuidora Japonesa)
(1, 1,  10, 250.00),
(1, 2,  15, 130.00),
(1, 3,  25,  60.00),
(1, 4,  20,  85.00),
(1, 5,  12, 175.00),
(1, 6,  30,  45.00),
(1, 7,  35,  38.00),
-- Compra 2 (Importadora China)
(2,  8,  8, 130.00),
(2,  9, 10, 160.00),
(2, 10, 50,  22.00),
(2, 11, 12, 115.00),
(2, 12, 60,  15.00),
(2, 13, 20,  48.00),
(2, 14, 10, 145.00),
(2, 15,  8, 225.00),
-- Compra 3 (Distribuidora Japonesa - reposicion)
(3,  8, 20, 130.00),
(3,  9, 15, 160.00),
(3, 11, 20, 115.00),
-- Compra 4 (Importadora China - reposicion)
(4, 10, 100,  22.00),
(4, 12, 150,  15.00),
(4, 13,  40,  48.00),
(4, 15,  15, 225.00);

-- =====================================================
-- CU4 - PEDIDOS (6 pedidos)
-- =====================================================
INSERT INTO pedido (id, cliente_id, fecha, estado) VALUES
(1, 6, '2026-02-10 08:00:00', 'DESPACHADO'),
(2, 8, '2026-03-18 10:00:00', 'DESPACHADO'),
(3, 7, '2026-04-20 09:00:00', 'DESPACHADO'),
(4, 9, '2026-05-05 14:00:00', 'DESPACHADO'),
(5,10, '2026-06-01 11:00:00', 'DESPACHADO'),
(6, 6, '2026-06-18 14:00:00', 'SOLICITADO');

INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad) VALUES
(1, 1, 2), (1, 4, 3), (1, 7, 4),
(2, 10, 8), (2, 12, 10),
(3, 3, 3), (3, 2, 1), (3, 8, 1),
(4, 4, 2), (4, 7, 5),
(5, 1, 1), (5, 5, 1),
(6, 9, 2), (6, 12, 5), (6, 13, 3);

-- =====================================================
-- CU6 - VENTAS (15 ventas: enero a junio 2026)
-- =====================================================
INSERT INTO venta (id, cliente_id, fecha, monto_total, tipo_venta, metodo_pago, estado) VALUES
-- Enero 2026
(1,  7, '2026-01-15 10:00:00',  700.00, 'CONTADO', 'EFECTIVO', 'COMPLETADA'),
(2,  9, '2026-01-28 15:30:00',  410.00, 'CONTADO', 'QR',       'COMPLETADA'),
-- Febrero 2026
(3,  6, '2026-02-15 10:30:00', 1490.00, 'CONTADO', 'EFECTIVO', 'COMPLETADA'),
(4,  8, '2026-02-20 14:00:00', 2120.00, 'CREDITO', 'EFECTIVO', 'PENDIENTE'),
-- Marzo 2026
(5,  6, '2026-03-05 09:00:00',  660.00, 'CONTADO', 'TARJETA',  'COMPLETADA'),
(6,  7, '2026-03-10 15:00:00',  220.00, 'CONTADO', 'QR',       'COMPLETADA'),
(7,  9, '2026-03-20 11:00:00',  280.00, 'CONTADO', 'EFECTIVO', 'COMPLETADA'),
-- Abril 2026
(8, 10, '2026-04-05 09:45:00',  250.00, 'CREDITO', 'TARJETA',  'PENDIENTE'),
(9, 10, '2026-04-12 16:00:00',  830.00, 'CONTADO', 'EFECTIVO', 'COMPLETADA'),
(10, 7, '2026-04-25 13:00:00', 1550.00, 'CREDITO', 'QR',       'PENDIENTE'),
-- Mayo 2026
(11, 6, '2026-05-08 10:00:00',  590.00, 'CONTADO', 'EFECTIVO', 'COMPLETADA'),
(12, 8, '2026-05-12 14:00:00', 4700.00, 'CREDITO', 'EFECTIVO', 'PENDIENTE'),
(13, 9, '2026-05-20 11:30:00',  490.00, 'CONTADO', 'EFECTIVO', 'COMPLETADA'),
-- Junio 2026
(14, 8, '2026-06-02 09:00:00', 1420.00, 'CONTADO', 'TARJETA',  'COMPLETADA'),
(15,10, '2026-06-10 15:00:00', 1020.00, 'CREDITO', 'EFECTIVO', 'PENDIENTE');

INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario) VALUES
-- V1: 2 kits transmision = 700
(1,  1, 2, 350.00),
-- V2: 4 filtros aceite + 4 bujias + 2 filtros gasolina = 140+220+50 = 410
(2, 10, 4,  35.00),
(2,  7, 4,  55.00),
(2, 12, 2,  25.00),
-- V3: 2 kits + 3 pastillas + 4 bujias + 3 espejos = 700+360+220+210 = 1490
(3,  1, 2, 350.00),
(3,  4, 3, 120.00),
(3,  7, 4,  55.00),
(3, 13, 3,  70.00),
-- V4: 3 cadenas + 4 pastillas + 2 discos + 3 manillares = 540+480+500+600 = 2120
(4,  2, 3, 180.00),
(4,  4, 4, 120.00),
(4,  5, 2, 250.00),
(4, 14, 3, 200.00),
-- V5: 4 pinones + 1 bobina + 2 espejos = 340+180+140 = 660
(5,  3, 4,  85.00),
(5,  8, 1, 180.00),
(5, 13, 2,  70.00),
-- V6: 4 bujias = 220
(6,  7, 4,  55.00),
-- V7: 8 filtros aceite = 280
(7, 10, 8,  35.00),
-- V8: 1 filtro aire + 1 cable freno + 1 filtro gasolina = 160+65+25 = 250
(8, 11, 1, 160.00),
(8,  6, 1,  65.00),
(8, 12, 1,  25.00),
-- V9: 10 bujias + 8 filtros aceite = 550+280 = 830
(9,  7,10,  55.00),
(9, 10, 8,  35.00),
-- V10: 3 cubre carter + 2 manillares + 1 CDI = 930+400+220 = 1550
(10,15, 3, 310.00),
(10,14, 2, 200.00),
(10, 9, 1, 220.00),
-- V11: 1 kit transmision + 2 pastillas = 350+240 = 590
(11, 1, 1, 350.00),
(11, 4, 2, 120.00),
-- V12: 5 kits + 4 discos + 6 manillares + 2 CDI + 1 cubre = 1750+1000+1200+440+310 = 4700
(12, 1, 5, 350.00),
(12, 5, 4, 250.00),
(12,14, 6, 200.00),
(12, 9, 2, 220.00),
(12,15, 1, 310.00),
-- V13: 5 cables freno + 3 bujias = 325+165 = 490
(13, 6, 5,  65.00),
(13, 7, 3,  55.00),
-- V14: 2 discos + 1 manillar + 4 cadenas = 500+200+720 = 1420
(14, 5, 2, 250.00),
(14,14, 1, 200.00),
(14, 2, 4, 180.00),
-- V15: 3 CDI + 2 bobinas = 660+360 = 1020
(15, 9, 3, 220.00),
(15, 8, 2, 180.00);

-- =====================================================
-- CU7 - CREDITOS Y PLAN DE PAGOS (5 creditos)
-- =====================================================
-- C1: V8  (Luis  , 250.00,  3 cuotas,  3.5%) -> 86.25/cuota  | VIGENTE
-- C2: V4  (Pedro , 2120.00, 4 cuotas,  5.0%) -> 556.50/cuota | MOROSO (cuotas 3 y 4 vencidas)
-- C3: V10 (Maria , 1550.00, 3 cuotas,  7.0%) -> 552.83/cuota | VIGENTE
-- C4: V12 (Pedro , 4700.00, 6 cuotas, 10.0%) -> 861.67/cuota | VIGENTE
-- C5: V15 (Luis  , 1020.00, 6 cuotas,  8.0%) -> 183.60/cuota | VIGENTE
INSERT INTO credito (id, venta_id, numero_cuotas, tasa_interes, saldo_pendiente, estado) VALUES
(1,  8, 3,  3.50,  172.50, 'VIGENTE'),
(2,  4, 4,  5.00, 1113.00, 'MOROSO'),
(3, 10, 3,  7.00, 1105.66, 'VIGENTE'),
(4, 12, 6, 10.00, 4308.35, 'VIGENTE'),
(5, 15, 6,  8.00, 1101.60, 'VIGENTE');

-- Credito 1: V8 (250.00), 3 cuotas 3.5% — vencimiento desde mayo
INSERT INTO pago_cuota (credito_id, numero_cuota, monto_cuota, fecha_vencimiento, fecha_pago, mora, estado) VALUES
(1, 1, 86.25, '2026-05-05', '2026-05-02', 0.00, 'PAGADO'),
(1, 2, 86.25, '2026-06-05',  NULL,        0.00, 'PENDIENTE'),
(1, 3, 86.25, '2026-07-05',  NULL,        0.00, 'PENDIENTE');

-- Credito 2: V4 (2120.00), 4 cuotas 5% — MOROSO: cuotas 3 y 4 vencidas
INSERT INTO pago_cuota (credito_id, numero_cuota, monto_cuota, fecha_vencimiento, fecha_pago, mora, estado) VALUES
(2, 1, 556.50, '2026-03-20', '2026-03-18', 0.00,  'PAGADO'),
(2, 2, 556.50, '2026-04-20', '2026-04-19', 0.00,  'PAGADO'),
(2, 3, 556.50, '2026-05-20',  NULL,       27.83,  'VENCIDO'),
(2, 4, 556.50, '2026-06-15',  NULL,       27.83,  'VENCIDO');

-- Credito 3: V10 (1550.00), 3 cuotas 7%
INSERT INTO pago_cuota (credito_id, numero_cuota, monto_cuota, fecha_vencimiento, fecha_pago, mora, estado) VALUES
(3, 1, 552.83, '2026-05-25', '2026-05-22', 0.00, 'PAGADO'),
(3, 2, 552.83, '2026-06-25',  NULL,        0.00, 'PENDIENTE'),
(3, 3, 552.83, '2026-07-25',  NULL,        0.00, 'PENDIENTE');

-- Credito 4: V12 (4700.00), 6 cuotas 10%
INSERT INTO pago_cuota (credito_id, numero_cuota, monto_cuota, fecha_vencimiento, fecha_pago, mora, estado) VALUES
(4, 1, 861.67, '2026-06-12', '2026-06-10', 0.00, 'PAGADO'),
(4, 2, 861.67, '2026-07-12',  NULL,        0.00, 'PENDIENTE'),
(4, 3, 861.67, '2026-08-12',  NULL,        0.00, 'PENDIENTE'),
(4, 4, 861.67, '2026-09-12',  NULL,        0.00, 'PENDIENTE'),
(4, 5, 861.67, '2026-10-12',  NULL,        0.00, 'PENDIENTE'),
(4, 6, 861.67, '2026-11-12',  NULL,        0.00, 'PENDIENTE');

-- Credito 5: V15 (1020.00), 6 cuotas 8% — todas pendientes (reciente)
INSERT INTO pago_cuota (credito_id, numero_cuota, monto_cuota, fecha_vencimiento, fecha_pago, mora, estado) VALUES
(5, 1, 183.60, '2026-07-10', NULL, 0.00, 'PENDIENTE'),
(5, 2, 183.60, '2026-08-10', NULL, 0.00, 'PENDIENTE'),
(5, 3, 183.60, '2026-09-10', NULL, 0.00, 'PENDIENTE'),
(5, 4, 183.60, '2026-10-10', NULL, 0.00, 'PENDIENTE'),
(5, 5, 183.60, '2026-11-10', NULL, 0.00, 'PENDIENTE'),
(5, 6, 183.60, '2026-12-10', NULL, 0.00, 'PENDIENTE');

-- =====================================================
-- Sincronizar secuencias con el MAX(id) actual
-- =====================================================
SELECT setval('usuario_id_seq',               (SELECT MAX(id) FROM usuario));
SELECT setval('proveedor_id_seq',             (SELECT MAX(id) FROM proveedor));
SELECT setval('producto_id_seq',              (SELECT MAX(id) FROM producto));
SELECT setval('inventario_id_seq',            (SELECT MAX(id) FROM inventario));
SELECT setval('movimiento_inventario_id_seq', (SELECT MAX(id) FROM movimiento_inventario));
SELECT setval('compra_id_seq',                (SELECT MAX(id) FROM compra));
SELECT setval('detalle_compra_id_seq',        (SELECT MAX(id) FROM detalle_compra));
SELECT setval('pedido_id_seq',                (SELECT MAX(id) FROM pedido));
SELECT setval('detalle_pedido_id_seq',        (SELECT MAX(id) FROM detalle_pedido));
SELECT setval('venta_id_seq',                 (SELECT MAX(id) FROM venta));
SELECT setval('detalle_venta_id_seq',         (SELECT MAX(id) FROM detalle_venta));
SELECT setval('credito_id_seq',               (SELECT MAX(id) FROM credito));
SELECT setval('pago_cuota_id_seq',            (SELECT MAX(id) FROM pago_cuota));

COMMIT;
