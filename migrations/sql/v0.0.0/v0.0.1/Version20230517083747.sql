-- Migration Version20230517083747
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD status VARCHAR(255) NOT NULL DEFAULT 'publicated';
ALTER TABLE dossier ALTER title DROP DEFAULT;


