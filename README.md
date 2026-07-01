# 🏍️ RAO MOTOS

**Sistema de Ventas al Crédito de Repuestos y Taller de Reparación de Motos.**

Proyecto 2 — INF-513 Tecnología Web · Grupo02sa · FICCT, UAGRM · Gestión 2026.

| | |
|---|---|
| **Stack** | Laravel 10 · Inertia.js · Vue 3 · Bootstrap 5 · PostgreSQL · Jetstream |
| **Arquitectura** | Tres capas / MVC-MVVM · Route → Controller → Service → Model |
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
# Configurar PostgreSQL en .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD)

# Base de datos (crea las tablas y datos de demo)
php artisan migrate:fresh --seed
php artisan storage:link

# Levantar (en dos terminales)
npm run dev
php artisan serve   # http://localhost:8000
```

### Usuarios de demo (tras el seed)

| Rol | Email | Contraseña |
|---|---|---|
| Admin | `fabioarnez200@gmail.com` | `admin123` |
| Vendedor | `vendedor@raomotos.com` | `demo123` |
| Almacenero | `almacenero@raomotos.com` | `demo123` |
| Mecánico | `mecanico@raomotos.com` | `demo123` |
| Cliente | `juan.perez@email.com` | `cliente123` |

## Comandos útiles

```bash
php artisan migrate:fresh --seed   # reconstruir la BD
php artisan backup:db              # backup pg_dump a storage/app/backups/
vendor/bin/pint                    # formateo PHP
php artisan test                   # pruebas
```

## Entrega

Archivo: `2026-1_INF513-P2_grupo02sa.tar.gz` (documentación + código). Despliegue en `https://www.tecnoweb.org.bo/inf513/grupo02sa/proyecto2`.
