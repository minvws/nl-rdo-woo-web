-- Migration Version20260804124120
-- Generated on 2026-08-04 12:42:27 by bin/console woopie:sql:dump
--

ALTER TABLE subject ADD landing_page_title VARCHAR(100) DEFAULT NULL;
ALTER TABLE subject ADD landing_page_description VARCHAR(10000) DEFAULT NULL;
ALTER TABLE subject ADD landing_page_status VARCHAR(255) DEFAULT NULL;
ALTER TABLE subject ADD landing_page_preview_token UUID DEFAULT NULL;
ALTER TABLE subject ADD landing_page_content_tree JSONB DEFAULT NULL;
CREATE UNIQUE INDEX UNIQ_FBCE3E7A737B07F3 ON subject (landing_page_preview_token);
