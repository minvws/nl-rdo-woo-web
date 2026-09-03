-- Migration Version20260817115346
-- Generated on 2026-08-17 11:58:16 by bin/console woopie:sql:dump
--

ALTER TABLE subject ADD has_visible_landing_page_content_tree BOOLEAN DEFAULT false NOT NULL;


