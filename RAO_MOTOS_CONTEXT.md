# RAO_MOTOS_CONTEXT.md
# Instrucciones para actualizar el CLAUDE.md de este repositorio

> Eres Claude Code trabajando sobre el proyecto base "Tienda Elena".
> Este archivo contiene TODO el contexto del nuevo proyecto que reemplaza a Tienda Elena.
> Léelo completo, luego actualiza el CLAUDE.md fusionando la arquitectura real que ya
> encontraste en este repo con el dominio de negocio descrito aquí.
> No borres convenciones técnicas reales del repo — solo reemplaza el dominio/negocio.

---

## MODO DE TRABAJO — MUY IMPORTANTE

Antes de hacer cualquier cosa, informa qué vas a hacer y espera confirmación.
Sigue este patrón en cada tarea:

1. **Anuncia** qué archivo vas a tocar y por qué
2. **Muestra** el plan concreto (qué cambia, qué se agrega, qué se elimina)
3. **Espera** que el usuario diga "adelante", "sí", "ok" o similar
4. **Ejecuta** y reporta qué hiciste
5. **Pregunta** qué sigue o si hay algo que corregir

Si una tarea tiene más de 3 pasos, divídela y confirma entre pasos.
Nunca hagas más de lo que se te pidió en un turno.
Si encuentras algo inesperado en el código, para y reporta antes de continuar.

---

## NUEVO PROYECTO: Sistema de Ventas al Crédito — RAO MOTOS

### Identidad
- **Nombre:** RAO MOTOS — Sistema de Ventas al Crédito de Repuestos
- **Grupo:** Grupo02sa — INF-513 SA, FICCT, UAGRM, Gestión 2025
- **Materia:** Tecnología Web
- **URL producción:** https://www.tecnoweb.org.bo/inf513/grupo02sa/proyecto2
- **GitHub Classroom:** https://classroom.github.com/a/LZscIDqi
- **Entrega:** `2026-1_INF513-P2_grupo02sa.tar.gz`

### Equipo
| Código | Nombre | Carrera |
|---|---|---|
| 221090436 | Carlos Diego Marca Peñaranda | Ingeniería en Sistemas |
| 221043721 | Fabio Alejandro Arnez Fernández | Ingeniería en Sistemas |
| 216027438 | Reymar Loaiza Labarden | Ingeniería en Sistemas |

### Origen del proyecto base
Este repo era "Tienda Elena" (tienda de ropa). Se reutiliza íntegramente su arquitectura
técnica (Laravel 10 + Inertia + Vue 3 + Bootstrap 5 + Jetstream + PostgreSQL).
Lo que cambia es TODO el dominio de negocio: modelos, migraciones, seeders, controladores,
servicios, vistas Vue y nombres de rutas.

Hay un proyecto anterior en Java (sistema de email POP3/SMTP) llamado `ProyectoMailGrupo02sa`
ubicado en `D:\Universidad\tecno\mailgroup02sa\mailgroup02sa`. Ese proyecto tiene:
- El esquema de BD completo (15 tablas, ya probado)
- La lógica de negocio de créditos, cuotas, moras
- El módulo PagoFácil QR (`PagoFacilService.java`) — REUTILIZAR la lógica en PHP

---

## BASE DE DATOS — 15 tablas del dominio RAO MOTOS

Estas tablas REEMPLAZAN las de Tienda Elena. Crear migraciones Laravel para cada una.

