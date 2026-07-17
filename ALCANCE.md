# ALCANCE — RAO MOTOS

> Sistema de Ventas al Contado y al **Crédito de Repuestos de Moto**
> Proyecto 2 — INF-513 Tecnología Web · Grupo02sa · FICCT, UAGRM · Gestión 2026
>
> **Estado:** documento de alcance vigente. Cada sección está numerada para poder referenciarla.
> Base técnica: Laravel 10 + Inertia + Vue 3 + Bootstrap 5 + Jetstream + PostgreSQL.

---

## 1. El negocio

RAO MOTOS es una empresa que **vende repuestos de moto** — al por menor (minorista) y al por mayor
(mayorista, con precio por volumen). Las ventas se pueden pagar **al contado o en cuotas (crédito)**.
El tema central del proyecto es el **crédito/cuotas** y el **pago electrónico (QR PagoFácil)**.

---

## 2. Actores y roles

Cuatro roles. Tres son **roles de negocio** (cumplen el mínimo del docente, que descarta al admin como
rol de negocio). El perfil propio (ver datos, cambiar contraseña, foto) lo puede editar **cualquier**
usuario autenticado.

| Rol | ¿Entra al sistema? | Qué hace |
|---|---|---|
| **Admin** (propietario) | Sí | Superusuario: puede todo. Gestiona usuarios, ve reportes globales y bitácora, configura parámetros. Único que ve el **Dashboard** de estadísticas |
| **Vendedor** | Sí | Ventas (contado/crédito), **aprueba/rechaza pedidos** (sin elegir método), **confirma el cobro en efectivo**, cobra cuotas y gestiona mora |
| **Almacenero** | Sí | Compras a proveedores, gestión de proveedores, inventario y movimientos, alta de productos, **despacha las ventas pagadas** (logística) |
| **Cliente** | Sí | Catálogo, hace pedidos, **elige cómo pagar su pedido aprobado (QR o efectivo)**, paga sus cuotas (QR), panel "Mi cuenta" |

> **Proveedor NO es un rol.** No entra al sistema. Es solo un dato (a quién se le compran repuestos),
> referenciado por las compras.

### 2.1 Decisión de diseño — herencia por rol

- `usuario` es la tabla base (la de Jetstream renombrada al español, sin romper el login). Tiene `rol_id`.
- Solo el **cliente** tiene una subtabla 1:1 (`cliente`) con su dato de facturación (`nit_ci`). **No hay
  "tipo de cliente"**: un cliente es solo cliente; que una compra sea mayorista lo decide el sistema por
  la cantidad (RN3), no una etiqueta.
- Admin, vendedor y almacenero se distinguen solo por `rol_id` (no necesitan datos extra).
- **Registro público** añade el campo `direccion` (opcional) además de los datos personales.

---

## 3. Matriz de permisos por módulo

| Módulo | Admin | Vendedor | Almacenero | Cliente |
|---|---|---|---|---|
| CU1 Usuarios | CRUD | — | — | perfil propio |
| CU2 Productos | ver | ver | **CRUD** | catálogo |
| CU3 Compras / Proveedores | ver | — | **CRUD** | — |
| CU4 Pedidos | ver | **aprobar/rechazar + cobrar** | — | crear + ver propios + **pagar por QR** |
| CU5 Inventario | ver | — | **CRUD + movimientos** | — |
| CU6 Ventas | ver todas | **crear + cobrar + ver** | — | ver propias |
| Despachos (almacén) | ver | — | **preparar + despachar** | — |
| CU7 Créditos / Cobranza | ver | **registrar pagos / mora** | — | pagar propias (QR) |
| CU8 Reportes | todos | ventas | inventario / compras | — |
| Dashboard (estadísticas) | **sí** | — | — | — |
| Bitácora | ver | — | — | — |
| Configuración | CRUD | — | — | — |

> **Dashboard solo admin:** los demás roles entran directo a su módulo principal (vendedor→Ventas,
> almacenero→Inventario, cliente→Catálogo).

---

## 4. Casos de uso (CU)

### CU1 — Gestión de usuarios
**Registro público:** cualquier persona puede crearse una cuenta, y por defecto queda como **cliente**
(se crea su fila en `cliente` automáticamente). Admin hace CRUD de usuarios y asigna otros roles
(vendedor, almacenero). Cualquier usuario cambia su propio perfil y contraseña desde su panel. El reseteo
de contraseña lo hace el **admin**: desde la gestión de usuarios asigna una nueva contraseña a un usuario
específico (no hay autoservicio de "olvidé mi contraseña"). Validación por tipo de campo en el formulario
de usuario (CI/teléfono numéricos, nombres solo letras) + campo `direccion`.

