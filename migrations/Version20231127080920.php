<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231127080920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_government_official DROP CONSTRAINT fk_4adf1a7d611c0c56');
        $this->addSql('ALTER TABLE dossier_government_official DROP CONSTRAINT fk_c79596a154de3212');
        $this->addSql('DROP TABLE dossier_government_official');
        $this->addSql('DROP TABLE government_official');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE dossier_government_official (dossier_id UUID NOT NULL, government_official_id UUID NOT NULL, PRIMARY KEY(dossier_id, government_official_id))');
        $this->addSql('CREATE INDEX idx_c79596a154de3212 ON dossier_government_official (government_official_id)');
        $this->addSql('CREATE INDEX idx_c79596a1611c0c56 ON dossier_government_official (dossier_id)');
        $this->addSql('COMMENT ON COLUMN dossier_government_official.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN dossier_government_official.government_official_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE government_official (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN government_official.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dossier_government_official ADD CONSTRAINT fk_4adf1a7d611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dossier_government_official ADD CONSTRAINT fk_c79596a154de3212 FOREIGN KEY (government_official_id) REFERENCES government_official (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
