# 🏍️ RAO MOTOS

**Sistema de Ventas al Crédito de Repuestos y Taller de Reparación de Motos.**

Proyecto 2 — INF-513 Tecnología Web · Grupo02sa · FICCT, UAGRM · Gestión 2026.

| | |
|---|---|
| **Stack** | Laravel 10 · Inertia.js · Vue 3 · Bootstrap 5 · PostgreSQL 18 · Jetstream |
| **Arquitectura** | Tres capas / MVC-MVVM · Route → Controller → Service → Model |
| **Base de datos** | PostgreSQL 18, base `rao_motos`, puerto `5432` |
| **Alcance** | Ver **[ALCANCE.md](ALCANCE.md)** (fuente de verdad del proyecto) |
| **Guía técnica** | Ver **[CLAUDE.md](CLAUDE.md)** |

## Equipo

- Carlos Diego Marca Peñaranda
- Fabio Alejandro Arnez Fernández
- Reymar Loaiza Labarden

## El negocio

RAO MOTOS **vende repuestos de moto** (minorista y mayorista por volumen) y **repara motos** en su taller. Ambas actividades se pagan al contado o **en cuotas (crédito)**. Roles: **admin, vendedor, almacenero, mecánico, cliente**.

## Puesta en marcha (desarrollo)

```bash
# Dependencias
composer install
npm install

# Entorno
cp .env.example .env
php artisan key:generate
# Configurar PostgreSQL en .env (DB_DATABASE=rao_motos, DB_USERNAME, DB_PASSWORD)

# Base de datos (crea tablas + datos demo)
php artisan migrate:fresh --seed
php artisan storage:link     # crea public/storage → storage/app/public

# Compilar assets
npm run build
# o para desarrollo:
npm run dev

# Servir
php artisan serve   # http://localhost:8000
```

### Seeders disponibles

| Seeder | Contenido |
|---|---|
| `RoleSeeder` | 5 roles: admin, vendedor, almacenero, mecanico, cliente |
| `UsuarioSeeder` | 3 admins, 3 staff demo, 5 clientes demo |
| `ProductoSeeder` | 15 repuestos reales con stock inicial |
| `ProveedorSeeder` | 2 proveedores |
| `ConfiguracionSeeder` | Parámetros del sistema (interés, mora, plazos) |
| `MetodoPagoSeeder` | 2 métodos: EFECTIVO, QR |
| `MenuItemSeeder` | Menú dinámico por rol (base de datos) |

### Usuarios de demo (tras el seed)

| Rol | Email | Contraseña |
|---|---|---|
| Admin | `fabioarnez200@gmail.com` | `admin123` |
| Vendedor | `vendedor@raomotos.com` | `demo123` |
| Almacenero | `almacenero@raomotos.com` | `demo123` |
| Mecánico | `mecanico@raomotos.com` | `demo123` |
| Cliente | `juan.perez@email.com` | `cliente123` |

## Almacenamiento de imágenes

Las imágenes de productos se guardan en el disco `public` de Laravel.

| Aspecto | Detalle |
|---|---|
| **Ruta local** | `storage/app/public/productos/<archivo>` |
| **URL de acceso** | `/storage/productos/<archivo>` (vía symlink `public/storage`) |
| **Modelo** | `Producto.foto_url` guarda el path relativo; `Producto.foto_completa` (accessor) devuelve la URL absoluta con `asset()` |
| **Frontend** | Las vistas Vue usan `p.foto_completa` (compatible con subdirectorios en producción) |
| **Creación** | `$request->file('foto')->store('productos', 'public')` |
| **Actualización** | Elimina la imagen anterior antes de guardar la nueva |
| **Seed** | Productos de demo no tienen imagen asignada |

> En **producción** (subdirectorio como `tecnoweb.org.bo/inf513/grupo02sa/proyecto2`), el accessor `foto_completa` usa `asset()` que respeta `APP_URL` y genera rutas correctas automáticamente. No es necesario cambiar código.

## Comandos útiles

```bash
php artisan migrate:fresh --seed   # reconstruir BD completa
php artisan storage:link           # crear symlink de imágenes
php artisan backup:db              # backup pg_dump a storage/app/backups/
php artisan creditos:marcar-vencidas  # ejecutar mora manualmente
vendor/bin/pint                    # formateo PHP
php artisan test                   # pruebas
```

## Entrega

Archivo: `2026-1_INF513-P2_grupo02sa.tar.gz` (documentación + código). Despliegue en `https://www.tecnoweb.org.bo/inf513/grupo02sa/proyecto2`.
