-- Migration Version20251201151332
-- Generated on 2025-12-01 15:15:38 by bin/console woopie:sql:dump
--

CREATE UNIQUE INDEX dossier_unique_external_id ON dossier (external_id, organisation_id);
