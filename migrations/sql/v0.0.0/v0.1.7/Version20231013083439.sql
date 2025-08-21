-- Migration Version20231013083439
-- Generated on 2023-10-13 10:37:47 by bin/console woopie:sql:dump
--

ALTER TABLE decision_document ALTER file_name TYPE VARCHAR(1024);
ALTER TABLE document ALTER file_name TYPE VARCHAR(1024);
ALTER TABLE document ALTER link TYPE VARCHAR(2048);
ALTER TABLE inventory ALTER file_name TYPE VARCHAR(1024);
ALTER TABLE raw_inventory ALTER file_name TYPE VARCHAR(1024);


