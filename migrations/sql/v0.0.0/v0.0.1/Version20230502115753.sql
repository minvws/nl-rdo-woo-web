-- Migration Version20230502115753
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE document (id UUID NOT NULL, dossier_id UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, mimetype VARCHAR(100) NOT NULL, filepath VARCHAR(1024) NOT NULL, filesize INT NOT NULL, page_count INT NOT NULL, duration INT NOT NULL, summary TEXT NOT NULL, title TEXT NOT NULL, PRIMARY KEY(id));
ALTER TABLE document OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE document TO woopie;

CREATE INDEX IDX_D8698A76611C0C56 ON document (dossier_id);
COMMENT ON COLUMN document.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN document.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN document.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN document.updated_at IS '(DC2Type:datetime_immutable)';

CREATE TABLE dossier (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, hash VARCHAR(100) NOT NULL, PRIMARY KEY(id));
GRANT SELECT,INSERT,UPDATE ON TABLE dossier TO woopie;
COMMENT ON COLUMN dossier.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN dossier.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN dossier.updated_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE dossier OWNER TO woo_dba;


CREATE TABLE slurper_progress (id UUID NOT NULL, document_id UUID DEFAULT NULL, content TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, pages_processed INT NOT NULL, page_count INT NOT NULL, PRIMARY KEY(id));
ALTER TABLE slurper_progress OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE slurper_progress TO woopie;

CREATE UNIQUE INDEX UNIQ_B407F9FDC33F7837 ON slurper_progress (document_id);
COMMENT ON COLUMN slurper_progress.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN slurper_progress.document_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN slurper_progress.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN slurper_progress.updated_at IS '(DC2Type:datetime_immutable)';

ALTER TABLE slurper_progress ADD CONSTRAINT FK_B407F9FDC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE document ADD CONSTRAINT FK_D8698A76611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE;


