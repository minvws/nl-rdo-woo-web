<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240514093438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add InvestigationReport tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE investigation_report_attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_77D31FC5611C0C56 ON investigation_report_attachment (dossier_id)');
        $this->addSql('COMMENT ON COLUMN investigation_report_attachment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_attachment.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_attachment.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_attachment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_attachment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE investigation_report_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FE90A702611C0C56 ON investigation_report_document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN investigation_report_document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_document.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_document.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN investigation_report_document.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE investigation_report_attachment ADD CONSTRAINT FK_77D31FC5611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE investigation_report_document ADD CONSTRAINT FK_FE90A702611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE investigation_report_attachment DROP CONSTRAINT FK_77D31FC5611C0C56');
        $this->addSql('ALTER TABLE investigation_report_document DROP CONSTRAINT FK_FE90A702611C0C56');
        $this->addSql('DROP TABLE investigation_report_attachment');
        $this->addSql('DROP TABLE investigation_report_document');
    }
}
