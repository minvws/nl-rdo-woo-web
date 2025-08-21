-- Migration Version20230612185924
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE document RENAME COLUMN document_number TO document_nr;
ALTER TABLE dossier ALTER dossier_nr DROP DEFAULT;


