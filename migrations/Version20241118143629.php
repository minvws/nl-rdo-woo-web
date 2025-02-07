<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241118143629 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_file_update ADD file_mimetype VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE document_file_update ADD file_path VARCHAR(1024) DEFAULT NULL');
        $this->addSql('ALTER TABLE document_file_update ADD file_hash VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE document_file_update ADD file_size INT NOT NULL');
        $this->addSql('ALTER TABLE document_file_update ADD file_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE document_file_update ADD file_name VARCHAR(1024) DEFAULT NULL');
        $this->addSql('ALTER TABLE document_file_update ADD file_uploaded BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE document_file_update ADD file_source_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE document_file_update ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE document_file_update ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_file_update DROP file_mimetype');
        $this->addSql('ALTER TABLE document_file_update DROP file_path');
        $this->addSql('ALTER TABLE document_file_update DROP file_hash');
        $this->addSql('ALTER TABLE document_file_update DROP file_size');
        $this->addSql('ALTER TABLE document_file_update DROP file_type');
        $this->addSql('ALTER TABLE document_file_update DROP file_name');
        $this->addSql('ALTER TABLE document_file_update DROP file_uploaded');
        $this->addSql('ALTER TABLE document_file_update DROP file_source_type');
        $this->addSql('ALTER TABLE document_file_update DROP file_page_count');
        $this->addSql('ALTER TABLE document_file_update DROP file_paginatable');
    }
}
