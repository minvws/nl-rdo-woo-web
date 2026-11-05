-- Migration Version20261017120000
-- Generated on 2026-10-17 10:04:41 by bin/console woopie:sql:dump
--

UPDATE subject SET landing_page_preview_token = gen_random_uuid() WHERE landing_page_preview_token IS NULL;
ALTER TABLE subject ALTER landing_page_preview_token SET NOT NULL;


