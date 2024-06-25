<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240604212333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE annual_report_attachment ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE annual_report_attachment ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE annual_report_document ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE annual_report_document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE complaint_judgement_document ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE complaint_judgement_document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE covenant_attachment ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE covenant_attachment ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE covenant_document ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE covenant_document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE decision_attachment ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE decision_attachment ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE decision_document ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE decision_document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE disposition_attachment ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE disposition_attachment ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE disposition_document ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE disposition_document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE document ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE inquiry_inventory ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE inquiry_inventory ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE inventory ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE inventory ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE inventory_process_run ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE inventory_process_run ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE investigation_report_attachment ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE investigation_report_attachment ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE investigation_report_document ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE investigation_report_document ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE raw_inventory ADD file_page_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE raw_inventory ADD file_paginatable BOOLEAN DEFAULT false NOT NULL');

        $this->addSql('UPDATE annual_report_attachment SET file_paginatable = true');
        $this->addSql('UPDATE annual_report_document SET file_paginatable = true');
        $this->addSql('UPDATE complaint_judgement_document SET file_paginatable = true');
        $this->addSql('UPDATE covenant_attachment SET file_paginatable = true');
        $this->addSql('UPDATE covenant_document SET file_paginatable = true');
        $this->addSql('UPDATE decision_attachment SET file_paginatable = true');
        $this->addSql('UPDATE decision_document SET file_paginatable = true');
        $this->addSql('UPDATE disposition_attachment SET file_paginatable = true');
        $this->addSql('UPDATE disposition_document SET file_paginatable = true');
        $this->addSql('UPDATE investigation_report_attachment SET file_paginatable = true');
        $this->addSql('UPDATE investigation_report_document SET file_paginatable = true');
        $this->addSql('UPDATE document SET file_paginatable = true');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE investigation_report_document DROP file_page_count');
        $this->addSql('ALTER TABLE investigation_report_document DROP file_paginatable');
        $this->addSql('ALTER TABLE annual_report_document DROP file_page_count');
        $this->addSql('ALTER TABLE annual_report_document DROP file_paginatable');
        $this->addSql('ALTER TABLE inventory DROP file_page_count');
        $this->addSql('ALTER TABLE inventory DROP file_paginatable');
        $this->addSql('ALTER TABLE complaint_judgement_document DROP file_page_count');
        $this->addSql('ALTER TABLE complaint_judgement_document DROP file_paginatable');
        $this->addSql('ALTER TABLE decision_attachment DROP file_page_count');
        $this->addSql('ALTER TABLE decision_attachment DROP file_paginatable');
        $this->addSql('ALTER TABLE investigation_report_attachment DROP file_page_count');
        $this->addSql('ALTER TABLE investigation_report_attachment DROP file_paginatable');
        $this->addSql('ALTER TABLE document DROP file_page_count');
        $this->addSql('ALTER TABLE document DROP file_paginatable');
        $this->addSql('ALTER TABLE inquiry_inventory DROP file_page_count');
        $this->addSql('ALTER TABLE inquiry_inventory DROP file_paginatable');
        $this->addSql('ALTER TABLE disposition_attachment DROP file_page_count');
        $this->addSql('ALTER TABLE disposition_attachment DROP file_paginatable');
        $this->addSql('ALTER TABLE inventory_process_run DROP file_page_count');
        $this->addSql('ALTER TABLE inventory_process_run DROP file_paginatable');
        $this->addSql('ALTER TABLE raw_inventory DROP file_page_count');
        $this->addSql('ALTER TABLE raw_inventory DROP file_paginatable');
        $this->addSql('ALTER TABLE covenant_document DROP file_page_count');
        $this->addSql('ALTER TABLE covenant_document DROP file_paginatable');
        $this->addSql('ALTER TABLE disposition_document DROP file_page_count');
        $this->addSql('ALTER TABLE disposition_document DROP file_paginatable');
        $this->addSql('ALTER TABLE covenant_attachment DROP file_page_count');
        $this->addSql('ALTER TABLE covenant_attachment DROP file_paginatable');
        $this->addSql('ALTER TABLE decision_document DROP file_page_count');
        $this->addSql('ALTER TABLE decision_document DROP file_paginatable');
        $this->addSql('ALTER TABLE annual_report_attachment DROP file_page_count');
        $this->addSql('ALTER TABLE annual_report_attachment DROP file_paginatable');
    }
}
