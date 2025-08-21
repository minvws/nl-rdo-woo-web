-- Migration Version20230523084457
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE woo_request_document (woo_request_id UUID NOT NULL, document_id UUID NOT NULL, PRIMARY KEY(woo_request_id, document_id));
ALTER TABLE woo_request_document OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE woo_request_document TO woopie;
CREATE INDEX IDX_5596E8B667EE6561 ON woo_request_document (woo_request_id);
CREATE INDEX IDX_5596E8B6C33F7837 ON woo_request_document (document_id);
COMMENT ON COLUMN woo_request_document.woo_request_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN woo_request_document.document_id IS '(DC2Type:uuid)';
ALTER TABLE woo_request_document ADD CONSTRAINT FK_5596E8B667EE6561 FOREIGN KEY (woo_request_id) REFERENCES woo_request (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE woo_request_document ADD CONSTRAINT FK_5596E8B6C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE token ALTER dossier_id DROP NOT NULL;
ALTER TABLE woo_request DROP CONSTRAINT fk_3555569f611c0c56;
DROP INDEX idx_3555569f611c0c56;
ALTER TABLE woo_request DROP dossier_id;
ALTER TABLE woo_request DROP applicant;
ALTER TABLE woo_request DROP description;
ALTER TABLE woo_request DROP status;


