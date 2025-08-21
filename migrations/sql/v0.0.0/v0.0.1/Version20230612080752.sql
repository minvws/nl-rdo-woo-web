-- Migration Version20230612080752
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE document_prefix (id UUID NOT NULL, prefix VARCHAR(255) NOT NULL, PRIMARY KEY(id));
ALTER TABLE document_prefix OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE document_prefix TO woopie;
COMMENT ON COLUMN document_prefix.id IS '(DC2Type:uuid)';


