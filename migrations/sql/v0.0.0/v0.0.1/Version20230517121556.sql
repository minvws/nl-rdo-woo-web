-- Migration Version20230517121556
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE document ADD family_id INT NOT NULL DEFAULT 0;
ALTER TABLE document ADD document_id INT NOT NULL DEFAULT 0;
ALTER TABLE document ADD thread_id INT NOT NULL DEFAULT 0;
ALTER TABLE document ADD judgement VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE document ADD grounds TEXT NOT NULL DEFAULT '[]';
ALTER TABLE document ADD subjects TEXT NOT NULL DEFAULT '[]';
ALTER TABLE document ADD period VARCHAR(255) NOT NULL DEFAULT '';
COMMENT ON COLUMN document.grounds IS '(DC2Type:array)';
COMMENT ON COLUMN document.subjects IS '(DC2Type:array)';
ALTER TABLE dossier ALTER status DROP DEFAULT;


