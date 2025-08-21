-- Migration Version20230927083429
-- Generated on 2023-09-27 09:03:17 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD decision_date DATE DEFAULT NULL;
ALTER TABLE dossier ALTER completed DROP DEFAULT;
UPDATE dossier SET decision_date = publication_date;
COMMENT ON COLUMN dossier.decision_date IS '(DC2Type:date_immutable)';


