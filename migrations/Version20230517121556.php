<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230517121556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document ADD family_id INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE document ADD document_id INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE document ADD thread_id INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE document ADD judgement VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE document ADD grounds TEXT NOT NULL DEFAULT \'[]\'');
        $this->addSql('ALTER TABLE document ADD subjects TEXT NOT NULL DEFAULT \'[]\'');
        $this->addSql('ALTER TABLE document ADD period VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('COMMENT ON COLUMN document.grounds IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN document.subjects IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE dossier ALTER status DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP family_id');
        $this->addSql('ALTER TABLE document DROP document_id');
        $this->addSql('ALTER TABLE document DROP thread_id');
        $this->addSql('ALTER TABLE document DROP judgement');
        $this->addSql('ALTER TABLE document DROP grounds');
        $this->addSql('ALTER TABLE document DROP subjects');
        $this->addSql('ALTER TABLE document DROP period');
        $this->addSql('ALTER TABLE dossier ALTER status SET DEFAULT \'publicated\'');
    }
}
