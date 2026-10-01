<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260922120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production_report_process_run ADD has_matter BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production_report_process_run DROP has_matter');
    }
}
