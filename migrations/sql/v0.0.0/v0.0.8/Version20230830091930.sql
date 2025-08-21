-- Migration Version20230830091930
-- Generated on 2023-08-30 15:06:51 by bin/console woopie:sql:dump
--

CREATE TABLE raw_inventory (id UUID NOT NULL, dossier_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(255) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_AE096B07611C0C56 ON raw_inventory (dossier_id);
COMMENT ON COLUMN raw_inventory.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN raw_inventory.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN raw_inventory.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN raw_inventory.updated_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE raw_inventory ADD CONSTRAINT FK_AE096B07611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE raw_inventory OWNER TO woo_dba;
