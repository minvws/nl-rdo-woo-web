-- Migration Version20231127080920
-- Generated on 2023-11-29 07:33:19 by bin/console woopie:sql:dump
--

ALTER TABLE dossier_government_official DROP CONSTRAINT fk_4adf1a7d611c0c56;
ALTER TABLE dossier_government_official DROP CONSTRAINT fk_c79596a154de3212;
DROP TABLE dossier_government_official;
DROP TABLE government_official;


