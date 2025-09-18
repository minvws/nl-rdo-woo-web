<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250804072130 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document DROP page_count');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ADD page_count INT NOT NULL');
    }
}
