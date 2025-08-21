-- Migration Version20230829083116
-- Generated on 2023-08-29 11:37:06 by bin/console woopie:sql:dump
--

ALTER TABLE dossier DROP CONSTRAINT fk_3d48e0372ecde55e;
DROP INDEX uniq_3d48e0372ecde55e;
ALTER TABLE dossier DROP decision_document_id;


