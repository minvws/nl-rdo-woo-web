<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230612185924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renamed documentNumber to documentNr and removed default value from dossierNr';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document RENAME COLUMN document_number TO document_nr');
        $this->addSql('ALTER TABLE dossier ALTER dossier_nr DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE document RENAME COLUMN document_nr TO document_number');
    }
}
