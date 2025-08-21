-- Migration Version20231206104824
-- Generated on 2023-12-08 07:26:49 by bin/console woopie:sql:dump
--

ALTER TABLE document_prefix ADD archived BOOLEAN DEFAULT false NOT NULL;


