-- Enlaza tablas que antes aparecían sueltas en el diagrama ER.
-- Ejecutar: psql -U postgres -d clinica_veterinaria -f database/schema/clinica_veterinaria_relaciones.sql

BEGIN;

-- ---------------------------------------------------------------------------
-- bitacora → usuarios
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'bitacora_usuario_id_foreign'
    ) THEN
        ALTER TABLE bitacora
            ADD CONSTRAINT bitacora_usuario_id_foreign
            FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL;
    END IF;
END $$;

-- ---------------------------------------------------------------------------
-- menus → menus (padre) y permisos
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'menus' AND column_name = 'permiso_id'
    ) THEN
        ALTER TABLE menus ADD COLUMN permiso_id BIGINT DEFAULT NULL;
    END IF;
END $$;

UPDATE menus m
SET permiso_id = p.id
FROM permisos p
WHERE m.permiso_id IS NULL
  AND m.permiso_bd IS NOT NULL
  AND p.nombre = m.permiso_bd;

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'menus_parent_id_foreign'
    ) THEN
        ALTER TABLE menus
            ADD CONSTRAINT menus_parent_id_foreign
            FOREIGN KEY (parent_id) REFERENCES menus (id) ON DELETE CASCADE;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'menus_permiso_id_foreign'
    ) THEN
        ALTER TABLE menus
            ADD CONSTRAINT menus_permiso_id_foreign
            FOREIGN KEY (permiso_id) REFERENCES permisos (id) ON DELETE SET NULL;
    END IF;
END $$;

-- ---------------------------------------------------------------------------
-- password_reset_tokens → usuarios (mantiene email para Laravel Fortify)
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'password_reset_tokens' AND column_name = 'usuario_id'
    ) THEN
        ALTER TABLE password_reset_tokens ADD COLUMN usuario_id BIGINT DEFAULT NULL;
    END IF;
END $$;

UPDATE password_reset_tokens pr
SET usuario_id = u.id
FROM usuarios u
WHERE pr.usuario_id IS NULL AND u.email = pr.email;

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'password_reset_tokens_usuario_id_foreign'
    ) THEN
        ALTER TABLE password_reset_tokens
            ADD CONSTRAINT password_reset_tokens_usuario_id_foreign
            FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;
    END IF;
END $$;

CREATE UNIQUE INDEX IF NOT EXISTS password_reset_tokens_usuario_id_unique
    ON password_reset_tokens (usuario_id);

CREATE OR REPLACE FUNCTION fn_password_reset_usuario_id()
RETURNS TRIGGER AS $$
BEGIN
    SELECT id INTO NEW.usuario_id FROM usuarios WHERE email = NEW.email LIMIT 1;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_password_reset_usuario_id ON password_reset_tokens;
CREATE TRIGGER trg_password_reset_usuario_id
    BEFORE INSERT OR UPDATE ON password_reset_tokens
    FOR EACH ROW EXECUTE PROCEDURE fn_password_reset_usuario_id();

-- ---------------------------------------------------------------------------
-- personal_access_tokens → usuarios (Sanctum; mantiene tokenable_* por compatibilidad)
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'personal_access_tokens' AND column_name = 'usuario_id'
    ) THEN
        ALTER TABLE personal_access_tokens ADD COLUMN usuario_id BIGINT DEFAULT NULL;
    END IF;
END $$;

UPDATE personal_access_tokens
SET usuario_id = tokenable_id
WHERE usuario_id IS NULL AND tokenable_type LIKE '%Usuario%';

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'personal_access_tokens_usuario_id_foreign'
    ) THEN
        ALTER TABLE personal_access_tokens
            ADD CONSTRAINT personal_access_tokens_usuario_id_foreign
            FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;
    END IF;
END $$;

CREATE OR REPLACE FUNCTION fn_personal_access_token_usuario_id()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.tokenable_type LIKE '%Usuario%' THEN
        NEW.usuario_id := NEW.tokenable_id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_personal_access_token_usuario_id ON personal_access_tokens;
CREATE TRIGGER trg_personal_access_token_usuario_id
    BEFORE INSERT OR UPDATE ON personal_access_tokens
    FOR EACH ROW EXECUTE PROCEDURE fn_personal_access_token_usuario_id();

-- ---------------------------------------------------------------------------
-- cuotas_credito: quitar nota_venta_id redundante (ya está en pagos)
-- ---------------------------------------------------------------------------
ALTER TABLE cuotas_credito DROP CONSTRAINT IF EXISTS cuotas_credito_nota_venta_id_foreign;
DROP INDEX IF EXISTS cuotas_credito_nota_venta_id_index;

DO $$ BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'cuotas_credito' AND column_name = 'nota_venta_id'
    ) THEN
        ALTER TABLE cuotas_credito DROP COLUMN nota_venta_id;
    END IF;
END $$;

-- ---------------------------------------------------------------------------
-- movimientos_inventario: FKs directas en lugar de referencia_tipo/referencia_id
-- ---------------------------------------------------------------------------
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'movimientos_inventario' AND column_name = 'detalle_nota_venta_id'
    ) THEN
        ALTER TABLE movimientos_inventario ADD COLUMN detalle_nota_venta_id BIGINT DEFAULT NULL;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'movimientos_inventario' AND column_name = 'detalle_nota_compra_id'
    ) THEN
        ALTER TABLE movimientos_inventario ADD COLUMN detalle_nota_compra_id BIGINT DEFAULT NULL;
    END IF;
END $$;

UPDATE movimientos_inventario
SET detalle_nota_venta_id = referencia_id
WHERE detalle_nota_venta_id IS NULL
  AND referencia_tipo = 'nota_venta'
  AND referencia_id IS NOT NULL;

UPDATE movimientos_inventario
SET detalle_nota_compra_id = referencia_id
WHERE detalle_nota_compra_id IS NULL
  AND referencia_tipo = 'nota_compra'
  AND referencia_id IS NOT NULL;

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'movimientos_inventario_detalle_nota_venta_id_foreign'
    ) THEN
        ALTER TABLE movimientos_inventario
            ADD CONSTRAINT movimientos_inventario_detalle_nota_venta_id_foreign
            FOREIGN KEY (detalle_nota_venta_id) REFERENCES detalles_nota_venta (id) ON DELETE SET NULL;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'movimientos_inventario_detalle_nota_compra_id_foreign'
    ) THEN
        ALTER TABLE movimientos_inventario
            ADD CONSTRAINT movimientos_inventario_detalle_nota_compra_id_foreign
            FOREIGN KEY (detalle_nota_compra_id) REFERENCES detalles_nota_compra (id) ON DELETE SET NULL;
    END IF;
END $$;

DROP INDEX IF EXISTS movimientos_inventario_referencia_index;

DO $$ BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'movimientos_inventario' AND column_name = 'referencia_tipo'
    ) THEN
        ALTER TABLE movimientos_inventario DROP COLUMN referencia_tipo;
    END IF;
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'movimientos_inventario' AND column_name = 'referencia_id'
    ) THEN
        ALTER TABLE movimientos_inventario DROP COLUMN referencia_id;
    END IF;
END $$;

COMMIT;
