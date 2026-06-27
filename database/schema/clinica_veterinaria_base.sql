-- Esquema base PostgreSQL — Clínica Veterinaria (Fumican Vet)
-- Tablas y columnas en español, alineado con el proyecto Laravel.
--
-- Instalación desde cero:
--   createdb -U postgres -E UTF8 clinica_veterinaria
--   psql -U postgres -h 127.0.0.1 -d clinica_veterinaria -f database/schema/clinica_veterinaria_base.sql
--
-- Si la BD ya tiene datos, use clinica_veterinaria_complemento.sql en su lugar.

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;

BEGIN;

-- ---------------------------------------------------------------------------
-- Limpieza (solo instalación nueva)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS pagos CASCADE;
DROP TABLE IF EXISTS cuotas_credito CASCADE;
DROP TABLE IF EXISTS detalles_nota_venta CASCADE;
DROP TABLE IF EXISTS notas_venta CASCADE;
DROP TABLE IF EXISTS inventarios CASCADE;
DROP TABLE IF EXISTS movimientos_inventario CASCADE;
DROP TABLE IF EXISTS detalles_nota_compra CASCADE;
DROP TABLE IF EXISTS notas_compra CASCADE;
DROP TABLE IF EXISTS tratamientos CASCADE;
DROP TABLE IF EXISTS consulta_servicios CASCADE;
DROP TABLE IF EXISTS historial_vacunacion CASCADE;
DROP TABLE IF EXISTS consultas_medicas CASCADE;
DROP TABLE IF EXISTS mascotas CASCADE;
DROP TABLE IF EXISTS clientes CASCADE;
DROP TABLE IF EXISTS razas CASCADE;
DROP TABLE IF EXISTS especies CASCADE;
DROP TABLE IF EXISTS vacunas CASCADE;
DROP TABLE IF EXISTS productos CASCADE;
DROP TABLE IF EXISTS categorias CASCADE;
DROP TABLE IF EXISTS servicios CASCADE;
DROP TABLE IF EXISTS veterinarios CASCADE;
DROP TABLE IF EXISTS proveedores CASCADE;
DROP TABLE IF EXISTS almacenes CASCADE;
DROP TABLE IF EXISTS personal_access_tokens CASCADE;
DROP TABLE IF EXISTS password_reset_tokens CASCADE;
DROP TABLE IF EXISTS usuarios CASCADE;
DROP TABLE IF EXISTS roles_permisos CASCADE;
DROP TABLE IF EXISTS permisos CASCADE;
DROP TABLE IF EXISTS roles CASCADE;
DROP TABLE IF EXISTS persona CASCADE;
DROP TABLE IF EXISTS migrations CASCADE;

-- ---------------------------------------------------------------------------
-- roles, permisos, usuarios
-- ---------------------------------------------------------------------------
CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL UNIQUE,
    descripcion VARCHAR(255) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL
);

CREATE TABLE permisos (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL UNIQUE,
    descripcion VARCHAR(255) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL
);

CREATE TABLE roles_permisos (
    rol_id BIGINT NOT NULL,
    permiso_id BIGINT NOT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    PRIMARY KEY (rol_id, permiso_id),
    CONSTRAINT roles_permisos_rol_id_foreign FOREIGN KEY (rol_id)
        REFERENCES roles (id) ON DELETE CASCADE,
    CONSTRAINT roles_permisos_permiso_id_foreign FOREIGN KEY (permiso_id)
        REFERENCES permisos (id) ON DELETE CASCADE
);

CREATE TABLE usuarios (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    esta_activo BOOLEAN NOT NULL DEFAULT TRUE,
    rol_id BIGINT DEFAULT NULL,
    remember_token VARCHAR(100) DEFAULT NULL,
    two_factor_secret TEXT DEFAULT NULL,
    two_factor_recovery_codes TEXT DEFAULT NULL,
    two_factor_confirmed_at TIMESTAMP DEFAULT NULL,
    profile_photo_path VARCHAR(2048) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT usuarios_rol_id_foreign FOREIGN KEY (rol_id)
        REFERENCES roles (id) ON DELETE SET NULL
);

CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT NULL
);

CREATE TABLE personal_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT DEFAULT NULL,
    last_used_at TIMESTAMP DEFAULT NULL,
    expires_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT NULL
);

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index
    ON personal_access_tokens (tokenable_type, tokenable_id);

-- ---------------------------------------------------------------------------
-- catálogos clínicos
-- ---------------------------------------------------------------------------
CREATE TABLE especies (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL
);

CREATE TABLE razas (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    especie_id BIGINT NOT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT razas_especie_id_foreign FOREIGN KEY (especie_id)
        REFERENCES especies (id) ON DELETE CASCADE
);

CREATE TABLE clientes (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT DEFAULT NULL,
    nombre VARCHAR(80) NOT NULL,
    apellido VARCHAR(80) DEFAULT NULL,
    ci VARCHAR(20) DEFAULT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    genero VARCHAR(20) DEFAULT NULL,
    fecha_nacimiento DATE DEFAULT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT clientes_usuario_id_foreign FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE SET NULL
);

CREATE TABLE mascotas (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    peso NUMERIC(8, 2) DEFAULT NULL,
    color VARCHAR(50) DEFAULT NULL,
    genero VARCHAR(20) DEFAULT NULL,
    fecha_nacimiento DATE DEFAULT NULL,
    url_foto VARCHAR(255) DEFAULT NULL,
    cliente_id BIGINT NOT NULL,
    raza_id BIGINT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT mascotas_cliente_id_foreign FOREIGN KEY (cliente_id)
        REFERENCES clientes (id) ON DELETE CASCADE,
    CONSTRAINT mascotas_raza_id_foreign FOREIGN KEY (raza_id)
        REFERENCES razas (id) ON DELETE SET NULL
);

CREATE TABLE servicios (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    precio NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    esta_activo BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL
);

CREATE TABLE veterinarios (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT DEFAULT NULL,
    nombre VARCHAR(80) NOT NULL,
    apellido VARCHAR(80) DEFAULT NULL,
    ci VARCHAR(20) DEFAULT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    email VARCHAR(120) DEFAULT NULL,
    licencia VARCHAR(50) DEFAULT NULL,
    es_especialista BOOLEAN NOT NULL DEFAULT FALSE,
    especialidad VARCHAR(100) DEFAULT NULL,
    esta_activo BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT veterinarios_usuario_id_foreign FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE SET NULL
);

CREATE TABLE consultas_medicas (
    id BIGSERIAL PRIMARY KEY,
    fecha DATE DEFAULT NULL,
    hora TIME DEFAULT NULL,
    motivo VARCHAR(255) DEFAULT NULL,
    diagnostico TEXT DEFAULT NULL,
    costo_consulta NUMERIC(10, 2) DEFAULT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'completada',
    mascota_id BIGINT NOT NULL,
    usuario_id BIGINT DEFAULT NULL,
    servicio_id BIGINT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT consultas_medicas_mascota_id_foreign FOREIGN KEY (mascota_id)
        REFERENCES mascotas (id) ON DELETE CASCADE,
    CONSTRAINT consultas_medicas_usuario_id_foreign FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT consultas_medicas_servicio_id_foreign FOREIGN KEY (servicio_id)
        REFERENCES servicios (id) ON DELETE SET NULL
);

CREATE TABLE vacunas (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    duracion_dias INTEGER DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL
);

CREATE TABLE historial_vacunacion (
    id BIGSERIAL PRIMARY KEY,
    mascota_id BIGINT NOT NULL,
    vacuna_id BIGINT NOT NULL,
    fecha_aplicacion DATE NOT NULL,
    fecha_proxima DATE DEFAULT NULL,
    aplicado_por BIGINT DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT historial_vacunacion_mascota_id_foreign FOREIGN KEY (mascota_id)
        REFERENCES mascotas (id) ON DELETE CASCADE,
    CONSTRAINT historial_vacunacion_vacuna_id_foreign FOREIGN KEY (vacuna_id)
        REFERENCES vacunas (id) ON DELETE CASCADE,
    CONSTRAINT historial_vacunacion_aplicado_por_foreign FOREIGN KEY (aplicado_por)
        REFERENCES usuarios (id) ON DELETE SET NULL
);

