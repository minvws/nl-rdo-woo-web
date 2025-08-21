-- Migration Version20230726072400
-- Generated on 2023-07-26 07:58:34 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD publication_date DATE DEFAULT NULL;
COMMENT ON COLUMN dossier.publication_date IS '(DC2Type:date_immutable)';


