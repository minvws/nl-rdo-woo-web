<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230929081242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document_prefix ADD organisation_id UUID');
        $this->addSql('COMMENT ON COLUMN document_prefix.organisation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE document_prefix ADD CONSTRAINT FK_2DD337E89E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_2DD337E89E6B1585 ON document_prefix (organisation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document_prefix DROP CONSTRAINT FK_2DD337E89E6B1585');
        $this->addSql('DROP INDEX IDX_2DD337E89E6B1585');
        $this->addSql('ALTER TABLE document_prefix DROP organisation_id');
    }
}
