-- Tablas de auditoría, crédito e inventario — PostgreSQL (misma BD que el negocio)
-- Ejecutar sobre clinica_veterinaria existente:
--   psql -U postgres -h 127.0.0.1 -d clinica_veterinaria -f database/schema/clinica_veterinaria_auditoria_pg.sql
-- Luego aplicar relaciones:
--   psql ... -f database/schema/clinica_veterinaria_relaciones.sql

BEGIN;

CREATE TABLE IF NOT EXISTS bitacora (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT DEFAULT NULL,
    accion VARCHAR(50) NOT NULL,
    modulo VARCHAR(80) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    datos_anteriores TEXT DEFAULT NULL,
    datos_nuevos TEXT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS visitas (
    id BIGSERIAL PRIMARY KEY,
    ruta VARCHAR(255) NOT NULL UNIQUE,
    contador INTEGER NOT NULL DEFAULT 0,
    ultima_visita TIMESTAMP DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS menus (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    icono VARCHAR(80) DEFAULT NULL,
    enlace VARCHAR(255) DEFAULT NULL,
    permiso_bd VARCHAR(80) DEFAULT NULL,
    permiso_id BIGINT DEFAULT NULL,
    parent_id BIGINT DEFAULT NULL,
    orden INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS cuotas_credito (
    id BIGSERIAL PRIMARY KEY,
    pago_id BIGINT NOT NULL,
    usuario_id BIGINT DEFAULT NULL,
    numero INTEGER NOT NULL,
    monto NUMERIC(12, 2) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    fecha_pago TIMESTAMP DEFAULT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    metodo_pago VARCHAR(50) DEFAULT NULL,
    id_transaccion_externa VARCHAR(100) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS cuotas_credito_pago_id_index ON cuotas_credito (pago_id);
CREATE INDEX IF NOT EXISTS cuotas_credito_estado_index ON cuotas_credito (estado);

CREATE TABLE IF NOT EXISTS movimientos_inventario (
    id BIGSERIAL PRIMARY KEY,
    producto_id BIGINT NOT NULL,
    almacen_id BIGINT NOT NULL,
    inventario_id BIGINT DEFAULT NULL,
    detalle_nota_venta_id BIGINT DEFAULT NULL,
    detalle_nota_compra_id BIGINT DEFAULT NULL,
    tipo VARCHAR(20) NOT NULL,
    cantidad INTEGER NOT NULL,
    stock_anterior INTEGER DEFAULT NULL,
    stock_posterior INTEGER DEFAULT NULL,
    usuario_id BIGINT DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMIT;
