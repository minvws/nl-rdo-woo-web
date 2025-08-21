-- Migration Version20231219101111
-- Generated on 2023-12-20 12:40:25 by bin/console woopie:sql:dump
--

CREATE TABLE document_referrals (document_id UUID NOT NULL, referred_document_id UUID NOT NULL, PRIMARY KEY(document_id, referred_document_id));
CREATE INDEX IDX_945E037C33F7837 ON document_referrals (document_id);
CREATE INDEX IDX_945E037E75CCE85 ON document_referrals (referred_document_id);
COMMENT ON COLUMN document_referrals.document_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN document_referrals.referred_document_id IS '(DC2Type:uuid)';
ALTER TABLE document_referrals ADD CONSTRAINT FK_945E037C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE document_referrals ADD CONSTRAINT FK_945E037E75CCE85 FOREIGN KEY (referred_document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE document_referrals TO woopie;
