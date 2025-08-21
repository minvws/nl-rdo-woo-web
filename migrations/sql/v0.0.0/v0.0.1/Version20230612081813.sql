-- Migration Version20230612081813
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE document_prefix ADD description VARCHAR(255) NOT NULL DEFAULT '';


