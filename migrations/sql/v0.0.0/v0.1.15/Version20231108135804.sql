-- Migration Version20231108135804
-- Generated on 2023-11-14 12:02:33 by bin/console woopie:sql:dump
--

ALTER TABLE batch_download ADD inquiry_id UUID DEFAULT NULL;
ALTER TABLE batch_download ALTER dossier_id DROP NOT NULL;
COMMENT ON COLUMN batch_download.inquiry_id IS '(DC2Type:uuid)';
ALTER TABLE batch_download ADD CONSTRAINT FK_F3F4EC10A7AD6D71 FOREIGN KEY (inquiry_id) REFERENCES inquiry (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_F3F4EC10A7AD6D71 ON batch_download (inquiry_id);
ALTER TABLE batch_download ADD filename VARCHAR(255);


