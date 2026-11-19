<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030080740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the legacy document prefix table; rollback restores its schema only';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_prefix DROP CONSTRAINT fk_2dd337e89e6b1585');
        $this->addSql('DROP TABLE document_prefix');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE document_prefix (
                id UUID NOT NULL,
                prefix VARCHAR(255) NOT NULL,
                organisation_id UUID NOT NULL,
                archived BOOLEAN DEFAULT false NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE INDEX idx_2dd337e89e6b1585 ON document_prefix (organisation_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_2dd337e893b1868e ON document_prefix (prefix)');
        $this->addSql(<<<'SQL'
            ALTER TABLE document_prefix ADD CONSTRAINT fk_2dd337e89e6b1585
                FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            SQL);
    }
}
