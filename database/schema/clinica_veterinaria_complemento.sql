-- Complemento idempotente para alinear clinica_veterinaria con Fumican Vet.
-- Ejecutar sobre la BD existente (no borra datos):
--   psql -U postgres -h 127.0.0.1 -d clinica_veterinaria -f database/schema/clinica_veterinaria_complemento.sql

BEGIN;

-- ---------------------------------------------------------------------------
-- roles_permisos
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'roles_permisos' AND column_name = 'creado_en') THEN
        ALTER TABLE roles_permisos ADD COLUMN creado_en TIMESTAMP DEFAULT NULL;
    END IF;
END $$;

-- ---------------------------------------------------------------------------
-- veterinarios (personal clínico)
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'veterinarios' AND column_name = 'nombre') THEN
        ALTER TABLE veterinarios ADD COLUMN nombre VARCHAR(80) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'veterinarios' AND column_name = 'apellido') THEN
        ALTER TABLE veterinarios ADD COLUMN apellido VARCHAR(80) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'veterinarios' AND column_name = 'ci') THEN
        ALTER TABLE veterinarios ADD COLUMN ci VARCHAR(20) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'veterinarios' AND column_name = 'email') THEN
        ALTER TABLE veterinarios ADD COLUMN email VARCHAR(120) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'veterinarios' AND column_name = 'licencia') THEN
        ALTER TABLE veterinarios ADD COLUMN licencia VARCHAR(50) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'veterinarios' AND column_name = 'es_especialista') THEN
        ALTER TABLE veterinarios ADD COLUMN es_especialista BOOLEAN NOT NULL DEFAULT FALSE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'veterinarios' AND column_name = 'esta_activo') THEN
        ALTER TABLE veterinarios ADD COLUMN esta_activo BOOLEAN NOT NULL DEFAULT TRUE;
    END IF;
END $$;

UPDATE veterinarios
SET licencia = numero_colegiatura
WHERE licencia IS NULL
  AND numero_colegiatura IS NOT NULL;

UPDATE veterinarios
SET es_especialista = TRUE
WHERE COALESCE(es_especialista, FALSE) = FALSE
  AND especialidad IS NOT NULL
  AND BTRIM(especialidad) <> '';

UPDATE veterinarios
SET esta_activo = TRUE
WHERE esta_activo IS NULL;

UPDATE veterinarios v
SET
    nombre = COALESCE(v.nombre, split_part(u.nombre, ' ', 1)),
    apellido = COALESCE(
        v.apellido,
        NULLIF(BTRIM(substring(u.nombre FROM position(' ' IN u.nombre) + 1)), '')
    ),
    email = COALESCE(v.email, u.email)
FROM usuarios u
WHERE v.usuario_id = u.id;

-- ---------------------------------------------------------------------------
-- usuarios (Jetstream / Fortify / Sanctum)
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'usuarios' AND column_name = 'remember_token') THEN
        ALTER TABLE usuarios ADD COLUMN remember_token VARCHAR(100) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'usuarios' AND column_name = 'two_factor_secret') THEN
        ALTER TABLE usuarios ADD COLUMN two_factor_secret TEXT DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'usuarios' AND column_name = 'two_factor_recovery_codes') THEN
        ALTER TABLE usuarios ADD COLUMN two_factor_recovery_codes TEXT DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'usuarios' AND column_name = 'two_factor_confirmed_at') THEN
        ALTER TABLE usuarios ADD COLUMN two_factor_confirmed_at TIMESTAMP DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'usuarios' AND column_name = 'profile_photo_path') THEN
        ALTER TABLE usuarios ADD COLUMN profile_photo_path VARCHAR(2048) DEFAULT NULL;
    END IF;
END $$;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS personal_access_tokens (
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

CREATE INDEX IF NOT EXISTS personal_access_tokens_tokenable_type_tokenable_id_index
    ON personal_access_tokens (tokenable_type, tokenable_id);

-- ---------------------------------------------------------------------------
-- clientes
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'clientes' AND column_name = 'ci') THEN
        ALTER TABLE clientes ADD COLUMN ci VARCHAR(20) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'clientes' AND column_name = 'email') THEN
        ALTER TABLE clientes ADD COLUMN email VARCHAR(255) DEFAULT NULL;
    END IF;
END $$;

ALTER TABLE clientes ALTER COLUMN apellido DROP NOT NULL;

-- ---------------------------------------------------------------------------
-- consultas_medicas + servicio_id
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'consultas_medicas' AND column_name = 'servicio_id') THEN
        ALTER TABLE consultas_medicas ADD COLUMN servicio_id BIGINT DEFAULT NULL;
        ALTER TABLE consultas_medicas
            ADD CONSTRAINT consultas_medicas_servicio_id_foreign
            FOREIGN KEY (servicio_id) REFERENCES servicios (id) ON DELETE SET NULL;
    END IF;
END $$;

UPDATE consultas_medicas cm
SET servicio_id = cs.servicio_id
FROM (
    SELECT DISTINCT ON (consulta_id) consulta_id, servicio_id
    FROM consulta_servicios
    ORDER BY consulta_id, id
) cs
WHERE cm.id = cs.consulta_id
  AND cm.servicio_id IS NULL;

