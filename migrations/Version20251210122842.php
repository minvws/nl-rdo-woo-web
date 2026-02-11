<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251210122842 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ADD external_id VARCHAR(128) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX document_unique_external_id ON document (external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX document_unique_external_id');
        $this->addSql('ALTER TABLE document DROP external_id');
    }
}
