<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240515093810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ComplaintJudgement tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE complaint_judgement_document (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, internal_reference VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, grounds JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_25D859F2611C0C56 ON complaint_judgement_document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN complaint_judgement_document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN complaint_judgement_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN complaint_judgement_document.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN complaint_judgement_document.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN complaint_judgement_document.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE complaint_judgement_document ADD CONSTRAINT FK_25D859F2611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE complaint_judgement_document DROP CONSTRAINT FK_25D859F2611C0C56');
        $this->addSql('DROP TABLE complaint_judgement_document');
    }
}
