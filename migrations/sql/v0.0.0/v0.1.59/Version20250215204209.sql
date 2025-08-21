-- Migration Version20250215204209
-- Generated on 2025-02-17 08:01:46 by bin/console woopie:sql:dump
--

ALTER TABLE woo_index_sitemap ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL;
ALTER TABLE woo_index_sitemap ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL;
COMMENT ON COLUMN woo_index_sitemap.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN woo_index_sitemap.updated_at IS '(DC2Type:datetime_immutable)';


