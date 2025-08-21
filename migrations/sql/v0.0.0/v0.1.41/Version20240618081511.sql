-- Migration Version20240618081511
-- Generated on 2024-06-18 13:00:11 by bin/console woopie:sql:dump
--

CREATE TABLE attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, entity_type VARCHAR(255) NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_795FD9BB611C0C56 ON attachment (dossier_id);
COMMENT ON COLUMN attachment.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN attachment.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN attachment.formal_date IS '(DC2Type:date_immutable)';
COMMENT ON COLUMN attachment.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN attachment.updated_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE investigation_report_document DROP CONSTRAINT fk_fe90a702611c0c56;
ALTER TABLE annual_report_document DROP CONSTRAINT fk_9a836917611c0c56;
ALTER TABLE covenant_document DROP CONSTRAINT fk_d8a14d7d611c0c56;
ALTER TABLE decision_attachment DROP CONSTRAINT fk_3341903b611c0c56;
ALTER TABLE disposition_attachment DROP CONSTRAINT fk_82855b83611c0c56;
ALTER TABLE annual_report_attachment DROP CONSTRAINT fk_3cba7275611c0c56;
ALTER TABLE investigation_report_attachment DROP CONSTRAINT fk_77d31fc5611c0c56;
ALTER TABLE covenant_attachment DROP CONSTRAINT fk_9fab57ab611c0c56;
ALTER TABLE disposition_document DROP CONSTRAINT fk_cdbce77e611c0c56;
ALTER TABLE complaint_judgement_document DROP CONSTRAINT fk_25d859f2611c0c56;
CREATE TABLE main_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, entity_type VARCHAR(255) NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_14B9B03611C0C56 ON main_document (dossier_id);
COMMENT ON COLUMN main_document.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN main_document.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN main_document.formal_date IS '(DC2Type:date_immutable)';
COMMENT ON COLUMN main_document.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN main_document.updated_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE main_document ADD CONSTRAINT FK_14B9B03611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
INSERT INTO attachment(
                            entity_type,
                            id,
                            dossier_id,
                            formal_date,
                            type,
                            internal_reference,
                            language,
                            grounds,
                            created_at,
                            updated_at,
                            file_mimetype,
                            file_path,
                            file_size,
                            file_type,
                            file_source_type,
                            file_name,
                            file_uploaded,
                            file_page_count,
                            file_paginatable
                       ) SELECT
                             'covenant_attachment',
                             a.id,
                             a.dossier_id,
                             a.formal_date,
                             a.type,
                             a.internal_reference,
                             a.language,
                             a.grounds,
                             a.created_at,
                             a.updated_at,
                             a.file_mimetype,
                             a.file_path,
                             a.file_size,
                             a.file_type,
                             a.file_source_type,
                             a.file_name,
                             a.file_uploaded,
                             a.file_page_count,
                             a.file_paginatable
                       FROM covenant_attachment a;
INSERT INTO attachment(
                            entity_type,
                            id,
                            dossier_id,
                            formal_date,
                            type,
                            internal_reference,
                            language,
                            grounds,
                            created_at,
                            updated_at,
                            file_mimetype,
                            file_path,
                            file_size,
                            file_type,
                            file_source_type,
                            file_name,
                            file_uploaded,
                            file_page_count,
                            file_paginatable
                       ) SELECT
                             'annual_report_attachment',
                             a.id,
                             a.dossier_id,
                             a.formal_date,
                             a.type,
                             a.internal_reference,
                             a.language,
                             a.grounds,
                             a.created_at,
                             a.updated_at,
                             a.file_mimetype,
                             a.file_path,
                             a.file_size,
                             a.file_type,
                             a.file_source_type,
                             a.file_name,
                             a.file_uploaded,
                             a.file_page_count,
                             a.file_paginatable
                       FROM annual_report_attachment a;
