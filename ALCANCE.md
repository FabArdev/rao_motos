# ALCANCE — RAO MOTOS

> Sistema de Ventas al Crédito de Repuestos **y Taller de Reparación de Motos**
> Proyecto 2 — INF-513 Tecnología Web · Grupo02sa · FICCT, UAGRM · Gestión 2026
>
> **Estado:** documento de alcance para revisar, aumentar, quitar o reforzar.
> Cada sección está numerada para poder referenciarla al discutir cambios.
> Base técnica reutilizada: Laravel 10 + Inertia + Vue 3 + Bootstrap 5 + Jetstream + PostgreSQL.

---

## 1. El negocio

RAO MOTOS es una empresa que hace **dos cosas igual de importantes**:

1. **Vende repuestos de moto** — al por menor (minorista) y al por mayor (mayorista, con precio por volumen).
2. **Repara motos** — el cliente deja su moto, el taller diagnostica, presupuesta y repara.

Ambas actividades se pueden pagar **al contado o en cuotas (crédito)**. El tema central del proyecto es el **crédito/cuotas**, que aplica tanto a la venta de repuestos como a las reparaciones.

---

## 2. Actores y roles

Cinco roles. Cuatro son **roles de negocio** (cumplen el mínimo del docente, que descarta al admin como rol de negocio). El perfil propio (ver datos, cambiar contraseña, foto) lo puede editar **cualquier** usuario autenticado.

| Rol | ¿Entra al sistema? | Qué hace |
|---|---|---|
| **Admin** (propietario) | Sí | Superusuario: puede todo. Gestiona usuarios, ve reportes globales y bitácora, configura parámetros |
| **Vendedor** | Sí | Ventas (contado/crédito), aprueba/rechaza pedidos (regla mayorista), factura órdenes de taller, cobra cuotas y gestiona mora |
| **Almacenero** | Sí | Compras a proveedores, gestión de proveedores, inventario y movimientos, alta de productos, aprueba solicitudes de repuestos del taller |
| **Mecánico** | Sí | Recibe motos, diagnostica, presupuesta, repara, solicita repuestos al almacén, marca órdenes terminadas |
| **Cliente** | Sí | Catálogo, hace pedidos, ve estado de su moto en taller, aprueba presupuestos, paga sus cuotas (QR), panel "Mi cuenta" |

> **Proveedor NO es un rol.** No entra al sistema. Es solo un dato (a quién se le compran repuestos), referenciado por las compras.

### 2.1 Decisión de diseño — herencia por rol

- `users` es la tabla base (la de Jetstream, para no romper el login). Tiene `role_id`.
- Solo el **cliente** tiene una subtabla 1:1 (`cliente`) con su dato de facturación (`nit_ci`). **No hay "tipo de cliente"**: un cliente es solo cliente; que una compra sea mayorista lo decide el sistema por la cantidad (CU2/CU4), no una etiqueta.
- Admin, vendedor, almacenero y mecánico se distinguen solo por `role_id` (no necesitan datos extra por ahora).

---

## 3. Matriz de permisos por módulo

| Módulo | Admin | Vendedor | Almacenero | Mecánico | Cliente |
|---|---|---|---|---|---|
| CU1 Usuarios | CRUD | — | — | — | perfil propio |
| CU2 Productos | ver | ver | **CRUD** | ver | catálogo |
| CU3 Compras / Proveedores | ver | — | **CRUD** | — | — |
| CU4 Pedidos | ver | **CRUD + aprobar/rechazar** | ver | — | crear + ver propios |
| CU5 Inventario | ver | ver | **CRUD + movimientos** | ver stock | — |
| CU6 Ventas | ver todas | **crear + ver** | — | — | ver propias |
| CU7 Créditos / Cobranza | ver | **registrar pagos / mora** | — | — | pagar propias (QR) |
| CU8 Reportes | todos | ventas propias | inventario / compras | — | — |
| CU9 Taller | ver | **facturar orden** | aprobar repuestos | **gestionar órdenes** | ver su orden + aprobar presupuesto |
| Bitácora | ver | — | — | — | — |
| Configuración | CRUD | — | — | — | — |