```sql
-- CU1: Usuarios (herencia por rol)
usuario: id, nombre, email(unique), telefono, direccion, foto_url, password, rol[PROPIETARIO|PROVEEDOR|CLIENTE], activo(bool), fecha_reg
cliente: id→usuario(cascade), nit_ci, tipo_cliente[REGULAR|FRECUENTE|MAYORISTA]
proveedor: id→usuario(cascade), razon_social, contacto_principal
propietario: id→usuario(cascade), nivel_acceso(default TOTAL)

-- CU2: Productos
producto: id, codigo(unique), nombre, marca, modelo, descripcion, precio_venta_base(decimal>0), foto_url, activo(bool), fecha_reg

-- CU3: Compras
compra: id, proveedor_id→proveedor, fecha, total(decimal>0), estado[PENDIENTE|RECIBIDA|ANULADA]
detalle_compra: id, compra_id→compra(cascade), producto_id→producto, cantidad(int>0), precio_unitario(decimal>0)

-- CU4: Pedidos
pedido: id, cliente_id→cliente, fecha, estado[SOLICITADO|EN_PROCESO|DESPACHADO|ANULADO]
detalle_pedido: id, pedido_id→pedido(cascade), producto_id→producto, cantidad(int>0)

-- CU5: Inventario
inventario: id, producto_id→producto, stock_actual(int>=0 default 0), tecnica_inventario[PERMANENTE|PERIODICO], tecnica_costo[PEPS|UEPS|PROMEDIO], fecha_actualizacion
movimiento_inventario: id, inventario_id→inventario, tipo_movimiento[INGRESO|EGRESO], cantidad(int>0), motivo, fecha

-- CU6: Ventas
venta: id, cliente_id→cliente, fecha, monto_total(decimal>0), tipo_venta[CONTADO|CREDITO], metodo_pago[EFECTIVO|QR|TARJETA], estado[COMPLETADA|PENDIENTE|ANULADA default PENDIENTE]
detalle_venta: id, venta_id→venta(cascade), producto_id→producto, cantidad(int>0), precio_unitario(decimal>0)

-- CU7: Créditos y Pagos
credito: id, venta_id→venta(unique), numero_cuotas(int>=2), tasa_interes(decimal 5,2 default 0), saldo_pendiente(decimal>=0), estado[VIGENTE|PAGADO|MOROSO default VIGENTE]
pago_cuota: id, credito_id→credito, numero_cuota(int), monto_cuota(decimal>0), fecha_vencimiento(date), fecha_pago(date nullable), mora(decimal default 0), estado[PENDIENTE|PAGADO|VENCIDO default PENDIENTE]
```

### Tablas adicionales (requisitos del docente — NO vienen del proyecto Java)

```sql
-- Menú dinámico por rol (REQ 2)
menu_item: id, nombre, icono, ruta, padre_id→menu_item nullable, orden(int default 0), activo(bool)
  -- NOTA: esta tabla YA EXISTE en Tienda Elena como menu_items. Verificar si la estructura
  -- es compatible antes de recrearla. Adaptar si ya existe.

-- Bitácora (REQ 4)  
bitacora: id, usuario_id→usuario nullable, email, accion[LOGIN_OK|LOGIN_FAIL|ACCESO_RECURSO], recurso, ip, user_agent, fecha
  -- NOTA: Tienda Elena puede tener algo similar. Verificar antes de crear.

-- Contador de visitas por página (REQ 7)
visita_pagina: id, ruta(varchar unique), contador(bigint default 0)
  -- NOTA: Tienda Elena tiene PageVisit/page_visits. Verificar si es equivalente.
```

---

## ROLES Y ACCESO

### Roles de negocio (los 3 roles del sistema)
| Rol | Descripción | Equivalente en Tienda Elena |
|---|---|---|
| `propietario` | Dueño de RAO MOTOS, acceso total | `admin` o rol con más permisos |
| `vendedor` | Gestiona ventas, pedidos, inventario | `vendedor` — puede REUTILIZARSE |
| `cliente` | Compra repuestos, ve sus pedidos y cuotas | `cliente` — puede REUTILIZARSE |

> El docente dice "administrador no es un rol de negocio". Propietario ES el dueño, no un admin técnico.
> Si Tienda Elena ya tiene roles `vendedor` y `cliente`, reutilizarlos — solo cambiar permisos.

