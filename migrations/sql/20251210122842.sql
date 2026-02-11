-- Migration Version20251210122842
-- Generated on 2025-12-10 15:17:27 by bin/console woopie:sql:dump
--

ALTER TABLE document ADD external_id VARCHAR(128) DEFAULT NULL;
CREATE UNIQUE INDEX document_unique_external_id ON document (external_id);