---

## 4. Casos de uso (CU)

### CU1 — Gestión de usuarios
**Registro público:** cualquier persona puede crearse una cuenta, y por defecto queda como **cliente** (se crea su fila en `cliente` automáticamente). Admin hace CRUD de usuarios y asigna otros roles (vendedor, almacenero, mecánico). Cualquier usuario edita su propio perfil y contraseña.

### CU2 — Gestión de productos (repuestos)
Almacenero hace CRUD. Cada producto define **dos precios** y su **umbral de mayoreo propio**: `precio_venta_base` (minorista), `precio_mayorista` (por volumen) y `cantidad_minima_mayorista` (a partir de cuántas unidades aplica el precio mayorista — porque 10 cadenas baratas ≠ 10 motores caros). Foto del producto. Código único.

### CU3 — Compras y proveedores
Almacenero registra proveedores y compras. Una compra tiene detalle (productos, cantidad, precio unitario) y estado `PENDIENTE → RECIBIDA → ANULADA`. Al recibir una compra se actualiza el inventario (ingreso).

### CU4 — Pedidos (con regla mayorista)
El cliente arma un pedido. El vendedor lo revisa y **aprueba o rechaza**:
- El precio mayorista se aplica **por línea y para cualquier cliente**: si la cantidad alcanza la `cantidad_minima_mayorista` **de ese producto** → `precio_mayorista`; si no → `precio_venta_base`. El umbral es **por producto**, no global. No depende de etiquetar al cliente.
- Al **aprobar** un pedido se **genera la venta** (PENDIENTE) lista para facturar (refuerzo D).
- Estados: `SOLICITADO → APROBADO/RECHAZADO → EN_PROCESO → DESPACHADO → ANULADO`.

### CU5 — Inventario
Cada producto tiene un registro de inventario con `stock_actual` y `stock_minimo`, técnica de inventario (PERMANENTE/PERIODICO) y de costo (PEPS/UEPS/PROMEDIO). Cada entrada/salida queda en `movimiento_inventario`. Cuando `stock_actual < stock_minimo`, se dispara **alerta de compra** al almacenero (refuerzo A).

### CU6 — Ventas
Venta al contado o a crédito. Origen: mostrador, pedido aprobado, o facturación de una orden de taller. Calcula el precio **por línea** según la cantidad vs el umbral del producto (minorista vs mayorista), igual para cualquier cliente. Reduce stock **una sola vez** (RN18: no si ya se descontó en el pedido o en taller). Estado `PENDIENTE → COMPLETADA → ANULADA`. Método de pago: **EFECTIVO** (físico) o **QR** (PagoFácil). *(Tarjeta queda fuera de alcance.)*

### CU7 — Créditos y pagos
Una venta a crédito genera un `credito` con `numero_cuotas` (mínimo 2), tasa de interés y un calendario de `pago_cuota`. El cliente paga cada cuota (QR PagoFácil). Estados del crédito: `VIGENTE → PAGADO / MOROSO`. El vendedor gestiona la cobranza.

**Interés vs. mora (dos cosas distintas):**
- **Interés del crédito** (`credito.tasa_interes`): costo de financiar; infla el saldo al inicio → `saldo_pendiente = monto_total + monto_total × tasa_interes/100`. Es **por crédito** (en los datos reales variaba 3.5%–10%); el default de `configuracion` es solo la sugerencia inicial, editable en cada venta.
- **Mora** (`pago_cuota.mora`): penalización por pagar **tarde**. Se calcula **por fecha (días de retraso)** sobre la **cuota vencida** — no por producto ni cantidad.

**Modelo de mora elegido — diaria proporcional con tope:**
```
mora = monto_cuota × (tasa_mora_diaria / 100) × días_de_retraso
       (limitada a un tope: máx. tope_mora_pct % de la cuota)
```
La **tarea programada diaria** (RN12) detecta cuotas vencidas, calcula la mora, marca la cuota `VENCIDO`, pone el crédito en `MOROSO` y dispara el aviso por email. Todas las tasas son **configurables por el admin** y tienen **valor por defecto** (ver Parámetros en sección 5.1).

