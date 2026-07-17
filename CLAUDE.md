# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project — RAO MOTOS

A **motorcycle-parts store** with cash and **credit/installment (cuotas)** sales, plus **electronic QR payment (PagoFácil)**. Built for INF-513 Tecnología Web, Proyecto 2, grupo02sa. The repo started as the previous-semester *Tienda Elena* (clothing e-commerce); its technical scaffolding (Laravel + Jetstream + Inertia + Vue) was kept and the **entire business domain was rebuilt** as RAO MOTOS.

> **`ALCANCE.md` (repo root) is the source of truth for scope** — 4 roles, the use cases (CU1–CU8 + despachos), the DB schema, business rules **RN1–RN24**, configurable params with defaults, and (§14) what was wrong in the prior project and how it's fixed. **Read it before any domain work.** `README.md` is the user-facing overview.

There is **no taller (workshop) module** — the business is 100% parts sales (contado/crédito). Notifications are **in-app only** (no email/SMTP).

The prior **Java email project** (`D:\Universidad\tecno\mailgroup02sa\mailgroup02sa`) is the *same* RAO MOTOS business, already validated — reference for cuota/mora logic and seed data. `PagoFacilService.php` (`app/Services/`) is the working PHP PagoFácil integration.

Spanish is the domain language — model names, columns, UI strings, and validation messages are in Spanish.

## Current status (what's built)

**Everything is implemented and verified** (backend + Vue, `migrate:fresh --seed` green, `npm run build` green, smoke-tested):

- **Data layer:** 30 migrations (RAO schema), 23 Eloquent models (22 + `ModeloBase`), 8 seeders. Seeded demo: 4 roles, 10 usuarios (3 admin, 1 vendedor, 1 almacenero, 5 clientes), 15 real products + inventario, 2 proveedores.
- **All CUs:** usuarios (CU1), productos con galería/carrusel (CU2), proveedores + compras (CU3), inventario (CU5), ventas con flujo pedido→venta→despacho y estado `PAGADA` (CU6), créditos + scheduler de mora (CU7), pedidos + catálogo (CU4), despachos (almacén), dashboard + reportes PDF (CU8).
- **Transversales:** bitácora (REQ4), contador de visitas (REQ7), 3 temas + día/noche + accesibilidad (REQ5), búsqueda global (REQ9), notificaciones in-app, menú dinámico por rol (REQ2), PagoFácil QR (venta contado, pedido aprobado, cuotas).
- **Recent additions:** stock validation on sale/pedido-approval (RN24, `InventarioService::verificarStock`), auto price recalculation on purchase receipt (RN23, `CompraController::recibir`), user profile photos (Jetstream `profilePhotos` enabled).

**Known pending:** PagoFácil live QR depends on the campus network + real credentials. (In-app notifications, profile photos and their links now work — an earlier "notifications" symptom was really the `APP_URL`/Herd-host mismatch, see the serving note.)

## Working style

The user granted autonomy to **implement without per-step confirmation** — proceed, and only pause for genuine business decisions or ambiguities not settled in ALCANCE. **The user handles all git commits himself — do not commit.** Favor automation + DB transactions + scheduled jobs over manual steps (ALCANCE §14). Build each change end-to-end (backend + Vue) and verify (`npm run build` + in-process/tinker smoke) before reporting. When ALCANCE and the code diverge, report it rather than assuming; keep ALCANCE/README in sync with the code.

## Dev environment & commands (this machine)

**PHP is NOT on PATH for tooling.** Use Herd's binaries:
- PHP 8.4 (has `pdo_pgsql`): `C:\Users\fabio\.config\herd\bin\php84\php.exe`
- Composer: `C:\Users\fabio\.config\herd\bin\composer.phar`
- psql: `C:\Program Files\PostgreSQL\18\bin\psql.exe`

Run artisan as `& "<php84>" artisan <cmd>` from the project dir (PowerShell). `npm`/`node` (v24) *are* on PATH.

**DB:** PostgreSQL **18 on port 5432**, database **`rao_motos`**, user `postgres`, password in `.env`. (PG14 is on 5433.) Production DB is created only from the campus labs (on tecnoweb); deploy target `https://www.tecnoweb.org.bo/inf513/grupo02sa/proyecto2`.

