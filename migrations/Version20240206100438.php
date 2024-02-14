<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240206100438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove duration for documents';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document DROP duration');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ADD duration INT NOT NULL');
    }
}