-- ---------------------------------------------------------------------------
-- inventario / farmacia
-- ---------------------------------------------------------------------------
CREATE TABLE categorias (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL
);

CREATE TABLE productos (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    unidad_medida VARCHAR(30) NOT NULL DEFAULT 'unidad',
    presentacion VARCHAR(120) DEFAULT NULL,
    dosificacion VARCHAR(255) DEFAULT NULL,
    fabricante VARCHAR(255) DEFAULT NULL,
    fecha_vencimiento DATE DEFAULT NULL,
    sustancia_controlada BOOLEAN NOT NULL DEFAULT FALSE,
    categoria_id BIGINT DEFAULT NULL,
    stock_minimo INTEGER NOT NULL DEFAULT 0,
    precio_venta_referencia NUMERIC(12, 2) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT productos_categoria_id_foreign FOREIGN KEY (categoria_id)
        REFERENCES categorias (id) ON DELETE SET NULL
);

CREATE TABLE proveedores (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    pais VARCHAR(80) DEFAULT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL
);

CREATE TABLE almacenes (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    ubicacion VARCHAR(255) DEFAULT NULL,
    descripcion TEXT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL
);

CREATE TABLE notas_compra (
    id BIGSERIAL PRIMARY KEY,
    fecha_compra DATE DEFAULT NULL,
    monto_total NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    proveedor_id BIGINT DEFAULT NULL,
    almacen_id BIGINT DEFAULT NULL,
    usuario_id BIGINT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT notas_compra_proveedor_id_foreign FOREIGN KEY (proveedor_id)
        REFERENCES proveedores (id) ON DELETE SET NULL,
    CONSTRAINT notas_compra_almacen_id_foreign FOREIGN KEY (almacen_id)
        REFERENCES almacenes (id) ON DELETE SET NULL,
    CONSTRAINT notas_compra_usuario_id_foreign FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE SET NULL
);

CREATE TABLE detalles_nota_compra (
    id BIGSERIAL PRIMARY KEY,
    cantidad INTEGER NOT NULL DEFAULT 1,
    precio_compra NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    subtotal NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    nota_compra_id BIGINT NOT NULL,
    producto_id BIGINT NOT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT detalles_nota_compra_nota_compra_id_foreign FOREIGN KEY (nota_compra_id)
        REFERENCES notas_compra (id) ON DELETE CASCADE,
    CONSTRAINT detalles_nota_compra_producto_id_foreign FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON DELETE CASCADE
);

CREATE TABLE inventarios (
    id BIGSERIAL PRIMARY KEY,
    stock INTEGER NOT NULL DEFAULT 0,
    precio_compra NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    precio NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    fecha_vencimiento DATE DEFAULT NULL,
    producto_id BIGINT NOT NULL,
    almacen_id BIGINT NOT NULL,
    detalle_nota_compra_id BIGINT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT inventarios_producto_id_foreign FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON DELETE CASCADE,
    CONSTRAINT inventarios_almacen_id_foreign FOREIGN KEY (almacen_id)
        REFERENCES almacenes (id) ON DELETE CASCADE,
    CONSTRAINT inventarios_detalle_nota_compra_id_foreign FOREIGN KEY (detalle_nota_compra_id)
        REFERENCES detalles_nota_compra (id) ON DELETE SET NULL
);

