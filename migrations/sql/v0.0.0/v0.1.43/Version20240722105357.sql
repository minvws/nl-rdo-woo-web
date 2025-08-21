-- Migration Version20240722105357
-- Generated on 2024-07-23 11:05:13 by bin/console woopie:sql:dump
--

ALTER TABLE attachment ADD file_hash VARCHAR(128) DEFAULT NULL;
ALTER TABLE decision_document ADD file_hash VARCHAR(128) DEFAULT NULL;
ALTER TABLE document ADD file_hash VARCHAR(128) DEFAULT NULL;
ALTER TABLE inquiry_inventory ADD file_hash VARCHAR(128) DEFAULT NULL;
ALTER TABLE inventory ADD file_hash VARCHAR(128) DEFAULT NULL;
ALTER TABLE inventory_process_run ADD file_hash VARCHAR(128) DEFAULT NULL;
ALTER TABLE main_document ADD file_hash VARCHAR(128) DEFAULT NULL;
ALTER TABLE raw_inventory ADD file_hash VARCHAR(128) DEFAULT NULL;


