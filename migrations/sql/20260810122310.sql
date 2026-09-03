-- Migration Version20260810122310
-- Generated on 2026-08-10 12:23:19 by bin/console woopie:sql:dump
--

ALTER TABLE subject ADD landing_page_slug VARCHAR(50) DEFAULT NULL;
ALTER TABLE subject ALTER landing_page_title TYPE VARCHAR(200);
CREATE UNIQUE INDEX UNIQ_FBCE3E7A2A22D98 ON subject (landing_page_slug);
