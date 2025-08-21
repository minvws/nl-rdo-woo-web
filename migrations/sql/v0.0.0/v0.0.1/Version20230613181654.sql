-- Migration Version20230613181654
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD date_from DATE NULL;
ALTER TABLE dossier ADD date_to DATE NULL;
ALTER TABLE dossier ADD publication_reason VARCHAR(255) NOT NULL DEFAULT '';
COMMENT ON COLUMN dossier.date_from IS '(DC2Type:date_immutable)';
COMMENT ON COLUMN dossier.date_to IS '(DC2Type:date_immutable)';


