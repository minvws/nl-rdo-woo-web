-- Migration Version20230531102355
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE ingest_log (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, success BOOLEAN NOT NULL, event VARCHAR(255) NOT NULL, message VARCHAR(255) NOT NULL, PRIMARY KEY(id));
ALTER TABLE ingest_log OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE ingest_log TO woopie;
COMMENT ON COLUMN ingest_log.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN ingest_log.created_at IS '(DC2Type:datetime_immutable)';


