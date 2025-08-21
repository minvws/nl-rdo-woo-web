-- Migration Version20240207084512
-- Generated on 2024-02-14 08:18:41 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD type VARCHAR(255);
ALTER TABLE dossier ALTER publication_reason DROP NOT NULL;
UPDATE dossier SET type='woo-decision';
ALTER TABLE dossier ALTER type SET NOT NULL;
ALTER TABLE dossier ADD internal_reference VARCHAR(255);
UPDATE dossier SET internal_reference='';
ALTER TABLE dossier ALTER internal_reference SET NOT NULL;
ALTER TABLE dossier ALTER decision DROP NOT NULL;


