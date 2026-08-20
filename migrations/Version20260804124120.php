<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804124120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subject ADD landing_page_title VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE subject ADD landing_page_description VARCHAR(10000) DEFAULT NULL');
        $this->addSql('ALTER TABLE subject ADD landing_page_status VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE subject ADD landing_page_preview_token UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE subject ADD landing_page_content_tree JSONB DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBCE3E7A737B07F3 ON subject (landing_page_preview_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_FBCE3E7A737B07F3');
        $this->addSql('ALTER TABLE subject DROP landing_page_title');
        $this->addSql('ALTER TABLE subject DROP landing_page_description');
        $this->addSql('ALTER TABLE subject DROP landing_page_status');
        $this->addSql('ALTER TABLE subject DROP landing_page_preview_token');
        $this->addSql('ALTER TABLE subject DROP landing_page_content_tree');
    }
}
