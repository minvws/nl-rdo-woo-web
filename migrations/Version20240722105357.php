<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240722105357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attachment ADD file_hash VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE decision_document ADD file_hash VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD file_hash VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE inquiry_inventory ADD file_hash VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE inventory ADD file_hash VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE inventory_process_run ADD file_hash VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE main_document ADD file_hash VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE raw_inventory ADD file_hash VARCHAR(128) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inquiry_inventory DROP file_hash');
        $this->addSql('ALTER TABLE attachment DROP file_hash');
        $this->addSql('ALTER TABLE main_document DROP file_hash');
        $this->addSql('ALTER TABLE document DROP file_hash');
        $this->addSql('ALTER TABLE inventory DROP file_hash');
        $this->addSql('ALTER TABLE decision_document DROP file_hash');
        $this->addSql('ALTER TABLE inventory_process_run DROP file_hash');
        $this->addSql('ALTER TABLE raw_inventory DROP file_hash');
    }
}
