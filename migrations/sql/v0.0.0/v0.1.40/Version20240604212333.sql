-- Migration Version20240604212333
-- Generated on 2024-06-11 14:57:01 by bin/console woopie:sql:dump
--

ALTER TABLE annual_report_attachment ADD file_page_count INT DEFAULT NULL;
ALTER TABLE annual_report_attachment ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE annual_report_document ADD file_page_count INT DEFAULT NULL;
ALTER TABLE annual_report_document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE complaint_judgement_document ADD file_page_count INT DEFAULT NULL;
ALTER TABLE complaint_judgement_document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE covenant_attachment ADD file_page_count INT DEFAULT NULL;
ALTER TABLE covenant_attachment ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE covenant_document ADD file_page_count INT DEFAULT NULL;
ALTER TABLE covenant_document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE decision_attachment ADD file_page_count INT DEFAULT NULL;
ALTER TABLE decision_attachment ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE decision_document ADD file_page_count INT DEFAULT NULL;
ALTER TABLE decision_document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE disposition_attachment ADD file_page_count INT DEFAULT NULL;
ALTER TABLE disposition_attachment ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE disposition_document ADD file_page_count INT DEFAULT NULL;
ALTER TABLE disposition_document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE document ADD file_page_count INT DEFAULT NULL;
ALTER TABLE document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE inquiry_inventory ADD file_page_count INT DEFAULT NULL;
ALTER TABLE inquiry_inventory ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE inventory ADD file_page_count INT DEFAULT NULL;
ALTER TABLE inventory ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE inventory_process_run ADD file_page_count INT DEFAULT NULL;
ALTER TABLE inventory_process_run ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE investigation_report_attachment ADD file_page_count INT DEFAULT NULL;
ALTER TABLE investigation_report_attachment ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE investigation_report_document ADD file_page_count INT DEFAULT NULL;
ALTER TABLE investigation_report_document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE raw_inventory ADD file_page_count INT DEFAULT NULL;
ALTER TABLE raw_inventory ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;
UPDATE annual_report_attachment SET file_paginatable = true;
UPDATE annual_report_document SET file_paginatable = true;
UPDATE complaint_judgement_document SET file_paginatable = true;
UPDATE covenant_attachment SET file_paginatable = true;
UPDATE covenant_document SET file_paginatable = true;
UPDATE decision_attachment SET file_paginatable = true;
UPDATE decision_document SET file_paginatable = true;
UPDATE disposition_attachment SET file_paginatable = true;
UPDATE disposition_document SET file_paginatable = true;
UPDATE investigation_report_attachment SET file_paginatable = true;
UPDATE investigation_report_document SET file_paginatable = true;
UPDATE document SET file_paginatable = true;


