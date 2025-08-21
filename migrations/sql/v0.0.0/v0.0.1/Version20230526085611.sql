-- Migration Version20230526085611
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE dossier_document DROP CONSTRAINT fk_f0296801611c0c56;
ALTER TABLE dossier_document DROP CONSTRAINT fk_f0296801c33f7837;
DROP TABLE dossier_document;