### CU2 — Gestión de productos (repuestos)
Almacenero hace CRUD. Cada producto define **dos precios** y su **umbral de mayoreo propio**:
`precio_venta_base` (minorista), `precio_mayorista` (por volumen) y `cantidad_minima_mayorista` (a partir
de cuántas unidades aplica el precio mayorista). **Múltiples imágenes por producto** (`foto_url` portada +
tabla `producto_imagen` para la galería), que se muestran como **carrusel** en el catálogo y el detalle.
Código único. Eliminación **lógica** (`activo=false`). Los dos precios se editan a mano y además se
**recalculan automáticamente al recibir una compra**, a partir del costo y los márgenes configurables (RN23).

### CU3 — Compras y proveedores
Almacenero registra proveedores y compras. Una compra tiene detalle (productos, cantidad, precio unitario)
y estado `PENDIENTE → RECIBIDA → ANULADA`. Al recibir una compra se actualiza el inventario (ingreso) y
se **recalcula el precio de venta** de cada producto desde su costo de compra (RN23).

### CU4 — Pedidos (con regla mayorista) y separación de roles
El cliente arma un pedido. El **vendedor** lo revisa y **aprueba o rechaza**; el **almacenero** despacha.

- El precio mayorista se aplica **por línea y para cualquier cliente**: si la cantidad alcanza la
  `cantidad_minima_mayorista` **de ese producto** → `precio_mayorista`; si no → `precio_venta_base`.
- Al **aprobar** se **genera la venta en estado `PENDIENTE`** (sin descontar stock). El **vendedor NO
  elige el método de pago** — solo aprueba o rechaza.
- **El cliente elige cómo pagar** (desde *Mis pedidos → detalle*). El pago ocurre **antes** del despacho:
  - **QR** → paga al instante por PagoFácil → la venta pasa a `PAGADA`.
  - **Efectivo** → paga en la tienda; el **vendedor** confirma el cobro ("Marcar pagado") → `PAGADA`.
- Cuando la venta queda **`PAGADA`** se **notifica al almacén** ("lista para despachar").
- El **almacenero**, desde su módulo **Despachos** (su cola de trabajo), despacha la venta pagada →
  descuenta stock (RN18), la venta pasa a `COMPLETADA` y el pedido a `DESPACHADO`; se **notifica al cliente**.
- El cliente recibe **notificaciones in-app** en cada transición (aprobado / rechazado / despachado) que
  **enlazan al detalle** del pedido.
- Estados del pedido (enum en BD): `SOLICITADO`, `APROBADO`, `RECHAZADO`, `EN_PROCESO`, `DESPACHADO`,
  `ANULADO`. Flujo principal: `SOLICITADO → APROBADO/RECHAZADO → DESPACHADO`.

### CU5 — Inventario
Cada producto tiene un registro de inventario con `stock_actual` y `stock_minimo`, técnica de inventario
(PERMANENTE/PERIODICO) y de costo (PEPS/UEPS/PROMEDIO). Cada entrada/salida queda en
`movimiento_inventario`. Cuando `stock_actual < stock_minimo`, se dispara **alerta de compra** al
almacenero (refuerzo A). Tras aplicar un ajuste, se vuelve al listado de inventario.

### CU6 — Ventas
Venta al contado o a crédito. Origen: **mostrador** (venta directa) o **pedido aprobado**. Calcula el
precio **por línea** según la cantidad vs el umbral del producto (minorista vs mayorista), igual para
cualquier cliente. Reduce stock **una sola vez** (RN18) y **no permite vender más que el stock disponible**
(RN24). Método de pago: **EFECTIVO** (físico) o **QR**
(PagoFácil). *(Tarjeta queda fuera de alcance.)* En una venta a **crédito** el vendedor **NO elige el
método de pago** — el cliente decide cómo paga **cada cuota** (QR o efectivo en caja).

**Estados de la venta:** `PENDIENTE → PAGADA → COMPLETADA` (o `ANULADA`).
- **Venta desde pedido:** `PENDIENTE` (sin stock) → cobro (efectivo/QR) → `PAGADA` (avisa al almacén) →
  el almacenero la despacha desde **Despachos** → descuenta stock y pasa a `COMPLETADA`.
