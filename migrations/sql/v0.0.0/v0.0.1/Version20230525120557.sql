-- Migration Version20230525120557
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE dossier_document (dossier_id UUID NOT NULL, document_id UUID NOT NULL, PRIMARY KEY(dossier_id, document_id));
ALTER TABLE dossier_document OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE dossier_document TO woopie;
CREATE INDEX IDX_F0296801611C0C56 ON dossier_document (dossier_id);
CREATE INDEX IDX_F0296801C33F7837 ON dossier_document (document_id);
COMMENT ON COLUMN dossier_document.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN dossier_document.document_id IS '(DC2Type:uuid)';
ALTER TABLE dossier_document ADD CONSTRAINT FK_F0296801611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE dossier_document ADD CONSTRAINT FK_F0296801C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE document ADD slurper_progress_id UUID DEFAULT NULL;
ALTER TABLE document ALTER mimetype DROP NOT NULL;
ALTER TABLE document ALTER filepath DROP NOT NULL;
COMMENT ON COLUMN document.slurper_progress_id IS '(DC2Type:uuid)';
ALTER TABLE document ADD CONSTRAINT FK_D8698A768047EA50 FOREIGN KEY (slurper_progress_id) REFERENCES slurper_progress (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE UNIQUE INDEX UNIQ_D8698A768047EA50 ON document (slurper_progress_id);
ALTER TABLE dossier ALTER dossier_nr SET DEFAULT '';
UPDATE dossier SET dossier_nr = '' WHERE dossier_nr IS NULL;
ALTER TABLE dossier ALTER dossier_nr SET NOT NULL;
ALTER TABLE dossier ALTER summary DROP DEFAULT;
ALTER TABLE dossier ALTER document_prefix DROP DEFAULT;


