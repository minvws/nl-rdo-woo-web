-- Migration Version20231127124128
-- Generated on 2023-11-30 13:10:08 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD organisation_id UUID;
COMMENT ON COLUMN dossier.organisation_id IS '(DC2Type:uuid)';
ALTER TABLE dossier ADD CONSTRAINT FK_3D48E0379E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_3D48E0379E6B1585 ON dossier (organisation_id);
ALTER TABLE inquiry ADD organisation_id UUID;
COMMENT ON COLUMN inquiry.organisation_id IS '(DC2Type:uuid)';
ALTER TABLE inquiry ADD CONSTRAINT FK_5A3903F09E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_5A3903F09E6B1585 ON inquiry (organisation_id);
UPDATE dossier SET organisation_id = (select organisation_id FROM document_prefix WHERE prefix = dossier.document_prefix);
UPDATE inquiry SET organisation_id = (select id from organisation limit 1);
ALTER TABLE dossier ALTER organisation_id SET NOT NULL;
ALTER TABLE inquiry ALTER organisation_id SET NOT NULL;