- **Venta directa de mostrador:** descuenta stock al crearse. Contado en efectivo → `COMPLETADA` directo;
  contado con QR → cobra → `COMPLETADA`; a crédito → `COMPLETADA` y genera el crédito.
- En la lista de ventas se puede **filtrar por estado** (TODAS/PENDIENTE/PAGADA/COMPLETADA/ANULADA). Para
  ventas a **crédito** se muestra el **estado del crédito** con el avance de cuotas (p. ej. "Crédito
  vigente · 1/3"), no "COMPLETADA".

**Despachos (módulo del almacén):** el **almacenero** ve la **cola de ventas `PAGADA`** listas para
preparar; abre el detalle (productos, cantidades, stock) y **despacha** → descuenta stock (RN18), venta
`COMPLETADA`, pedido `DESPACHADO`, aviso al cliente. Es la contraparte logística del cobro del vendedor
(RN21).

### CU7 — Créditos y pagos
Una venta a crédito genera un `credito` con `numero_cuotas` (mínimo 2), tasa de interés y un calendario de
`pago_cuota`. El cliente paga cada cuota (QR PagoFácil) o el vendedor la cobra en caja (efectivo). Estados
del crédito: `VIGENTE → PAGADO / MOROSO`. El vendedor gestiona la cobranza.

**Interés vs. mora (dos cosas distintas):**
- **Interés del crédito** (`credito.tasa_interes`): costo de financiar; infla el saldo al inicio →
  `saldo_pendiente = monto_total + monto_total × tasa_interes/100`. Es **por crédito**; el default de
  `configuracion` es solo la sugerencia inicial, editable en cada venta.
- **Mora** (`pago_cuota.mora`): penalización por pagar **tarde**, calculada **por días de retraso** sobre
  la cuota vencida — no por producto ni cantidad.

**Modelo de mora — diaria proporcional con tope:**
```
mora = monto_cuota × (tasa_mora_diaria / 100) × días_de_retraso
       (limitada a un tope: máx. tope_mora_pct % de la cuota)
```
La **tarea programada diaria** (RN12) detecta cuotas vencidas, calcula la mora, marca la cuota `VENCIDO`,
pone el crédito en `MOROSO` y genera la notificación in-app de mora al cliente. Todas las tasas son
**configurables por el admin** y tienen **valor por defecto** (sección 5.1).

### CU8 — Reportes y estadísticas
**Dashboard (solo admin)** con gráficas (Chart.js, adaptadas a modo día/noche) y reportes PDF. Ventas por
mes (contado vs crédito), top productos, créditos vigentes vs morosos, inventario crítico. El cliente ve
sus compras (contado/crédito) en "Mi cuenta".

---

## 5. Reglas de negocio

| # | Regla |
|---|---|
| RN1 | Admin es superusuario: salta todas las policies (`Gate::before`). |
| RN2 | Cualquier usuario edita su propio perfil y contraseña, sin importar el rol. |
| RN3 | Precio **por línea, para cualquier cliente**: si cantidad ≥ `producto.cantidad_minima_mayorista` → `precio_mayorista`; si no → `precio_venta_base`. **No hay tipo de cliente**; el mayoreo lo decide la cantidad. |
| RN4 | El **vendedor** solo aprueba/rechaza el pedido (no elige el método de pago); el precio mayorista se aplica línea por línea según el umbral de cada producto. **El cliente** elige pagar por QR o efectivo. |
| RN8 | Venta a crédito → mínimo 2 cuotas; se calcula mora por atraso. |
| RN9 | Stock por debajo del mínimo → alerta de compra al almacenero. |
| RN10 | El **umbral mayorista se define por producto** (lo fija el almacenero), no como un valor global. |
| RN11 | Los **totales** (venta, compra) los **calcula el servidor** desde el detalle; el usuario nunca teclea el monto. |
| RN12 | Una **tarea programada diaria** marca cuotas vencidas, calcula mora y actualiza el estado del crédito (MOROSO). |
| RN13 | El pago por **QR se confirma por callback de PagoFácil** y la transacción se guarda en la **BD** (no en archivos). El webhook exige un identificador (payment_number/transaction_id) antes de marcar nada pagado. |
| RN14 | El registro de un cliente es **atómico**: usuario + fila `cliente` en una sola transacción. |
| RN15 | **Anular** una compra/venta ya procesada **revierte** sus movimientos de inventario (o se restringe la anulación). |
| RN16 | **Mora = diaria proporcional sobre la cuota vencida**, con tope: `monto_cuota × (tasa_mora_diaria/100) × días_retraso`, máx. `tope_mora_pct%` de la cuota. |
| RN17 | **Todo parámetro configurable tiene un valor por defecto sembrado**; si el admin no lo cambia, el sistema funciona igual. |
| RN18 | El **stock se descuenta exactamente una vez**, donde la mercadería sale físicamente: en la **venta directa** (al crearse) o en el **despacho** de una venta originada en pedido (al despacharla el almacenero). |
| RN19 | **No existe "tipo de cliente"**: el cliente es solo cliente; mayorista/minorista se decide por compra según la cantidad (RN3). |
| RN20 | **El pago ocurre ANTES del despacho.** Una venta de pedido no se despacha si no está `PAGADA`. |
| RN21 | **Separación de funciones en el pedido:** el **vendedor** aprueba y cobra (comercial), el **almacenero** despacha (logística). Ningún rol hace ambos pasos. |
| RN22 | Cambios de estado del pedido/venta **notifican in-app**: al almacén cuando una venta queda `PAGADA`; al cliente al aprobar/rechazar/despachar su pedido (con enlace al detalle). |
| RN23 | Al **recibir una compra** (`RECIBIDA`), el precio de venta de cada producto de la compra se **recalcula desde su costo**: `precio = costo × (1 + margen/100)`, con margen minorista y mayorista configurables (5.1). |
| RN24 | Una **venta directa** o la **aprobación de un pedido** no procede si no hay **stock suficiente**; se valida antes de comprometer la operación y se muestra el detalle de lo que falta. |

### 5.1 Parámetros configurables (tabla `configuracion`, editables por el admin)

| Clave | Significado | Valor por defecto |
|---|---|---|
| `tasa_interes_credito` | Interés por defecto al financiar una venta a crédito (%) | **5.00** |
| `tasa_mora_diaria` | Mora por día de retraso sobre la cuota vencida (%) | **0.50** |
| `tope_mora_pct` | Tope máximo de mora como % de la cuota | **20** |
| `dias_entre_cuotas` | Días entre vencimientos de cuotas consecutivas | **30** |
| `dias_aviso_cuota` | Días de anticipación para avisar al cliente de una cuota por vencer | **3** |
| `margen_venta_minorista` | Margen (%) sobre el costo de compra para el precio de venta minorista | **25** |
| `margen_venta_mayorista` | Margen (%) sobre el costo de compra para el precio de venta mayorista | **15** |

---

## 6. Refuerzos de interacción entre actores

- **A. Alerta de stock bajo → compra** — Inventario → Almacenero → Proveedor. Cierra el ciclo CU5↔CU3.
- **C. Panel "Mi cuenta" del cliente** — vista única: sus **compras** (contado/crédito con sus productos),
  sus pedidos y sus cuotas.
- **D. Aprobar pedido → genera venta** — Cliente → Vendedor → Venta, sin recapturar datos.
- **E. Separación cobro/despacho** — Vendedor cobra → Almacenero despacha, con avisos in-app entre ellos.

### 6.1 Notificaciones (in-app)

Las notificaciones son **únicamente in-app** (tabla `notificacion` + badge en el navbar, clic → enlace al recurso):
- **Stock bajo** (al almacenero).
- **Pedido por aprobar** (al vendedor).
- **Venta pagada, lista para despachar** (al almacenero).
- **Pedido aprobado / rechazado / despachado** (al cliente).
- **Cuota vencida / mora** (al cliente): la genera la tarea programada diaria al marcar el crédito MOROSO.

---

## 7. Esquema de base de datos

```
── CU1 Usuarios ──
usuario : id, nombre, apellidos, ci(unique), telefono, direccion, correo(unique,null), password,
          profile_photo_path, estado(bool), fecha_nacimiento, rol_id→rol
          ↑ tabla de Jetstream RENOMBRADA a `usuario` (login/registro/perfil siguen intactos).
            `ci` = carnet personal.
rol     : id, nombre, descripcion   (admin|vendedor|almacenero|cliente)
cliente : id(=usuario.id, cascade), nit_ci    (sin "tipo de cliente"; el mayoreo lo decide la cantidad)

── CU2 Productos / CU5 Inventario ──
producto : id, codigo(unique), nombre, marca, modelo, descripcion,
           precio_venta_base(>0), precio_mayorista(>0), cantidad_minima_mayorista(>=1) def 1,
           foto_url (portada), activo
producto_imagen : id, producto_id→producto(cascade), ruta, orden   (galería para el carrusel)
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
venta : id, numero_venta(unique), cliente_id→cliente, vendedor_id→usuario, fecha, monto_total(>0),
        tipo_venta[CONTADO|CREDITO], metodo_pago[EFECTIVO|QR],
        estado[PENDIENTE|PAGADA|COMPLETADA|ANULADA] def PENDIENTE,
        + pago_facil_(id_transaccion, imagen_qr, estado, numero_pago, respuesta_cruda)
detalle_venta : id, venta_id→venta(cascade), producto_id→producto(null), descripcion(null),
                cantidad(>0), precio_unitario(>0)

── CU7 Créditos y Pagos ──
credito : id, venta_id→venta(unique), numero_cuotas(>=2), tasa_interes, saldo_pendiente(>=0),
          estado[VIGENTE|PAGADO|MOROSO]
pago_cuota : id, credito_id→credito, numero_cuota, monto_cuota(>0), fecha_vencimiento,
             fecha_pago(null), mora def 0, estado[PENDIENTE|PAGADO|VENCIDO],
             metodo_pago_id→metodo_pago(null),
             + pago_facil_(id_transaccion, numero_pago, imagen_qr, expira_en, estado, respuesta_cruda)
metodo_pago : id, nombre(unique), activo(bool)   (catálogo: EFECTIVO, QR)

── Soporte y requisitos del docente ──
configuracion : id, clave, valor, descripcion     (admin edita: tasa_mora_diaria, tasa_interes_credito, ...)
notificacion  : id, usuario_id→usuario, tipo, mensaje, recurso, leido(bool), fecha   (in-app, sección 6.1)
item_menu     : id, etiqueta, ruta_laravel, icono, orden, padre_id→item_menu(null), rol_id→rol, activo(bool)
bitacora      : id, usuario_id→usuario(null), correo, accion[LOGIN_OK|LOGIN_FAIL|ACCESO_RECURSO],
                recurso, ip, agente_usuario, fecha
visita_pagina : ruta(unique), contador
```

> **Todo el esquema está en español**, incluidas las marcas de tiempo: cada tabla propia usa
> `creado_en` / `actualizado_en` en lugar de `created_at` / `updated_at` (ver §12.1).

> Extensión de PostgreSQL **`unaccent`** habilitada por migración: la búsqueda global ignora tildes.

### 12.1 Convención de nombres — español en todo el dominio

Nombres de tabla, columna, modelo, relación y variable van **en español y en singular**. Lo único que se
conserva en inglés es la fontanería de Laravel que no se puede renombrar sin romper el framework:

| Se conserva en inglés | Por qué |
|---|---|
| `password` | Contrato `Authenticatable::getAuthPassword()` de Laravel |
| `profile_photo_path`, `profile_photo_url`, `current_team_id` | Los gestiona el trait `HasProfilePhoto` de Jetstream |
| `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` | Los gestiona `TwoFactorAuthenticatable` de Fortify |
| Tablas `sessions`, `failed_jobs`, `password_reset_tokens`, `personal_access_tokens` | Tablas internas del framework/Sanctum |
| Campo `remember` del login | Fortify lo lee del request para "recordarme" |

Detalles de implementación:

- **Marcas de tiempo:** `App\Models\ModeloBase` declara `CREATED_AT = 'creado_en'` y
  `UPDATED_AT = 'actualizado_en'`; **todo modelo del dominio debe extenderlo**. `Usuario` es la única
  excepción (extiende `Authenticatable` porque lo exige Fortify) y por eso repite las constantes.
  *No sirve un trait:* `Eloquent\Model` ya define esas constantes y PHP considera el choque incompatible.
- **Login por `correo`:** `config/fortify.php` usa `'username' => 'correo'`. El *provider* de
  `config/auth.php` se llama `usuarios`.
- **`Usuario::nombre_completo`** sustituye al accesor `name` de Jetstream; por eso `Usuario` sobrescribe
  `defaultProfilePhotoUrl()`, que en el original lee `$this->name` para las iniciales del avatar.
- **PagoFácil:** las columnas propias van en español (`pago_facil_id_transaccion`, …), pero las claves del
  *payload* que viaja al API (`transactionId`, `paymentNumber`, `phoneNumber`, `email`, …) son del contrato
  externo y **no se traducen**.

---

## 8. Páginas Vue por módulo (referencia)

```
resources/js/Pages/
├── Auth/            # Login, Register, verificación 2FA (Jetstream; el reseteo de contraseña lo hace el admin)
├── Profile/         # Perfil propio: foto, datos, contraseña, 2FA, sesiones, eliminación de cuenta (Jetstream — todos los roles)
├── Dashboard.vue    # Estadísticas (solo admin)
├── Welcome.vue      # Landing público con catálogo
├── Usuarios/        # Index, Create, Edit, Show
├── Productos/       # Index, Create, Edit, Show (2 precios + galería de imágenes)
├── Proveedores/     # Index, Create, Edit, Show
├── Compras/         # Index, Create, Show
├── Pedidos/         # Index (estado + pago/despacho), Show (aprobar/rechazar con método)
├── Inventario/      # Index, Show (ajustes)
├── Ventas/          # Index (filtros por estado), Create (contado/crédito), Show (cobrar)
├── Despachos/       # Index (cola de PAGADA), Show (preparar + despachar) — almacén
├── Creditos/        # Index, Show
├── MisPedidos/      # Index, Show (pagar por QR el pedido aprobado)
├── MisCreditos/     # Index, Show (pagar cuotas)
├── MiCuenta/        # Panel unificado del cliente (refuerzo C)
├── Catalogo/        # Catálogo del cliente + carrito de pedido
├── Pagos/           # Qr.vue (pantalla genérica de pago por QR: cuota o venta)
├── Reportes/        # Index + reportes PDF
├── Notificaciones/  # Index (bandeja in-app)
├── Buscar/          # Resultados de la búsqueda global
├── Bitacora/        # Index (solo admin)
└── Configuracion/   # Parámetros (solo admin)
```

---

## 9. Requisitos del docente — cobertura

| REQ | Cómo se cumple |
|---|---|
| 1. Diseño y navegación | Topbar con logo + buscador + usuario + tema; **sidebar** con menús desplegables (mini-menú flotante); menú dinámico desde BD; footer con contador de visitas; responsive (offcanvas en móvil) |
| 2. ≥2 roles de negocio + menú dinámico BD | **3 roles de negocio** (vendedor, almacenero, cliente) + admin; `item_menu` filtrado por rol |
| 3. MVC-MVVM | Laravel + Inertia + Vue |
| 4. Control de acceso + bitácora | Middleware `role` + `Gate::before` (admin superusuario); tabla `bitacora` (LOGIN_OK/FAIL/ACCESO_RECURSO) |
| 5. 3 temas + accesibilidad | Temas Niños/Jóvenes/Adultos, día/noche, tamaño de letra, contraste (`useTheme`) |
| 6. Validación en español | Form Requests con `messages()` + validación Vue |
| 7. Contador de visitas | Middleware `RegistrarVisitasPagina` + `visita_pagina` en el footer de cada página |
| 8. Estadísticas | Dashboard Chart.js (solo admin) + reportes PDF |
| 9. Búsqueda global | Campo en el encabezado que busca **información del negocio** (productos, clientes, pedidos) **y funcionalidades** del rol (te lleva a la página); ignora tildes y mayúsculas; role-safe. Ruta `GET /buscar?q=...` |
| 10. Pagos electrónicos | Catálogo de **métodos de pago** (EFECTIVO, QR) registrado en cada venta/pago + **pago único** (venta contado) + **plan de pagos** (cuotas). **PagoFácil QR** para venta al contado, pago de pedido aprobado y pago de cuotas |

---

## 10. Fuera de alcance

- **Taller mecánico** (mecánicos, órdenes de trabajo, solicitud de repuestos): no forma parte del sistema; el negocio es 100% venta de repuestos (contado/crédito).
- **Pago con tarjeta** — solo EFECTIVO y QR (PagoFácil).
- **Notificaciones en tiempo real** (websockets/push) — las in-app son por recarga/badge simple.
- **Notificaciones por email/SMTP** — fuera de alcance; las notificaciones del sistema son únicamente in-app.
- Colas de trabajo (queues) asíncronas.
- Integración con pasarela real distinta de PagoFácil.

---

## 11. Estado de implementación (resumen)

Todos los CU y transversales están implementados y verificados (build + smoke + flujos): usuarios,
productos (con galería/carrusel), proveedores/compras, inventario, ventas (con el flujo separado
pedido→venta→despacho y estado PAGADA), créditos + scheduler de mora, pedidos + catálogo, dashboard,
reportes, configuración, bitácora, notificaciones in-app, temas, búsqueda global, contador de visitas y
PagoFácil QR (venta contado, pedido aprobado y cuotas). Incluye validación de stock en venta y aprobación
de pedidos (RN24), recálculo automático del precio de venta al recibir compras (RN23) y foto de perfil de
usuarios. Las notificaciones son únicamente in-app (el email queda fuera de alcance).

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
| Base de datos | PostgreSQL — **solo se crea desde los laboratorios** |
| Fecha de entrega | Último día de clases 1-2026, 07:00–20:00 |

> ⚠️ **Despliegue:** la BD de producción se crea desde los labs con la cuenta PostgreSQL del grupo. Correr
> `migrate --seed` ahí (incluye la extensión `unaccent`; si el usuario de BD no puede `CREATE EXTENSION`,
> resolver con el DBA del lab). Cuidar credenciales y nombre de BD del entorno de tecnoweb.

---

## 13. Activos reutilizables del proyecto anterior

Ubicación: `D:\Universidad\tecno\mailgroup02sa\mailgroup02sa` (proyecto Java POP3/SMTP del mismo grupo).
**Es el mismo negocio RAO MOTOS**, ya probado en consola de email.

| Activo | Dónde está | Cómo lo reutilizamos |
|---|---|---|
| **Datos semilla** | `seed_data.sql` | 15 productos reales, 2 proveedores, 5 clientes, el equipo → seeders Laravel |
| **Lógica de cuotas/mora** | `PagoCuotaService.java` | Portada a `CreditoService` en PHP |
| **PagoFácil QR** | `PagoFacilService.java` + credenciales | Reescrito en `PagoFacilService.php` con las credenciales válidas del grupo |

> ✅ **Credenciales válidas = las del proyecto de email (`mailgroup02sa`).** Viven en `.env` (no se suben al
> entregable público).

---

## 14. Qué estaba mal en el proyecto anterior y cómo lo corregimos

El proyecto de email funcionaba, pero era **manual, con roles pobres y poca automatización**. Correcciones:

| # | Problema en el anterior | Cómo lo resolvemos |
|---|---|---|
| L1 | **Roles pobres**: solo `PROPIETARIO` y `CLIENTE`; el propietario hacía TODO | Roles con menú dinámico + matriz de acceso, con **separación cobro (vendedor) / despacho (almacenero)** |
| L2 | **Montos tecleados a mano** que no cuadraban con el detalle | Totales **calculados en el servidor** desde las líneas (RN11) |
| L3 | **Registro de cliente en 2 pasos** | Registro **atómico** usuario + cliente (RN14) |
| L4 | **Compra**: total manual; anular no tocaba inventario | Compra con detalle, total automático, RECIBIDA → ingreso auto; anular revierte (RN11, RN15) |
| L5 | **Pedido sin aprobación ni cobro**: despachar solo bajaba stock | Pedido con aprobación (regla mayorista) → venta → **cobro antes del despacho** → despacho del almacén (RN20, RN21) |
| L6 | **Anulaciones no revertían inventario** | Anular revierte los movimientos (RN15) |
| L7 | **Crédito manual**: cuotas, interés y fechas tecleadas | Interés desde `configuracion`, cuotas y calendario **auto-generados** (RN11) |
| L8 | **Mora NO automática** | **Tarea programada diaria** marca vencidas, calcula mora, pone MOROSO (RN12) |
| L9 | **Pago QR manual y fuera de la BD** (archivo JSON) | **Callback de PagoFácil** confirma automático; transacción en la **BD** (RN13) |
| L10 | **Inconsistencias código-esquema** | Estados consistentes y validados + transacciones de BD |
| L11 | **Sin trazabilidad de quién vende** | `venta.vendedor_id` + bitácora de accesos/recursos (REQ4) |

> **Principio rector:** el sistema **calcula y mantiene** el estado; el usuario no lo teclea ni lo dispara a
> mano. Robustez = transacciones de BD + tareas programadas + validación dual (Form Request + Vue).