-- ---------------------------------------------------------------------------
-- ventas y pagos
-- ---------------------------------------------------------------------------
CREATE TABLE notas_venta (
    id BIGSERIAL PRIMARY KEY,
    fecha_venta TIMESTAMP DEFAULT NULL,
    monto_total NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    estado VARCHAR(30) NOT NULL DEFAULT 'completada',
    cliente_id BIGINT DEFAULT NULL,
    usuario_id BIGINT DEFAULT NULL,
    almacen_id BIGINT DEFAULT NULL,
    consulta_id BIGINT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT notas_venta_cliente_id_foreign FOREIGN KEY (cliente_id)
        REFERENCES clientes (id) ON DELETE SET NULL,
    CONSTRAINT notas_venta_usuario_id_foreign FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT notas_venta_almacen_id_foreign FOREIGN KEY (almacen_id)
        REFERENCES almacenes (id) ON DELETE SET NULL,
    CONSTRAINT notas_venta_consulta_id_foreign FOREIGN KEY (consulta_id)
        REFERENCES consultas_medicas (id) ON DELETE SET NULL,
    CONSTRAINT chk_notas_venta_estado CHECK (estado::text = ANY (ARRAY[
        'pendiente'::varchar,
        'pagada'::varchar,
        'anulada'::varchar,
        'credito'::varchar,
        'completada'::varchar
    ]::text[]))
);

CREATE TABLE detalles_nota_venta (
    id BIGSERIAL PRIMARY KEY,
    cantidad INTEGER NOT NULL DEFAULT 1,
    precio_venta NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    subtotal NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    nota_venta_id BIGINT NOT NULL,
    producto_id BIGINT NOT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT detalles_nota_venta_nota_venta_id_foreign FOREIGN KEY (nota_venta_id)
        REFERENCES notas_venta (id) ON DELETE CASCADE,
    CONSTRAINT detalles_nota_venta_producto_id_foreign FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON DELETE CASCADE
);

CREATE TABLE pagos (
    id BIGSERIAL PRIMARY KEY,
    consulta_id BIGINT DEFAULT NULL,
    servicio_id BIGINT DEFAULT NULL,
    cliente_id BIGINT DEFAULT NULL,
    mascota_id BIGINT DEFAULT NULL,
    fecha_pago TIMESTAMP DEFAULT NULL,
    monto NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    metodo_pago VARCHAR(50) NOT NULL DEFAULT 'efectivo',
    tipo_pago VARCHAR(30) NOT NULL DEFAULT 'contado',
    concepto_pago VARCHAR(30) DEFAULT NULL,
    id_transaccion_externa VARCHAR(100) DEFAULT NULL,
    nota_venta_id BIGINT DEFAULT NULL,
    usuario_id BIGINT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT pagos_consulta_id_foreign FOREIGN KEY (consulta_id)
        REFERENCES consultas_medicas (id) ON DELETE SET NULL,
    CONSTRAINT pagos_servicio_id_foreign FOREIGN KEY (servicio_id)
        REFERENCES servicios (id) ON DELETE SET NULL,
    CONSTRAINT pagos_cliente_id_foreign FOREIGN KEY (cliente_id)
        REFERENCES clientes (id) ON DELETE SET NULL,
    CONSTRAINT pagos_mascota_id_foreign FOREIGN KEY (mascota_id)
        REFERENCES mascotas (id) ON DELETE SET NULL,
    CONSTRAINT pagos_nota_venta_id_foreign FOREIGN KEY (nota_venta_id)
        REFERENCES notas_venta (id) ON DELETE SET NULL,
    CONSTRAINT pagos_usuario_id_foreign FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE SET NULL
);

CREATE TABLE tratamientos (
    id BIGSERIAL PRIMARY KEY,
    consulta_medica_id BIGINT NOT NULL,
    producto_id BIGINT DEFAULT NULL,
    cantidad INTEGER DEFAULT NULL,
    instrucciones_dosis TEXT DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT NULL,
    CONSTRAINT tratamientos_consulta_medica_id_foreign FOREIGN KEY (consulta_medica_id)
        REFERENCES consultas_medicas (id) ON DELETE CASCADE,
    CONSTRAINT tratamientos_producto_id_foreign FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON DELETE SET NULL
);

-- Tabla interna de Laravel (opcional)
CREATE TABLE migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

COMMIT;