```
# rebuild DB (demo data)
& "<php84>" artisan migrate:fresh --seed
& "<php84>" artisan storage:link        # needed for product images & profile photos at /storage/...
# scheduled mora job (run manually)
& "<php84>" artisan creditos:marcar-vencidas
# frontend
npm install ; npm run build             # or `npm run dev` for HMR
# format / backup
vendor\bin\pint  ;  & "<php84>" artisan backup:db
```

**Serving the app:** `php artisan serve` **fails to bind a socket** on this machine (and inside the agent sandbox). Use **Herd**: this project is linked as **`rao_motos`** (underscore) → **http://rao_motos.test**, and `APP_URL` in `.env` must match it exactly. (Note: a stale Herd link `rao-motos` with a hyphen points to the old *tienda_elena* project — do not use `rao-motos.test`; the underscore/hyphen mismatch breaks absolute URLs like profile photos and notification links.) To verify from the agent without a socket, use `artisan tinker --execute="..."` or boot the app in-process.

It is **Laravel 10** (`composer.json` pins `^10.10`, classic `app/Http/Kernel.php`) — don't use Laravel 11-only APIs.

## Architecture & the per-CU pattern

Three layers: **Route → Controller → (Service) → Eloquent Model**, with a **Form Request** (Spanish `messages()`) for validation. Controllers stay thin and render Inertia pages via `Inertia::render('<Resource>/Index')`.

**Models** (`app/Models/`, 23): all set `protected $table` to the singular Spanish name (e.g. `Producto`→`producto`, `DetalleVenta`→`detalle_venta`). `Cliente` is a 1:1 subtable of `usuario` (PK = `usuario.id`), exposed via `Cliente::usuario()`. Helpers: `Producto::precioPara($cant)`, `Inventario::bajoMinimo()`, `Configuracion::valor($clave,$default)`, `Notificacion::paraRol($rol,$tipo,$msg,$recurso)`.

**Everything is in Spanish — including timestamps.** See **ALCANCE §12.1** for the full convention and the exhaustive list of what stays English. The load-bearing points:
- **`ModeloBase`** (`app/Models/ModeloBase.php`) declares `CREATED_AT = 'creado_en'` / `UPDATED_AT = 'actualizado_en'`. **Every domain model must extend it** instead of `Model` — a new model that extends `Model` silently gets `created_at` and breaks on insert. `Usuario` is the one exception (must extend `Authenticatable` for Fortify) and repeats the constants inline. A trait does *not* work here: `Eloquent\Model` already defines those constants and PHP rejects the collision.
- **`->latest()` / `->oldest()` with no argument default to `created_at`** and will blow up. Always pass `'creado_en'`.
- Login is by **`correo`** (`config/fortify.php` `'username' => 'correo'`); the `config/auth.php` provider is `usuarios`. Full name is **`Usuario::nombre_completo`** (not `name`), which is why `Usuario` overrides Jetstream's `defaultProfilePhotoUrl()`.
- Inertia shares the user as **`auth.usuario`** and the menu as **`itemsMenu`**.
- Kept English (do not "fix"): `password`, `profile_photo_path`/`profile_photo_url`, `current_team_id`, `two_factor_*`, the `remember` login field, and the `sessions`/`failed_jobs`/`password_reset_tokens`/`personal_access_tokens` tables.
- **PagoFácil:** our columns are Spanish (`pago_facil_id_transaccion`, …) but the API payload keys (`transactionId`, `paymentNumber`, `email`, …) are the external contract — never translate them.

**Services** (`app/Services/`): `VentaService` (creates ventas from mostrador or pedido, one pipeline), `CreditoService` (cuota calendar + mora), `InventarioService` (all stock movement + `verificarStock`), `PagoFacilService` (QR), `VisitaPaginaService`.

