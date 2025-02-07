<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230531102649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ingest_log ADD document_id UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN ingest_log.document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE ingest_log ADD CONSTRAINT FK_3B8D4059C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3B8D4059C33F7837 ON ingest_log (document_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE ingest_log DROP CONSTRAINT FK_3B8D4059C33F7837');
        $this->addSql('DROP INDEX IDX_3B8D4059C33F7837');
        $this->addSql('ALTER TABLE ingest_log DROP document_id');
    }
}
