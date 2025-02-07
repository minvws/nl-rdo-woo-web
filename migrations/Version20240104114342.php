<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240104114342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE decision_attachment (id UUID NOT NULL, dossier_id UUID NOT NULL, formal_date DATE NOT NULL, type VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3341903B611C0C56 ON decision_attachment (dossier_id)');
        $this->addSql('COMMENT ON COLUMN decision_attachment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN decision_attachment.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN decision_attachment.formal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN decision_attachment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN decision_attachment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE decision_attachment ADD CONSTRAINT FK_3341903B611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX idx_ae096b07611c0c56');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AE096B07611C0C56 ON raw_inventory (dossier_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE decision_attachment DROP CONSTRAINT FK_3341903B611C0C56');
        $this->addSql('DROP TABLE decision_attachment');
        $this->addSql('DROP INDEX UNIQ_AE096B07611C0C56');
        $this->addSql('CREATE INDEX idx_ae096b07611c0c56 ON raw_inventory (dossier_id)');
    }
}
