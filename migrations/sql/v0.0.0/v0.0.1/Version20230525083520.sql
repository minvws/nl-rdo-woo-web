-- Migration Version20230525083520
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD summary TEXT NOT NULL DEFAULT '';
ALTER TABLE dossier ADD document_prefix VARCHAR(255) NOT NULL DEFAULT '';