### CU8 — Reportes y estadísticas
Dashboard con gráficas (Chart.js) y reportes PDF. Ventas por mes (contado vs crédito), top productos, créditos vigentes vs morosos, top clientes por monto, moras pendientes, inventario crítico. Reportes de taller (órdenes por estado, ingresos por reparación).

### CU9 — Taller de reparación **(NUEVO, núcleo del proyecto)**
Flujo completo de una reparación, atravesando 4 actores:

```
1. Cliente trae la moto              → orden_trabajo (RECIBIDA)
2. Mecánico diagnostica + presupuesta → DIAGNOSTICADA  (costo estimado mano de obra + repuestos)
3. Cliente aprueba o rechaza          → APROBADO → EN_REPARACION   |  RECHAZA → CANCELADA
4. Mecánico solicita repuestos        → detalle_orden (SOLICITADO)
5. Almacenero aprueba/rechaza         → APROBADO (descuenta inventario) → ENTREGADO  |  RECHAZADO
6. Mecánico termina                    → TERMINADA
7. Vendedor factura                    → genera VENTA (mano de obra + repuestos) → CONTADO o CREDITO(cuotas)
8. Cliente recoge                      → ENTREGADA
```

El taller **no tiene su propio sistema de cobro**: al terminar genera una venta normal, que reutiliza todo el pipeline `venta → credito → pago_cuota`.

---

## 5. Reglas de negocio

| # | Regla |
|---|---|
| RN1 | Admin es superusuario: salta todas las policies (`Gate::before`). |
| RN2 | Cualquier usuario edita su propio perfil y contraseña, sin importar el rol. |
| RN3 | Precio **por línea, para cualquier cliente**: si cantidad ≥ `producto.cantidad_minima_mayorista` → `precio_mayorista`; si no → `precio_venta_base`. **No hay tipo de cliente**; el mayoreo lo decide la cantidad. |
| RN4 | El vendedor aprueba/rechaza el pedido; el precio mayorista se aplica línea por línea según el umbral de cada producto. |
| RN5 | El mecánico NO toca el inventario: solicita repuestos y el almacenero aprueba (descuenta stock). |
| RN6 | La reparación no empieza sin que el cliente **apruebe el presupuesto**. |
| RN7 | Orden de taller terminada → la factura la emite el **vendedor** (separación: mecánico repara, vendedor cobra). |
| RN8 | Venta a crédito → mínimo 2 cuotas; se calcula mora por atraso. |
| RN9 | Stock por debajo del mínimo → alerta de compra al almacenero. |
| RN10 | El **umbral mayorista se define por producto** (lo fija el almacenero al crear/editar el producto), no como un valor global. |
| RN11 | Los **totales** (venta, compra) los **calcula el servidor** desde el detalle; el usuario nunca teclea el monto. |
| RN12 | Una **tarea programada diaria** marca cuotas vencidas, calcula mora y actualiza el estado del crédito (MOROSO); no depende de que alguien intente pagar. |
| RN13 | El pago por **QR se confirma por callback de PagoFácil** y la transacción se guarda en la **BD** (no en archivos). |
| RN14 | El registro de un cliente es **atómico**: usuario + fila `cliente` en una sola transacción. |
| RN15 | **Anular** una compra/pedido ya procesado **revierte** sus movimientos de inventario (o se restringe la anulación). |
| RN16 | **Mora = diaria proporcional sobre la cuota vencida**, con tope: `monto_cuota × (tasa_mora_diaria/100) × días_retraso`, máx. `tope_mora_pct%` de la cuota. |
| RN17 | **Todo parámetro configurable tiene un valor por defecto sembrado**; si el admin no lo cambia, el sistema funciona igual. |
| RN18 | El **stock se descuenta exactamente una vez**, donde la mercadería sale físicamente: venta directa, venta generada de un pedido, o aprobación de repuestos en taller. Una venta generada desde taller **no** vuelve a descontar inventario. |
| RN19 | **No existe "tipo de cliente"**: el cliente es solo cliente; mayorista/minorista se decide por compra según la cantidad (RN3). |

