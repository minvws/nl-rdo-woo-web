-- Migration Version20260928142915
-- Generated on 2026-09-28 14:29:45 by bin/console woopie:sql:dump
--

UPDATE subject SET landing_page_status = 'concept' WHERE landing_page_status IS NULL;
ALTER TABLE subject ALTER landing_page_status SET NOT NULL;
ALTER TABLE subject ALTER landing_page_content_tree_status SET NOT NULL;


