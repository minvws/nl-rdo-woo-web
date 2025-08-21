-- Migration Version20230815072107
-- Generated on 2023-08-15 12:35:45 by bin/console woopie:sql:dump
--

ALTER TABLE document ADD link VARCHAR(255) DEFAULT NULL;
ALTER TABLE document ADD remark TEXT DEFAULT NULL;


