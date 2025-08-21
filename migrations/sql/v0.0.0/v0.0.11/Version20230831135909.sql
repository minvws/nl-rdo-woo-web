-- Migration Version20230831135909
-- Generated on 2023-09-04 07:00:18 by bin/console woopie:sql:dump
--
ALTER TABLE document ADD withdraw_reason VARCHAR(255) DEFAULT NULL;
ALTER TABLE document ADD withdraw_explanation TEXT DEFAULT NULL;
ALTER TABLE document ADD withdraw_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;
ALTER TABLE document ALTER suspended DROP DEFAULT;
ALTER TABLE document ALTER withdrawn DROP DEFAULT;
COMMENT ON COLUMN document.withdraw_date IS '(DC2Type:datetime_immutable)';


