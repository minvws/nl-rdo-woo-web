-- Migration Version20240102145100
-- Generated on 2024-01-03 07:57:06 by bin/console woopie:sql:dump
--

DROP INDEX uniq_3d48e037da892fc0;
CREATE UNIQUE INDEX dossier_unique_index ON dossier (dossier_nr, document_prefix);


