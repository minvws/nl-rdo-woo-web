-- Migration Version20250710085154
-- Generated on 2025-07-21 13:10:06 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ALTER dossier_nr TYPE VARCHAR(50);
ALTER TABLE dossier ALTER previous_version_link TYPE VARCHAR(2048);