INSERT INTO attachment(
                            entity_type,
                            id,
                            dossier_id,
                            formal_date,
                            type,
                            internal_reference,
                            language,
                            grounds,
                            created_at,
                            updated_at,
                            file_mimetype,
                            file_path,
                            file_size,
                            file_type,
                            file_source_type,
                            file_name,
                            file_uploaded,
                            file_page_count,
                            file_paginatable
                       ) SELECT
                             'decision_attachment',
                             a.id,
                             a.dossier_id,
                             a.formal_date,
                             a.type,
                             a.internal_reference,
                             a.language,
                             a.grounds,
                             a.created_at,
                             a.updated_at,
                             a.file_mimetype,
                             a.file_path,
                             a.file_size,
                             a.file_type,
                             a.file_source_type,
                             a.file_name,
                             a.file_uploaded,
                             a.file_page_count,
                             a.file_paginatable
                       FROM decision_attachment a;
INSERT INTO attachment(
                            entity_type,
                            id,
                            dossier_id,
                            formal_date,
                            type,
                            internal_reference,
                            language,
                            grounds,
                            created_at,
                            updated_at,
                            file_mimetype,
                            file_path,
                            file_size,
                            file_type,
                            file_source_type,
                            file_name,
                            file_uploaded,
                            file_page_count,
                            file_paginatable
                       ) SELECT
                             'investigation_report_attachment',
                             a.id,
                             a.dossier_id,
                             a.formal_date,
                             a.type,
                             a.internal_reference,
                             a.language,
                             a.grounds,
                             a.created_at,
                             a.updated_at,
                             a.file_mimetype,
                             a.file_path,
                             a.file_size,
                             a.file_type,
                             a.file_source_type,
                             a.file_name,
                             a.file_uploaded,
                             a.file_page_count,
                             a.file_paginatable
                       FROM investigation_report_attachment a;
INSERT INTO attachment(
                            entity_type,
                            id,
                            dossier_id,
                            formal_date,
                            type,
                            internal_reference,
                            language,
                            grounds,
                            created_at,
                            updated_at,
                            file_mimetype,
                            file_path,
                            file_size,
                            file_type,
                            file_source_type,
                            file_name,
                            file_uploaded,
                            file_page_count,
                            file_paginatable
                       ) SELECT
                             'disposition_attachment',
                             a.id,
                             a.dossier_id,
                             a.formal_date,
                             a.type,
                             a.internal_reference,
                             a.language,
                             a.grounds,
                             a.created_at,
                             a.updated_at,
                             a.file_mimetype,
                             a.file_path,
                             a.file_size,
                             a.file_type,
                             a.file_source_type,
                             a.file_name,
                             a.file_uploaded,
                             a.file_page_count,
                             a.file_paginatable
                       FROM disposition_attachment a;
INSERT INTO main_document(
                            entity_type,
                            id,
                            dossier_id,
                            formal_date,
                            type,
                            internal_reference,
                            language,
                            grounds,
                            created_at,
                            updated_at,
                            file_mimetype,
                            file_path,
                            file_size,
                            file_type,
                            file_source_type,
                            file_name,
                            file_uploaded,
                            file_page_count,
                            file_paginatable
                       ) SELECT
                             'covenant_main_document',
                             d.id,
                             d.dossier_id,
                             d.formal_date,
                             d.type,
                             d.internal_reference,
                             d.language,
                             d.grounds,
                             d.created_at,
                             d.updated_at,
                             d.file_mimetype,
                             d.file_path,
                             d.file_size,
                             d.file_type,
                             d.file_source_type,
                             d.file_name,
                             d.file_uploaded,
                             d.file_page_count,
                             d.file_paginatable
                       FROM covenant_document d;
