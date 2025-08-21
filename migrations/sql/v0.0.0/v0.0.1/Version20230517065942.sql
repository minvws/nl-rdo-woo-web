-- Migration Version20230517065942
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE woo_request (id UUID NOT NULL, casenr VARCHAR(255) NOT NULL, applicant VARCHAR(255) NOT NULL, description VARCHAR(1024) NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id));
ALTER TABLE woo_request OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE woo_request TO woopie;

COMMENT ON COLUMN woo_request.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN woo_request.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN woo_request.updated_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE dossier ADD woo_request_id UUID DEFAULT NULL;
COMMENT ON COLUMN dossier.woo_request_id IS '(DC2Type:uuid)';
ALTER TABLE dossier ADD CONSTRAINT FK_3D48E03767EE6561 FOREIGN KEY (woo_request_id) REFERENCES woo_request (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_3D48E03767EE6561 ON dossier (woo_request_id);


