# RAO MOTOS

**Sistema de venta de repuestos de moto — al contado y al crédito (cuotas), con pago electrónico por QR (PagoFácil).**

Proyecto 2 · **INF-513 Tecnología Web** · Grupo02sa · FICCT, UAGRM · Gestión 2026.
Arquitectura de tres capas **MVC / MVVM** con **Laravel + Inertia + Vue**.

> El alcance completo, las reglas de negocio (RN1–RN24) y el esquema de datos están en **[`ALCANCE.md`](ALCANCE.md)** (fuente de verdad del proyecto).

---

## Tabla de contenido

1. [El negocio](#1-el-negocio)
2. [Stack tecnológico](#2-stack-tecnológico)
3. [Roles y permisos](#3-roles-y-permisos)
4. [Funcionalidades (casos de uso)](#4-funcionalidades-casos-de-uso)
5. [Reglas de negocio clave](#5-reglas-de-negocio-clave)
6. [Parámetros configurables](#6-parámetros-configurables)
7. [Requisitos del docente](#7-requisitos-del-docente)
8. [Instalación y puesta en marcha](#8-instalación-y-puesta-en-marcha)
9. [Comandos útiles](#9-comandos-útiles)
10. [Usuarios de demostración](#10-usuarios-de-demostración)
11. [Estructura del proyecto](#11-estructura-del-proyecto)
12. [Despliegue](#12-despliegue)

---

## 1. El negocio

RAO MOTOS vende repuestos de moto **al por menor (minorista)** y **al por mayor (mayorista, con precio por volumen)**. Las ventas se pagan **al contado** o **en cuotas (crédito)**. El foco del proyecto es el **crédito/cuotas** (con interés y mora automática) y el **pago electrónico por QR (PagoFácil)**.

El flujo comercial separa funciones: el **cliente** hace pedidos, el **vendedor** los aprueba y cobra, y el **almacenero** despacha. El **pago ocurre antes del despacho**.

---

## 2. Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | **Laravel 10** (PHP 8.4) |
| Autenticación | Jetstream + Fortify (login por **correo**, 2FA opcional) |
| Puente SPA | **Inertia.js** |
| Frontend | **Vue 3** + **Bootstrap 5** (SPA, responsive) |
| Build | Vite |
| Base de datos | **PostgreSQL 18** (extensión `unaccent` para búsqueda sin tildes) |
| Gráficas | Chart.js · **Reportes PDF** con dompdf |
| Pago electrónico | **PagoFácil** (QR) |

---

## 3. Roles y permisos

Cuatro roles (un rol por usuario). Tres son **de negocio**; el admin es superusuario.

| Rol | Qué hace |
|---|---|
| **Admin** (propietario) | Superusuario. Gestiona usuarios, ve reportes globales y bitácora, configura parámetros. Único con **Dashboard** de estadísticas. |
| **Vendedor** | Ventas (contado/crédito), **aprueba/rechaza pedidos**, **confirma cobros en efectivo**, cobra cuotas y gestiona mora. |
| **Almacenero** | Compras, proveedores, inventario y movimientos, alta de productos, **despacha las ventas pagadas**. |
| **Cliente** | Catálogo, hace pedidos, **elige cómo pagar** (QR o efectivo), paga sus cuotas por QR, panel "Mi cuenta". |

> **Proveedor NO es un rol** — es solo un dato (a quién se le compran repuestos). No hay "tipo de cliente": que una venta sea mayorista lo decide la **cantidad** por línea, no una etiqueta.

Control de acceso: middleware `role` + `Gate::before` (admin superusuario) + **menú dinámico desde BD** filtrado por rol.

---

## 4. Funcionalidades (casos de uso)

- **CU1 — Usuarios:** registro público (queda como cliente), CRUD del admin, perfil propio (datos, contraseña, **foto**, 2FA). El reseteo de contraseña lo hace el admin.
- **CU2 — Productos:** CRUD del almacenero, dos precios (minorista/mayorista) + umbral de mayoreo por producto, **galería de imágenes con carrusel**, eliminación lógica. Los precios se **recalculan automáticamente al recibir una compra**.
- **CU3 — Compras y proveedores:** compra con detalle, estados `PENDIENTE → RECIBIDA → ANULADA`; al recibir ingresa inventario y actualiza precios de venta.
- **CU4 — Pedidos:** el cliente arma el pedido; el vendedor aprueba/rechaza (genera la venta); el cliente elige pago; el almacenero despacha.
- **CU5 — Inventario:** stock, mínimos, técnicas de inventario/costo, movimientos y **alerta de stock bajo**.
- **CU6 — Ventas:** mostrador o desde pedido; contado o crédito; estados `PENDIENTE → PAGADA → COMPLETADA` (o `ANULADA`); **no permite vender más que el stock disponible**.
- **CU7 — Créditos y cuotas:** genera calendario de cuotas con interés; pago por QR o efectivo; **mora diaria automática** por tarea programada.
- **CU8 — Reportes y estadísticas:** Dashboard con gráficas (solo admin) + reportes PDF.
- **Despachos:** cola del almacén para preparar y entregar ventas pagadas.
- **Transversales:** bitácora, contador de visitas, **3 temas** + día/noche + accesibilidad, **búsqueda global**, **notificaciones in-app**.

---

## 5. Reglas de negocio clave

- **RN3/RN19 — Precio por línea, sin tipo de cliente:** si la cantidad ≥ `cantidad_minima_mayorista` del producto → precio mayorista; si no, minorista.
- **RN11 — Totales calculados en el servidor** desde el detalle; nunca se teclean.
- **RN12/RN16 — Mora diaria automática:** `monto_cuota × (tasa_mora_diaria/100) × días_retraso`, con tope; tarea programada diaria marca cuotas vencidas y pone el crédito MOROSO.
- **RN18 — El stock se descuenta una sola vez**, donde la mercadería sale físicamente (venta directa al crearse, o despacho de una venta de pedido).
- **RN20/RN21 — El pago ocurre antes del despacho** y separa funciones: el vendedor cobra, el almacenero despacha.
- **RN23 — Recálculo de precio al recibir compra:** `precio = costo × (1 + margen/100)` para base y mayorista.
- **RN24 — No se vende ni se aprueba un pedido sin stock suficiente:** se valida antes y se muestra el detalle de lo que falta.

Lista completa en [`ALCANCE.md` §5](ALCANCE.md).

---

## 6. Parámetros configurables

Editables por el admin (tabla `configuracion`); todos con valor por defecto sembrado.

| Clave | Significado | Default |
|---|---|---|
| `tasa_interes_credito` | Interés por defecto al financiar una venta a crédito (%) | 5.00 |
| `tasa_mora_diaria` | Mora por día de retraso sobre la cuota vencida (%) | 0.50 |
| `tope_mora_pct` | Tope máximo de mora como % de la cuota | 20 |
| `dias_entre_cuotas` | Días entre vencimientos de cuotas | 30 |
| `dias_aviso_cuota` | Días de anticipación para avisar de una cuota por vencer | 3 |
| `margen_venta_minorista` | Margen (%) sobre el costo para el precio minorista | 25 |
| `margen_venta_mayorista` | Margen (%) sobre el costo para el precio mayorista | 15 |

---

## 7. Requisitos del docente

| REQ | Cobertura |
|---|---|
| 1. Diseño y navegación | Topbar (logo, buscador, usuario, tema), menús dinámicos, footer con contador de visitas, responsive |
| 2. ≥2 roles de negocio + menú dinámico BD | 3 roles de negocio + admin; `item_menu` filtrado por rol |
| 3. MVC-MVVM | Laravel + Inertia + Vue |
| 4. Control de acceso + bitácora | Middleware `rol` + `Gate::before`; tabla `bitacora` (LOGIN_OK/FAIL/ACCESO_RECURSO) |
| 5. 3 temas + accesibilidad | Temas Niños/Jóvenes/Adultos, día/noche, tamaño de letra, contraste |
| 6. Validación en español | Form Requests con `messages()` + validación en Vue |
| 7. Contador de visitas | Middleware `RegistrarVisitasPagina` + `visita_pagina` en el footer |
| 8. Estadísticas | Dashboard Chart.js (solo admin) + reportes PDF |
| 9. Búsqueda global | Busca información del negocio y funcionalidades del rol; ignora tildes/mayúsculas; `GET /buscar?q=...` |
| 10. Pagos electrónicos | Métodos EFECTIVO/QR + pago único (contado) + plan de pagos (cuotas); **PagoFácil QR** |

---

## 8. Instalación y puesta en marcha

### Requisitos
- PHP **8.4** con `pdo_pgsql`
- Composer
- Node.js **24** + npm
- PostgreSQL **18**

> **En la máquina de desarrollo del grupo**, PHP no está en el PATH; se usan los binarios de **Herd**:
> - PHP 8.4: `C:\Users\fabio\.config\herd\bin\php84\php.exe`
> - Composer: `C:\Users\fabio\.config\herd\bin\composer.phar`
> - psql: `C:\Program Files\PostgreSQL\18\bin\psql.exe`
>
> Ejecuta artisan como: `& "<php84>" artisan <comando>`

### Pasos

```bash
# 1. Dependencias
composer install
npm install

# 2. Entorno
cp .env.example .env
php artisan key:generate
#   Configura en .env: DB_CONNECTION=pgsql, DB_DATABASE=rao_motos, DB_USERNAME, DB_PASSWORD

# 3. Base de datos (crea el esquema y datos de demo)
php artisan migrate:fresh --seed

# 4. Enlace de almacenamiento (imágenes de productos y fotos de perfil)
php artisan storage:link

# 5. Compilar frontend
npm run build          # o `npm run dev` para desarrollo con HMR
```

### Servir la aplicación
Recomendado con **Herd**: el proyecto se sirve en **http://rao_motos.test** (con guion **bajo** `_`). `APP_URL` en `.env` debe coincidir exactamente con ese host, o las URLs absolutas (fotos de perfil, enlaces de notificaciones) se rompen.

### Tarea programada (mora automática)
Para que la mora se calcule sola, el scheduler de Laravel debe estar activo (en producción, un cron cada minuto que ejecute `php artisan schedule:run`). Manualmente:

```bash
php artisan creditos:marcar-vencidas
```

---

## 9. Comandos útiles

```bash
php artisan migrate:fresh --seed     # reconstruir BD + datos demo
php artisan db:seed                   # solo sembrar
php artisan creditos:marcar-vencidas  # correr la mora manualmente
php artisan backup:db                 # respaldo de la BD
vendor\bin\pint                       # formatear código PHP
npm run build                         # compilar frontend
npm run dev                           # frontend con HMR
```

---

## 10. Usuarios de demostración

Inicio de sesión **por correo electrónico**. Contraseñas de los datos sembrados:

| Rol | Correo | Contraseña |
|---|---|---|
| Admin | `fabioarnez200@gmail.com` | `admin123` |
| Admin | `marcacarlosestudio@gmail.com` | `admin123` |
| Admin | `loaizalabardenreymar@gmail.com` | `admin123` |
| Vendedor | `vendedor@raomotos.com` | `demo123` |
| Almacenero | `almacenero@raomotos.com` | `demo123` |
| Cliente | `juan.perez@email.com` | `cliente123` |
| Cliente | `maria.flores@email.com` | `cliente123` |

> También se siembran los clientes `pedro.gutierrez@email.com`, `ana.rodriguez@email.com` y `luis.vargas@email.com` (todos con `cliente123`), 15 productos reales con inventario y 2 proveedores.

---

## 11. Estructura del proyecto

```
app/
├── Console/Commands/     # MarcarCuotasVencidas, BackupDatabase
├── Http/Controllers/     # 1 controlador por módulo (Producto, Venta, Compra, Pedido, Despacho, Credito, ...)
├── Http/Requests/        # Form Requests con validación en español
├── Models/               # 22 modelos Eloquent (tablas singulares en español)
└── Services/             # VentaService, CreditoService, InventarioService, PagoFacilService, PageVisitService
database/
├── migrations/           # esquema RAO
└── seeders/              # roles, usuarios, productos, proveedores, configuración, menús, métodos de pago
resources/js/
├── Layouts/AppLayout.vue # navbar + menú dinámico + notificaciones + footer
├── Pages/                # páginas Vue por módulo (Inertia)
├── Components/           # componentes compartidos (ThemeSwitcher, FlashNotification, ...)
└── composables/useTheme.js
routes/web.php            # rutas agrupadas por middleware de rol
ALCANCE.md                # alcance, reglas de negocio y esquema (fuente de verdad)
```

---

## 12. Despliegue

- **Objetivo:** `https://www.tecnoweb.org.bo/inf513/grupo02sa/proyecto2`
- La **base de datos de producción se crea desde los laboratorios del campus** con la cuenta PostgreSQL del grupo.
- Correr `php artisan migrate --seed` en el servidor (incluye la extensión `unaccent`; si el usuario de BD no puede `CREATE EXTENSION`, coordinar con el DBA del laboratorio).
- Las credenciales y el nombre de la BD del entorno de tecnoweb van en `.env` (no se suben al entregable público).

### Dirección del sitio (sin `/public`)

El **`index.php` está en la raíz del proyecto** (no en `public/`), así que el sitio se
abre en `.../proyecto2` y no en `.../proyecto2/public`. El `.htaccess` de la raíz
encamina los archivos estáticos (`/build`, `/img`, `/storage`) hacia `public/`, manda
el resto a ese `index.php` y **bloquea por HTTP lo que no debe verse** (`.env`, `app/`,
`vendor/`, `storage/`…), porque al servir desde la raíz esas carpetas quedan al alcance
del navegador. Requiere `mod_rewrite` y `AllowOverride All` (ambos activos en tecnoweb).

`public/index.php` sigue existiendo pero solo llama al de la raíz, para que el entorno
local (Herd, que sirve `public/`) funcione sin cambios.

**`APP_URL` debe ser la dirección real y completa**, incluida la subcarpeta:

```
APP_URL=https://www.tecnoweb.org.bo/inf513/grupo02sa/proyecto2
```

De ahí salen las direcciones de imágenes, del build de Vite y los enlaces absolutos.
Al cambiarla hay que **recompilar** el frontend (`deploy.sh` ya lo hace), porque el
prefijo queda dentro del build.

### Imágenes

Las fotos **no dependen del enlace `public/storage`**. Se intenta `php artisan storage:link`,
pero si el hosting no permite enlaces simbólicos la app lo detecta sola y sirve los
archivos por la ruta `/media/{ruta}` (`App\Support\Media` decide cuál usar).

### Actualizar el servidor: `git pull` y listo

**`public/build` se versiona a propósito** (ver `.gitignore`): el frontend viaja ya
compilado en el repositorio, así que en el servidor **no hay que correr nada** después
del `git pull`.

Qué se actualiza solo con el pull, y qué no:

| Qué cambiaste | ¿Basta el `git pull`? |
|---|---|
| Controlador, modelo, ruta, servicio (`.php`) | ✅ Sí, al instante |
| Texto o etiqueta de una vista (`.vue`) | ✅ Sí, **si hiciste `npm run build` antes del commit** |
| Migración nueva | ❌ Falta `php artisan migrate --force` |
| Dependencia nueva de composer | ❌ Falta `composer install` |
| Algo de `config/` con la config cacheada | ❌ Falta `php artisan config:clear` |

> **Lo único que hay que recordar:** las vistas Vue viven dentro del paquete compilado.
> Si tocaste algo de `resources/js`, corré `npm run build` **antes** del commit y sumá
> `public/build` al mismo commit; si no, el servidor seguirá mostrando la versión
> anterior de esa pantalla aunque el `.vue` ya esté actualizado en el repo.
>
> Para no olvidarlo, se puede dejar que git lo haga solo al hacer commit:
> ```bash
> printf '#!/bin/sh\nnpm run build && git add public/build\n' > .git/hooks/pre-commit
> chmod +x .git/hooks/pre-commit
> ```

---

*Repositorio del grupo02sa — INF-513, FICCT, UAGRM.*
