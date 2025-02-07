<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230613104803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP CONSTRAINT fk_d8698a768047ea50');
        $this->addSql('ALTER TABLE document ADD source_type VARCHAR(255) NOT NULL DEFAULT \'pdf\'');
        $this->addSql('ALTER TABLE document ADD class VARCHAR(255) NOT NULL DEFAULT \'App\\\\Entity\\\\Document\'');
        $this->addSql('ALTER TABLE document RENAME COLUMN document_type TO file_type');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document ADD document_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE document DROP file_type');
        $this->addSql('ALTER TABLE document DROP source_type');
        $this->addSql('ALTER TABLE document DROP class');
    }
}
