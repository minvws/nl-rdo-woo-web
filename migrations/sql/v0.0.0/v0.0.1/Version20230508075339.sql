-- Migration Version20230508075339
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE document ADD department JSON NOT NULL DEFAULT '[]'::jsonb;
ALTER TABLE document ADD official JSON NOT NULL DEFAULT '[]'::jsonb;
ALTER TABLE document ADD document_type VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE document ADD subject JSON NOT NULL DEFAULT '[]'::jsonb;
ALTER TABLE document ADD publication_type VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE document ADD publication_section VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE document ADD document_number VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE document ADD document_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE document ADD status VARCHAR(255) NOT NULL DEFAULT '';


