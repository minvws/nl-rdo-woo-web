-- Migration Version20240515074143
-- Generated on 2024-05-15 10:31:54 by bin/console woopie:sql:dump
--

CREATE TABLE disposition_attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_82855B83611C0C56 ON disposition_attachment (dossier_id);
COMMENT ON COLUMN disposition_attachment.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN disposition_attachment.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN disposition_attachment.formal_date IS '(DC2Type:date_immutable)';
COMMENT ON COLUMN disposition_attachment.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN disposition_attachment.updated_at IS '(DC2Type:datetime_immutable)';
CREATE TABLE disposition_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id));
CREATE UNIQUE INDEX UNIQ_CDBCE77E611C0C56 ON disposition_document (dossier_id);
COMMENT ON COLUMN disposition_document.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN disposition_document.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN disposition_document.formal_date IS '(DC2Type:date_immutable)';
COMMENT ON COLUMN disposition_document.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN disposition_document.updated_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE disposition_attachment ADD CONSTRAINT FK_82855B83611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE disposition_document ADD CONSTRAINT FK_CDBCE77E611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE disposition_attachment TO woopie;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE disposition_document TO woopie;