### 5.1 Parámetros configurables (tabla `configuracion`, editables por el admin)

| Clave | Significado | Valor por defecto |
|---|---|---|
| `tasa_interes_credito` | Interés por defecto al financiar una venta a crédito (%) | **5.00** |
| `tasa_mora_diaria` | Mora por día de retraso sobre la cuota vencida (%) | **0.50** |
| `tope_mora_pct` | Tope máximo de mora como % de la cuota | **20** |
| `dias_entre_cuotas` | Días entre vencimientos de cuotas consecutivas | **30** |
| `dias_aviso_cuota` | Días antes del vencimiento para avisar al cliente por email | **3** |

> Los valores por defecto son una base razonable; el admin puede ajustarlos desde el sistema sin tocar código.

---

## 6. Refuerzos de interacción entre actores

- **A. Alerta de stock bajo → compra** — Inventario → Almacenero → Proveedor. Cierra el ciclo CU5↔CU3.
- **C. Panel "Mi cuenta" del cliente** — vista única: sus pedidos + estado de su moto en taller + sus cuotas + su(s) moto(s).
- **D. Aprobar pedido → genera venta** — Cliente → Vendedor → Venta, sin recapturar datos.

### 6.1 Notificaciones (dos canales, enfocadas en lo que importa)

No buscamos un sistema de notificaciones grande; solo avisos que aporten valor real.

**Canal EMAIL (SMTP) — eventos importantes hacia el cliente:**
- Confirmación del **diagnóstico/presupuesto** de su moto (qué se hará y costo estimado).
- **Recordatorio de cuota por vencer** / "te toca pagar esta cuota" (puede incluir el QR de pago).
- Aviso de **mora** (cuota vencida).
- (Opcional) confirmación de venta/boleta.

> SMTP reutiliza el servidor de tecnoweb del proyecto anterior: `mail.tecnoweb.org.bo:25`, remitente `grupo02sa@tecnoweb.org.bo`, sin TLS/auth. En Laravel se hace con **Mailables nativos** (el QR va como imagen inline) — no hay que portar el socket Java.

**Canal IN-APP (sencillo) — alertas operativas internas:**
- **Stock bajo** (al almacenero).
- **Solicitud de repuesto pendiente** (al almacenero).
- **Pedido por aprobar** (al vendedor).

> In-app = tabla `notificacion` + badge en el navbar. Simple, sin tiempo real.

---

## 7. Esquema de base de datos (desde 0)

