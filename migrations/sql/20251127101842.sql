-- Migration Version20251127101842
-- Generated on 2025-11-27 10:18:51 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD external_id VARCHAR(128) DEFAULT NULL;
CREATE INDEX IDX_3D48E0379F75D7B0 ON dossier (external_id);


