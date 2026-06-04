-- Migration Version20260318144920
-- Generated on 2026-03-18 14:49:36 by bin/console woopie:sql:dump
--

ALTER TABLE organisation_department DROP CONSTRAINT fk_45f0f7b89e6b1585;
ALTER TABLE organisation_department ADD CONSTRAINT FK_45F0F7B89E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) ON DELETE CASCADE;
ALTER TABLE upload ALTER external_id TYPE VARCHAR(128);
CREATE INDEX IDX_17BDE61F9F75D7B0 ON upload (external_id);


