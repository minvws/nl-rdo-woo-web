<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230508075339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document ADD department JSON NOT NULL DEFAULT \'[]\'::jsonb');
        $this->addSql('ALTER TABLE document ADD official JSON NOT NULL DEFAULT \'[]\'::jsonb');
        $this->addSql('ALTER TABLE document ADD document_type VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE document ADD subject JSON NOT NULL DEFAULT \'[]\'::jsonb');
        $this->addSql('ALTER TABLE document ADD publication_type VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE document ADD publication_section VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE document ADD document_number VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE document ADD document_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE document ADD status VARCHAR(255) NOT NULL DEFAULT \'\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP department');
        $this->addSql('ALTER TABLE document DROP official');
        $this->addSql('ALTER TABLE document DROP document_type');
        $this->addSql('ALTER TABLE document DROP subject');
        $this->addSql('ALTER TABLE document DROP publication_type');
        $this->addSql('ALTER TABLE document DROP publication_section');
        $this->addSql('ALTER TABLE document DROP document_number');
        $this->addSql('ALTER TABLE document DROP document_date');
        $this->addSql('ALTER TABLE document DROP status');
    }
}
