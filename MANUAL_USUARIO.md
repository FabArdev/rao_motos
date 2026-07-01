# Manual de Usuario — RAO MOTOS

Sistema de venta de repuestos de moto y taller de reparación, con ventas al contado y al crédito (cuotas).
INF-513 Tecnología Web · Proyecto 2 · grupo02sa.

---

## 1. ¿Qué es RAO MOTOS?

RAO MOTOS hace dos cosas:

1. **Vende repuestos de moto** — al por menor (minorista) y al por mayor (precio por volumen).
2. **Repara motos** — el cliente deja su moto, el taller diagnostica, presupuesta y repara.

Ambas se pueden pagar **al contado o en cuotas (crédito)**.

---

## 2. Roles del sistema

Cada usuario tiene **un rol**. Según el rol ve unos módulos u otros en el menú lateral.

| Rol | Qué puede hacer |
|---|---|
| **Administrador** | Todo: usuarios, reportes globales, bitácora, configuración del sistema |
| **Vendedor** | Ventas, aprobar/despachar pedidos, cobrar cuotas y gestionar mora, facturar órdenes de taller |
| **Almacenero** | Productos, compras, proveedores, inventario, aprobar repuestos del taller |
| **Mecánico** | Recibir motos, diagnosticar, presupuestar, reparar, solicitar repuestos, terminar órdenes |
| **Cliente** | Catálogo, hacer pedidos, ver su moto en taller, aprobar presupuestos, pagar cuotas (QR), panel "Mi cuenta" |

---

## 3. Acceso al sistema

### 3.1 Registrarse (clientes)
En la página de inicio, **Crear cuenta**. Al registrarte quedas como **cliente** automáticamente.

### 3.2 Iniciar sesión
Botón **Iniciar sesión** → correo y contraseña.

### 3.3 Usuarios de demostración

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | fabioarnez200@gmail.com | `admin123` |
| Vendedor | vendedor@raomotos.com | `demo123` |
| Almacenero | almacenero@raomotos.com | `demo123` |
| Mecánico | mecanico@raomotos.com | `demo123` |
| Cliente | juan.perez@email.com | `cliente123` |

### 3.4 Mi perfil
Cualquier usuario puede editar su perfil, foto y contraseña desde el menú de su nombre (arriba a la derecha) → **Mi perfil**.

---

## 4. La pantalla principal

- **Menú lateral (izquierda):** cambia según tu rol; solo muestra los módulos que puedes usar.
- **Buscador (arriba):** busca en el negocio — productos, clientes, pedidos y órdenes de taller. Escribe y presiona Enter.
- **Campana de notificaciones:** avisos internos (stock bajo, pedido por aprobar, repuesto solicitado, presupuesto listo, mora). El número rojo indica los no leídos.
- **Apariencia (ícono de paleta):** elige **tema** (Niños / Jóvenes / Adultos), **modo** (Día / Noche / Auto), **tamaño de letra** y **alto contraste**. Tu elección se recuerda.
- **Footer:** muestra el **contador de visitas** del sistema.

---

## 5. Guía por rol

### 5.1 Administrador

**Usuarios** (menú → Usuarios)
- Crear, editar, activar/desactivar usuarios y asignar su rol.
- No puedes eliminarte a ti mismo.

**Configuración** (menú → Configuración)
- Ajusta los parámetros del negocio y guarda:
  - `tasa_interes_credito` — interés por defecto al financiar una venta a crédito (%).
  - `tasa_mora_diaria` — mora por día de retraso (%).
  - `tope_mora_pct` — tope máximo de mora como % de la cuota.
  - `dias_entre_cuotas` — días entre vencimientos de cuotas.
  - `dias_aviso_cuota` — días de anticipación para avisar al cliente.

**Bitácora** (menú → Bitácora)
- Registro de accesos: inicios de sesión correctos/fallidos y acceso a recursos. Filtra por acción o busca por correo/recurso.

