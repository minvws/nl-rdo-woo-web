<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250211155955 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attachment ALTER file_size TYPE BIGINT');
        $this->addSql('ALTER TABLE batch_download ALTER filename SET NOT NULL');
        $this->addSql('ALTER TABLE document ALTER file_size TYPE BIGINT');
        $this->addSql('ALTER TABLE document_file_update ALTER file_size TYPE BIGINT');
        $this->addSql('ALTER TABLE document_file_upload ALTER file_size TYPE BIGINT');
        $this->addSql('ALTER TABLE inquiry_inventory ALTER file_size TYPE BIGINT');
        $this->addSql('ALTER TABLE inventory ALTER file_size TYPE BIGINT');
        $this->addSql('ALTER TABLE main_document ALTER file_size TYPE BIGINT');
        $this->addSql('ALTER TABLE production_report ALTER file_size TYPE BIGINT');
        $this->addSql('ALTER TABLE production_report_process_run ALTER file_size TYPE BIGINT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production_report_process_run ALTER file_size TYPE INT');
        $this->addSql('ALTER TABLE attachment ALTER file_size TYPE INT');
        $this->addSql('ALTER TABLE main_document ALTER file_size TYPE INT');
        $this->addSql('ALTER TABLE document_file_update ALTER file_size TYPE INT');
        $this->addSql('ALTER TABLE inventory ALTER file_size TYPE INT');
        $this->addSql('ALTER TABLE batch_download ALTER filename DROP NOT NULL');
        $this->addSql('ALTER TABLE inquiry_inventory ALTER file_size TYPE INT');
        $this->addSql('ALTER TABLE production_report ALTER file_size TYPE INT');
        $this->addSql('ALTER TABLE document ALTER file_size TYPE INT');
        $this->addSql('ALTER TABLE document_file_upload ALTER file_size TYPE INT');
    }
}