```
── CU1 Usuarios ──
users   : id, nombre, apellidos, ci(unique), telefono, direccion, email(unique,null), password,
          profile_photo_path, estado(bool), fecha_nacimiento, role_id→roles
          ↑ tabla de Jetstream CONSERVADA (ya integrada con login/registro/perfil). `ci` = carnet personal.
roles   : id, nombre, descripcion   (admin|vendedor|almacenero|mecanico|cliente)
cliente : id(=users.id, cascade), nit_ci      (sin "tipo de cliente"; el mayoreo lo decide la cantidad por compra)

── CU2 Productos / CU5 Inventario ──
producto : id, codigo(unique), nombre, marca, modelo, descripcion,
           precio_venta_base(>0), precio_mayorista(>0), cantidad_minima_mayorista(>=1) def 1,
           foto_url, activo
           ↑ umbral mayorista POR PRODUCTO (no global): el precio_mayorista aplica
             cuando la cantidad de esa línea ≥ cantidad_minima_mayorista del producto
inventario : id, producto_id→producto, stock_actual(>=0), stock_minimo(>=0),
             tecnica_inventario[PERMANENTE|PERIODICO], tecnica_costo[PEPS|UEPS|PROMEDIO], fecha_actualizacion
movimiento_inventario : id, inventario_id→inventario, tipo_movimiento[INGRESO|EGRESO], cantidad(>0), motivo, fecha

── CU3 Compras ──
proveedor : id, razon_social, contacto_principal, nit, telefono, activo     (NO usuario)
compra : id, proveedor_id→proveedor, fecha, total(>0), estado[PENDIENTE|RECIBIDA|ANULADA]
detalle_compra : id, compra_id→compra(cascade), producto_id→producto, cantidad(>0), precio_unitario(>0)

── CU4 Pedidos ──
pedido : id, cliente_id→cliente, fecha,
         estado[SOLICITADO|APROBADO|RECHAZADO|EN_PROCESO|DESPACHADO|ANULADO],
         motivo_rechazo(null), venta_id→venta(null)
detalle_pedido : id, pedido_id→pedido(cascade), producto_id→producto, cantidad(>0)

── CU6 Ventas ──
venta : id, numero_venta(unique), cliente_id→cliente, vendedor_id→users, fecha, monto_total(>0),
        tipo_venta[CONTADO|CREDITO], metodo_pago[EFECTIVO|QR],
        estado[COMPLETADA|PENDIENTE|ANULADA] def PENDIENTE,
        + pagofacil(transaction_id, qr_image, status, payment_number, raw_response)
detalle_venta : id, venta_id→venta(cascade), producto_id→producto(NULL para servicios), descripcion(null),
                cantidad(>0), precio_unitario(>0)
                ↑ línea = repuesto (producto_id) o servicio/mano de obra (descripcion). Mano de obra del taller va así.

── CU7 Créditos y Pagos ──
credito : id, venta_id→venta(unique), numero_cuotas(>=2), tasa_interes, saldo_pendiente(>=0),
          estado[VIGENTE|PAGADO|MOROSO]
pago_cuota : id, credito_id→credito, numero_cuota, monto_cuota(>0), fecha_vencimiento,
             fecha_pago(null), mora def 0, estado[PENDIENTE|PAGADO|VENCIDO], + pagofacil(...)
metodos_pago : id, nombre   (catálogo: EFECTIVO, QR). Cada venta/pago guarda el método usado (REQ10, sin puente por usuario)

── CU9 Taller ──
moto : id, cliente_id→cliente, placa, marca, modelo, anio
orden_trabajo : id, cliente_id→cliente, moto_id→moto, fecha_ingreso, descripcion_problema,
                diagnostico, fecha_diagnostico, costo_estimado_mano_obra, costo_estimado_repuestos,
                presupuesto_aprobado(bool), costo_mano_obra, venta_id→venta(null),
                estado[RECIBIDA|DIAGNOSTICADA|EN_REPARACION|TERMINADA|ENTREGADA|CANCELADA]
detalle_orden : id, orden_trabajo_id→orden_trabajo(cascade), producto_id→producto, cantidad(>0),
                estado[SOLICITADO|APROBADO|RECHAZADO|ENTREGADO], motivo(null)

── Soporte y requisitos del docente ──
configuracion : id, clave, valor, descripcion     (admin edita: ej. tasa_mora_diaria, tasa_interes_credito)
notificacion  : id, usuario_id→users, tipo, mensaje, recurso, leido(bool), fecha   (in-app, sección 6.1)
menu_item     : id, etiqueta, ruta_laravel, icono, orden, parent_id, role_id→roles
bitacora      : id, usuario_id→users(null), email, accion[LOGIN_OK|LOGIN_FAIL|ACCESO_RECURSO],
                recurso, ip, user_agent, fecha
page_visits   : ruta(unique), contador     (se recrea en el set limpio; estructura reutilizada)
```

---

## 8. Páginas Vue por módulo (referencia)

```
resources/js/Pages/
├── Auth/            # Login, Register (Jetstream)
├── Profile/         # Perfil propio (Jetstream — todos los roles)
├── Dashboard/       # Estadísticas
├── Usuarios/        # Index, Create, Edit, Show
├── Productos/       # Index, Create, Edit, Show (2 precios + foto)
├── Proveedores/     # Index, Create, Edit, Show
├── Compras/         # Index, Create, Show
├── Pedidos/         # Index, Create, Show (aprobar/rechazar)
├── Inventario/      # Index, Movimientos, Alertas
├── Ventas/          # Index, Create (contado/crédito), Show
├── Creditos/        # Index, Show, MisCuotas (cliente)
├── Taller/          # Ordenes (Index/Create/Show), Diagnostico, SolicitudRepuestos
├── MiCuenta/        # Panel unificado del cliente (refuerzo C)
├── Reportes/        # Index + reportes PDF
├── Bitacora/        # Index (solo admin)
└── Configuracion/   # Parámetros (solo admin)
```

