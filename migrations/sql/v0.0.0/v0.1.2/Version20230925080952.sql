-- Migration Version20230925080952
-- Generated on 2023-09-25 10:51:30 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD preview_date DATE DEFAULT NULL;
COMMENT ON COLUMN dossier.preview_date IS '(DC2Type:date_immutable)';


