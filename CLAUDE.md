# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project — RAO MOTOS

A **motorcycle-parts store + repair workshop (taller)** with cash and **credit/installment (cuotas)** sales. Built for INF-513 Tecnología Web, Proyecto 2, grupo02sa. The repo started as the previous-semester *Tienda Elena* (clothing e-commerce); its technical scaffolding (Laravel + Jetstream + Inertia + Vue) was kept and the **entire business domain was rebuilt** as RAO MOTOS.

> **`ALCANCE.md` (repo root) is the FROZEN source of truth for scope** — 5 roles, 9 use cases (incl. Taller), the DB schema, 19 business rules (RN1–RN19), configurable params with defaults, and (§14) what was wrong in the prior project and how it's fixed. **Read it before any domain work.**

The prior **Java email project** (`D:\Universidad\tecno\mailgroup02sa\mailgroup02sa`) is the *same* RAO MOTOS business, already validated — reference for cuota/mora logic (`PagoCuotaService.java`), seed data, and the real tecnoweb/PagoFácil credentials. `PagoFacilService.php` (kept in `app/Services/`) is the working PHP PagoFácil integration to adapt in the payments phase.

Spanish is the domain language — model names, columns, UI strings, and validation messages are in Spanish.

## Current status (what's built)

- **Data layer: done & verified.** 22 clean migrations (the RAO schema), 23 Eloquent models, 7 seeders. `migrate:fresh --seed` runs green and is verified against the DB (5 roles, 11 users, 15 real products + inventario, etc.).
- **Project cleaned & rebranded to RAO MOTOS.** All old Tienda Elena controllers/services/requests/Vue-pages/views were deleted. `routes/web.php` is minimal, `HandleInertiaRequests` is clean, `AppLayout.vue` was rewritten. README/composer/package/`APP_NAME` rebranded.
- **CU1 — Usuarios (done):** `UserController` resource CRUD under `role:admin`, `Store/UpdateUsuarioRequest`, `Usuarios/{Index,Create,Edit,Show}.vue`, role-aware `Dashboard.vue`.
- **CU2 — Productos (done):** `ProductoController` resource CRUD under `role:admin,almacenero`, `Store/UpdateProductoRequest` (foto upload, 2 prices, `cantidad_minima_mayorista`, `stock_minimo`), auto-creates the `inventario` row on store, **logical delete** (`activo=false`), `Productos/{Index,Create,Edit,Show}.vue`.
- **Next:** CU3 (proveedores + compras), then inventario (CU5), pedidos, ventas, créditos, taller, reportes, bitácora, configuración, plus transversales (themes REQ5, search REQ9, bitácora REQ4, visit counter REQ7) and PagoFácil.

## Working style

Scope is frozen (ALCANCE.md), and the user granted autonomy to **implement without per-step confirmation** — proceed, and only pause for genuine business decisions or ambiguities not settled in ALCANCE. **The user handles all git commits himself — do not commit.** Favor automation + DB transactions + scheduled jobs over manual steps (ALCANCE §14). Build each CU end-to-end (backend + Vue) and verify (`npm run build` + in-process smoke) before reporting.

## Dev environment & commands (this machine)

**PHP is NOT on PATH for tooling.** Use Herd's binaries:
- PHP 8.4 (has `pdo_pgsql`): `C:\Users\fabio\.config\herd\bin\php84\php.exe`
- Composer: `C:\Users\fabio\.config\herd\bin\composer.phar`
- psql: `C:\Program Files\PostgreSQL\18\bin\psql.exe`

Run artisan as `& "<php84>" artisan <cmd>` from the project dir (PowerShell). `npm`/`node` (v24) *are* on PATH.

**DB:** PostgreSQL **18 on port 5432**, database **`rao_motos`**, user `postgres`, password in `.env` (`1234`). (PG14 is on 5433.) Production DB is created only from the campus labs (`db_grupo02sa` on tecnoweb); deploy target `https://www.tecnoweb.org.bo/inf513/grupo02sa/proyecto2`.

```
# rebuild DB
& "<php84>" artisan migrate:fresh --seed
& "<php84>" artisan storage:link        # done; needed for product images at /storage/...
# frontend
npm install ; npm run build             # or `npm run dev` for HMR
# format / backup
vendor\bin\pint  ;  & "<php84>" artisan backup:db
```

**Serving the app:** `php artisan serve` **fails to bind a socket** on this machine (and inside the agent sandbox). Use **Herd**: `herd link rao-motos` → **http://rao-motos.test**. To verify HTTP from the agent (no socket), boot the app in-process: require `bootstrap/app.php`, then `$kernel->handle(Request::create('/path','GET'))` and read `->getStatusCode()`.

