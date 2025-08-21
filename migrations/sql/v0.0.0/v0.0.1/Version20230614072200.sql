-- Migration Version20230614072200
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD decision VARCHAR(255) NOT NULL DEFAULT 'partial_public';