### Matriz de acceso por CU
| Módulo | propietario | vendedor | cliente |
|---|---|---|---|
| CU1 Usuarios | CRUD + foto | Ver | Ver propio |
| CU2 Productos | CRUD + foto | CRUD | Ver |
| CU3 Compras | CRUD | Ver | — |
| CU4 Pedidos | CRUD + despachar | CRUD | Crear + ver propios |
| CU5 Inventario | CRUD + movimientos | Ver + ingresar | Ver stock |
| CU6 Ventas | CRUD todos tipos | Crear + ver | Ver propias |
| CU7 Pagos/Créditos | Todo | Registrar pagos | Pagar cuotas propias |
| CU8 Reportes | Todos | Ventas propias | — |
| Bitácora | Ver completa | — | — |
| Dashboard estadísticas | ✓ | Parcial | — |

---

## STORAGE — CARPETAS PARA ARCHIVOS SUBIDOS

Crear estas subcarpetas dentro de `storage/app/public/` (el `storage:link` ya existe en Tienda Elena):

```
storage/app/public/
├── usuarios/        # fotos de perfil (CU1) — jpg/png, max 2MB
├── productos/       # fotos de productos (CU2) — jpg/png, max 5MB
└── qr/              # códigos QR generados por PagoFácil (CU6/CU7)
```

En las migraciones, `foto_url` guarda la ruta relativa: `usuarios/foto_123.jpg`
Acceso público via `Storage::url($foto_url)` → `storage/usuarios/foto_123.jpg`

---

## PAGOFÁCIL QR — REUTILIZAR DEL PROYECTO JAVA

