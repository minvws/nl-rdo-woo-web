<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241015091005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE raw_inventory RENAME TO production_report');
        $this->addSql('ALTER TABLE inventory_process_run RENAME TO production_report_process_run');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production_report RENAME TO raw_inventory');
        $this->addSql('ALTER TABLE production_report_process_run RENAME TO inventory_process_run');
    }
}
