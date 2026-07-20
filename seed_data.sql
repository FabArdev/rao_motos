-- =====================================================
-- DATOS SEMILLA — "RAO MOTOS" (proyecto EMAIL / Etapa 1)
-- Replica los seeders de la app Laravel (Etapa 2).
-- Ejecutar DESPUÉS de database_schema.sql.
--
-- Nota: en el medio email la identidad es el REMITENTE; la contraseña no se usa
-- para autenticar. Se guardan en texto plano solo como dato demo.
-- =====================================================

-- ---------- ROLES (ids fijos: 1 admin, 2 vendedor, 3 almacenero, 5 cliente) ----------
INSERT INTO rol (id, nombre, descripcion) VALUES
    (1, 'admin',      'Administrador / propietario, acceso total'),
    (2, 'vendedor',   'Ventas, pedidos y cobranza'),
    (3, 'almacenero', 'Compras, proveedores, inventario, productos'),
    (5, 'cliente',    'Compra, pedidos y sus cuotas');
SELECT setval('rol_id_seq', (SELECT MAX(id) FROM rol));

-- ---------- USUARIOS ----------
-- 3 admins (equipo)
INSERT INTO usuario (id, nombre, apellidos, ci, correo, telefono, password, rol_id, estado) VALUES
    (1, 'Carlos Diego',    'Marca Peñaranda', '7000001', 'marcacarlosestudio@gmail.com',  '77123401', 'admin123', 1, TRUE),
    (2, 'Fabio Alejandro', 'Arnez Fernández', '7000002', 'fabioarnez200@gmail.com',       '77123402', 'admin123', 1, TRUE),
    (3, 'Reymar',          'Loaiza Labarden', '7000003', 'loaizalabardenreymar@gmail.com','77123403', 'admin123', 1, TRUE);

-- 1 demo por rol operativo
INSERT INTO usuario (id, nombre, apellidos, ci, correo, telefono, password, rol_id, estado) VALUES
    (4, 'Vendedor',   'Demo', '8000001', 'vendedor@raomotos.com',   '70000001', 'demo123', 2, TRUE),
    (5, 'Almacenero', 'Demo', '8000002', 'almacenero@raomotos.com', '70000002', 'demo123', 3, TRUE);

-- 5 clientes
INSERT INTO usuario (id, nombre, apellidos, ci, correo, telefono, password, rol_id, estado) VALUES
    (6,  'Juan',  'Perez Mamani',    '9000001', 'juan.perez@email.com',      '72123401', 'cliente123', 5, TRUE),
    (7,  'Maria', 'Flores Quispe',   '9000002', 'maria.flores@email.com',    '72123402', 'cliente123', 5, TRUE),
    (8,  'Pedro', 'Gutierrez Soliz', '9000003', 'pedro.gutierrez@email.com', '72123403', 'cliente123', 5, TRUE),
    (9,  'Ana',   'Rodriguez Lopez', '9000004', 'ana.rodriguez@email.com',   '72123404', 'cliente123', 5, TRUE),
    (10, 'Luis',  'Vargas Rojas',    '9000005', 'luis.vargas@email.com',     '72123405', 'cliente123', 5, TRUE);
SELECT setval('usuario_id_seq', (SELECT MAX(id) FROM usuario));

-- Subtabla cliente (1:1 con los usuarios cliente)
INSERT INTO cliente (id, nit_ci) VALUES
    (6, '12345601'), (7, '12345602'), (8, '12345603'), (9, '12345604'), (10, '12345605');

-- ---------- PROVEEDORES ----------
INSERT INTO proveedor (razon_social, contacto_principal, nit, telefono, activo) VALUES
    ('Distribuidora Japonesa Ltda.', 'Tanaka Suzuki', '10012345', '44123401', TRUE),
    ('Importadora China del Sur',    'Li Wei',        '10067890', '44123402', TRUE);

-- ---------- MÉTODOS DE PAGO ----------
INSERT INTO metodo_pago (nombre, activo) VALUES
    ('EFECTIVO', TRUE),
    ('QR',       TRUE);

