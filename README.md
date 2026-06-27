# Fumican Vet

Sistema de gestión veterinaria desarrollado con **Laravel 11**, **Inertia.js** y **Vue 3**. Cubre el ciclo clínico (clientes, mascotas, consultas, vacunas, pagos) y el módulo de ventas (productos, inventarios, compras y ventas).

## Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│  Frontend: Vue 3 + Inertia + Flowbite + Tailwind            │
├─────────────────────────────────────────────────────────────┤
│  Backend: Laravel (controladores, servicios, repositorios)  │
├──────────────────────┬──────────────────────────────────────┤
│  PostgreSQL (grupo)  │  SQLite local (auditoría)            │
│  db_grupo23sa        │  database/auditoria.sqlite           │
│  Datos de negocio    │  bitácora, visitas, menús, cuotas   │
└──────────────────────┴──────────────────────────────────────┘
```

- **Modelo de auth:** `App\Models\Usuario` con `rol_id` y permisos en tablas `roles`, `permisos`, `roles_permisos`.
- **Permisos:** nombres en BD (`snake_case`) mapeados desde el enum de aplicación vía `config/permisos-bd.php` y el composable `usePermisos.js`.
- **Rutas protegidas:** middleware `permiso:nombre_permiso` en `routes/web.php`.
- **UI condicional:** botones crear/editar/eliminar ocultos según permisos del usuario.

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+ y npm
- Extensión `pdo_pgsql` (producción/académico) y `pdo_sqlite` (auditoría local)

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

### Variables de entorno relevantes

| Variable | Descripción |
|----------|-------------|
| `USAR_BD_GRUPO=true` | Usa PostgreSQL `db_grupo23sa` en Tecnoweb (no ejecuta seeders de dominio). |
| `USAR_BD_GRUPO=false` | Entorno local con SQLite/PostgreSQL propio y seeders completos. |
| `APP_LOCALE=es` | Interfaz y validaciones en español. |
| `DB_*` | Conexión principal (PostgreSQL del grupo o local). |

### Entorno local con PostgreSQL (`clinica_veterinaria`)

En `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=clinica_veterinaria
DB_USERNAME=postgres
DB_PASSWORD=admin123
USAR_BD_GRUPO=false
```

Si la BD ya existe pero le faltan columnas o tablas del proyecto, aplique el complemento SQL (idempotente, no borra datos):

```bash
psql -U postgres -h 127.0.0.1 -d clinica_veterinaria -f database/schema/clinica_veterinaria_complemento.sql
```

Para **instalación desde cero** (borra tablas del esquema del proyecto):

```bash
createdb -U postgres -E UTF8 clinica_veterinaria
psql -U postgres -h 127.0.0.1 -d clinica_veterinaria -f database/schema/clinica_veterinaria_base.sql
php artisan db:seed
```

Migrar solo la auditoría (SQLite) — se aplica sola al arrancar la app, o manualmente:

```bash
sqlite3 database/auditoria.sqlite < database/schema/auditoria.sql
```

El esquema de negocio vive en `database/schema/` (SQL en español). **No hay carpeta `database/migrations`.**

### Entorno 100% local (SQLite)

En `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
USAR_BD_GRUPO=false
```

Instalación en un solo paso:

```bash
php artisan fumican:install-local --fresh
```

O manualmente:

```bash
type nul > database\database.sqlite
php artisan fumican:install-local
```

### Base de datos de auditoría (SQLite)

Se crea automáticamente al arrancar la app si no existe (`database/schema/auditoria.sql`).

**No modificar el esquema de `db_grupo23sa`.** Las tablas de bitácora, visitas, menús dinámicos y planes de pago viven en SQLite.

### Ejecutar en desarrollo

```bash
php artisan serve
npm run dev
```

O con el script de Composer (si está configurado):

```bash
composer dev
```

## Usuarios de prueba

Con `USAR_BD_GRUPO=false` y seeders ejecutados:

| Email | Contraseña | Rol |
|-------|------------|-----|
| `juancho123sc@gmail.com` | `12345678` | propietario (todos los permisos) |
| `armando@gmail.com` | `12345678` | propietario |

En la BD del grupo, use las credenciales asignadas por el docente. Asegúrese de que el usuario tenga `rol_id` y permisos coherentes para la demostración.

## Roles de negocio

| Rol | Alcance típico |
|-----|----------------|
| **propietario** / **administrador** | Acceso total (`administrar_sistema` o todos los permisos). |
| **veterinario** | Clientes, mascotas, consultas, vacunas, servicios, pagos, reportes. |
| **cliente** | Consulta de sus mascotas, pagos y servicios (solo lectura). |

La administración de usuarios y roles requiere `administrar_sistema`.

## Módulos principales

| Módulo | Ruta base | Permisos clave |
|--------|-----------|----------------|
| Clientes | `/clientes` | `listar_clientes`, `crear_clientes` |
| Mascotas | `/mascotas` | `listar_mascotas`, `gestionar_mascotas` |
| Consultas | `/consultas-medicas` | `listar_consultas`, `gestionar_consultas` |
| Pagos | `/pagos` | `listar_pagos`, `gestionar_pagos` |
| Productos / categorías | `/productos`, `/categorias` | `listar_productos`, `gestionar_productos` |
| Almacenes / inventario | `/almacenes` | `listar_inventarios`, `gestionar_inventarios` |
| Notas de venta / compra | `/notas-venta`, `/notas-compra` | `listar_ventas` / `gestionar_ventas`, etc. |
| Reportes | `/reportes` | `ver_reportes`, `administrar_sistema` |

## Funcionalidades académicas

1. **Bitácora** — registro de acciones y accesos (`/reportes/bitacora`).
2. **Contador de visitas** — por página en el pie del layout.
3. **Menú dinámico** — tabla `menus` en SQLite con fallback estático.
4. **Matriz de acceso** — `/reportes/matriz-acceso`.
5. **Temas** — niños/jóvenes/adultos × día/noche.
6. **Accesibilidad** — tamaño de letra y alto contraste.
7. **Pagos** — contado/crédito, métodos múltiples, cuotas en SQLite.
8. **Permisos por rol** — backend (middleware) y frontend (`usePermisos`).
9. **Interfaz en español** — rutas, vistas y mensajes de validación.
10. **Arquitectura MVC-MVVM** — servicios, repositorios, composables Vue.

## Pruebas

Guía detallada: **[docs/PRUEBAS.md](docs/PRUEBAS.md)**.

```bash
php artisan test
```

Las pruebas usan **SQLite en memoria** (no dependen de Tecnoweb). Las pruebas Jetstream heredadas del skeleton se omiten automáticamente porque el proyecto usa el esquema `Usuario` del grupo.

## Estructura de carpetas (resumen)

```
app/
  Http/Controllers/     # Controladores por dominio (Servicios/, Ventas/, Usuarios/)
  Services/             # Lógica de negocio
  Models/               # Usuario, modelos de dominio y Auditoria/
resources/js/
  Pages/                # Vistas Inertia (Servicios/, Ventas/, Usuarios/, Reportes/)
  Composables/          # usePermisos, useTheme, etc.
database/
  schema/               # SQL en español (PostgreSQL, SQLite, auditoría)
  auditoria.sqlite      # BD auxiliar (generada en runtime)
  seeders/              # Solo con USAR_BD_GRUPO=false
config/
  permisos-bd.php       # Mapeo permisos app ↔ BD
  tablas.php            # Flag usar_bd_grupo
```

## Licencia

Proyecto académico — Grupo 23 SA.
