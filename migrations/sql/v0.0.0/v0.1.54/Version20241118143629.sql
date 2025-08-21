-- Migration Version20241118143629
-- Generated on 2024-11-19 14:56:43 by bin/console woopie:sql:dump
--

ALTER TABLE document_file_update ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE document_file_update ADD file_uploaded BOOLEAN NOT NULL;
ALTER TABLE document_file_update ADD file_size INT NOT NULL;
ALTER TABLE document_file_update ADD file_page_count INT DEFAULT NULL;
ALTER TABLE document_file_update ADD file_mimetype VARCHAR(100) DEFAULT NULL;
ALTER TABLE document_file_update ADD file_path VARCHAR(1024) DEFAULT NULL;
ALTER TABLE document_file_update ADD file_hash VARCHAR(128) DEFAULT NULL;
ALTER TABLE document_file_update ADD file_type VARCHAR(255) DEFAULT NULL;
ALTER TABLE document_file_update ADD file_name VARCHAR(1024) DEFAULT NULL;
ALTER TABLE document_file_update ADD file_source_type VARCHAR(255) DEFAULT NULL;


