<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230502115753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE document (id UUID NOT NULL, dossier_id UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, mimetype VARCHAR(100) NOT NULL, filepath VARCHAR(1024) NOT NULL, filesize INT NOT NULL, page_count INT NOT NULL, duration INT NOT NULL, summary TEXT NOT NULL, title TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D8698A76611C0C56 ON document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN document.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE dossier (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, hash VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN dossier.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN dossier.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN dossier.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE slurper_progress (id UUID NOT NULL, document_id UUID DEFAULT NULL, content TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, pages_processed INT NOT NULL, page_count INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B407F9FDC33F7837 ON slurper_progress (document_id)');
        $this->addSql('COMMENT ON COLUMN slurper_progress.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN slurper_progress.document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN slurper_progress.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN slurper_progress.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('ALTER TABLE slurper_progress ADD CONSTRAINT FK_B407F9FDC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE dossier');
        $this->addSql('DROP TABLE slurper_progress');
    }
}
