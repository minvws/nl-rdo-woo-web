-- Migration Version20231018084238
-- Generated on 2023-10-25 18:17:44 by bin/console woopie:sql:dump
--

CREATE TABLE inventory_process_run (id UUID NOT NULL, dossier_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, generic_errors JSON NOT NULL, row_errors JSON NOT NULL, status VARCHAR(255) NOT NULL, progress SMALLINT NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id));
CREATE UNIQUE INDEX UNIQ_1846874C611C0C56 ON inventory_process_run (dossier_id);
COMMENT ON COLUMN inventory_process_run.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN inventory_process_run.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN inventory_process_run.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN inventory_process_run.started_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN inventory_process_run.ended_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE inventory_process_run ADD CONSTRAINT FK_1846874C611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE inventory_process_run OWNER TO woo_dba;
GRANT SELECT,INSERT ON TABLE inventory_process_run TO woopie;

