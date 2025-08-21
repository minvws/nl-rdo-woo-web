-- Migration Version20230505110715
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE document ALTER dossier_id SET NOT NULL;
ALTER TABLE document ALTER summary DROP NOT NULL;
ALTER TABLE document ALTER title DROP NOT NULL;
ALTER TABLE dossier DROP hash;
