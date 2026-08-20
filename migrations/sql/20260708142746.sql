-- Migration Version20260708142746
-- Generated on 2026-07-09 11:20:55 by bin/console woopie:sql:dump
--

ALTER TABLE document ADD publication_context VARCHAR(255) DEFAULT NULL;


