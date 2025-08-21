-- Migration Version20230929081242
-- Generated on 2023-10-02 09:24:18 by bin/console woopie:sql:dump
--

ALTER TABLE document_prefix ADD organisation_id UUID;
COMMENT ON COLUMN document_prefix.organisation_id IS '(DC2Type:uuid)';
ALTER TABLE document_prefix ADD CONSTRAINT FK_2DD337E89E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_2DD337E89E6B1585 ON document_prefix (organisation_id);


