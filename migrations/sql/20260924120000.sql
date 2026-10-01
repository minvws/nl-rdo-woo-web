-- Migration Version20260924120000
-- Generated on 2026-09-24 12:00:00 by bin/console woopie:sql:dump
--

ALTER TABLE subject ADD landing_page_content_tree_status VARCHAR(255) DEFAULT NULL;
UPDATE subject SET landing_page_content_tree_status = CASE WHEN has_visible_landing_page_content_tree THEN 'published' ELSE 'concept' END;
ALTER TABLE subject DROP has_visible_landing_page_content_tree;

