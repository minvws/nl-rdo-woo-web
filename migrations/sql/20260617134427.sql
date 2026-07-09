-- Migration Version20260617134427
-- Generated on 2026-06-19 10:08:21 by bin/console woopie:sql:dump
--

DROP INDEX dossier_unique_index;
ALTER TABLE dossier RENAME COLUMN dossier_nr TO dossier_number;
CREATE UNIQUE INDEX dossier_unique_index ON dossier (dossier_number, document_prefix);
DROP INDEX idx_d8698a7678aa5ba1;
ALTER TABLE document RENAME COLUMN document_nr TO document_number;
CREATE INDEX IDX_D8698A7628F2AE32 ON document (document_number);


