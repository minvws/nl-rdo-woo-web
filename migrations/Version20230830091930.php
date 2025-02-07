<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230830091930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE raw_inventory (id UUID NOT NULL, dossier_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(255) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AE096B07611C0C56 ON raw_inventory (dossier_id)');
        $this->addSql('COMMENT ON COLUMN raw_inventory.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN raw_inventory.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN raw_inventory.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN raw_inventory.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE raw_inventory ADD CONSTRAINT FK_AE096B07611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE raw_inventory DROP CONSTRAINT FK_AE096B07611C0C56');
        $this->addSql('DROP TABLE raw_inventory');
    }
}
