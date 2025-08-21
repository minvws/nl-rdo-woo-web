-- Migration Version20231214125604
-- Generated on 2023-12-14 14:46:55 by bin/console woopie:sql:dump
--

ALTER TABLE document ADD links JSON DEFAULT NULL;
UPDATE document SET links = json_build_array(link);
ALTER TABLE document DROP link;


