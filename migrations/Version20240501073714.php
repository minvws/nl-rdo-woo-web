<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240501073714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AnnualReport dossier type and related entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE annual_report_attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3CBA7275611C0C56 ON annual_report_attachment (dossier_id)');
        $this->addSql('COMMENT ON COLUMN annual_report_attachment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_attachment.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_attachment.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_attachment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_attachment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE annual_report_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9A836917611C0C56 ON annual_report_document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN annual_report_document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_document.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_document.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN annual_report_document.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE annual_report_attachment ADD CONSTRAINT FK_3CBA7275611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE annual_report_document ADD CONSTRAINT FK_9A836917611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE covenant_attachment ALTER language TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE covenant_document ALTER language TYPE VARCHAR(255)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE annual_report_attachment DROP CONSTRAINT FK_3CBA7275611C0C56');
        $this->addSql('ALTER TABLE annual_report_document DROP CONSTRAINT FK_9A836917611C0C56');
        $this->addSql('DROP TABLE annual_report_attachment');
        $this->addSql('DROP TABLE annual_report_document');
        $this->addSql('ALTER TABLE covenant_document ALTER language TYPE VARCHAR(10)');
        $this->addSql('ALTER TABLE covenant_attachment ALTER language TYPE VARCHAR(10)');
    }
}
