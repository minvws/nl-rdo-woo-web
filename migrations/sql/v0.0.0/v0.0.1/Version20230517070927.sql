-- Migration Version20230517070927
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE woo_request ADD dossier_id UUID DEFAULT NULL;
COMMENT ON COLUMN woo_request.dossier_id IS '(DC2Type:uuid)';
ALTER TABLE woo_request ADD CONSTRAINT FK_3555569F611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_3555569F611C0C56 ON woo_request (dossier_id);


