-- Migration Version20230627074027
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE batch_download (id UUID NOT NULL, dossier_id UUID NOT NULL, expiration TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, downloaded INT NOT NULL, documents JSON NOT NULL, PRIMARY KEY(id));
ALTER TABLE batch_download OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE batch_download TO woopie;

CREATE INDEX IDX_F3F4EC10611C0C56 ON batch_download (dossier_id);
COMMENT ON COLUMN batch_download.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN batch_download.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN batch_download.expiration IS '(DC2Type:datetime_immutable)';
ALTER TABLE batch_download ADD CONSTRAINT FK_F3F4EC10611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE;


