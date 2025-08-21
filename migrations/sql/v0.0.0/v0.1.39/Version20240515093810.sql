-- Migration Version20240515093810
-- Generated on 2024-05-15 10:31:54 by bin/console woopie:sql:dump
--

CREATE TABLE complaint_judgement_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id));
CREATE UNIQUE INDEX UNIQ_25D859F2611C0C56 ON complaint_judgement_document (dossier_id);
COMMENT ON COLUMN complaint_judgement_document.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN complaint_judgement_document.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN complaint_judgement_document.formal_date IS '(DC2Type:date_immutable)';
COMMENT ON COLUMN complaint_judgement_document.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN complaint_judgement_document.updated_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE complaint_judgement_document ADD CONSTRAINT FK_25D859F2611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE complaint_judgement_document TO woopie;
