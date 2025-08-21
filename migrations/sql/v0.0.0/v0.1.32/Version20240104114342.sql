-- Migration Version20240104114342
-- Generated on 2024-01-30 12:45:12 by bin/console woopie:sql:dump
--

CREATE TABLE decision_attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_3341903B611C0C56 ON decision_attachment (dossier_id);
COMMENT ON COLUMN decision_attachment.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN decision_attachment.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN decision_attachment.formal_date IS '(DC2Type:date_immutable)';
COMMENT ON COLUMN decision_attachment.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN decision_attachment.updated_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE decision_attachment ADD CONSTRAINT FK_3341903B611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
DROP INDEX idx_ae096b07611c0c56;
CREATE UNIQUE INDEX UNIQ_AE096B07611C0C56 ON raw_inventory (dossier_id);

GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE decision_attachment TO woopie;