It is **Laravel 10** (`composer.json` pins `^10.10`, classic `app/Http/Kernel.php`) — don't use Laravel 11-only APIs.

## Architecture & the per-CU pattern

Three layers: **Route → Controller → (Service) → Eloquent Model**, with a **Form Request** (Spanish `messages()`) for validation. Controllers stay thin and render Inertia pages via `Inertia::render('<Resource>/Index')`.

**Models** (`app/Models/`, 23): all set `protected $table` to the singular Spanish name (e.g. `Producto`→`producto`, `DetalleVenta`→`detalle_venta`). `Cliente` is a 1:1 subtable of `users` (`$incrementing=false`, PK = `users.id`). Relations + casts are defined; `Producto::precioPara($cant)`, `Inventario::bajoMinimo()`, `Configuracion::valor($clave,$default)` are helpers.

**Established CRUD pattern** (follow CU1 `UserController` / CU2 `ProductoController` for new resources):
- `Route::resource('<plural>', XController::class)->parameters(['<plural>' => '<singular>'])` inside a `role:...` middleware group in `routes/web.php`.
- `index`: `->with(...)` eager load, `ilike` search on a `q` param, `paginate(12)->withQueryString()`.
- `store`/`update`: `DB::transaction`, validate via `Store/Update<Recurso>Request`. File uploads use `$request->file('foto')->store('<dir>','public')` and store the relative path; on the **Vue side, updates with a file use `form.transform(d => ({...d, _method:'put'})).post(...)`** (PHP doesn't parse multipart on PUT).
- `destroy`: prefer **logical delete** (`activo=false`) for entities referenced by FKs (productos, etc.); guard against self-deletion (usuarios).
- Vue: 4 files `Pages/<Resource>/{Index,Create,Edit,Show}.vue`, each wrapped in `<AppLayout title="...">`, Bootstrap-styled, using `useForm` and `form.errors` for inline validation. Flash shown from `$page.props.flash`.

## Authorization (current state + plan)

Single role per user (`users.role_id` → `roles`; **not** many-to-many). Roles: `admin`, `vendedor`, `almacenero`, `mecanico`, `cliente`.
- **Enforcement today:** route-level `role` middleware (`app/Http/Middleware/RoleMiddleware.php`, alias in `app/Http/Kernel.php`, checks `User::tieneRol()`), plus **`admin` is a superuser** via `Gate::before` in `AuthServiceProvider`. No per-resource Policies exist yet.
- **`HandleInertiaRequests`** shares `auth.user` (with `rol`), `menuItems` (role-filtered nav from `menu_items`), and `flash`. (Per-resource `auth.permissions` was removed during cleanup; reintroduce with Policies as CUs need finer UI gating.)
- **Dynamic sidebar:** `AppLayout.vue` renders `menuItems` but **only items whose route already exists** (`route().has(...)`), so a module appears in the menu automatically once you add its route (its `menu_items` row is already seeded per role in `MenuItemSeeder`). Add a CU → its route + the seeded menu entry → it shows for the right roles.

## Cross-cutting & docente requirements (mostly TODO)

`menu_items` (dynamic menu, REQ2) and `bitacora`/`page_visits`/`notificacion`/`configuracion` tables exist (seeded where relevant). Still to build: **bitácora** logging (REQ4), **page-visit counter** in the footer (REQ7), **3 themes + day/night + font/contrast** (REQ5), **global business search** in the header (REQ9), stats/dashboard (REQ8), and **notifications** (email/SMTP for client events, in-app for operational alerts — ALCANCE §6.1). The old Tienda Elena transversales were deleted; build these fresh for RAO.

## Key business rules (see ALCANCE.md for all RN1–RN19)

- Totals (venta/compra) are **computed server-side** from line items — never typed (RN11).
- **Stock decremented exactly once**, where goods physically leave (direct sale, sale generated from a pedido, or taller parts-request approval); a taller-generated venta does **not** re-decrement (RN18).
- **Wholesale price is per line, per product threshold** (`producto.cantidad_minima_mayorista`), for any client — there is **no client tier** (RN3/RN19). Use `Producto::precioPara($cantidad)`.
- **Mora** = daily-proportional on the overdue cuota, capped, run by a **daily scheduler** that marks overdue cuotas and sets credits MOROSO (RN12/RN16).
- Every configurable parameter (interest, mora rate, cap, cuota interval) lives in `configuracion` with a seeded **default** (RN17).
- Money is bolivianos (Bs.). Eager-load to avoid N+1.
