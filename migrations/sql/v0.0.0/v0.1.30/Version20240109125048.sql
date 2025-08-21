-- Migration Version20240109125048
-- Generated on 2024-01-10 08:02:19 by bin/console woopie:sql:dump
--

ALTER TABLE document ALTER document_date TYPE TIMESTAMP(0) WITHOUT TIME ZONE;
ALTER TABLE document ALTER document_date DROP NOT NULL;
COMMENT ON COLUMN document.document_date IS '(DC2Type:datetime_immutable)';


