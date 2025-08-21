-- Migration Version20231107090907
-- Generated on 2023-11-07 10:11:03 by bin/console woopie:sql:dump
--

CREATE TABLE inquiry_inventory (id UUID NOT NULL, inquiry_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id));
CREATE UNIQUE INDEX UNIQ_C857A5D8A7AD6D71 ON inquiry_inventory (inquiry_id);
COMMENT ON COLUMN inquiry_inventory.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN inquiry_inventory.inquiry_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN inquiry_inventory.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN inquiry_inventory.updated_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE inquiry_inventory ADD CONSTRAINT FK_C857A5D8A7AD6D71 FOREIGN KEY (inquiry_id) REFERENCES inquiry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE inquiry_inventory TO woopie;