**Established CRUD pattern** (follow `UserController` / `ProductoController`):
- `Route::resource('<plural>', XController::class)->parameters(['<plural>' => '<singular>'])` inside a `rol:...` middleware group in `routes/web.php`.
- `index`: `->with(...)` eager load, `ilike` search on a `q` param, `paginate(12)->withQueryString()`.
- `store`/`update`: `DB::transaction`, validate via `Store/Update<Recurso>Request`. File uploads use `$request->file('foto')->store('<dir>','public')` and store the relative path; on the **Vue side, updates with a file use `form.transform(d => ({...d, _method:'put'})).post(...)`** (PHP doesn't parse multipart on PUT).
- `destroy`: prefer **logical delete** (`activo=false`) for entities referenced by FKs; guard against self-deletion (usuarios).
- Vue: `Pages/<Resource>/{Index,Create,Edit,Show}.vue`, each wrapped in `<AppLayout title="...">`, Bootstrap-styled, using `useForm` and `form.errors`. Flash shown from `$page.props.flash` (`success`/`error`) via `FlashNotification.vue`.

## Authorization

Single role per user (`usuario.rol_id` → `rol`; **not** many-to-many). Roles: **`admin`, `vendedor`, `almacenero`, `cliente`**.
- **Enforcement:** route-level `rol` middleware (`app/Http/Middleware/RolMiddleware.php`, alias in `app/Http/Kernel.php`, checks `Usuario::tieneRol()`), plus **`admin` is a superuser** via `Gate::before` in `AuthServiceProvider`. Note `Gate::before` covers Gates only — a route behind `rol:cliente` still 403s for admin (e.g. `/catalogo`).
- **`HandleInertiaRequests`** shares `auth.usuario` (with `rol`), `itemsMenu` (role-filtered from `item_menu`, `activo=true`), `notificaciones` (`{no_leidas, recientes}`), `visitas`, and `flash`.
- **Dynamic navbar:** `AppLayout.vue` is a top navbar with grouped dropdowns (built in JS from the role's `itemsMenu`) + responsive offcanvas + footer visit counter. Items render only when their route exists (`route().has(...)`), so a module shows up once its route + seeded `item_menu` row (per role, in `ItemMenuSeeder`) exist.
- Login is **by correo** (Fortify `username = correo`). No self-service password reset (`resetPasswords` disabled) — the admin resets a specific user's password from user management. 2FA and account deletion are available in the profile.

## Cross-cutting (all implemented)

`item_menu` (dynamic menu REQ2), `bitacora` (REQ4, LOGIN_OK/FAIL/ACCESO_RECURSO), `visita_pagina` (REQ7 footer counter via `RegistrarVisitasPagina`), `configuracion` (admin-editable params), `notificacion` (in-app, badge + dropdown). Themes (REQ5) via `useTheme.js` + `ThemeSwitcher.vue`. Global search (REQ9) at `GET /buscar` (business data + role functionalities, accent/case-insensitive via `unaccent`). Dashboard Chart.js + PDF reports (REQ8, dompdf).

**Notifications are in-app only** (types: `STOCK_BAJO`, `PEDIDO_POR_APROBAR`, `VENTA_PAGADA`, `PEDIDO_APROBADO`, `PEDIDO_RECHAZADO`, `PEDIDO_DESPACHADO`, `MORA`). No email/SMTP.

## Key business rules (see ALCANCE.md for all RN1–RN24)

- Totals (venta/compra) are **computed server-side** from line items — never typed (RN11).
- **Stock decremented exactly once**, where goods physically leave: direct sale (at creation) or dispatch of a pedido-originated sale (at despacho by the almacenero) (RN18). Sales/pedido-approval **validate stock first** and fail with a clear message if short (RN24, `InventarioService::verificarStock`).
- **Wholesale price is per line, per product threshold** (`producto.cantidad_minima_mayorista`), for any client — there is **no client tier** (RN3/RN19). Use `Producto::precioPara($cantidad)`.
- **Prices recalc on purchase receipt:** when a compra is RECIBIDA, each product's `precio_venta_base`/`precio_mayorista` = `costo × (1 + margen/100)`, with `margen_venta_minorista`/`margen_venta_mayorista` config params (RN23).
- **Payment happens before dispatch;** vendedor cobra, almacenero despacha (RN20/RN21). Venta states `PENDIENTE → PAGADA → COMPLETADA` (or `ANULADA`).
- **Mora** = daily-proportional on the overdue cuota, capped, run by a **daily scheduler** (`creditos:marcar-vencidas`) that marks overdue cuotas, sets credits MOROSO, and posts an in-app MORA notification (RN12/RN16).
- Every configurable parameter lives in `configuracion` with a seeded **default** (RN17).
- Money is bolivianos (Bs.). Eager-load to avoid N+1.
