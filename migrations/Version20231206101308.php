<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231206101308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added site column to history table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE history ADD site VARCHAR(255) DEFAULT \'both\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE history DROP site');
    }
}