**Reportes** (menú → Reportes) — ver §6.

> El administrador es **superusuario**: puede entrar a cualquier módulo de los demás roles.

---

### 5.2 Almacenero

**Productos** (menú → Productos)
1. **Nuevo producto:** código único, nombre, marca/modelo, **precio minorista**, **precio mayorista** y **cantidad mínima para mayoreo** (a partir de cuántas unidades aplica el precio mayorista), foto y **stock mínimo**.
2. Al crear un producto se crea su registro de inventario (stock inicial 0).
3. Eliminar es **lógico** (queda inactivo): el producto puede estar en ventas/compras previas.

**Proveedores** (menú → Proveedores)
- CRUD de proveedores (razón social, contacto, NIT, teléfono). No son usuarios del sistema.

**Compras** (menú → Compras)
1. **Nueva compra:** elige proveedor y agrega líneas (producto, cantidad, precio unitario). El **total lo calcula el sistema**.
2. La compra nace **PENDIENTE**.
3. **Recibir** la compra → ingresa el stock al inventario automáticamente y pasa a **RECIBIDA**.
4. **Anular:** si ya estaba recibida, revierte el stock.

**Inventario** (menú → Inventario)
- Lista de stock por producto; el botón **Stock bajo** filtra los que están por debajo del mínimo (también te llegan por notificación).
- En el detalle de un producto puedes hacer un **ajuste manual** (cantidad positiva = ingreso, negativa = egreso) con su motivo, y ver los movimientos.

**Taller (repuestos):** cuando un mecánico solicita repuestos, te llega una notificación. En la orden, **Aprobar** el repuesto descuenta el stock; **Rechazar** lo descarta.

---

### 5.3 Vendedor

**Ventas** (menú → Ventas)
1. **Nueva venta:** elige el cliente, tipo (**Contado** o **Crédito**) y método (**Efectivo** o **QR**).
2. Agrega productos con su cantidad. El **precio por línea** se calcula solo: si la cantidad alcanza el umbral mayorista del producto, aplica el **precio mayorista**; si no, el minorista.
3. Si es **Crédito**, indica **número de cuotas** (mínimo 2) y, opcionalmente, la tasa de interés (por defecto la configurada).
4. Al guardar: se descuenta el stock, y si es a crédito se genera el crédito con su calendario de cuotas.

**Pedidos** (menú → Pedidos)
1. Los clientes envían pedidos (te llega notificación). En el detalle puedes:
   - **Aprobar** → se genera la venta (pendiente de despacho) con precio mayorista por línea.
   - **Rechazar** → indicando el motivo.
2. **Despachar** un pedido aprobado → descuenta el stock y completa la venta.

**Créditos y cobranza** (menú → Créditos)
- Verás el resumen de créditos **vigentes / morosos / pagados** y podrás filtrar.
- En el detalle de un crédito ves el calendario de cuotas con su **mora** al día. Botón **Registrar pago** por cada cuota (elige el método de cobro).

**Taller (facturar):** cuando una orden queda **TERMINADA**, en su detalle usas **Facturar orden** para generar la venta (mano de obra + repuestos), contado o crédito. **No** se vuelve a descontar el stock (los repuestos ya salieron al aprobarse).

---

### 5.4 Mecánico

**Taller** (menú → Taller)
1. **Recibir moto:** elige el cliente y su moto (o registra una moto nueva) y describe el problema. La orden nace **RECIBIDA**.
2. **Diagnóstico y presupuesto:** en el detalle, registra el diagnóstico y los costos estimados (mano de obra + repuestos). La orden pasa a **DIAGNOSTICADA** y se avisa al cliente por notificación.
3. El **cliente aprueba** el presupuesto → la orden pasa a **EN_REPARACION**. (Sin aprobación, la reparación no empieza.)
4. **Solicitar repuestos:** agrega los repuestos necesarios; el almacenero los aprueba (descuenta stock).
5. **Terminar:** registra el costo real de mano de obra → la orden queda **TERMINADA** y lista para que el vendedor la facture.

