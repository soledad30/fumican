-- Elimina tablas legacy que ya no usa Fumican Vet.
-- Ejecutar sobre clinica_veterinaria:
--   psql -U postgres -h 127.0.0.1 -d clinica_veterinaria -f database/schema/limpiar_tablas_obsoletas.sql
--
-- Tablas obsoletas:
--   persona              — reemplazada por clientes / veterinarios / usuarios
--   consulta_servicios   — servicio_id directo en consultas_medicas

BEGIN;

DO $$ BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'public' AND table_name = 'consulta_servicios'
    ) THEN
        UPDATE consultas_medicas cm
        SET servicio_id = cs.servicio_id
        FROM (
            SELECT DISTINCT ON (consulta_id) consulta_id, servicio_id
            FROM consulta_servicios
            ORDER BY consulta_id, id
        ) cs
        WHERE cm.id = cs.consulta_id
          AND cm.servicio_id IS NULL;
    END IF;
END $$;

DROP TABLE IF EXISTS consulta_servicios CASCADE;
DROP TABLE IF EXISTS persona CASCADE;

COMMIT;
