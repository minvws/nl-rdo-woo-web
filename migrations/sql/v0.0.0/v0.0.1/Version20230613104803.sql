-- Migration Version20230613104803
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE document DROP CONSTRAINT fk_d8698a768047ea50;
ALTER TABLE document ADD source_type VARCHAR(255) NOT NULL DEFAULT 'pdf';
ALTER TABLE document ADD class VARCHAR(255) NOT NULL DEFAULT 'App\\Entity\\Document';
ALTER TABLE document RENAME COLUMN document_type TO file_type;


