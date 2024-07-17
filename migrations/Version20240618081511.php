<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240618081511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, entity_type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_795FD9BB611C0C56 ON attachment (dossier_id)');
        $this->addSql('COMMENT ON COLUMN attachment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN attachment.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN attachment.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN attachment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN attachment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE investigation_report_document DROP CONSTRAINT fk_fe90a702611c0c56');
        $this->addSql('ALTER TABLE annual_report_document DROP CONSTRAINT fk_9a836917611c0c56');
        $this->addSql('ALTER TABLE covenant_document DROP CONSTRAINT fk_d8a14d7d611c0c56');
        $this->addSql('ALTER TABLE decision_attachment DROP CONSTRAINT fk_3341903b611c0c56');
        $this->addSql('ALTER TABLE disposition_attachment DROP CONSTRAINT fk_82855b83611c0c56');
        $this->addSql('ALTER TABLE annual_report_attachment DROP CONSTRAINT fk_3cba7275611c0c56');
        $this->addSql('ALTER TABLE investigation_report_attachment DROP CONSTRAINT fk_77d31fc5611c0c56');
        $this->addSql('ALTER TABLE covenant_attachment DROP CONSTRAINT fk_9fab57ab611c0c56');
        $this->addSql('ALTER TABLE disposition_document DROP CONSTRAINT fk_cdbce77e611c0c56');
        $this->addSql('ALTER TABLE complaint_judgement_document DROP CONSTRAINT fk_25d859f2611c0c56');

        $this->addSql('CREATE TABLE main_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, entity_type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_14B9B03611C0C56 ON main_document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN main_document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN main_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN main_document.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN main_document.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN main_document.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE main_document ADD CONSTRAINT FK_14B9B03611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('INSERT INTO attachment(
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
                             \'covenant_attachment\',
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
                       FROM covenant_attachment a');

        $this->addSql('INSERT INTO attachment(
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
                             \'annual_report_attachment\',
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
                       FROM annual_report_attachment a');

        $this->addSql('INSERT INTO attachment(
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
                             \'decision_attachment\',
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
                       FROM decision_attachment a');

        $this->addSql('INSERT INTO attachment(
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
                             \'investigation_report_attachment\',
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
                       FROM investigation_report_attachment a');

        $this->addSql('INSERT INTO attachment(
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
                             \'disposition_attachment\',
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
                       FROM disposition_attachment a');

        $this->addSql('INSERT INTO main_document(
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
                             \'covenant_main_document\',
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
                       FROM covenant_document d');

        $this->addSql('INSERT INTO main_document(
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
                             \'annual_report_main_document\',
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
                       FROM annual_report_document d');

        $this->addSql('INSERT INTO main_document(
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
                             \'investigation_report_main_document\',
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
                       FROM investigation_report_document d');

        $this->addSql('INSERT INTO main_document(
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
                             \'disposition_main_document\',
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
                       FROM disposition_document d');

        $this->addSql('INSERT INTO main_document(
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
                             \'complaint_judgement_main_document\',
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
                       FROM complaint_judgement_document d');

        $this->addSql('DROP TABLE investigation_report_document');
        $this->addSql('DROP TABLE annual_report_document');
        $this->addSql('DROP TABLE covenant_document');
        $this->addSql('DROP TABLE decision_attachment');
        $this->addSql('DROP TABLE disposition_attachment');
        $this->addSql('DROP TABLE annual_report_attachment');
        $this->addSql('DROP TABLE investigation_report_attachment');
        $this->addSql('DROP TABLE covenant_attachment');
        $this->addSql('DROP TABLE disposition_document');
        $this->addSql('DROP TABLE complaint_judgement_document');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE investigation_report_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_fe90a702611c0c56 ON investigation_report_document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN investigation_report_document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_document.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_document.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_document.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE annual_report_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_9a836917611c0c56 ON annual_report_document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN annual_report_document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_document.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_document.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_document.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE covenant_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_d8a14d7d611c0c56 ON covenant_document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN covenant_document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN covenant_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN covenant_document.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN covenant_document.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN covenant_document.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE decision_attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_3341903b611c0c56 ON decision_attachment (dossier_id)');
        $this->addSql('COMMENT ON COLUMN decision_attachment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN decision_attachment.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN decision_attachment.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN decision_attachment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN decision_attachment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE disposition_attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_82855b83611c0c56 ON disposition_attachment (dossier_id)');
        $this->addSql('COMMENT ON COLUMN disposition_attachment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN disposition_attachment.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN disposition_attachment.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN disposition_attachment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN disposition_attachment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE annual_report_attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_3cba7275611c0c56 ON annual_report_attachment (dossier_id)');
        $this->addSql('COMMENT ON COLUMN annual_report_attachment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_attachment.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_attachment.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_attachment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_attachment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE investigation_report_attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_77d31fc5611c0c56 ON investigation_report_attachment (dossier_id)');
        $this->addSql('COMMENT ON COLUMN investigation_report_attachment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_attachment.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_attachment.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_attachment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_attachment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE covenant_attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_9fab57ab611c0c56 ON covenant_attachment (dossier_id)');
        $this->addSql('COMMENT ON COLUMN covenant_attachment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN covenant_attachment.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN covenant_attachment.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN covenant_attachment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN covenant_attachment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE disposition_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_cdbce77e611c0c56 ON disposition_document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN disposition_document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN disposition_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN disposition_document.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN disposition_document.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN disposition_document.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE complaint_judgement_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_25d859f2611c0c56 ON complaint_judgement_document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN complaint_judgement_document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN complaint_judgement_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN complaint_judgement_document.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN complaint_judgement_document.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN complaint_judgement_document.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE investigation_report_document ADD CONSTRAINT fk_fe90a702611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE annual_report_document ADD CONSTRAINT fk_9a836917611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE covenant_document ADD CONSTRAINT fk_d8a14d7d611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE decision_attachment ADD CONSTRAINT fk_3341903b611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE disposition_attachment ADD CONSTRAINT fk_82855b83611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE annual_report_attachment ADD CONSTRAINT fk_3cba7275611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE investigation_report_attachment ADD CONSTRAINT fk_77d31fc5611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE covenant_attachment ADD CONSTRAINT fk_9fab57ab611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE disposition_document ADD CONSTRAINT fk_cdbce77e611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE complaint_judgement_document ADD CONSTRAINT fk_25d859f2611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE attachment DROP CONSTRAINT FK_795FD9BB611C0C56');
        $this->addSql('DROP TABLE attachment');
        $this->addSql('ALTER TABLE main_document DROP CONSTRAINT FK_14B9B03611C0C56');
        $this->addSql('DROP TABLE main_document');
    }
}
