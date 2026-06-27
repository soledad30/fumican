# Guía de pruebas — Fumican Vet

Checklist para validar el sistema antes de entrega o demostración. Marque cada ítem al completarlo.

## Preparación del entorno

- [ ] `.env` configurado con credenciales de PostgreSQL (`db_grupo23sa`) o `USAR_BD_GRUPO=false` para local.
- [ ] `php artisan key:generate` ejecutado.
- [ ] `npm run build` o `npm run dev` activo.
- [ ] Archivo `database/auditoria.sqlite` existe (se crea al primer arranque).
- [ ] Usuario de prueba con `rol_id` asignado y sesión iniciada.

## 1. Autenticación y seguridad

- [ ] Login exitoso redirige al panel principal.
- [ ] Login fallido registra evento en bitácora (`/reportes/bitacora`).
- [ ] Usuario sin permiso recibe 403 al acceder a ruta protegida (ej. `/usuarios` como cliente).
- [ ] Botones crear/editar/eliminar **no aparecen** sin el permiso correspondiente (probar con rol veterinario en Ventas).

## 2. Clientes y mascotas

- [ ] Listar y buscar clientes (`/clientes`).
- [ ] Crear cliente (solo con `crear_clientes`).
- [ ] Crear mascota vinculada a cliente (`/mascotas`).
- [ ] Editar y eliminar según permisos.

## 3. Consultas médicas

- [ ] Registrar consulta con mascota y veterinario.
- [ ] Agregar tratamiento a una consulta.
- [ ] Generar reporte PDF de consultas o historial de mascota.

## 4. Vacunas y servicios

- [ ] Listar catálogo de vacunas y registrar historial.
- [ ] CRUD de servicios del catálogo (con permiso `gestionar_servicios`).

## 5. Pagos

- [ ] Crear pago al contado (efectivo, tarjeta, transferencia o QR).
- [ ] Crear pago a crédito con plan de cuotas (`num_cuotas` > 1).
- [ ] Registrar pago de una cuota pendiente.
- [ ] Verificar que planes/cuotas persisten en SQLite (`auditoria`).

## 6. Ventas e inventario

- [ ] CRUD categorías y productos (`/categorias`, `/productos`).
- [ ] Agregar lote a producto (requiere `gestionar_inventarios`).
- [ ] CRUD almacenes y ver inventario por medicamento.
- [ ] Crear nota de compra y comprobar stock en almacén.
- [ ] Crear nota de venta y generar PDF.
- [ ] Proveedores: crear, editar y eliminar (permiso `gestionar_compras`).

## 7. Administración

- [ ] Listar usuarios y asignar rol (`/usuarios`) — solo `administrar_sistema`.
- [ ] Listar roles y sincronizar permisos (`/roles`).
- [ ] Matriz de acceso (`/reportes/matriz-acceso`) muestra roles × permisos.

## 8. Requisitos transversales

- [ ] **Bitácora:** acciones CRUD visibles en `/reportes/bitacora` con filtros.
- [ ] **Visitas:** contador en pie de página incrementa al navegar.
- [ ] **Menú dinámico:** ítems coherentes con rol (tabla `menus` o fallback).
- [ ] **Temas:** cambiar perfil (niños/jóvenes/adultos) y modo día/noche.
- [ ] **Accesibilidad:** A-/A+ y alto contraste aplican en toda la UI.
- [ ] **Idioma:** mensajes de validación y etiquetas en español.

---

## Guion de demostración (~15 minutos)

Use un usuario **propietario** o **administrador** para mostrar todo; cierre con un **veterinario** y un **cliente** para evidenciar restricciones.

| Min | Acción | Qué destacar |
|-----|--------|--------------|
| 0–2 | Login + panel | Tema, accesibilidad, menú lateral, contador de visitas |
| 2–4 | Cliente + mascota | Formularios en español, permisos en botones |
| 4–7 | Consulta + tratamiento | Flujo clínico, PDF si hay tiempo |
| 7–9 | Pago a crédito con cuotas | Métodos de pago, registro de cuota |
| 9–12 | Nota de compra → venta | Impacto en inventario, PDF de venta |
| 12–14 | Reportes | Bitácora (incl. login fallido previo), matriz de acceso |
| 14–15 | Cambio de rol | Veterinario sin botones de Ventas; cliente solo lectura |

### Credenciales locales (seeders)

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| `juancho123sc@gmail.com` | `12345678` | propietario |
| Usuarios factory (aleatorio) | — | veterinario / cliente / administrador |

### Comandos útiles

```bash
# Esquema auditoría (SQLite) — normalmente automático al arrancar
sqlite3 database/auditoria.sqlite < database/schema/auditoria.sql

# Seeders completos (solo USAR_BD_GRUPO=false)
php artisan db:seed

# Tests automatizados
php artisan test
```

### Criterios de aceptación

El sistema se considera listo para entrega cuando:

1. Los 10 requisitos académicos del enunciado son demostrables en vivo.
2. Los 8 casos de uso del dominio veterinario tienen flujo completo sin errores 500.
3. Permisos se aplican en **rutas** y en **interfaz** (no solo backend).
4. La BD del grupo no fue alterada (solo lectura/escritura de datos, sin migraciones destructivas).
