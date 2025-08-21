-- Migration Version20241230095031
-- Generated on 2024-12-31 11:15:19 by bin/console woopie:sql:dump
--

ALTER TABLE attachment ADD withdrawn BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE attachment ADD withdraw_reason VARCHAR(255) DEFAULT NULL;
ALTER TABLE attachment ADD withdraw_explanation TEXT DEFAULT NULL;
ALTER TABLE attachment ADD withdraw_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;
COMMENT ON COLUMN attachment.withdraw_date IS '(DC2Type:datetime_immutable)';