-- ---------------------------------------------------------------------------
-- productos (farmacia)
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'productos' AND column_name = 'unidad_medida') THEN
        ALTER TABLE productos ADD COLUMN unidad_medida VARCHAR(30) NOT NULL DEFAULT 'unidad';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'productos' AND column_name = 'presentacion') THEN
        ALTER TABLE productos ADD COLUMN presentacion VARCHAR(120) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'productos' AND column_name = 'stock_minimo') THEN
        ALTER TABLE productos ADD COLUMN stock_minimo INTEGER NOT NULL DEFAULT 0;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'productos' AND column_name = 'precio_venta_referencia') THEN
        ALTER TABLE productos ADD COLUMN precio_venta_referencia NUMERIC(12, 2) DEFAULT NULL;
    END IF;
END $$;

-- ---------------------------------------------------------------------------
-- inventarios
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'inventarios' AND column_name = 'precio_compra') THEN
        ALTER TABLE inventarios ADD COLUMN precio_compra NUMERIC(12, 2) NOT NULL DEFAULT 0;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'inventarios' AND column_name = 'detalle_nota_compra_id') THEN
        ALTER TABLE inventarios ADD COLUMN detalle_nota_compra_id BIGINT DEFAULT NULL;
        ALTER TABLE inventarios
            ADD CONSTRAINT inventarios_detalle_nota_compra_id_foreign
            FOREIGN KEY (detalle_nota_compra_id) REFERENCES detalles_nota_compra (id) ON DELETE SET NULL;
    END IF;
END $$;

-- ---------------------------------------------------------------------------
-- pagos (consultas, crédito, concepto)
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'pagos' AND column_name = 'consulta_id') THEN
        ALTER TABLE pagos ADD COLUMN consulta_id BIGINT DEFAULT NULL;
        ALTER TABLE pagos
            ADD CONSTRAINT pagos_consulta_id_foreign
            FOREIGN KEY (consulta_id) REFERENCES consultas_medicas (id) ON DELETE SET NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'pagos' AND column_name = 'servicio_id') THEN
        ALTER TABLE pagos ADD COLUMN servicio_id BIGINT DEFAULT NULL;
        ALTER TABLE pagos
            ADD CONSTRAINT pagos_servicio_id_foreign
            FOREIGN KEY (servicio_id) REFERENCES servicios (id) ON DELETE SET NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'pagos' AND column_name = 'cliente_id') THEN
        ALTER TABLE pagos ADD COLUMN cliente_id BIGINT DEFAULT NULL;
        ALTER TABLE pagos
            ADD CONSTRAINT pagos_cliente_id_foreign
            FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE SET NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'pagos' AND column_name = 'mascota_id') THEN
        ALTER TABLE pagos ADD COLUMN mascota_id BIGINT DEFAULT NULL;
        ALTER TABLE pagos
            ADD CONSTRAINT pagos_mascota_id_foreign
            FOREIGN KEY (mascota_id) REFERENCES mascotas (id) ON DELETE SET NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'pagos' AND column_name = 'concepto_pago') THEN
        ALTER TABLE pagos ADD COLUMN concepto_pago VARCHAR(30) DEFAULT NULL;
    END IF;
END $$;

-- ---------------------------------------------------------------------------
-- tratamientos: consulta_id -> consulta_medica_id (esquema del proyecto)
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'tratamientos' AND column_name = 'consulta_id'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'tratamientos' AND column_name = 'consulta_medica_id'
    ) THEN
        ALTER TABLE tratamientos RENAME COLUMN consulta_id TO consulta_medica_id;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'tratamientos' AND column_name = 'actualizado_en'
    ) THEN
        ALTER TABLE tratamientos ADD COLUMN actualizado_en TIMESTAMP DEFAULT NULL;
    END IF;
END $$;

-- ---------------------------------------------------------------------------
-- notas_venta: permitir estado 'completada' (usado por la app)
-- ---------------------------------------------------------------------------
ALTER TABLE notas_venta DROP CONSTRAINT IF EXISTS chk_notas_venta_estado;
ALTER TABLE notas_venta ADD CONSTRAINT chk_notas_venta_estado
    CHECK (estado::text = ANY (ARRAY[
        'pendiente'::varchar,
        'pagada'::varchar,
        'anulada'::varchar,
        'credito'::varchar,
        'completada'::varchar
    ]::text[]));

-- ---------------------------------------------------------------------------
-- consultas_medicas: permitir estado 'no_asistio'
-- ---------------------------------------------------------------------------
ALTER TABLE consultas_medicas DROP CONSTRAINT IF EXISTS chk_consultas_estado;
ALTER TABLE consultas_medicas ADD CONSTRAINT chk_consultas_estado
    CHECK (estado::text = ANY (ARRAY[
        'reservada'::varchar,
        'en_atencion'::varchar,
        'completada'::varchar,
        'cancelada'::varchar,
        'no_asistio'::varchar
    ]::text[]));

COMMIT;