El proyecto Java tiene `PagoFacilService.java` en:
`D:\Universidad\tecno\mailgroup02sa\mailgroup02sa\src\main\java\...\pagos\`

Tienda Elena ya tiene un `PagoFacilService.php` scaffoldeado (mencionado en su CLAUDE.md).
Antes de reescribir, leer ese archivo y ver qué tan completo está.

Variables de entorno necesarias (ya deben estar en `.env`):
```env
PAGOFACIL_TOKEN_SERVICE=
PAGOFACIL_TOKEN_SECRET=
PAGOFACIL_TEST_MODE=true
```

Flujo QR para ventas:
1. Cliente selecciona método de pago QR
2. `PagoFacilService::generarQR($monto, $descripcion)` → devuelve imagen QR
3. Cliente escanea y paga
4. Webhook o polling confirma el pago → actualiza `venta.estado = 'COMPLETADA'`

---

## REQUISITOS DEL DOCENTE — CHECKLIST

Marcar `[x]` cuando esté implementado. Al diagnosticar el repo, identificar cuáles
ya existen en Tienda Elena y cuáles hay que construir desde cero.

### REQ 1 — Diseño y Navegación
- [ ] Header: logo RAO MOTOS + buscador global + usuario activo + selector de tema
- [ ] Menú dinámico desde BD filtrado por rol
- [ ] Footer con contador de visitas de la página actual
- [ ] Breadcrumbs en todas las páginas de gestión
- [ ] Paginación en todas las tablas

### REQ 2 — Mínimo 2 roles de negocio + menú dinámico desde BD
- [ ] Roles: propietario, vendedor, cliente (datos en BD, no hardcodeados)
- [ ] `menu_items` tabla con ítems por rol
- [ ] Middleware filtra menú según rol del usuario autenticado

### REQ 3 — MVC-MVVM (Laravel + Inertia + Vue) ← YA EXISTE en Tienda Elena
- [x] Arquitectura Route → Controller → Service → Model
- [x] Inertia::render() en controllers
- [x] Pages Vue con defineProps()
- [ ] Verificar que no queden restos de "Tienda Elena" en vistas

### REQ 4 — Control de Acceso y Bitácora
- [ ] Middleware de roles funcionando (Tienda Elena ya tiene RoleMiddleware)
- [ ] Login exitoso → escribe en `bitacora` (accion=LOGIN_OK)
- [ ] Login fallido → escribe en `bitacora` (accion=LOGIN_FAIL)
- [ ] Cada request autenticado → escribe recurso más accedido (accion=ACCESO_RECURSO)
- [ ] Vista de bitácora para propietario

### REQ 5 — 3 Temas + Accesibilidad
- [ ] Variables CSS/Bootstrap para temas
- [ ] **Tema Niños:** colores vivos, tipografía grande y redondeada
- [ ] **Tema Jóvenes:** moderno, oscuro/vibrante  
- [ ] **Tema Adultos:** formal, neutro
- [ ] **Modo Día/Noche automático:** JS detecta hora del cliente (< 6:00 o > 20:00 → noche)
- [ ] Selector tamaño de letra (pequeño/normal/grande)
- [ ] Toggle contraste alto
- [ ] Tema persistido en localStorage
- [ ] NOTA: Tienda Elena tiene `useTheme` composable — verificar qué tan avanzado está

### REQ 6 — Validación en español
- [ ] FormRequests Laravel con `messages()` en español para todos los CU
- [ ] Validación Vue en frontend también en español
- [ ] Ningún formulario sin validación

### REQ 7 — Contador de visitas en footer
- [ ] Middleware `TrackPageVisits` (Tienda Elena ya lo tiene — verificar)
- [ ] Footer muestra `visita_pagina.contador` de la ruta actual
- [ ] Visible en TODAS las páginas

### REQ 8 — Estadísticas del negocio
- [ ] Ventas por mes (contado vs crédito, gráfica)
- [ ] Top 10 productos más vendidos
- [ ] Créditos vigentes vs morosos
- [ ] Clientes por tipo (REGULAR/FRECUENTE/MAYORISTA)
- [ ] Moras pendientes con montos
- [ ] Dashboard con gráficas (Chart.js — Tienda Elena puede tenerlo)

### REQ 9 — Búsqueda global en header
- [ ] Campo de texto en header de página principal
- [ ] Busca en: productos (nombre, código, marca), clientes, pedidos
- [ ] Ruta: `GET /buscar?q=...`
- [ ] NOTA: Tienda Elena tiene `useSearch` composable — verificar

### REQ 10 — Pagos electrónicos
- [ ] Registro de métodos de pago por usuario
- [ ] Pago único contado (EFECTIVO, QR, TARJETA)
- [ ] Plan de pagos crédito (mínimo 2 cuotas)
- [ ] PagoFácil QR integrado (o simulado en modo test)
- [ ] Registro de cada pago en `pago_cuota`

---

## CASOS DE USO — PÁGINAS VUE NECESARIAS

Estructura de Pages/ que debe quedar (reemplaza las de Tienda Elena):

```
resources/js/Pages/
├── Auth/                    # Login, Register (Jetstream — mantener)
├── Dashboard/               # Index.vue (estadísticas propietario)
├── Usuarios/                # Index, Create, Edit, Show (con foto upload)
├── Productos/               # Index, Create, Edit, Show (con foto upload)
├── Compras/                 # Index, Create, Show
├── Pedidos/                 # Index, Create, Show
├── Inventario/              # Index, Movimientos
├── Ventas/                  # Index, Create (contado/crédito), Show
├── Pagos/                   # Index (mis cuotas), Pagar
├── Reportes/                # Index, VentasMes, Moras, Productos
└── Bitacora/                # Index (solo propietario)
```

---

## CONVENCIONES A MANTENER DEL PROYECTO BASE

Estas convenciones vienen de Tienda Elena y SE MANTIENEN:

- Arquitectura: Route → Controller → Service → Model → Policy
- Autorización data-driven: permisos en `menu_items`, Policies consultan esa tabla
- `HandleInertiaRequests` comparte: `auth.permissions`, `menuItems`, `flash`, `pageVisits`
- Gate en Vue: `$page.props.auth.permissions?.<resource>?.<action>` (solo UI, backend es la autoridad)
- Eager loading con `with()` para evitar N+1
- FormRequests para validación (Store*/Update* por recurso)
- Moneda en bolivianos (Bs.)
- 4 archivos Vue por recurso: Index/Create/Edit/Show

---

## INSTRUCCIÓN FINAL PARA CLAUDE CODE

Después de leer este archivo, el flujo esperado es:

1. Reporta qué encontraste en Tienda Elena que se puede reutilizar directamente
2. Reporta qué hay que crear desde cero
3. Reporta qué hay que modificar
4. Propón el orden de trabajo
5. Espera aprobación antes de tocar cualquier archivo

No empieces a escribir código sin confirmar el plan con el usuario.
