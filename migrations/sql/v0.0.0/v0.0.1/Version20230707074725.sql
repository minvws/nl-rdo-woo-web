-- Migration Version20230707074725
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE inquiry_dossier (inquiry_id UUID NOT NULL, dossier_id UUID NOT NULL, PRIMARY KEY(inquiry_id, dossier_id));
ALTER TABLE inquiry_dossier OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE inquiry_dossier TO woopie;
CREATE INDEX IDX_D6558E92A7AD6D71 ON inquiry_dossier (inquiry_id);
CREATE INDEX IDX_D6558E92611C0C56 ON inquiry_dossier (dossier_id);
COMMENT ON COLUMN inquiry_dossier.inquiry_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN inquiry_dossier.dossier_id IS '(DC2Type:uuid)';
ALTER TABLE inquiry_dossier ADD CONSTRAINT FK_D6558E92A7AD6D71 FOREIGN KEY (inquiry_id) REFERENCES inquiry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE inquiry_dossier ADD CONSTRAINT FK_D6558E92611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;


