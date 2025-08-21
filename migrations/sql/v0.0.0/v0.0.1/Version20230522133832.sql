-- Migration Version20230522133832
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE token (id UUID NOT NULL, dossier_id UUID NOT NULL, expiry_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, remark VARCHAR(1024) DEFAULT NULL, PRIMARY KEY(id));
ALTER TABLE token OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE token TO woopie;
CREATE INDEX IDX_5F37A13B611C0C56 ON token (dossier_id);
COMMENT ON COLUMN token.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN token.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN token.expiry_date IS '(DC2Type:datetime_immutable)';
ALTER TABLE token ADD CONSTRAINT FK_5F37A13B611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE;


