<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019140000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE main_document ADD spreadsheet_table JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE main_document ADD spreadsheet_table_truncated BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE main_document DROP spreadsheet_table');
        $this->addSql('ALTER TABLE main_document DROP spreadsheet_table_truncated');
    }
}
