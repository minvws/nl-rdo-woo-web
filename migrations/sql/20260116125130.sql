-- Migration Version20260116125130
-- Generated on 2026-01-16 13:27:57 by bin/console woopie:sql:dump
--

ALTER TABLE attachment ADD external_id VARCHAR(128) DEFAULT NULL;
CREATE UNIQUE INDEX UNIQ_795FD9BB611C0C569F75D7B0 ON attachment (dossier_id, external_id);
ALTER INDEX document_unique_external_id RENAME TO UNIQ_D8698A769F75D7B0;


