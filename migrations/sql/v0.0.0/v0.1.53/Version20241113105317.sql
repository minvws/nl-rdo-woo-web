-- Migration Version20241113105317
-- Generated on 2024-11-14 09:18:00 by bin/console woopie:sql:dump
--

CREATE TABLE document_file_set (
    id UUID NOT NULL,
    dossier_id UUID NOT NULL,
    status VARCHAR(255) NOT NULL,
    created_at TIMESTAMP (0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP (0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY (id)
);
CREATE INDEX idx_46965a1c611c0c56 ON document_file_set (dossier_id);
COMMENT ON COLUMN document_file_set.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN document_file_set.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN document_file_set.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN document_file_set.updated_at IS '(DC2Type:datetime_immutable)';
CREATE TABLE document_file_update (
    id UUID NOT NULL,
    document_file_set_id UUID NOT NULL,
    document_id UUID NOT NULL,
    type VARCHAR(255) NOT NULL,
    status VARCHAR(255) NOT NULL,
    created_at TIMESTAMP (0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP (0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY (id)
);
CREATE INDEX idx_a29b24ad2a451ccd ON document_file_update (
    document_file_set_id
);
CREATE INDEX idx_a29b24adc33f7837 ON document_file_update (document_id);
COMMENT ON COLUMN document_file_update.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN document_file_update.document_file_set_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN document_file_update.document_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN document_file_update.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN document_file_update.updated_at IS '(DC2Type:datetime_immutable)';
CREATE TABLE document_file_upload (
    id UUID NOT NULL,
    document_file_set_id UUID NOT NULL,
    status VARCHAR(255) NOT NULL,
    error VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP (0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP (0) WITHOUT TIME ZONE NOT NULL,
    file_mimetype VARCHAR(100) DEFAULT NULL,
    file_path VARCHAR(1024) DEFAULT NULL,
    file_hash VARCHAR(128) DEFAULT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(255) DEFAULT NULL,
    file_name VARCHAR(1024) DEFAULT NULL,
    file_uploaded BOOLEAN NOT NULL,
    file_source_type VARCHAR(255) DEFAULT NULL,
    file_page_count INT DEFAULT NULL,
    file_paginatable BOOLEAN DEFAULT FALSE NOT NULL,
    PRIMARY KEY (id)
);
CREATE INDEX idx_2d03f7ca2a451ccd ON document_file_upload (
    document_file_set_id
);
COMMENT ON COLUMN document_file_upload.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN document_file_upload.document_file_set_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN document_file_upload.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN document_file_upload.updated_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE document_file_set ADD CONSTRAINT fk_46965a1c611c0c56 FOREIGN KEY (
    dossier_id
) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE document_file_update ADD CONSTRAINT fk_a29b24ad2a451ccd FOREIGN KEY (
    document_file_set_id
) REFERENCES document_file_set (
    id
) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE document_file_update ADD CONSTRAINT fk_a29b24adc33f7837 FOREIGN KEY (
    document_id
) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE document_file_upload ADD CONSTRAINT fk_2d03f7ca2a451ccd FOREIGN KEY (
    document_file_set_id
) REFERENCES document_file_set (
    id
) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER INDEX uniq_ae096b07611c0c56 RENAME TO uniq_b8fe3d9611c0c56;
ALTER INDEX uniq_1846874c611c0c56 RENAME TO uniq_d8900ff7611c0c56;
CREATE UNIQUE INDEX unique_document_for_set ON document_file_update (
    document_file_set_id, document_id
);

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE document_file_set TO woopie;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE document_file_update TO woopie;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE document_file_upload TO woopie;
