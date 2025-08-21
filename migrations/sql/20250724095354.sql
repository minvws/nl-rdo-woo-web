-- Migration Version20250724095354
-- Generated on 2025-07-24 09:55:05 by bin/console woopie:sql:dump
--

ALTER TABLE department ADD responsibility_content VARCHAR(1000) DEFAULT NULL;
ALTER TABLE department ALTER landing_page_description TYPE VARCHAR(10000);
ALTER TABLE department ALTER feedback_content TYPE VARCHAR(10000);