---

## 9. Requisitos del docente — cobertura

| REQ | Cómo se cumple |
|---|---|
| 1. Diseño y navegación | Header con logo + buscador + usuario + tema; menú dinámico; footer con visitas; breadcrumbs; paginación |
| 2. ≥2 roles de negocio + menú dinámico BD | 4 roles de negocio (vendedor, almacenero, mecánico, cliente) + admin; `menu_item` filtrado por rol |
| 3. MVC-MVVM | Laravel + Inertia + Vue (heredado de la base) |
| 4. Control de acceso + bitácora | Middleware `role` + Policies; tabla `bitacora` (LOGIN_OK/FAIL/ACCESO_RECURSO) |
| 5. 3 temas + accesibilidad | Temas Niños/Jóvenes/Adultos, día/noche, tamaño de letra, contraste (composable `useTheme`) |
| 6. Validación en español | Form Requests con `messages()` + validación Vue |
| 7. Contador de visitas | Middleware `TrackPageVisits` + `page_visits` en footer |
| 8. Estadísticas | Dashboard Chart.js + reportes PDF |
| 9. Búsqueda global | Campo en el **encabezado de la página principal** (`useSearch`) que busca **información del negocio**: productos (nombre/código/marca/modelo), clientes, pedidos y órdenes de taller. Ruta `GET /buscar?q=...` |
| 10. Pagos electrónicos | Catálogo de **métodos de pago** (EFECTIVO, QR) y el método queda registrado en cada venta/pago + **pagos únicos** (venta contado) + **plan de pagos** (cuotas de crédito). PagoFácil QR para venta al contado y pago de cuotas |

> ⚠️ **No asumir:** los transversales heredados de Tienda Elena (3 temas + día/noche + tamaño/contraste de REQ5, búsqueda de REQ9, contador de visitas de REQ7) fueron hechos para el dominio anterior. Se **verifican y re-adaptan** al llegar a ellos, no se dan por funcionando.

---

## 10. Fuera de alcance (por ahora)

- **Pago con tarjeta** — solo EFECTIVO y QR (PagoFácil).
- **Notificaciones en tiempo real** (websockets/push) — las notificaciones in-app son por recarga/badge simple; los avisos importantes van por email (ver 6.1).
- Colas de trabajo (queues) asíncronas.
- Taller "completo" (agenda de mecánicos, asignación por carga, control de tiempos) — solo el flujo enfocado de la sección CU9.
- Integración con pasarela real distinta de PagoFácil.

---

## 11. Plan de trabajo propuesto (cuando se apruebe el alcance)

1. Actualizar `CLAUDE.md` con este dominio y los 5 roles.
2. Limpiar migraciones viejas de Tienda Elena (conservando las de Jetstream/auth).
3. Escribir el set limpio de migraciones (sección 7) + seeders (roles, admin, menú por rol, configuración).
4. `migrate:fresh --seed` y verificar.
5. Backend por CU: Models → Services → Policies → Form Requests → Controllers.
6. Frontend Vue por CU (sección 8).
7. Transversales: bitácora, temas, búsqueda del negocio, footer de visitas, dashboard.
8. PagoFácil conectado al flujo de venta/cuota.

---

## 12. Entrega y despliegue (datos oficiales del docente)

| Dato | Valor |
|---|---|
| Materia / Proyecto | INF-513 Tecnología Web — Proyecto Final (Proyecto 2) |
| Grupo | grupo02sa |
| Arquitectura exigida | Sitio web + base de datos, **tres capas**, **MVC-MVVM (Laravel-Inertia-Vue)** |
| Roles | Mínimo 2 roles de negocio (**administrador no cuenta**) + menú dinámico desde BD |
| Archivo de entrega | `2026-1_INF513-P2_grupo02sa.tar.gz` (comprimido, con **documentación + código fuente**) |
| GitHub Classroom (obligatorio) | https://classroom.github.com/a/LZscIDqi |
| Sitio de pruebas/presentación | https://www.tecnoweb.org.bo/inf513/grupo02sa/proyecto2 |
| Base de datos | PostgreSQL — **solo se crea desde los laboratorios** (cuenta de usuario del gestor provista) |
| Fecha de entrega | Último día de clases 1-2026, 07:00–20:00 |

