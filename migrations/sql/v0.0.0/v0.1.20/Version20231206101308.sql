-- Migration Version20231206101308
-- Generated on 2023-12-06 11:36:32 by bin/console woopie:sql:dump
--

ALTER TABLE history ADD site VARCHAR(255) DEFAULT 'both' NOT NULL;


