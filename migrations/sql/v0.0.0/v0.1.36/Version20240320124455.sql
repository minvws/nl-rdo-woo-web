-- Migration Version20240320124455
-- Generated on 2024-03-21 08:22:57 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD previous_version_link VARCHAR(255) DEFAULT NULL;
ALTER TABLE dossier ADD parties JSON DEFAULT NULL;


