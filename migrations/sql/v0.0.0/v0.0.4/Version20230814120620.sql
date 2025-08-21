-- Migration Version20230814120620
-- Generated on 2023-08-15 12:35:45 by bin/console woopie:sql:dump
--

CREATE TABLE decision_document (id UUID NOT NULL, dossier_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(255) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, PRIMARY KEY(id));
CREATE UNIQUE INDEX UNIQ_55B54548611C0C56 ON decision_document (dossier_id);
COMMENT ON COLUMN decision_document.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN decision_document.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN decision_document.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN decision_document.updated_at IS '(DC2Type:datetime_immutable)';
CREATE TABLE inventory (id UUID NOT NULL, dossier_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(255) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, PRIMARY KEY(id));
CREATE UNIQUE INDEX UNIQ_B12D4A36611C0C56 ON inventory (dossier_id);
COMMENT ON COLUMN inventory.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN inventory.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN inventory.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN inventory.updated_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE decision_document ADD CONSTRAINT FK_55B54548611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A36611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
INSERT INTO inventory(id, dossier_id, created_at, updated_at, file_mimetype, file_path, file_size, file_type, file_source_type, file_name, file_uploaded)
                                SELECT d.id, dd.dossier_id, d.created_at, d.updated_at, d.mimetype, d.filepath, d.filesize, d.file_type, d.source_type, d.filename, d.uploaded
                                FROM document d
                                JOIN document_dossier dd ON dd.document_id = d.id
                                WHERE d.class='inventory';
DELETE FROM document WHERE class='inventory';
INSERT INTO decision_document(id, dossier_id, created_at, updated_at, file_mimetype, file_path, file_size, file_type, file_source_type, file_name, file_uploaded)
                                SELECT d.id, dd.dossier_id, d.created_at, d.updated_at, d.mimetype, d.filepath, d.filesize, d.file_type, d.source_type, d.filename, d.uploaded
                                FROM document d
                                JOIN document_dossier dd ON dd.document_id = d.id
                                WHERE d.class='decision';
DELETE FROM document WHERE class='decision';
ALTER TABLE document DROP class;
ALTER TABLE document ALTER file_type DROP NOT NULL;
ALTER TABLE document RENAME COLUMN filename TO file_name;
ALTER TABLE document RENAME COLUMN source_type TO file_source_type;
ALTER TABLE document RENAME COLUMN mimetype TO file_mimetype;
ALTER TABLE document RENAME COLUMN filepath TO file_path;
ALTER TABLE document RENAME COLUMN filesize TO file_size;
ALTER TABLE document RENAME COLUMN uploaded TO file_uploaded;
ALTER TABLE dossier ADD inventory_id UUID DEFAULT NULL;
ALTER TABLE dossier ADD decision_document_id UUID DEFAULT NULL;
COMMENT ON COLUMN dossier.inventory_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN dossier.decision_document_id IS '(DC2Type:uuid)';
ALTER TABLE dossier ADD CONSTRAINT FK_3D48E0379EEA759 FOREIGN KEY (inventory_id) REFERENCES inventory (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE dossier ADD CONSTRAINT FK_3D48E0372ECDE55E FOREIGN KEY (decision_document_id) REFERENCES decision_document (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE UNIQUE INDEX UNIQ_3D48E0379EEA759 ON dossier (inventory_id);
CREATE UNIQUE INDEX UNIQ_3D48E0372ECDE55E ON dossier (decision_document_id);
ALTER TABLE inquiry_dossier DROP CONSTRAINT inquiry_dossier_pkey;
ALTER TABLE inquiry_dossier ADD PRIMARY KEY (dossier_id, inquiry_id);