> ⚠️ **Implicación de despliegue:** la BD de producción se crea desde los labs con la cuenta PostgreSQL del grupo. Las migraciones y seeders deben correr ahí; cuidar credenciales y nombre de BD del entorno de tecnoweb (distinto al local).

---

## 13. Activos reutilizables del proyecto anterior

Ubicación: `D:\Universidad\tecno\mailgroup02sa\mailgroup02sa` (proyecto Java POP3/SMTP del mismo grupo). **Es el mismo negocio RAO MOTOS**, ya probado, en consola de email. Nuestro proyecto web es su evolución.

| Activo | Dónde está | Cómo lo reutilizamos |
|---|---|---|
| **Esquema de BD** | `database_schema.sql` | Coincide con nuestro diseño (sección 7). Lo portamos a migraciones Laravel; nuestra versión agrega taller, `precio_mayorista`, `vendedor_id`, estados de aprobación de pedido |
| **Datos semilla** | `seed_data.sql`, `backup_grupo02.sql` | **15 productos reales** (repuestos con código/marca/precio), 2 proveedores, 5 clientes, el equipo. Adaptar a seeders Laravel agregando `precio_mayorista`, `cantidad_minima_mayorista`, `vendedor_id`, bcrypt, y usuarios demo por rol. Convertir ventas `TARJETA`→`EFECTIVO/QR` |
| **Lógica de cuotas/mora** | `negocio/pagos/PagoCuotaService.java` | Generación de cuotas, registro y confirmación de pago, cierre de crédito → portar a `CreditService`/`PaymentService` en PHP |
| **PagoFácil QR** | `negocio/pagos/PagoFacilService.java` + `Credentials.txt` | Lógica de generación de QR. Reescribir en PHP usando estas credenciales (las válidas del grupo) |
| **SMTP** | `presentacion/email/ClienteSMTP.java`, `.env` | Solo referencia: en Laravel usamos Mailables. Servidor: `mail.tecnoweb.org.bo:25`, remitente `grupo02sa@tecnoweb.org.bo` |
| **Flujos documentados** | `flujos.md`, `README.md` | Referencia de los flujos; **ver sección 14** para lo que estaba mal y corregimos |

### Conexiones y credenciales (tecnoweb)
- **PostgreSQL (producción):** `mail.tecnoweb.org.bo:5432`, BD `db_grupo02sa` (solo se crea desde los labs).
- **SMTP:** `mail.tecnoweb.org.bo:25`, usuario `grupo02sa`, remitente `grupo02sa@tecnoweb.org.bo`, sin TLS/auth.
- **PagoFácil:** `COMMERCE_ID`, `TOKEN_SERVICE`, `TOKEN_SECRET` reales (test mode) en `Credentials.txt`/`.env` del proyecto **mailgroup02sa**.

> ✅ **Credenciales válidas = las del proyecto de email (`mailgroup02sa`).** Las que trae hoy el `.env` de `tienda_elena` (`PAGOFACIL_TC_TOKEN_SERVICE`, BD `tienda_elena`, etc.) son del **semestre anterior / otro grupo** → se **descartan**. Las credenciales sensibles **no se copian a este documento**; viven en `.env` (que no debe subirse al entregable público).

### Plan de datos iniciales (seeders)

