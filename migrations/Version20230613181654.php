<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230613181654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ADD date_from DATE NULL');
        $this->addSql('ALTER TABLE dossier ADD date_to DATE NULL');
        $this->addSql('ALTER TABLE dossier ADD publication_reason VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('COMMENT ON COLUMN dossier.date_from IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN dossier.date_to IS \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier DROP date_from');
        $this->addSql('ALTER TABLE dossier DROP date_to');
        $this->addSql('ALTER TABLE dossier DROP publication_reason');
    }
}