INSERT INTO main_document(
                            entity_type,
                            id,
                            dossier_id,
                            formal_date,
                            type,
                            internal_reference,
                            language,
                            grounds,
                            created_at,
                            updated_at,
                            file_mimetype,
                            file_path,
                            file_size,
                            file_type,
                            file_source_type,
                            file_name,
                            file_uploaded,
                            file_page_count,
                            file_paginatable
                       ) SELECT
                             'annual_report_main_document',
                             d.id,
                             d.dossier_id,
                             d.formal_date,
                             d.type,
                             d.internal_reference,
                             d.language,
                             d.grounds,
                             d.created_at,
                             d.updated_at,
                             d.file_mimetype,
                             d.file_path,
                             d.file_size,
                             d.file_type,
                             d.file_source_type,
                             d.file_name,
                             d.file_uploaded,
                             d.file_page_count,
                             d.file_paginatable
                       FROM annual_report_document d;
INSERT INTO main_document(
                            entity_type,
                            id,
                            dossier_id,
                            formal_date,
                            type,
                            internal_reference,
                            language,
                            grounds,
                            created_at,
                            updated_at,
                            file_mimetype,
                            file_path,
                            file_size,
                            file_type,
                            file_source_type,
                            file_name,
                            file_uploaded,
                            file_page_count,
                            file_paginatable
                       ) SELECT
                             'investigation_report_main_document',
                             d.id,
                             d.dossier_id,
                             d.formal_date,
                             d.type,
                             d.internal_reference,
                             d.language,
                             d.grounds,
                             d.created_at,
                             d.updated_at,
                             d.file_mimetype,
                             d.file_path,
                             d.file_size,
                             d.file_type,
                             d.file_source_type,
                             d.file_name,
                             d.file_uploaded,
                             d.file_page_count,
                             d.file_paginatable
                       FROM investigation_report_document d;
INSERT INTO main_document(
                            entity_type,
                            id,
                            dossier_id,
                            formal_date,
                            type,
                            internal_reference,
                            language,
                            grounds,
                            created_at,
                            updated_at,
                            file_mimetype,
                            file_path,
                            file_size,
                            file_type,
                            file_source_type,
                            file_name,
                            file_uploaded,
                            file_page_count,
                            file_paginatable
                       ) SELECT
                             'disposition_main_document',
                             d.id,
                             d.dossier_id,
                             d.formal_date,
                             d.type,
                             d.internal_reference,
                             d.language,
                             d.grounds,
                             d.created_at,
                             d.updated_at,
                             d.file_mimetype,
                             d.file_path,
                             d.file_size,
                             d.file_type,
                             d.file_source_type,
                             d.file_name,
                             d.file_uploaded,
                             d.file_page_count,
                             d.file_paginatable
                       FROM disposition_document d;
INSERT INTO main_document(
                            entity_type,
                            id,
                            dossier_id,
                            formal_date,
                            type,
                            internal_reference,
                            language,
                            grounds,
                            created_at,
                            updated_at,
                            file_mimetype,
                            file_path,
                            file_size,
                            file_type,
                            file_source_type,
                            file_name,
                            file_uploaded,
                            file_page_count,
                            file_paginatable
                       ) SELECT
                             'complaint_judgement_main_document',
                             d.id,
                             d.dossier_id,
                             d.formal_date,
                             d.type,
                             d.internal_reference,
                             d.language,
                             d.grounds,
                             d.created_at,
                             d.updated_at,
                             d.file_mimetype,
                             d.file_path,
                             d.file_size,
                             d.file_type,
                             d.file_source_type,
                             d.file_name,
                             d.file_uploaded,
                             d.file_page_count,
                             d.file_paginatable
                       FROM complaint_judgement_document d;
DROP TABLE investigation_report_document;
DROP TABLE annual_report_document;
DROP TABLE covenant_document;
DROP TABLE decision_attachment;
DROP TABLE disposition_attachment;
DROP TABLE annual_report_attachment;
DROP TABLE investigation_report_attachment;
DROP TABLE covenant_attachment;
DROP TABLE disposition_document;
DROP TABLE complaint_judgement_document;

GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE attachment TO woopie;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE main_document TO woopie;