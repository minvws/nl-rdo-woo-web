-- Migration Version20230829082733
-- Generated on 2023-08-29 11:37:06 by bin/console woopie:sql:dump
--

ALTER TABLE dossier DROP CONSTRAINT fk_3d48e0379eea759;
DROP INDEX uniq_3d48e0379eea759;
ALTER TABLE dossier DROP inventory_id;


