<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231204134516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_prefix DROP description');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_prefix ADD description VARCHAR(1024) NOT NULL');
    }
}
