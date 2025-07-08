<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQL120Platform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250630085436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial DB structure';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            ! $this->connection->getDatabasePlatform() instanceof PostgreSQL120Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL120Platform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE organisation (id UUID NOT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_e6e132b45e237e06 ON organisation (name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSONB NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, mfa_token TEXT DEFAULT NULL, mfa_recovery TEXT DEFAULT NULL, enabled BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, changepwd BOOLEAN NOT NULL, organisation_id UUID NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_8d93d6499e6b1585 ON "user" (organisation_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_8d93d649e7927c74 ON "user" (email)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD CONSTRAINT fk_8d93d6499e6b1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE subject (id UUID NOT NULL, organisation_id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_fbce3e7a9e6b1585 ON subject (organisation_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE subject ADD CONSTRAINT fk_fbce3e7a9e6b1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE department (id UUID NOT NULL, name VARCHAR(255) NOT NULL, short_tag VARCHAR(20) DEFAULT NULL, slug VARCHAR(20) NOT NULL, public BOOLEAN NOT NULL, landing_page_title VARCHAR(100) DEFAULT NULL, landing_page_description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_hash VARCHAR(128) DEFAULT NULL, file_size BIGINT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, feedback_content TEXT DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_cd1de18a5e237e06 ON department (name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_cd1de18a74c9f71c ON department (short_tag)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_cd1de18a989d9b62 ON department (slug)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE dossier (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, dossier_nr VARCHAR(255) NOT NULL, title VARCHAR(500) NOT NULL, status VARCHAR(255) NOT NULL, summary TEXT NOT NULL, document_prefix VARCHAR(255) NOT NULL, date_from DATE DEFAULT NULL, date_to DATE DEFAULT NULL, publication_reason VARCHAR(255) DEFAULT NULL, decision VARCHAR(255) DEFAULT NULL, publication_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, preview_date DATE DEFAULT NULL, completed BOOLEAN NOT NULL, decision_date DATE DEFAULT NULL, organisation_id UUID NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, previous_version_link VARCHAR(255) DEFAULT NULL, parties JSON DEFAULT NULL, subject_id UUID DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX dossier_unique_index ON dossier (dossier_nr, document_prefix)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_3d48e03723edc87 ON dossier (subject_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_3d48e0379e6b1585 ON dossier (organisation_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE dossier_department (dossier_id UUID NOT NULL, department_id UUID NOT NULL, PRIMARY KEY(dossier_id, department_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_e367a5af611c0c56 ON dossier_department (dossier_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_e367a5afae80f5df ON dossier_department (department_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dossier_department ADD CONSTRAINT fk_e367a5af611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dossier_department ADD CONSTRAINT fk_e367a5afae80f5df FOREIGN KEY (department_id) REFERENCES department (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dossier ADD CONSTRAINT fk_3d48e03723edc87 FOREIGN KEY (subject_id) REFERENCES subject (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dossier ADD CONSTRAINT fk_3d48e0379e6b1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE content_page (slug VARCHAR(20) NOT NULL, title VARCHAR(100) NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(slug))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE upload (id UUID NOT NULL, upload_id VARCHAR(50) NOT NULL, external_id VARCHAR(255) DEFAULT NULL, upload_group_id VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, size BIGINT DEFAULT NULL, mimetype VARCHAR(100) DEFAULT NULL, filename VARCHAR(255) DEFAULT NULL, error JSON DEFAULT NULL, context JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_17bde61fa76ed395 ON upload (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE upload ADD CONSTRAINT fk_830914d0a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE production_report_process_run (id UUID NOT NULL, dossier_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, generic_errors JSON NOT NULL, row_errors JSON NOT NULL, status VARCHAR(255) NOT NULL, progress SMALLINT NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size BIGINT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, changeset JSON DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, file_hash VARCHAR(128) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_d8900ff7611c0c56 ON production_report_process_run (dossier_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE production_report_process_run ADD CONSTRAINT fk_1846874c611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE production_report (id UUID NOT NULL, dossier_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size BIGINT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, file_hash VARCHAR(128) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_b8fe3d9611c0c56 ON production_report (dossier_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE production_report ADD CONSTRAINT fk_ae096b07611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE woo_index_sitemap (id UUID NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE document (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size BIGINT NOT NULL, page_count INT NOT NULL, summary TEXT DEFAULT NULL, title TEXT DEFAULT NULL, file_type VARCHAR(255) DEFAULT NULL, document_nr VARCHAR(255) NOT NULL, document_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, family_id INT DEFAULT NULL, document_id VARCHAR(255) DEFAULT NULL, thread_id INT DEFAULT NULL, judgement VARCHAR(255) DEFAULT NULL, grounds JSON NOT NULL, period VARCHAR(255) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, suspended BOOLEAN NOT NULL, withdrawn BOOLEAN NOT NULL, remark TEXT DEFAULT NULL, withdraw_reason VARCHAR(255) DEFAULT NULL, withdraw_explanation TEXT DEFAULT NULL, withdraw_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, links JSON DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, file_hash VARCHAR(128) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX lower_case_document_nr ON document (lower(document_nr::text));
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE inquiry (id UUID NOT NULL, casenr VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, token VARCHAR(255) NOT NULL, organisation_id UUID NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_5a3903f09e6b1585 ON inquiry (organisation_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE inquiry ADD CONSTRAINT fk_5a3903f09e6b1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE main_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size BIGINT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, entity_type VARCHAR(255) NOT NULL, file_hash VARCHAR(128) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_14b9b03611c0c56 ON main_document (dossier_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE main_document ADD CONSTRAINT fk_14b9b03611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE inquiry_inventory (id UUID NOT NULL, inquiry_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size BIGINT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, file_hash VARCHAR(128) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_c857a5d8a7ad6d71 ON inquiry_inventory (inquiry_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE inquiry_inventory ADD CONSTRAINT fk_c857a5d8a7ad6d71 FOREIGN KEY (inquiry_id) REFERENCES inquiry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE document_file_set (id UUID NOT NULL, dossier_id UUID NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_46965a1c611c0c56 ON document_file_set (dossier_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document_file_set ADD CONSTRAINT fk_46965a1c611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE document_file_upload (id UUID NOT NULL, document_file_set_id UUID NOT NULL, status VARCHAR(255) NOT NULL, error VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_hash VARCHAR(128) DEFAULT NULL, file_size BIGINT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_2d03f7ca2a451ccd ON document_file_upload (document_file_set_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document_file_upload ADD CONSTRAINT fk_2d03f7ca2a451ccd FOREIGN KEY (document_file_set_id) REFERENCES document_file_set (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE document_file_update (id UUID NOT NULL, document_file_set_id UUID NOT NULL, document_id UUID NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_hash VARCHAR(128) DEFAULT NULL, file_size BIGINT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_a29b24ad2a451ccd ON document_file_update (document_file_set_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_a29b24adc33f7837 ON document_file_update (document_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX unique_document_for_set ON document_file_update (document_file_set_id, document_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE document_dossier (document_id UUID NOT NULL, woo_decision_id UUID NOT NULL, PRIMARY KEY(document_id, woo_decision_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_992746eb21c2e34d ON document_dossier (woo_decision_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_992746ebc33f7837 ON document_dossier (document_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document_dossier ADD CONSTRAINT fk_992746eb21c2e34d FOREIGN KEY (woo_decision_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document_dossier ADD CONSTRAINT fk_992746ebc33f7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document_file_update ADD CONSTRAINT fk_a29b24ad2a451ccd FOREIGN KEY (document_file_set_id) REFERENCES document_file_set (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document_file_update ADD CONSTRAINT fk_a29b24adc33f7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE batch_download (id UUID NOT NULL, dossier_id UUID DEFAULT NULL, expiration TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, downloaded INT NOT NULL, status VARCHAR(255) NOT NULL, size BIGINT DEFAULT NULL, inquiry_id UUID DEFAULT NULL, filename VARCHAR(255) NOT NULL, file_count INT DEFAULT 0 NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_f3f4ec10611c0c56 ON batch_download (dossier_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_f3f4ec10a7ad6d71 ON batch_download (inquiry_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE batch_download ADD CONSTRAINT fk_f3f4ec10611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE batch_download ADD CONSTRAINT fk_f3f4ec10a7ad6d71 FOREIGN KEY (inquiry_id) REFERENCES inquiry (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size BIGINT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, entity_type VARCHAR(255) NOT NULL, file_hash VARCHAR(128) DEFAULT NULL, withdrawn BOOLEAN DEFAULT false NOT NULL, withdraw_reason VARCHAR(255) DEFAULT NULL, withdraw_explanation TEXT DEFAULT NULL, withdraw_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_795fd9bb611c0c56 ON attachment (dossier_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE attachment ADD CONSTRAINT fk_795fd9bb611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE organisation_department (organisation_id UUID NOT NULL, department_id UUID NOT NULL, PRIMARY KEY(organisation_id, department_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_45f0f7b89e6b1585 ON organisation_department (organisation_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_45f0f7b8ae80f5df ON organisation_department (department_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE organisation_department ADD CONSTRAINT fk_45f0f7b89e6b1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE organisation_department ADD CONSTRAINT fk_45f0f7b8ae80f5df FOREIGN KEY (department_id) REFERENCES department (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE document_referrals (document_id UUID NOT NULL, referred_document_id UUID NOT NULL, PRIMARY KEY(document_id, referred_document_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_945e037c33f7837 ON document_referrals (document_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_945e037e75cce85 ON document_referrals (referred_document_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document_referrals ADD CONSTRAINT fk_945e037c33f7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document_referrals ADD CONSTRAINT fk_945e037e75cce85 FOREIGN KEY (referred_document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE history (id UUID NOT NULL, type VARCHAR(255) NOT NULL, identifier UUID NOT NULL, created_dt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, context_key VARCHAR(255) NOT NULL, context JSON NOT NULL, site VARCHAR(255) DEFAULT 'both' NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_27ba704b8cde5729772e836a ON history (type, identifier)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE login_activity (id UUID NOT NULL, account_id UUID NOT NULL, login_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_82d029c59b6b5fba ON login_activity (account_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE login_activity ADD CONSTRAINT fk_82d029c59b6b5fba FOREIGN KEY (account_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE worker_stats (id UUID NOT NULL, section VARCHAR(100) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, duration INT NOT NULL, hostname VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE encrypted_audit_entry (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, data TEXT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE audit_entry (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, request JSON NOT NULL, event_code VARCHAR(255) NOT NULL, action_code VARCHAR(255) NOT NULL, failed BOOLEAN NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE inventory (id UUID NOT NULL, dossier_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size BIGINT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, file_hash VARCHAR(128) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_b12d4a36611c0c56 ON inventory (dossier_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE inventory ADD CONSTRAINT fk_b12d4a36611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE inquiry_dossier (inquiry_id UUID NOT NULL, woo_decision_id UUID NOT NULL, PRIMARY KEY(inquiry_id, woo_decision_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_d6558e9221c2e34d ON inquiry_dossier (woo_decision_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_d6558e92a7ad6d71 ON inquiry_dossier (inquiry_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE inquiry_dossier ADD CONSTRAINT fk_d6558e9221c2e34d FOREIGN KEY (woo_decision_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE inquiry_dossier ADD CONSTRAINT fk_d6558e92a7ad6d71 FOREIGN KEY (inquiry_id) REFERENCES inquiry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE inquiry_document (inquiry_id UUID NOT NULL, document_id UUID NOT NULL, PRIMARY KEY(inquiry_id, document_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_7e3ec07fa7ad6d71 ON inquiry_document (inquiry_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_7e3ec07fc33f7837 ON inquiry_document (document_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE inquiry_document ADD CONSTRAINT fk_5596e8b667ee6561 FOREIGN KEY (inquiry_id) REFERENCES inquiry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE inquiry_document ADD CONSTRAINT fk_5596e8b6c33f7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE document_prefix (id UUID NOT NULL, prefix VARCHAR(255) NOT NULL, organisation_id UUID NOT NULL, archived BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_2dd337e89e6b1585 ON document_prefix (organisation_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_2dd337e893b1868e ON document_prefix (prefix)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document_prefix ADD CONSTRAINT fk_2dd337e89e6b1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
