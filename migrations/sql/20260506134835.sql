-- Migration Version20260506134835
-- Generated on 2026-05-08 12:41:42 by bin/console woopie:sql:dump
--

CREATE TABLE notice_not_public (id UUID NOT NULL, document_name VARCHAR(255) DEFAULT NULL, formal_date DATE NOT NULL, grounds JSON NOT NULL, explanation TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, dossier_id UUID NOT NULL, PRIMARY KEY (id));
CREATE UNIQUE INDEX UNIQ_31A50C68611C0C56 ON notice_not_public (dossier_id);
ALTER TABLE notice_not_public ADD CONSTRAINT FK_31A50C68611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE;


