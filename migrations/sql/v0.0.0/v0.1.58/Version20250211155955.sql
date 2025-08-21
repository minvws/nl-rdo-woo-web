-- Migration Version20250211155955
-- Generated on 2025-02-12 12:55:57 by bin/console woopie:sql:dump
--

ALTER TABLE attachment ALTER file_size TYPE BIGINT;
ALTER TABLE batch_download ALTER filename SET NOT NULL;
ALTER TABLE document ALTER file_size TYPE BIGINT;
ALTER TABLE document_file_update ALTER file_size TYPE BIGINT;
ALTER TABLE document_file_upload ALTER file_size TYPE BIGINT;
ALTER TABLE inquiry_inventory ALTER file_size TYPE BIGINT;
ALTER TABLE inventory ALTER file_size TYPE BIGINT;
ALTER TABLE main_document ALTER file_size TYPE BIGINT;
ALTER TABLE production_report ALTER file_size TYPE BIGINT;
ALTER TABLE production_report_process_run ALTER file_size TYPE BIGINT;


