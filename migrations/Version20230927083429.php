<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230927083429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add decisionDate to Dossier';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ADD decision_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE dossier ALTER completed DROP DEFAULT');
        $this->addSql('UPDATE dossier SET decision_date = publication_date');
        $this->addSql('COMMENT ON COLUMN dossier.decision_date IS \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE dossier DROP decision_date');
        $this->addSql('ALTER TABLE dossier ALTER completed SET DEFAULT true');
    }
}
