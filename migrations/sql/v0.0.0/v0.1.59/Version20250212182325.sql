-- Migration Version20250212182325
-- Generated on 2025-02-13 09:08:30 by bin/console woopie:sql:dump
--

CREATE TABLE woo_index_sitemap (id UUID NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id));
COMMENT ON COLUMN woo_index_sitemap.id IS '(DC2Type:uuid)';


