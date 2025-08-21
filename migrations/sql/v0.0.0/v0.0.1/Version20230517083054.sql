-- Migration Version20230517083054
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD dossier_nr VARCHAR(255) DEFAULT NULL;
ALTER TABLE dossier ADD title VARCHAR(500) NOT NULL DEFAULT '';


