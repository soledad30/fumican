-- Esquema SQLite — auditoría (bitácora, visitas, menús, planes de pago)
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS bitacora (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER DEFAULT NULL,
    accion VARCHAR(50) NOT NULL,
    modulo VARCHAR(80) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    datos_anteriores TEXT DEFAULT NULL,
    datos_nuevos TEXT DEFAULT NULL,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS visitas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ruta VARCHAR(255) NOT NULL UNIQUE,
    contador INTEGER NOT NULL DEFAULT 0,
    ultima_visita DATETIME DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS menus (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre VARCHAR(80) NOT NULL,
    icono VARCHAR(80) DEFAULT NULL,
    enlace VARCHAR(255) DEFAULT NULL,
    permiso_bd VARCHAR(80) DEFAULT NULL,
    parent_id INTEGER DEFAULT NULL,
    orden INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS planes_pago (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pago_id INTEGER DEFAULT NULL,
    usuario_id INTEGER DEFAULT NULL,
    nota_venta_id INTEGER DEFAULT NULL,
    monto_total NUMERIC(12, 2) NOT NULL,
    num_cuotas INTEGER NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'activo',
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS cuotas_pago (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plan_pago_id INTEGER NOT NULL,
    numero INTEGER NOT NULL,
    monto NUMERIC(12, 2) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    fecha_pago DATE DEFAULT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    metodo_pago VARCHAR(30) DEFAULT NULL,
    id_transaccion_externa VARCHAR(100) DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (plan_pago_id) REFERENCES planes_pago (id) ON DELETE CASCADE
);