---

### 5.5 Cliente

**Catálogo** (menú → Catálogo)
1. Busca repuestos y añádelos a tu pedido (columna derecha).
2. Ajusta cantidades y pulsa **Enviar pedido**. Un vendedor lo revisará.

**Mis pedidos** (menú → Mis Pedidos)
- Estado de cada pedido (solicitado, aprobado, rechazado, despachado). Si fue rechazado, verás el motivo.

**Mi taller** (menú → Mi Taller)
- Estado de tu moto y su reparación.
- Cuando el mecánico presupuesta, verás el costo estimado y podrás **Aprobar** o **Rechazar** el presupuesto.
- Al facturar, verás la venta y, si es a crédito, el enlace a tus cuotas.

**Mis créditos** (menú → Mis Créditos)
- Lista de tus créditos y, en el detalle, tus cuotas con su mora al día.
- Botón **Pagar con QR** en la próxima cuota: se genera un **QR de PagoFácil**; escanéalo con tu app bancaria. El pago se confirma y la cuota queda pagada.

**Mi cuenta** (menú → Mi Cuenta)
- Panel unificado: tus pedidos, tu moto en taller, tus créditos y tus motos, todo en una vista.

---

## 6. Reportes (Admin / Vendedor / Almacenero)

Menú → **Reportes**. Cada uno se descarga en **PDF**:
- **Ventas por fecha** (elige un rango; separa contado vs crédito).
- **Créditos por estado** (vigentes, morosos, pagados; saldo pendiente).
- **Inventario crítico** (productos bajo el stock mínimo).
- **Top productos vendidos** (ranking por unidades y monto).

El **Dashboard** (inicio tras iniciar sesión) muestra tarjetas de indicadores y gráficas: ventas por mes, top de productos y órdenes de taller por estado.

---

## 7. Cómo funciona el crédito y la mora

- Una venta a crédito genera un **crédito** con **mínimo 2 cuotas**. El **interés** infla el saldo al inicio.
- Cada cuota tiene su **fecha de vencimiento** (separadas por los días configurados).
- La **mora** se calcula por días de retraso sobre la cuota vencida, con un tope, de forma **automática**: una tarea diaria marca las cuotas vencidas, calcula la mora y pone el crédito en **MOROSO** (además avisa al cliente).
- El crédito pasa a **PAGADO** cuando se pagan todas sus cuotas.

---

## 8. Estados de referencia

| Entidad | Estados |
|---|---|
| Compra | PENDIENTE → RECIBIDA → ANULADA |
| Pedido | SOLICITADO → APROBADO / RECHAZADO → DESPACHADO → ANULADO |
| Venta | PENDIENTE → COMPLETADA → ANULADA |
| Crédito | VIGENTE → PAGADO / MOROSO |
| Cuota | PENDIENTE → PAGADO / VENCIDO |
| Orden de taller | RECIBIDA → DIAGNOSTICADA → EN_REPARACION → TERMINADA → ENTREGADA (o CANCELADA) |

---

## 9. Preguntas frecuentes

**No veo un módulo en mi menú.** El menú depende de tu rol; solo aparecen los módulos que puedes usar.

**El precio cambió al subir la cantidad.** Es correcto: al alcanzar el umbral mayorista del producto, aplica el precio mayorista.

**El texto se ve poco contra el fondo.** Usa el ícono de **paleta** y cambia el modo (Día/Noche), el tamaño de letra o activa **alto contraste**.

**No puedo anular una venta.** Las ventas con crédito asociado no se anulan; las de un pedido/taller se revierten según su origen.

**El QR no se genera.** El pago por QR usa PagoFácil y requiere conexión. Si falla, realiza el pago en caja (efectivo).