- **Roles:** admin, vendedor, almacenero, mecanico, cliente.
- **Usuarios:** los 3 del equipo (Carlos, Fabio, Reymar) como **admin** (password bcrypt); **1 demo por rol** operativo (vendedor, almacenero, mecánico); los **5 clientes** reales (solo `nit_ci`).
- **Configuración:** los parámetros de la sección 5.1 con sus defaults.
- **Productos:** los **15 reales**. Precio mayorista y umbral por **regla automática**:
  - `precio_mayorista ≈ precio_venta_base × 0.88` (≈12% menos).
  - `cantidad_minima_mayorista` por valor del repuesto: base `< 100 Bs → 20` · `100–200 Bs → 8` · `> 200 Bs → 3`.
- **Proveedores:** los 2 reales.
- **Inventario:** un registro por producto (PERMANENTE / PROMEDIO) con stock y `stock_minimo`; movimientos derivados de las compras.
- **Ventas/créditos/cuotas:** adaptar los reales — `TARJETA`→`EFECTIVO/QR`, agregar `vendedor_id`, totales **recalculados** desde el detalle.
- **Taller:** 1–2 motos + órdenes de trabajo de ejemplo para demo.
- **Menú:** ítems por rol.

---

## 14. Qué estaba mal en el proyecto anterior y cómo lo corregimos

El proyecto de email funcionaba, pero era **manual, con roles pobres y poca automatización**: todo se disparaba con comandos de email tecleados a mano, muchos datos que el sistema debería calcular se ingresaban por parámetro, y el estado del negocio no se mantenía solo. Estas son las correcciones (cada una etiquetada L#):

| # | Problema en el anterior | Cómo lo resolvemos |
|---|---|---|
| L1 | **Roles pobres**: solo `PROPIETARIO` y `CLIENTE`; el propietario hacía TODO. Sin separación de funciones | 5 roles con Policies + menú dinámico + matriz de acceso (secciones 2–3) |
| L2 | **Montos tecleados a mano**: `compra.total` y `venta.monto` se pasaban como parámetro y podían no cuadrar con el detalle (ej. real: Compra #1 dice `total=12500` pero el detalle suma `12430`) | Totales **calculados en el servidor** desde las líneas (RN11) |
| L3 | **Registro de cliente en 2 pasos** (crear usuario y luego `UPDATECLIENTE` para nit/tipo) | Registro **atómico** usuario + cliente en una transacción (RN14) |
| L4 | **Compra**: detalles agregados después y total manual; anular no tocaba inventario | Compra con detalle en un formulario, total automático, RECIBIDA → ingreso auto; anular revierte (RN11, RN15) |
| L5 | **Pedido sin aprobación ni cobro**: despachar solo bajaba stock, sin venta ni regla mayorista | Pedido con aprobación (regla mayorista) → genera venta (refuerzo D) → cobro real |
| L6 | **Anulaciones no revertían inventario** → stock inconsistente | Anular revierte los movimientos (o se restringe tras RECIBIDA/DESPACHADO) (RN15) |
| L7 | **Crédito manual**: cuotas, interés y fechas se tecleaban por venta | Interés desde `configuracion`, cuotas y calendario **auto-generados**, monto desde el detalle (RN7-base, RN11) |
| L8 | **Mora NO automática**: una cuota solo se marcaba VENCIDA al intentar pagarla; el crédito quedaba VIGENTE para siempre | **Tarea programada diaria** marca vencidas, calcula mora, pone MOROSO y avisa por email (RN12) |
| L9 | **Pago QR manual y fuera de la BD**: confirmar era a mano; las transacciones QR se guardaban en un **archivo JSON** | **Callback de PagoFácil** confirma automático; transacción en la **BD** (RN13) |
| L10 | **Inconsistencias código-esquema** (ej. crédito en estado `CANCELADO` que el CHECK no permite) | Estados consistentes y validados + transacciones de BD |
| L11 | **Sin trazabilidad de quién vende** | `venta.vendedor_id` + bitácora de accesos/recursos (REQ4) |

> **Principio rector:** el sistema **calcula y mantiene** el estado; el usuario no lo teclea ni lo dispara a mano.
> Robustez = transacciones de BD + tareas programadas (scheduler) + validación dual (Form Request + Vue).

---

> **Para revisar:** anota junto a cada número (CU, RN, L#, refuerzo, tabla) lo que quieras aumentar, quitar o reforzar, y lo ajustamos antes de tocar código.
