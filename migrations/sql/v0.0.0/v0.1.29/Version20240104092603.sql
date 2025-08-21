-- Migration Version20240104092603
-- Generated on 2024-01-04 12:11:07 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ALTER publication_date TYPE TIMESTAMP(0) WITHOUT TIME ZONE;
COMMENT ON COLUMN dossier.publication_date IS '(DC2Type:datetime_immutable)';


