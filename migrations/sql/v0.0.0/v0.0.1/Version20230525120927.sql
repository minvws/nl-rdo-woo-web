-- Migration Version20230525120927
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE document_dossier (document_id UUID NOT NULL, dossier_id UUID NOT NULL, PRIMARY KEY(document_id, dossier_id));
ALTER TABLE document_dossier OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE document_dossier TO woopie;
CREATE INDEX IDX_992746EBC33F7837 ON document_dossier (document_id);
CREATE INDEX IDX_992746EB611C0C56 ON document_dossier (dossier_id);
COMMENT ON COLUMN document_dossier.document_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN document_dossier.dossier_id IS '(DC2Type:uuid)';
ALTER TABLE document_dossier ADD CONSTRAINT FK_992746EBC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE document_dossier ADD CONSTRAINT FK_992746EB611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE document DROP CONSTRAINT fk_d8698a76611c0c56;
DROP INDEX idx_d8698a76611c0c56;
ALTER TABLE document DROP dossier_id;


