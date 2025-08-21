-- Migration Version20240320135010
-- Generated on 2024-03-21 08:22:57 by bin/console woopie:sql:dump
--

CREATE TABLE covenant_attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(10) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_9FAB57AB611C0C56 ON covenant_attachment (dossier_id);
COMMENT ON COLUMN covenant_attachment.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN covenant_attachment.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN covenant_attachment.formal_date IS '(DC2Type:date_immutable)';
COMMENT ON COLUMN covenant_attachment.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN covenant_attachment.updated_at IS '(DC2Type:datetime_immutable)';
CREATE TABLE covenant_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(10) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id));
CREATE UNIQUE INDEX UNIQ_D8A14D7D611C0C56 ON covenant_document (dossier_id);
COMMENT ON COLUMN covenant_document.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN covenant_document.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN covenant_document.formal_date IS '(DC2Type:date_immutable)';
COMMENT ON COLUMN covenant_document.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN covenant_document.updated_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE covenant_attachment ADD CONSTRAINT FK_9FAB57AB611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE covenant_document ADD CONSTRAINT FK_D8A14D7D611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

GRANT SELECT,INSERT,UPDATE ON TABLE covenant_attachment TO woopie;
GRANT SELECT,INSERT,UPDATE ON TABLE covenant_document TO woopie;