-- ---------- CONFIGURACIÓN (parámetros con default, RN5.1) ----------
INSERT INTO configuracion (clave, valor, descripcion) VALUES
    ('tasa_interes_credito',   '5.00', 'Interés por defecto al financiar una venta a crédito (%)'),
    ('tasa_mora_diaria',       '0.50', 'Mora por día de retraso sobre la cuota vencida (%)'),
    ('tope_mora_pct',          '20',   'Tope máximo de mora como % de la cuota'),
    ('dias_entre_cuotas',      '30',   'Días entre vencimientos de cuotas consecutivas'),
    ('dias_aviso_cuota',       '3',    'Días de anticipación para avisar de una cuota por vencer'),
    ('margen_venta_minorista', '25',   'Margen (%) sobre el costo de compra para el precio minorista'),
    ('margen_venta_mayorista', '15',   'Margen (%) sobre el costo de compra para el precio mayorista');

-- ---------- PRODUCTOS + INVENTARIO ----------
-- precio_mayorista ≈ 12% menos que el base; umbral por valor (barato→alto, caro→bajo).
-- stock_minimo = 5 para todos.
INSERT INTO producto (codigo, nombre, marca, modelo, descripcion, precio_venta_base, precio_mayorista, cantidad_minima_mayorista) VALUES
    ('CAD-001', 'Kit Transmision Completo',    'DID',         '520VX3',  'Kit cadena 520 + pinon + corona 150cc',   350.00, 308.00,  3),
    ('CAD-002', 'Cadena de Distribucion',      'Tsubaki',     'DID830',  'Cadena distribucion para motos 200cc',    180.00, 158.40,  8),
    ('CAD-003', 'Pinon de Acero 15T',          'Sunstar',     '15T-520', 'Pinon delantero 15 dientes cadena 520',    85.00,  74.80, 20),
    ('FRE-001', 'Pastillas Freno Delantero',   'EBC',         'FA209',   'Pastillas sinterizadas para freno disco', 120.00, 105.60,  8),
    ('FRE-002', 'Disco Freno Trasero',         'Brembo',      'DB203',   'Disco freno trasero 220mm',               250.00, 220.00,  3),
    ('FRE-003', 'Cable de Freno Acero',        'Venhill',     'CB-15',   'Cable freno delantero con funda acero',    65.00,  57.20, 20),
    ('BUJ-001', 'Bujia Iridium',               'NGK',         'CR8EIX',  'Bujia iridio para motos 125-250cc',        55.00,  48.40, 20),
    ('BUJ-002', 'Bobina de Encendido',         'Denso',       '129700',  'Bobina encendido universal 12V',          180.00, 158.40,  8),
    ('BUJ-003', 'CDI Electronico',             'Mitsubishi',  'CDI-150', 'Modulo encendido CDI para motos 150cc',   220.00, 193.60,  3),
    ('FIL-001', 'Filtro de Aceite',            'Hiflofiltro', 'HF-204',  'Filtro aceite para motos 125-250cc',       35.00,  30.80, 20),
    ('FIL-002', 'Filtro Aire Deportivo',       'K&N',         'KA-1508', 'Filtro aire alto flujo',                  160.00, 140.80,  8),
    ('FIL-003', 'Filtro de Gasolina',          'Bosch',       '045-123', 'Filtro combustible universal',             25.00,  22.00, 20),
    ('ACC-001', 'Espejo Retrovisor Universal', 'TST',         'MR-01',   'Espejo retrovisor negro universal',        70.00,  61.60, 20),
    ('ACC-002', 'Manillar Deportivo',          'Renthal',     'RC-971',  'Manillar aluminio 28mm',                  200.00, 176.00,  8),
    ('ACC-003', 'Cubre Carter Aluminio',       'Givi',        'GC-150',  'Cubre carter aluminio pulido',            310.00, 272.80,  3);

-- Inventario: un registro por producto (stock inicial del seeder Laravel)
INSERT INTO inventario (producto_id, stock_actual, stock_minimo, tecnica_inventario, tecnica_costo)
SELECT p.id, s.stock, 5, 'PERMANENTE', 'PROMEDIO'
FROM producto p
JOIN (VALUES
    ('CAD-001', 14), ('CAD-002', 11), ('CAD-003', 21), ('FRE-001',  8), ('FRE-002',  4),
    ('FRE-003', 24), ('BUJ-001',  8), ('BUJ-002', 24), ('BUJ-003',  4), ('FIL-001', 94),
    ('FIL-002', 31), ('FIL-003', 208),('ACC-001', 55), ('ACC-002',  3), ('ACC-003', 19)
) AS s(codigo, stock) ON s.codigo = p.codigo;
