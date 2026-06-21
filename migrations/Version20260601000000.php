<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inquiry RENAME COLUMN casenr TO inquiry_number');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inquiry RENAME COLUMN inquiry_number TO casenr');
    }
}
