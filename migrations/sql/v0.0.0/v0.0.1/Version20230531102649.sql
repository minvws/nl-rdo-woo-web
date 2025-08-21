-- Migration Version20230531102649
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE ingest_log ADD document_id UUID NOT NULL;
COMMENT ON COLUMN ingest_log.document_id IS '(DC2Type:uuid)';
ALTER TABLE ingest_log ADD CONSTRAINT FK_3B8D4059C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_3B8D4059C33F7837 ON ingest_log (document_id);


