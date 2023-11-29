<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231013083439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE decision_document ALTER file_name TYPE VARCHAR(1024)');
        $this->addSql('ALTER TABLE document ALTER file_name TYPE VARCHAR(1024)');
        $this->addSql('ALTER TABLE document ALTER link TYPE VARCHAR(2048)');
        $this->addSql('ALTER TABLE inventory ALTER file_name TYPE VARCHAR(1024)');
        $this->addSql('ALTER TABLE raw_inventory ALTER file_name TYPE VARCHAR(1024)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE raw_inventory ALTER file_name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE inventory ALTER file_name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE document ALTER link TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE document ALTER file_name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE decision_document ALTER file_name TYPE VARCHAR(255)');
    }
}
