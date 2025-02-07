<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240318193318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove ingestlog';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ingest_log DROP CONSTRAINT fk_3b8d4059c33f7837');
        $this->addSql('DROP TABLE ingest_log');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ingest_log (id UUID NOT NULL, document_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, success BOOLEAN NOT NULL, event VARCHAR(255) NOT NULL, message VARCHAR(1024) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_3b8d4059c33f7837 ON ingest_log (document_id)');
        $this->addSql('COMMENT ON COLUMN ingest_log.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN ingest_log.document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN ingest_log.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE ingest_log ADD CONSTRAINT fk_3b8d4059c33f7837 FOREIGN KEY (document_id) REFERENCES document (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
