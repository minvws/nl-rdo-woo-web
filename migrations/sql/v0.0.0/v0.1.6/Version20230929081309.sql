-- Migration Version20230929081309
-- Generated on 2023-10-02 09:24:18 by bin/console woopie:sql:dump
--

UPDATE document_prefix SET organisation_id = (SELECT id FROM "organisation" WHERE name LIKE 'Programmadirectie Openbaarheid');;
ALTER TABLE document_prefix ALTER organisation_id SET NOT NULL;


