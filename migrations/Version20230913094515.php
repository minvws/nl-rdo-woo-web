<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230913094515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_entry (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, request JSON NOT NULL, event_code VARCHAR(255) NOT NULL, action_code VARCHAR(255) NOT NULL, failed BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN audit_entry.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN audit_entry.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE encrypted_audit_entry (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, data TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN encrypted_audit_entry.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN encrypted_audit_entry.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE audit_entry');
        $this->addSql('DROP TABLE encrypted_audit_entry');
    }
}
